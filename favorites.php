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
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/user-dashboard.css">
<style>
/* Variables Sunrise Africa */
:root {
  --amber:       #D85A30;
  --amber-light: #FBE3DA;
  --amber-dark:  #C04A22;
  --text-muted:  #6b7280;
}

/* ── Layout dashboard ── */
body.user-dash-page { background: #F5F0E8 !important; }

.user-dash-layout {
  display: flex !important;
  max-width: 1200px;
  margin: 0 auto;
  padding: 28px 20px;
  gap: 24px;
  align-items: flex-start;
}

/* ── Sidebar ── */
.user-sidebar {
  width: 220px !important;
  flex-shrink: 0;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  overflow: hidden;
  position: sticky;
  top: 80px;
}
.user-sidebar-profile {
  padding: 24px 16px 16px;
  text-align: center;
  background: linear-gradient(135deg, #1A1A18, #292524);
  border-bottom: 1px solid rgba(255,255,255,.08);
}
.user-avatar-ring {
  width: 64px; height: 64px; border-radius: 50%;
  margin: 0 auto 10px;
  border: 3px solid #D85A30;
  overflow: hidden;
  display: flex; align-items: center; justify-content: center;
}
.user-avatar-ring img { width: 100%; height: 100%; object-fit: cover; }
.user-avatar-placeholder {
  width: 100%; height: 100%;
  background: linear-gradient(135deg, #D85A30, #085041);
  color: #fff; font-size: 22px; font-weight: 800;
  display: flex; align-items: center; justify-content: center;
}
.user-sidebar-name  { font-size: 14px; font-weight: 700; color: #FBE3DA; }
.user-sidebar-email { font-size: 11px; color: rgba(255,255,255,.45); margin-top: 2px; }
.user-sidebar-nav   { padding: 8px 0; }
.user-nav-item {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 16px; font-size: 13px;
  color: #6b7280; text-decoration: none;
  transition: background .15s, color .15s;
  border-left: 3px solid transparent;
}
.user-nav-item i { font-size: 17px; flex-shrink: 0; }
.user-nav-item:hover { background: #F5F0E8; color: #C04A22; }
.user-nav-item.active {
  background: #FBE3DA; color: #C04A22;
  font-weight: 600; border-left-color: #D85A30;
}
.user-sidebar-footer { border-top: 1px solid #f3f4f6; padding: 6px 0; }

/* ── Main ── */
.user-dash-main { flex: 1; min-width: 0; }
.dash-topbar    { margin-bottom: 20px; }
.dash-title     { font-size: 22px; font-weight: 800; color: #1A1A18; margin-bottom: 4px; }
.dash-sub       { font-size: 13px; color: #6b7280; }

/* ── Stat cards ── */
.dash-stats-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px; margin-bottom: 28px;
}
.dash-stat-card {
  background: #fff; border: 1px solid #e5e7eb;
  border-radius: 14px; padding: 16px;
  display: flex; align-items: center; gap: 14px;
  box-shadow: 0 2px 8px rgba(0,0,0,.04);
  position: relative; overflow: hidden;
}
.dash-stat-card::before {
  content: ''; position: absolute; top: 0; left: 0; right: 0;
  height: 3px; background: linear-gradient(90deg, #D85A30, #085041);
}
.dash-stat-icon {
  width: 44px; height: 44px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 20px; flex-shrink: 0;
}
.dash-stat-val   { font-size: 24px; font-weight: 800; color: #1A1A18; line-height: 1; }
.dash-stat-label { font-size: 11px; color: #6b7280; margin-top: 2px; }

/* ── Sections ── */
.dash-section       { margin-bottom: 32px; }
.dash-section-title {
  font-size: 16px; font-weight: 700; color: #1A1A18;
  margin-bottom: 16px;
  display: flex; align-items: center; gap: 8px;
}
.dash-section-title i { font-size: 20px; color: #D85A30; }

/* ── Module cards ── */
.dash-modules-grid {
  display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;
}
.dash-module-card {
  background: #fff; border: 1px solid #e5e7eb;
  border-radius: 14px; overflow: hidden; text-decoration: none;
  color: inherit; display: block;
  transition: transform .2s, box-shadow .2s;
}
.dash-module-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,.09); }
.dash-module-thumb { height: 100px; overflow: hidden; background: #f9fafb; }
.dash-module-thumb img { width: 100%; height: 100%; object-fit: cover; }
.dash-module-thumb-ph {
  width: 100%; height: 100%;
  display: flex; align-items: center; justify-content: center; font-size: 32px;
}
.dash-module-info    { padding: 12px 14px; }
.dash-module-cat     { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
.dash-module-info h3 { font-size: 13px; font-weight: 700; color: #1A1A18; margin: 4px 0 10px; line-height: 1.35; }
.dash-progress-wrap  { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
.dash-progress-bar   { flex: 1; height: 5px; background: #e5e7eb; border-radius: 3px; overflow: hidden; }
.dash-progress-fill  { height: 100%; background: linear-gradient(90deg, #D85A30, #085041); border-radius: 3px; }
.dash-pct            { font-size: 11px; font-weight: 700; color: #C04A22; flex-shrink: 0; }
.dash-module-meta    { display: flex; justify-content: space-between; align-items: center; font-size: 11px; color: #9ca3af; }
.dash-module-continue { font-size: 11px; font-weight: 700; color: #D85A30; display: flex; align-items: center; gap: 3px; }

/* ── Empty state ── */
.dash-empty { text-align: center; padding: 48px 24px; background: #fff; border: 1px dashed #fde68a; border-radius: 16px; }
.dash-empty i  { font-size: 48px; color: #fde68a; display: block; margin-bottom: 12px; }
.dash-empty h3 { font-size: 16px; font-weight: 700; color: #1A1A18; margin-bottom: 6px; }
.dash-empty p  { font-size: 13px; color: #6b7280; }
.dash-empty .btn-primary {
  display: inline-flex; align-items: center; gap: 6px;
  background: linear-gradient(135deg, #D85A30, #C04A22);
  color: #1A1A18; padding: 10px 22px; border-radius: 9px;
  font-weight: 700; font-size: 13px; text-decoration: none;
  margin-top: 16px;
}

/* ── Available courses ── */
.dash-available-grid {
  display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px;
}
.dash-available-card {
  background: #fff; border: 1px solid #e5e7eb;
  border-radius: 12px; overflow: hidden; text-decoration: none;
  color: inherit; display: block;
  transition: transform .2s, box-shadow .2s;
}
.dash-available-card:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0,0,0,.07); }
.dash-avail-thumb  { height: 80px; overflow: hidden; background: #f9fafb; }
.dash-avail-thumb img { width: 100%; height: 100%; object-fit: cover; }
.dash-avail-info   { padding: 10px 12px 12px; }
.dash-avail-info h3 { font-size: 12px; font-weight: 700; color: #1A1A18; margin: 4px 0; line-height: 1.35; }
.dash-enroll-btn {
  font-size: 11px; font-weight: 700;
  background: linear-gradient(135deg, #D85A30, #C04A22);
  color: #fff; padding: 4px 12px; border-radius: 6px;
}

/* ── Responsive ── */
@media (max-width: 1024px) {
  .dash-stats-row { grid-template-columns: repeat(2, 1fr); }
  .dash-modules-grid { grid-template-columns: repeat(2, 1fr); }
  .dash-available-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
  .user-dash-layout { flex-direction: column !important; padding: 16px; }
  .user-sidebar { width: 100% !important; position: static; }
  .dash-stats-row { grid-template-columns: repeat(2, 1fr); }
  .dash-modules-grid { grid-template-columns: 1fr 1fr; }
  .dash-available-grid { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 480px) {
  .dash-stats-row { grid-template-columns: 1fr 1fr; }
  .dash-modules-grid, .dash-available-grid { grid-template-columns: 1fr; }
}
</style>
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