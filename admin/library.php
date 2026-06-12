<?php
// admin/library.php — Gestion de la bibliothèque de ressources
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();
$pdo = getPDO();

// Supprimer une ressource
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'supprimer') {
    verifierCSRF();
    $id = (int)($_POST['id'] ?? 0);
    $r = $pdo->prepare('SELECT fichier FROM library_resources WHERE id = ?');
    $r->execute([$id]);
    $r = $r->fetch();
    if ($r) {
        $path = UPLOAD_DIR . 'library/' . $r['fichier'];
        if (file_exists($path)) unlink($path);
        $pdo->prepare('DELETE FROM library_resources WHERE id = ?')->execute([$id]);
    }
    redirect(SITE_URL . '/admin/library.php', 'Ressource supprimée.', 'success');
}

$resources = $pdo->query('SELECT * FROM library_resources ORDER BY created_at DESC')->fetchAll();
$types = ['business_plan'=>'Business Plan','social_media'=>'Réseaux sociaux','sales_script'=>'Script de vente','autre'=>'Autre'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Bibliothèque — Admin <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/dashboard.css">
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <div><h1 class="admin-page-title">Bibliothèque de ressources</h1><p class="admin-page-sub"><?= count($resources) ?> ressources</p></div>
    <a href="<?= SITE_URL ?>/admin/library_add.php" class="btn-primary"><i class="ti ti-plus"></i> Ajouter</a>
  </div>
  <?= flash() ?>
  <div class="admin-card">
    <table class="admin-table">
      <thead><tr><th>Titre</th><th>Type</th><th>Accès min</th><th>Téléch.</th><th>Actif</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($resources as $r): ?>
        <tr>
          <td><strong><?= h($r['titre']) ?></strong><br><span style="font-size:11px;color:var(--text-muted)"><?= h($r['fichier']) ?></span></td>
          <td><?= $types[$r['type']] ?? $r['type'] ?></td>
          <td><?= ucfirst(str_replace('_',' ',$r['tarif_min'])) ?></td>
          <td><?= $r['downloads'] ?></td>
          <td><?= $r['actif'] ? '<span style="color:#16a34a">✓</span>' : '<span style="color:#dc2626">✗</span>' ?></td>
          <td style="display:flex;gap:6px">
            <a href="<?= SITE_URL ?>/assets/uploads/library/<?= h($r['fichier']) ?>" download class="btn-outline btn-sm"><i class="ti ti-download"></i></a>
            <form method="POST" style="margin:0">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="action" value="supprimer">
              <input type="hidden" name="id" value="<?= $r['id'] ?>">
              <button type="submit" class="btn-danger btn-sm" onclick="return confirm('Supprimer ?')"><i class="ti ti-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($resources)): ?>
        <tr><td colspan="6" style="text-align:center;color:var(--text-muted)">Aucune ressource</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
