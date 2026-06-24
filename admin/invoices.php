<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();
$pdo = getPDO();

// Générer facture PDF manuellement
if (isset($_GET['generate'])) {
    $userId   = (int)$_GET['user_id'];
    $courseId = (int)$_GET['course_id'];
    $montant  = (float)$_GET['montant'];
    $numero   = 'INV-' . date('Y') . '-' . str_pad(rand(1,9999), 4, '0', STR_PAD_LEFT);
    try {
        $pdo->prepare('INSERT IGNORE INTO invoices (user_id, course_id, montant, numero, statut) VALUES (?,?,?,?,\"payee\")')
            ->execute([$userId, $courseId, $montant, $numero]);
        redirect(SITE_URL . '/admin/invoices.php', 'Facture générée : ' . $numero, 'success');
    } catch(Exception $e) {
        redirect(SITE_URL . '/admin/invoices.php', 'Erreur : ' . $e->getMessage(), 'error');
    }
}

// Export CSV factures
if (isset($_GET['export_comptable'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="export-comptable-' . date('Y-m') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($out, ['Numéro facture','Date','Client','Email','Produit','Montant HT (FCFA)','TVA 18%','Montant TTC (FCFA)','Statut'], ';');
    try {
        $rows = getPDO()->query(
            'SELECT i.numero, i.created_at, u.nom, u.prenom, u.email, c.titre as cours, i.montant, i.statut
             FROM invoices i JOIN users u ON u.id=i.user_id LEFT JOIN courses c ON c.id=i.course_id
             ORDER BY i.created_at DESC'
        )->fetchAll();
        foreach ($rows as $r) {
            $ht  = round($r['montant'] / 1.18, 0);
            $tva = $r['montant'] - $ht;
            fputcsv($out, [$r['numero'], date('d/m/Y', strtotime($r['created_at'])), $r['nom'].' '.$r['prenom'], $r['email'], $r['cours']??'Formation', number_format($ht,0,'',','), number_format($tva,0,'',','), number_format($r['montant'],0,'',','), ucfirst($r['statut'])], ';');
        }
    } catch(Exception $e) {}
    fclose($out);
    exit;
}

if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="factures-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($out, ['Numéro', 'Étudiant', 'Email', 'Cours', 'Montant (FCFA)', 'Statut', 'Date'], ';');
    try {
        $rows = $pdo->query(
            'SELECT i.numero, u.nom, u.prenom, u.email, c.titre as cours, i.montant, i.statut, i.created_at
             FROM invoices i
             JOIN users u ON u.id=i.user_id
             LEFT JOIN courses c ON c.id=i.course_id
             ORDER BY i.created_at DESC'
        )->fetchAll();
        foreach ($rows as $r) {
            fputcsv($out, [$r['numero'], $r['nom'].' '.$r['prenom'], $r['email'], $r['cours']??'—', number_format($r['montant'],0,'.',','), $r['statut'], date('d/m/Y', strtotime($r['created_at']))], ';');
        }
    } catch(Exception $e) {}
    fclose($out);
    exit;
}

try {
    $invoices = $pdo->query(
        'SELECT i.*, u.nom, u.prenom, u.email, c.titre as cours_titre
         FROM invoices i
         JOIN users u ON u.id=i.user_id
         LEFT JOIN courses c ON c.id=i.course_id
         ORDER BY i.created_at DESC LIMIT 200'
    )->fetchAll();
} catch(Exception $e) { $invoices = []; }

$currentPage = 'invoices.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Factures — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <div>
      <h1 class="admin-page-title">Factures</h1>
      <p class="admin-page-sub"><?= count($invoices) ?> facture(s)</p>
    </div>
    <a href="?export=1" class="btn-outline"><i class="ti ti-download"></i> Export simple</a>
    <a href="?export_comptable=1" class="btn-outline"><i class="ti ti-calculator"></i> Export comptable</a>
  </div>
  <?= flash() ?>
  <div class="admin-card">
    <table class="admin-table">
      <thead>
        <tr><th>Numéro</th><th>Étudiant</th><th>Cours</th><th>Montant</th><th>Statut</th><th>Date</th></tr>
      </thead>
      <tbody>
        <?php foreach ($invoices as $inv): ?>
        <tr>
          <td><strong style="font-family:monospace"><?= h($inv['numero']) ?></strong></td>
          <td>
            <div style="font-weight:600;font-size:13px"><?= h($inv['nom'].' '.$inv['prenom']) ?></div>
            <div style="font-size:11px;color:#9ca3af"><?= h($inv['email']) ?></div>
          </td>
          <td><?= h($inv['cours_titre'] ?? '—') ?></td>
          <td><strong><?= number_format($inv['montant'],0,'.',',') ?> FCFA</strong></td>
          <td>
            <span class="badge <?= $inv['statut']==='payee'?'badge-success':'badge-neutral' ?>">
              <?= ucfirst($inv['statut']) ?>
            </span>
          </td>
          <td style="font-size:12px;color:#6b7280"><?= date('d/m/Y', strtotime($inv['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($invoices)): ?>
        <tr><td colspan="6" style="text-align:center;padding:40px;color:#9ca3af">Aucune facture</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
