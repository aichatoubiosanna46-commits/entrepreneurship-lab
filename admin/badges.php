<?php
// admin/badges.php — Liste des badges
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();

$pdo = getPDO();
$badges = $pdo->query(
    'SELECT b.*, (SELECT COUNT(*) FROM user_badges ub WHERE ub.badge_id = b.id) as nb_obtenus
     FROM badges b ORDER BY b.id DESC'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Badges — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/dashboard.css">
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<div class="admin-content">
  <div class="admin-topbar">
    <div><h1 class="admin-page-title">Badges</h1></div>
    <a href="<?= SITE_URL ?>/admin/badge_add.php" class="btn-primary btn-sm">
      <i class="ti ti-plus"></i> Nouveau badge
    </a>
  </div>

  <?= flash() ?>

  <?php if (empty($badges)): ?>
  <div style="text-align:center;padding:56px;color:var(--text-muted)">
    <i class="ti ti-medal" style="font-size:52px;display:block;margin-bottom:16px;opacity:.3"></i>
    <h3 style="color:var(--text);font-weight:500">Aucun badge créé</h3>
  </div>
  <?php else: ?>
  <div class="admin-card" style="padding:0;overflow:hidden">
    <table class="admin-table" style="margin:0">
      <thead><tr><th>Badge</th><th>Description</th><th>Obtenu par</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($badges as $b): ?>
        <tr>
          <td style="font-size:20px"><?= h($b['icone']) ?> <span style="font-size:13px;font-weight:600;vertical-align:middle"><?= h($b['titre']) ?></span></td>
          <td style="font-size:13px;color:var(--text-muted)"><?= h($b['description'] ?? '') ?></td>
          <td><?= (int)$b['nb_obtenus'] ?> apprenant<?= $b['nb_obtenus'] != 1 ? 's' : '' ?></td>
          <td>
            <div style="display:flex;gap:6px">
              <a href="<?= SITE_URL ?>/admin/badge_add.php?id=<?= $b['id'] ?>" class="btn-icon" title="Modifier"><i class="ti ti-edit"></i></a>
              <a href="<?= SITE_URL ?>/admin/badge_delete.php?id=<?= $b['id'] ?>&csrf=<?= csrfToken() ?>" class="btn-icon btn-icon-danger"
                 title="Supprimer" onclick="return confirm('Supprimer ce badge ?')"><i class="ti ti-trash"></i></a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<script src="<?= SITE_URL ?>/assets/js/dashboard.js"></script>
</body>
</html>
