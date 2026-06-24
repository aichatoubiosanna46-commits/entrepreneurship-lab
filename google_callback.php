<?php
// ============================================================
//  google_callback.php — Échange le code OAuth2 contre un token,
//  récupère le profil Google, puis crée/connecte l'utilisateur.
// ============================================================
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

if (estConnecte()) {
    redirect(SITE_URL . '/dashboard.php');
}

$code  = $_GET['code']  ?? '';
$state = $_GET['state'] ?? '';

if (!$code || !$state || !hash_equals($_SESSION['google_oauth_state'] ?? '', $state)) {
    redirect(SITE_URL . '/login.php', 'Connexion Google annulée ou invalide.', 'error');
}
unset($_SESSION['google_oauth_state']);

// ── 1. Échanger le code contre un access token ──
$tokenParams = [
    'code'          => $code,
    'client_id'     => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'grant_type'    => 'authorization_code',
];

$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query($tokenParams),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
]);
$tokenResponse = curl_exec($ch);
$tokenHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($tokenResponse === false || $tokenHttpCode !== 200) {
    redirect(SITE_URL . '/login.php', 'Erreur de connexion avec Google. Réessayez.', 'error');
}

$tokenData = json_decode($tokenResponse, true);
$accessToken = $tokenData['access_token'] ?? '';
if (!$accessToken) {
    redirect(SITE_URL . '/login.php', 'Erreur de connexion avec Google. Réessayez.', 'error');
}

// ── 2. Récupérer le profil utilisateur Google ──
$ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
]);
$profileResponse = curl_exec($ch);
$profileHttpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($profileResponse === false || $profileHttpCode !== 200) {
    redirect(SITE_URL . '/login.php', 'Impossible de récupérer le profil Google.', 'error');
}

$profile = json_decode($profileResponse, true);
$email   = trim($profile['email'] ?? '');
$prenom  = trim($profile['given_name'] ?? '') ?: 'Utilisateur';
$nom     = trim($profile['family_name'] ?? '') ?: 'Google';

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect(SITE_URL . '/login.php', 'Votre compte Google ne fournit pas d\'adresse e-mail valide.', 'error');
}

// ── 3. Créer ou retrouver l'utilisateur correspondant ──
$pdo  = getPDO();
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    // Mot de passe aléatoire (jamais utilisé : connexion via Google uniquement)
    $randomPassword = password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $pdo->prepare(
        'INSERT INTO users (nom, prenom, email, password) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$nom, $prenom, $email, $randomPassword]);
    $userId = $pdo->lastInsertId();

    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    triggerAutomation('user_registered', (int)$userId, 0);
    emailBienvenue($email, $prenom);
    logAction('register_google', 'Inscription via Google pour ' . $email, (int)$userId);
} else {
    if (!$user['actif']) {
        redirect(SITE_URL . '/login.php', 'Ce compte est désactivé.', 'error');
    }
    logAction('login_google', 'Connexion via Google pour ' . $email, (int)$user['id']);
}

connecterUtilisateur($user);
redirect(SITE_URL . '/dashboard.php', 'Bienvenue, ' . $user['prenom'] . ' !', 'success');
