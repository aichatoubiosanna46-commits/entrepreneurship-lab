<?php
// ============================================================
//  api/webhook_fedapay.php — Webhook FedaPay
//  Vérifie la signature HMAC-SHA256 et traite les événements
//
//  URL à configurer dans le dashboard FedaPay :
//  https://votre-domaine.com/api/webhook_fedapay.php
// ============================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

// API uniquement — pas de session, pas de HTML
header('Content-Type: application/json');

// -------------------------------------------------------
//  1. Lire le body brut AVANT toute autre opération
// -------------------------------------------------------
$body = file_get_contents('php://input');

if (empty($body)) {
    http_response_code(400);
    echo json_encode(['error' => 'Empty body']);
    exit;
}

// -------------------------------------------------------
//  2. Vérifier la signature HMAC-SHA256
//  FedaPay envoie : x-fedapay-signature: t=TIMESTAMP,v1=HASH
// -------------------------------------------------------
$sigHeader = $_SERVER['HTTP_X_FEDAPAY_SIGNATURE'] ?? '';

if (empty($sigHeader)) {
    http_response_code(401);
    logAction('webhook_fedapay_no_signature', 'Header x-fedapay-signature manquant');
    echo json_encode(['error' => 'Missing signature header']);
    exit;
}

// Extraire le timestamp et la signature
$parts     = [];
$timestamp = '';
$v1sig     = '';

foreach (explode(',', $sigHeader) as $part) {
    [$key, $val] = array_pad(explode('=', $part, 2), 2, '');
    if ($key === 't')  $timestamp = $val;
    if ($key === 'v1') $v1sig     = $val;
}

// Construire le payload signé : timestamp + '.' + body
$signedPayload = $timestamp . '.' . $body;
$expected      = hash_hmac('sha256', $signedPayload, FEDAPAY_WEBHOOK_SECRET);

if (!hash_equals($expected, $v1sig)) {
    http_response_code(401);
    logAction('webhook_fedapay_bad_signature', 'Signature invalide reçue');
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

// Rejeter les webhooks trop anciens (protection replay attack — fenêtre 5 min)
if ($timestamp && abs(time() - (int)$timestamp) > 300) {
    http_response_code(400);
    logAction('webhook_fedapay_replay', 'Webhook trop ancien : timestamp=' . $timestamp);
    echo json_encode(['error' => 'Webhook too old (replay protection)']);
    exit;
}

// -------------------------------------------------------
//  3. Décoder et valider le JSON
// -------------------------------------------------------
$data = json_decode($body, true);
if (!$data || json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// -------------------------------------------------------
//  4. Identifier le type d'événement
// -------------------------------------------------------
$eventName = $data['name'] ?? '';         // ex: "transaction.approved"
$txnData   = $data['data']['object'] ?? ($data['data'] ?? []);
$txnId     = $txnData['id'] ?? null;
$txnStatus = $txnData['status'] ?? '';
$amount    = $txnData['amount'] ?? 0;

// Loguer l'événement reçu
logAction('webhook_fedapay_event', 'Événement : ' . $eventName . ' | TXN : ' . $txnId . ' | Statut : ' . $txnStatus);

// Ne traiter que les paiements approuvés
if ($eventName !== 'transaction.approved' && $txnStatus !== 'approved') {
    http_response_code(200);
    echo json_encode(['message' => 'Event ignored', 'event' => $eventName]);
    exit;
}

if (!$txnId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing transaction id']);
    exit;
}

$pdo = getPDO();

// -------------------------------------------------------
//  5. Récupérer les métadonnées (user_id, plan, ref)
//  Stockées lors de la création de transaction
// -------------------------------------------------------
$meta   = $txnData['metadata'] ?? [];
$userId = isset($meta['user_id']) ? (int)$meta['user_id'] : null;
$plan   = $meta['plan'] ?? null;
$ref    = $meta['ref']  ?? null;

// -------------------------------------------------------
//  6. Idempotence — transaction déjà traitée ?
// -------------------------------------------------------
if ($ref) {
    $check = $pdo->prepare('SELECT statut FROM payments WHERE reference = ?');
    $check->execute([$ref]);
    $existing = $check->fetch();
    if ($existing && $existing['statut'] === 'valide') {
        http_response_code(200);
        echo json_encode(['message' => 'Already processed', 'ref' => $ref]);
        exit;
    }
}

// -------------------------------------------------------
//  7. Valider l'utilisateur et le plan
// -------------------------------------------------------
if (!$userId || !$plan) {
    http_response_code(400);
    logAction('webhook_fedapay_missing_meta', 'Métadonnées manquantes : user=' . $userId . ' plan=' . $plan);
    echo json_encode(['error' => 'Missing metadata (user_id or plan)']);
    exit;
}

$userCheck = $pdo->prepare('SELECT id, prenom, nom, email FROM users WHERE id = ? AND actif = 1');
$userCheck->execute([$userId]);
$user = $userCheck->fetch();

if (!$user) {
    http_response_code(400);
    logAction('webhook_fedapay_user_not_found', 'Utilisateur introuvable : ' . $userId);
    echo json_encode(['error' => 'User not found']);
    exit;
}

// -------------------------------------------------------
//  8. Mettre à jour le paiement et activer l'abonnement
// -------------------------------------------------------
if ($ref) {
    // Mettre à jour le paiement existant
    $pdo->prepare('UPDATE payments SET statut = "valide", montant = ? WHERE reference = ?')
        ->execute([$amount, $ref]);
} else {
    // Créer si inexistant (webhook arrivé avant la redirection)
    $ref = 'FDP-' . $txnId;
    $pdo->prepare(
        'INSERT IGNORE INTO payments (user_id, plan, montant, reference, operateur, statut) VALUES (?, ?, ?, ?, "autre", "valide")'
    )->execute([$userId, $plan, $amount, $ref]);
}

// Activer ou créer l'abonnement
$existSub = $pdo->prepare('SELECT id FROM subscriptions WHERE user_id = ? AND plan = ?');
$existSub->execute([$userId, $plan]);
if ($existSub->fetch()) {
    $pdo->prepare('UPDATE subscriptions SET statut = "actif", paye = 1 WHERE user_id = ? AND plan = ?')
        ->execute([$userId, $plan]);
} else {
    $pdo->prepare('INSERT INTO subscriptions (user_id, plan, statut, paye) VALUES (?, ?, "actif", 1)')
        ->execute([$userId, $plan]);
}

// -------------------------------------------------------
//  9. Notifications et XP
// -------------------------------------------------------
$planNom = ucfirst(str_replace('_', ' ', $plan));

sendNotification(
    $userId,
    'Paiement FedaPay confirmé — Accès activé !',
    'Votre paiement de ' . number_format($amount, 0, ',', ' ') . ' FCFA a été validé. Votre abonnement ' . $planNom . ' est actif.',
    'success',
    SITE_URL . '/dashboard.php'
);

addXP($userId, 'badge_obtenu', 100, 'Abonnement ' . $planNom . ' débloqué');

logAction(
    'payment_fedapay_success',
    'FedaPay confirmé. TXN: ' . $txnId . ' | Ref: ' . $ref . ' | Utilisateur: ' . $userId . ' | Plan: ' . $plan . ' | Montant: ' . $amount . ' FCFA',
    $userId
);

// -------------------------------------------------------
//  10. Répondre 200 OK rapidement à FedaPay
// -------------------------------------------------------
http_response_code(200);
echo json_encode([
    'message'   => 'Webhook FedaPay traité avec succès',
    'user_id'   => $userId,
    'plan'      => $plan,
    'txn_id'    => $txnId,
    'ref'       => $ref,
    'amount'    => $amount,
]);
exit;
