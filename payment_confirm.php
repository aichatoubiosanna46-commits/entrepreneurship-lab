<?php
// payment_confirm.php — Confirmation de paiement soumis
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
reqConnecte();

$pdo = getPDO();
$ref = trim($_GET['ref'] ?? '');
$pay = null;
if ($ref) {
    $stmt = $pdo->prepare('SELECT * FROM payments WHERE reference = ? AND user_id = ?');
    $stmt->execute([$ref, $_SESSION['user_id']]);
    $pay = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Paiement en attente — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<div style="max-width:560px;margin:80px auto;padding:0 20px;text-align:center">
  <div style="width:80px;height:80px;border-radius:50%;background:#fffbf0;border:4px solid #BA7517;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;font-size:36px;color:#BA7517">
    <i class="ti ti-clock"></i>
  </div>
  <h1 style="font-size:26px;font-weight:800;margin:0 0 12px">Demande envoyée !</h1>
  <p style="color:var(--text-muted,#6b7280);margin-bottom:24px">
    Votre demande d'abonnement a été reçue. Notre équipe va valider votre paiement sous <strong>24 heures</strong>.
    Vous recevrez une notification dès l'activation de votre compte.
  </p>
  <?php if ($pay): ?>
  <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:16px;margin-bottom:24px;font-size:13px;text-align:left">
    <div><strong>Référence :</strong> <?= h($pay['reference']) ?></div>
    <div><strong>Montant :</strong> <?= number_format($pay['montant'],0,',',' ') ?> FCFA</div>
    <div><strong>Statut :</strong> En attente de validation</div>
  </div>
  <?php endif; ?>
  <div style="display:flex;gap:12px;justify-content:center">
    <a href="<?= SITE_URL ?>/dashboard.php" style="padding:12px 24px;background:#534AB7;color:#fff;border-radius:10px;text-decoration:none;font-weight:700">Retour au tableau de bord</a>
    <a href="<?= SITE_URL ?>/payment_history.php" style="padding:12px 24px;border:1px solid #e5e7eb;border-radius:10px;text-decoration:none;font-weight:600;color:#111">Historique des paiements</a>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
