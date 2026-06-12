<?php
// admin/payments.php — Gestion des paiements
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();
$pdo = getPDO();

// Valider un paiement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'valider') {
    verifierCSRF();
    $payId = (int)($_POST['pay_id'] ?? 0);
    $pay = $pdo->prepare('SELECT * FROM payments WHERE id = ?');
    $pay->execute([$payId]);
    $pay = $pay->fetch();
    if ($pay) {
        $pdo->prepare('UPDATE payments SET statut = "valide" WHERE id = ?')->execute([$payId]);
        // Activer l'abonnement
        $pdo->prepare(
            'UPDATE subscriptions SET paye = 1, statut = "actif" WHERE user_id = ? ORDER BY created_at DESC LIMIT 1'
        )->execute([$pay['user_id']]);
        // Notifier
        $pdo->prepare(
            'INSERT INTO user_notifications (user_id, titre, message, type, lien) VALUES (?, ?, ?, "success", ?)'
        )->execute([$pay['user_id'], 'Paiement validé !', 'Votre paiement de '.number_format($pay['montant'],0,',',' ').' FCFA a été validé. Votre accès est activé.', SITE_URL.'/dashboard.php']);
        redirect(SITE_URL . '/admin/payments.php', 'Paiement validé et accès activé.', 'success');
    }
}

$payments = $pdo->query(
    'SELECT p.*, u.prenom, u.nom, u.email
     FROM payments p
     JOIN users u ON u.id = p.user_id
     ORDER BY p.created_at DESC'
)->fetchAll();

// Stats rapides
$stats = [
    'total'    => $pdo->query('SELECT COALESCE(SUM(montant),0) FROM payments WHERE statut="valide"')->fetchColumn(),
    'attente'  => $pdo->query('SELECT COUNT(*) FROM payments WHERE statut="en_attente"')->fetchColumn(),
    'valides'  => $pdo->query('SELECT COUNT(*) FROM payments WHERE statut="valide"')->fetchColumn(),
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Paiements — Admin <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/dashboard.css">
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <h1 class="admin-page-title">Paiements</h1>
  </div>
  <?= flash() ?>

  <div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card">
      <div class="stat-icon" style="background:#EAF3DE;color:#3B6D11"><i class="ti ti-coin"></i></div>
      <div><p class="stat-label">Revenus validés</p><p class="stat-val"><?= number_format((float)$stats['total'],0,',',' ') ?> FCFA</p></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#f5f3ff;color:#6C47D4"><i class="ti ti-clock"></i></div>
      <div><p class="stat-label">En attente</p><p class="stat-val"><?= $stats['attente'] ?></p></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#EEEDFE;color:#534AB7"><i class="ti ti-check"></i></div>
      <div><p class="stat-label">Validés</p><p class="stat-val"><?= $stats['valides'] ?></p></div>
    </div>
  </div>

  <div class="admin-card">
    <table class="admin-table">
      <thead><tr><th>Utilisateur</th><th>Référence</th><th>Montant</th><th>Méthode</th><th>Statut</th><th>Date</th><th>Action</th></tr></thead>
      <tbody>
        <?php foreach ($payments as $p):
          $statColors = ['valide'=>['#EAF3DE','#27500A'],'en_attente'=>['#f5f3ff','#92400e'],'echoue'=>['#FAECE7','#993C1D']];
          [$bg,$fg] = $statColors[$p['statut']] ?? ['#f9fafb','#6b7280'];
        ?>
        <tr>
          <td>
            <div style="font-weight:600"><?= h($p['prenom'].' '.$p['nom']) ?></div>
            <div style="font-size:11px;color:var(--text-muted)"><?= h($p['email']) ?></div>
          </td>
          <td style="font-family:monospace;font-size:12px"><?= h($p['reference']) ?></td>
          <td style="font-weight:700"><?= number_format($p['montant'],0,',',' ') ?> FCFA</td>
          <td><?= h(str_replace('_',' ',ucfirst($p['methode']))) ?></td>
          <td>
            <span style="padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;background:<?= $bg ?>;color:<?= $fg ?>">
              <?= ['en_attente'=>'En attente','valide'=>'Validé','echoue'=>'Échoué'][$p['statut']] ?? $p['statut'] ?>
            </span>
          </td>
          <td style="font-size:12px;color:var(--text-muted)"><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></td>
          <td>
            <?php if ($p['statut'] === 'en_attente'): ?>
            <form method="POST" style="margin:0">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="action" value="valider">
              <input type="hidden" name="pay_id" value="<?= $p['id'] ?>">
              <button type="submit" class="btn-primary btn-sm" onclick="return confirm('Valider ce paiement ?')">
                <i class="ti ti-check"></i> Valider
              </button>
            </form>
            <?php else: ?><span style="color:var(--text-muted);font-size:12px">—</span><?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($payments)): ?>
        <tr><td colspan="7" style="text-align:center;color:var(--text-muted)">Aucun paiement</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
