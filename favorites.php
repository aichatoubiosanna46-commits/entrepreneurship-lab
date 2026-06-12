<?php
// favorites.php — Formations favorites
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
reqConnecte();

$pdo    = getPDO();
$userId = $_SESSION['user_id'];

// Toggle favori via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    verifierCSRF();
    $courseId = (int)($_POST['course_id'] ?? 0);
    if ($courseId) {
        $check = $pdo->prepare('SELECT id FROM favorites WHERE user_id = ? AND course_id = ?');
        $check->execute([$userId, $courseId]);
        if ($check->fetch()) {
            $pdo->prepare('DELETE FROM favorites WHERE user_id = ? AND course_id = ?')->execute([$userId, $courseId]);
        } else {
            $pdo->prepare('INSERT IGNORE INTO favorites (user_id, course_id) VALUES (?, ?)')->execute([$userId, $courseId]);
        }
    }
    header('Location: ' . SITE_URL . '/favorites.php');
    exit;
}

$favs = $pdo->prepare(
    'SELECT c.*, cat.nom as categorie, cat.icone as cat_icone, cat.couleur as cat_couleur,
            (SELECT COUNT(*) FROM sequences s JOIN modules m ON m.id = s.module_id WHERE m.course_id = c.id AND s.actif = 1) as nb_lecons
     FROM favorites f
     JOIN courses c ON c.id = f.course_id
     JOIN categories cat ON cat.id = c.category_id
     WHERE f.user_id = ? ORDER BY f.created_at DESC'
);
$favs->execute([$userId]);
$favs = $favs->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mes favoris — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
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
      <a href="<?= SITE_URL ?>/favorites.php" class="user-nav-item active"><i class="ti ti-heart"></i> Favoris</a>
      <a href="<?= SITE_URL ?>/payment.php" class="user-nav-item"><i class="ti ti-credit-card"></i> Abonnement</a>
      <a href="<?= SITE_URL ?>/payment_history.php" class="user-nav-item"><i class="ti ti-receipt"></i> Paiements</a>
      <a href="<?= SITE_URL ?>/profil.php" class="user-nav-item"><i class="ti ti-user"></i> Mon profil</a>
    </nav>
    <div class="user-sidebar-footer">
      <a href="<?= SITE_URL ?>/logout.php" class="user-nav-item" style="color:#F0997B"><i class="ti ti-logout"></i> Déconnexion</a>
    </div>
  </aside>
  <main class="user-dash-main">
    <div class="dash-topbar">
      <h1 class="dash-title"><i class="ti ti-heart" style="color:#dc2626"></i> Mes favoris</h1>
    </div>
    <?= flash() ?>

    <?php if (empty($favs)): ?>
    <div class="dash-empty">
      <i class="ti ti-heart"></i>
      <h3>Aucun favori pour l'instant.</h3>
      <p>Ajoutez des formations à vos favoris en cliquant sur le cœur ❤️.</p>
      <a href="<?= SITE_URL ?>/search.php" class="btn-primary" style="margin-top:16px">Explorer les formations</a>
    </div>
    <?php else: ?>
    <div class="dash-modules-grid">
      <?php foreach ($favs as $c): ?>
      <div class="dash-module-card" style="position:relative">
        <form method="POST" style="position:absolute;top:10px;right:10px;z-index:2;margin:0">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="action" value="toggle">
          <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
          <button type="submit" style="background:#fff;border:none;width:32px;height:32px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,.15)" title="Retirer des favoris">
            <i class="ti ti-heart-filled" style="color:#dc2626"></i>
          </button>
        </form>
        <a href="<?= SITE_URL ?>/module.php?slug=<?= h($c['slug']) ?>" style="text-decoration:none;color:inherit;display:flex;flex-direction:column;height:100%">
          <div class="dash-module-thumb">
            <?php if ($c['miniature']): ?>
              <img src="<?= SITE_URL ?>/assets/uploads/<?= h($c['miniature']) ?>" alt="">
            <?php else: ?>
              <div class="dash-module-thumb-ph" style="background:<?= h($c['cat_couleur']) ?>22">
                <i class="ti <?= h($c['cat_icone']) ?>" style="color:<?= h($c['cat_couleur']) ?>"></i>
              </div>
            <?php endif; ?>
          </div>
          <div class="dash-module-info">
            <span class="dash-module-cat" style="color:<?= h($c['cat_couleur']) ?>"><?= h($c['categorie']) ?></span>
            <h3><?= h($c['titre']) ?></h3>
            <div class="dash-module-meta">
              <span><i class="ti ti-list"></i> <?= $c['nb_lecons'] ?> séquences</span>
              <?php if ($c['type']==='gratuit'): ?>
                <span style="color:#16a34a;font-weight:600">Gratuit</span>
              <?php else: ?>
                <span style="color:#6C47D4;font-weight:600"><?= fcfa((float)$c['prix']) ?></span>
              <?php endif; ?>
            </div>
          </div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </main>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
