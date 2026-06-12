<?php
// admin/slides.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();

$pdo    = getPDO();
$slides = $pdo->query('SELECT * FROM slides ORDER BY ordre ASC, created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Slides — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/dashboard.css">
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<div class="admin-content">
  <div class="admin-topbar">
    <h1 class="admin-page-title">Slides de la page d'accueil</h1>
    <a href="<?= SITE_URL ?>/admin/slide_add.php" class="btn-primary btn-sm">
      <i class="ti ti-plus" aria-hidden="true"></i> Ajouter un slide
    </a>
  </div>

  <?= flash() ?>

  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
    <?php foreach ($slides as $s): ?>
    <div class="admin-card" style="padding:0;overflow:hidden">
      <img src="<?= SITE_URL ?>/assets/uploads/slides/<?= h($s['image']) ?>"
           style="width:100%;height:140px;object-fit:cover">
      <div style="padding:12px">
        <div style="font-weight:500;font-size:14px;margin-bottom:4px"><?= h($s['titre']) ?></div>
        <div style="font-size:12px;color:var(--text-muted);margin-bottom:10px"><?= h($s['sous_titre'] ?: '—') ?></div>
        <div style="display:flex;justify-content:space-between;align-items:center">
          <?= $s['actif'] ? '<span class="badge badge-success">Actif</span>' : '<span class="badge badge-neutral">Masqué</span>' ?>
          <a href="<?= SITE_URL ?>/admin/slides.php?delete=<?= $s['id'] ?>&csrf=<?= csrfToken() ?>"
             class="btn-icon btn-icon-danger"
             onclick="return confirm('Supprimer ce slide ?')">
            <i class="ti ti-trash" aria-hidden="true"></i>
          </a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($slides)): ?>
      <p style="color:var(--text-muted);font-size:14px">Aucun slide. <a href="<?= SITE_URL ?>/admin/slide_add.php">Ajouter le premier</a></p>
    <?php endif; ?>
  </div>
</div>

<?php
// Suppression inline
if (isset($_GET['delete'])) {
    $csrf = $_GET['csrf'] ?? '';
    if (hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
        $sid = (int)$_GET['delete'];
        $s = $pdo->prepare('SELECT image FROM slides WHERE id = ?');
        $s->execute([$sid]);
        $slide = $s->fetch();
        if ($slide) {
            $f = UPLOAD_DIR . 'slides/' . $slide['image'];
            if (file_exists($f)) unlink($f);
            $pdo->prepare('DELETE FROM slides WHERE id = ?')->execute([$sid]);
        }
    }
    redirect(SITE_URL . '/admin/slides.php', 'Slide supprimé.', 'success');
}
?>
<script src="<?= SITE_URL ?>/assets/js/dashboard.js"></script>
</body>
</html>
