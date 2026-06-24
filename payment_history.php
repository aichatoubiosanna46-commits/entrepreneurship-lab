<?php
// payment_history.php — Historique des paiements
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
reqConnecte();

$pdo    = getPDO();
$userId = $_SESSION['user_id'];

$payments = $pdo->prepare(
    'SELECT p.*, c.titre as course_titre
     FROM payments p
     LEFT JOIN courses c ON c.id = p.course_id
     WHERE p.user_id = ? ORDER BY p.created_at DESC'
);
$payments->execute([$userId]);
$payments = $payments->fetchAll();

try {
    $invStmt = $pdo->prepare('SELECT * FROM invoices WHERE user_id = ? ORDER BY created_at DESC');
    $invStmt->execute([$userId]);
    $invoices = $invStmt->fetchAll();
} catch (Exception $e) { $invoices = []; }

$subs = $pdo->prepare('SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC');
$subs->execute([$userId]);
$subs = $subs->fetchAll();
$planNames = ['decouverte'=>'Découverte','business_plan'=>'Business Plan','lancement'=>'Lancement'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Historique paiements — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/user-dashboard.css">
</head>
<body class="user-dash-page">
<?php include __DIR__ . '/includes/header.php'; ?>
<div class="user-dash-layout">
  <aside class="user-sidebar">
    <div class="user-sidebar-profile">
      <div class="user-avatar-ring">
        <div class="user-avatar-placeholder"><?= mb_strtoupper(mb_substr($_SESSION['user_nom']??'U',0,1)) ?></div>
      </div>
      <div class="user-sidebar-name"><?= h($_SESSION['user_nom']??'') ?></div>
    </div>
    <nav class="user-sidebar-nav">
      <a href="<?= SITE_URL ?>/dashboard.php" class="user-nav-item"><i class="ti ti-layout-dashboard"></i> Tableau de bord</a>
      <a href="<?= SITE_URL ?>/favorites.php" class="user-nav-item"><i class="ti ti-heart"></i> Favoris</a>
      <a href="<?= SITE_URL ?>/payment.php" class="user-nav-item"><i class="ti ti-credit-card"></i> Abonnement</a>
      <a href="<?= SITE_URL ?>/payment_history.php" class="user-nav-item active"><i class="ti ti-receipt"></i> Paiements</a>
      <a href="<?= SITE_URL ?>/profil.php" class="user-nav-item"><i class="ti ti-user"></i> Mon profil</a>
    </nav>
    <div class="user-sidebar-footer">
      <a href="<?= SITE_URL ?>/logout.php" class="user-nav-item" style="color:#F0997B"><i class="ti ti-logout"></i> Déconnexion</a>
    </div>
  </aside>
  <main class="user-dash-main">
    <div class="dash-topbar">
      <h1 class="dash-title">Historique des paiements</h1>
    </div>

    <h2 style="font-size:16px;font-weight:700;margin:0 0 12px">Abonnements</h2>
    <?php if (empty($subs)): ?>
      <p style="color:var(--text-muted,#6b7280);font-size:14px">Aucun abonnement.</p>
    <?php else: ?>
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;margin-bottom:32px">
      <table style="width:100%;border-collapse:collapse;font-size:13px">
        <thead><tr style="background:#f9fafb">
          <th style="padding:12px 16px;text-align:left">Offre</th>
          <th style="padding:12px 16px;text-align:left">Statut</th>
          <th style="padding:12px 16px;text-align:left">Payé</th>
          <th style="padding:12px 16px;text-align:left">Date</th>
        </tr></thead>
        <tbody>
          <?php foreach ($subs as $s): ?>
          <tr style="border-top:1px solid #e5e7eb">
            <td style="padding:12px 16px;font-weight:600"><?= $planNames[$s['tarif']] ?? $s['tarif'] ?></td>
            <td style="padding:12px 16px">
              <span style="padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;background:<?= $s['statut']==='actif'?'#EAF3DE':'#f9fafb' ?>;color:<?= $s['statut']==='actif'?'#27500A':'#6b7280' ?>">
                <?= ucfirst($s['statut']) ?>
              </span>
            </td>
            <td style="padding:12px 16px"><?= $s['paye'] ? '<span style="color:#16a34a">✓ Oui</span>' : '<span style="color:#dc2626">⏳ En attente</span>' ?></td>
            <td style="padding:12px 16px;color:var(--text-muted,#6b7280)"><?= date('d/m/Y', strtotime($s['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <h2 style="font-size:16px;font-weight:700;margin:0 0 12px">Transactions</h2>
    <?php if (empty($payments)): ?>
      <p style="color:var(--text-muted,#6b7280);font-size:14px">Aucune transaction.</p>
    <?php else: ?>
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden">
      <table style="width:100%;border-collapse:collapse;font-size:13px">
        <thead><tr style="background:#f9fafb">
          <th style="padding:12px 16px;text-align:left">Référence</th>
          <th style="padding:12px 16px;text-align:left">Montant</th>
          <th style="padding:12px 16px;text-align:left">Méthode</th>
          <th style="padding:12px 16px;text-align:left">Statut</th>
          <th style="padding:12px 16px;text-align:left">Date</th>
          <th style="padding:12px 16px;text-align:center">Remboursement</th>
        </tr></thead>
        <tbody>
          <?php foreach ($payments as $p):
            $statColors = ['valide'=>['#EAF3DE','#27500A'],'en_attente'=>['#f5f3ff','#92400e'],'echoue'=>['#FAECE7','#993C1D']];
            [$bg,$fg] = $statColors[$p['statut']] ?? ['#f9fafb','#6b7280'];
          ?>
          <tr style="border-top:1px solid #e5e7eb">
            <td style="padding:12px 16px;font-family:monospace;font-size:12px"><?= h($p['reference']) ?></td>
            <td style="padding:12px 16px;font-weight:600"><?= number_format($p['montant'],0,',',' ') ?> FCFA</td>
            <td style="padding:12px 16px"><?= h(str_replace('_',' ',ucfirst($p['methode'] ?? $p['operateur'] ?? ''))) ?></td>
            <td style="padding:12px 16px">
              <span style="padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;background:<?= $bg ?>;color:<?= $fg ?>">
                <?= ['en_attente'=>'En attente','valide'=>'Validé','echoue'=>'Échoué','rembourse'=>'Remboursé'][$p['statut']] ?? $p['statut'] ?>
              </span>
            </td>
            <td style="padding:12px 16px;color:var(--text-muted,#6b7280)"><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></td>
            <td style="padding:12px 16px;text-align:center">
              <?php
                $refundStatus = $p['refund_status'] ?? 'aucun';
              ?>
              <?php if ($p['statut'] === 'valide' && $refundStatus === 'aucun'): ?>
                <form method="POST" action="<?= SITE_URL ?>/refund.php" onsubmit="return confirm('Demander un remboursement pour ce paiement ?')" style="display:inline">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                  <input type="hidden" name="motif" value="Demande via historique des paiements">
                  <button type="submit" class="btn" style="font-size:11px;padding:5px 10px;background:#fff;border:1px solid #e5e7eb;border-radius:6px;cursor:pointer">
                    <i class="ti ti-receipt-refund"></i> Remboursement
                  </button>
                </form>
              <?php elseif ($refundStatus === 'demande'): ?>
                <span style="padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;background:#FBE3DA;color:#92400e">Remb. demandé</span>
              <?php elseif ($refundStatus === 'rembourse'): ?>
                <span style="padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;background:#EAF3DE;color:#27500A">Remboursé</span>
              <?php elseif ($refundStatus === 'refuse'): ?>
                <span style="padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;background:#FAECE7;color:#993C1D">Remb. refusé</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <h2 style="font-size:16px;font-weight:700;margin:32px 0 12px">Mes factures</h2>
    <?php if (empty($invoices)): ?>
      <p style="color:var(--text-muted,#6b7280);font-size:14px">Aucune facture disponible.</p>
    <?php else: ?>
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden">
      <table style="width:100%;border-collapse:collapse;font-size:13px">
        <thead><tr style="background:#f9fafb">
          <th style="padding:12px 16px;text-align:left">Numéro</th>
          <th style="padding:12px 16px;text-align:left">Montant</th>
          <th style="padding:12px 16px;text-align:left">Statut</th>
          <th style="padding:12px 16px;text-align:left">Date</th>
          <th style="padding:12px 16px;text-align:center">PDF</th>
        </tr></thead>
        <tbody>
          <?php foreach ($invoices as $inv): ?>
          <tr style="border-top:1px solid #e5e7eb">
            <td style="padding:12px 16px;font-family:monospace;font-size:12px"><?= h($inv['numero']) ?></td>
            <td style="padding:12px 16px;font-weight:600"><?= number_format($inv['montant'],0,',',' ') ?> FCFA</td>
            <td style="padding:12px 16px"><?= ucfirst($inv['statut']) ?></td>
            <td style="padding:12px 16px;color:var(--text-muted,#6b7280)"><?= date('d/m/Y', strtotime($inv['created_at'])) ?></td>
            <td style="padding:12px 16px;text-align:center">
              <a href="<?= SITE_URL ?>/invoice_pdf.php?id=<?= $inv['id'] ?>" class="btn" style="font-size:11px;padding:5px 10px;background:#534AB7;color:#fff;border-radius:6px;text-decoration:none;display:inline-flex;align-items:center;gap:4px">
                <i class="ti ti-download"></i> PDF
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </main>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
