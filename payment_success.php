<?php
// ============================================================
//  payment_success.php — Callback FedaPay après paiement
//  FedaPay redirige ici après complétion du widget
//  On vérifie le statut via l'API avant d'activer l'accès
// ============================================================
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
reqConnecte();

$pdo    = getPDO();
$userId = $_SESSION['user_id'];
$ref    = trim($_GET['ref']    ?? '');
$txnId  = trim($_GET['txn_id'] ?? '');

$status  = 'pending'; // 'success' | 'pending' | 'error'
$message = '';
$plan    = '';
$montant = 0;

// -------------------------------------------------------
//  Récupérer le paiement pré-enregistré
// -------------------------------------------------------
$pay = null;
if ($ref) {
    $stmt = $pdo->prepare('SELECT * FROM payments WHERE reference = ? AND user_id = ?');
    $stmt->execute([$ref, $userId]);
    $pay = $stmt->fetch();
}

if (!$pay) {
    $status  = 'error';
    $message = 'Référence de paiement introuvable.';
} elseif ($pay['statut'] === 'valide') {
    // Déjà traité (ex: webhook arrivé avant la redirection)
    $status  = 'success';
    $message = 'Votre paiement a déjà été validé. Votre accès est actif.';
    $plan    = $pay['plan'];
    $montant = $pay['montant'];
} elseif ($txnId) {
    // -------------------------------------------------------
    //  Vérifier la transaction via l'API FedaPay
    // -------------------------------------------------------
    $ch = curl_init(FEDAPAY_API_URL . '/transactions/' . intval($txnId));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . FEDAPAY_SECRET_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr || $httpCode !== 200) {
        // Impossible de vérifier maintenant → en attente
        $status  = 'pending';
        $message = 'Votre paiement est en cours de validation. Vous recevrez une notification dès qu\'il est confirmé.';
    } else {
        $data      = json_decode($response, true);
        $txnData   = $data['v1/transaction'] ?? [];
        $txnStatus = $txnData['status'] ?? '';
        $montant   = $txnData['amount'] ?? $pay['montant'];

        if ($txnStatus === 'approved') {
            // ✅ Paiement validé — activer l'abonnement
            $plan = $pay['plan'];

            // Idempotence : ne pas doubler si déjà validé
            $pdo->prepare('UPDATE payments SET statut = "valide", montant = ? WHERE reference = ?')
                ->execute([$montant, $ref]);

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

            // Notification in-app
            sendNotification(
                $userId,
                'Paiement confirmé — Accès activé !',
                'Votre paiement de ' . number_format($montant, 0, ',', ' ') . ' FCFA a été validé. Votre abonnement ' . ucfirst($plan) . ' est actif.',
                'success',
                SITE_URL . '/dashboard.php'
            );

            // XP bonus pour premier paiement
            addXP($userId, 'badge_obtenu', 100, 'Accès ' . $plan . ' débloqué');

            $status  = 'success';
            $message = 'Paiement validé ! Votre abonnement <strong>' . h(ucfirst(str_replace('_', ' ', $plan))) . '</strong> est actif.';

        } elseif (in_array($txnStatus, ['declined', 'canceled', 'refunded'])) {
            $pdo->prepare('UPDATE payments SET statut = "rejete" WHERE reference = ?')->execute([$ref]);
            $status  = 'error';
            $message = 'Votre paiement a été ' . ($txnStatus === 'declined' ? 'refusé' : 'annulé') . '. Aucun montant n\'a été débité.';
        } else {
            // En attente (transfert en cours)
            $status  = 'pending';
            $message = 'Votre paiement est en cours de traitement (statut : ' . h($txnStatus) . '). Vous serez notifié dès validation.';
        }
    }
} else {
    $status  = 'pending';
    $message = 'Votre demande a été reçue. Vous serez notifié dès que le paiement est confirmé.';
}

// Icône et couleurs selon statut
$config = [
    'success' => ['icon' => 'ti-circle-check', 'color' => '#16a34a', 'bg' => '#f0fdf4', 'border' => '#86efac', 'title' => 'Paiement réussi !'],
    'pending' => ['icon' => 'ti-clock',         'color' => '#d97706', 'bg' => '#fffbeb', 'border' => '#fcd34d', 'title' => 'Paiement en attente'],
    'error'   => ['icon' => 'ti-alert-circle',  'color' => '#dc2626', 'bg' => '#fef2f2', 'border' => '#fca5a5', 'title' => 'Paiement non confirmé'],
][$status];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $config['title'] ?> — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>

<div style="max-width:560px;margin:80px auto;padding:0 24px 80px;text-align:center">

  <div style="width:88px;height:88px;border-radius:50%;background:<?= $config['bg'] ?>;border:4px solid <?= $config['border'] ?>;
              display:flex;align-items:center;justify-content:center;margin:0 auto 24px;font-size:40px;color:<?= $config['color'] ?>">
    <i class="ti <?= $config['icon'] ?>"></i>
  </div>

  <h1 style="font-size:26px;font-weight:800;margin:0 0 12px"><?= $config['title'] ?></h1>

  <p style="color:#6b7280;margin-bottom:24px;line-height:1.6">
    <?= $message ?>
  </p>

  <?php if ($pay): ?>
  <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:20px;margin-bottom:28px;text-align:left;font-size:13px">
    <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f3f4f6">
      <span style="color:#6b7280">Référence</span>
      <strong><?= h($pay['reference']) ?></strong>
    </div>
    <?php if ($montant): ?>
    <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f3f4f6">
      <span style="color:#6b7280">Montant</span>
      <strong><?= number_format($montant, 0, ',', ' ') ?> FCFA</strong>
    </div>
    <?php endif; ?>
    <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f3f4f6">
      <span style="color:#6b7280">Offre</span>
      <strong><?= h(ucfirst(str_replace('_', ' ', $pay['plan']))) ?></strong>
    </div>
    <div style="display:flex;justify-content:space-between;padding:6px 0">
      <span style="color:#6b7280">Statut</span>
      <strong style="color:<?= $config['color'] ?>"><?= h(ucfirst($status === 'success' ? 'Validé' : ($status === 'pending' ? 'En attente' : 'Refusé'))) ?></strong>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($status === 'success'): ?>
  <a href="<?= SITE_URL ?>/dashboard.php"
     style="display:inline-block;padding:14px 32px;background:#534AB7;color:#fff;border-radius:10px;text-decoration:none;font-weight:700;margin-bottom:12px">
    <i class="ti ti-layout-dashboard"></i> Accéder à mon espace
  </a>
  <?php elseif ($status === 'pending'): ?>
  <a href="<?= SITE_URL ?>/dashboard.php"
     style="display:inline-block;padding:14px 32px;background:#534AB7;color:#fff;border-radius:10px;text-decoration:none;font-weight:700;margin-bottom:12px">
    <i class="ti ti-layout-dashboard"></i> Retour au tableau de bord
  </a><br>
  <a href="<?= SITE_URL ?>/payment_history.php"
     style="display:inline-block;margin-top:12px;font-size:13px;color:#6b7280">
    Voir l'historique des paiements
  </a>
  <?php else: ?>
  <a href="<?= SITE_URL ?>/payment.php"
     style="display:inline-block;padding:14px 32px;background:#534AB7;color:#fff;border-radius:10px;text-decoration:none;font-weight:700;margin-bottom:12px">
    <i class="ti ti-refresh"></i> Réessayer le paiement
  </a><br>
  <a href="<?= SITE_URL ?>/dashboard.php"
     style="display:inline-block;margin-top:12px;font-size:13px;color:#6b7280">
    Retour au tableau de bord
  </a>
  <?php endif; ?>

  <div style="margin-top:32px;font-size:12px;color:#9ca3af">
    <i class="ti ti-shield-lock"></i>
    Paiement sécurisé par <strong>FedaPay</strong> · SSL chiffré · Aucune donnée bancaire stockée sur nos serveurs
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
