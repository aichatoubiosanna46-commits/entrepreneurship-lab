<?php
// notifications.php — Centre de notifications
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
reqConnecte();

$pdo    = getPDO();
$userId = $_SESSION['user_id'];

// Marquer tout lu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'tout_lire') {
    verifierCSRF();
    $pdo->prepare('UPDATE user_notifications SET lu = 1 WHERE user_id = ?')->execute([$userId]);
    redirect(SITE_URL . '/notifications.php', 'Toutes les notifications marquées comme lues.', 'success');
}

$notifs = $pdo->prepare(
    'SELECT * FROM user_notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50'
);
$notifs->execute([$userId]);
$notifs = $notifs->fetchAll();

// Marquer comme lues en affichant
$pdo->prepare('UPDATE user_notifications SET lu = 1 WHERE user_id = ? AND lu = 0')->execute([$userId]);

$typeIcons = ['info'=>'ti-info-circle','success'=>'ti-check-circle','warning'=>'ti-alert-triangle','error'=>'ti-alert-circle'];
$typeColors = ['info'=>'#534AB7','success'=>'#16a34a','warning'=>'#6C47D4','error'=>'#dc2626'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/user-dashboard.css">
<style>
/* Variables Sunrise Africa */
:root {
  --amber:       #F59E0B;
  --amber-light: #FEF3C7;
  --amber-dark:  #D97706;
  --text-muted:  #6b7280;
}

/* ── Layout dashboard ── */
body.user-dash-page { background: #FFFBEB !important; }

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
  background: linear-gradient(135deg, #1C1917, #292524);
  border-bottom: 1px solid rgba(255,255,255,.08);
}
.user-avatar-ring {
  width: 64px; height: 64px; border-radius: 50%;
  margin: 0 auto 10px;
  border: 3px solid #F59E0B;
  overflow: hidden;
  display: flex; align-items: center; justify-content: center;
}
.user-avatar-ring img { width: 100%; height: 100%; object-fit: cover; }
.user-avatar-placeholder {
  width: 100%; height: 100%;
  background: linear-gradient(135deg, #F59E0B, #EF4444);
  color: #fff; font-size: 22px; font-weight: 800;
  display: flex; align-items: center; justify-content: center;
}
.user-sidebar-name  { font-size: 14px; font-weight: 700; color: #FEF3C7; }
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
.user-nav-item:hover { background: #FFFBEB; color: #D97706; }
.user-nav-item.active {
  background: #FEF3C7; color: #D97706;
  font-weight: 600; border-left-color: #F59E0B;
}
.user-sidebar-footer { border-top: 1px solid #f3f4f6; padding: 6px 0; }

/* ── Main ── */
.user-dash-main { flex: 1; min-width: 0; }
.dash-topbar    { margin-bottom: 20px; }
.dash-title     { font-size: 22px; font-weight: 800; color: #1C1917; margin-bottom: 4px; }
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
  height: 3px; background: linear-gradient(90deg, #F59E0B, #EF4444);
}
.dash-stat-icon {
  width: 44px; height: 44px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 20px; flex-shrink: 0;
}
.dash-stat-val   { font-size: 24px; font-weight: 800; color: #1C1917; line-height: 1; }
.dash-stat-label { font-size: 11px; color: #6b7280; margin-top: 2px; }

/* ── Sections ── */
.dash-section       { margin-bottom: 32px; }
.dash-section-title {
  font-size: 16px; font-weight: 700; color: #1C1917;
  margin-bottom: 16px;
  display: flex; align-items: center; gap: 8px;
}
.dash-section-title i { font-size: 20px; color: #F59E0B; }

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
.dash-module-info h3 { font-size: 13px; font-weight: 700; color: #1C1917; margin: 4px 0 10px; line-height: 1.35; }
.dash-progress-wrap  { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
.dash-progress-bar   { flex: 1; height: 5px; background: #e5e7eb; border-radius: 3px; overflow: hidden; }
.dash-progress-fill  { height: 100%; background: linear-gradient(90deg, #F59E0B, #EF4444); border-radius: 3px; }
.dash-pct            { font-size: 11px; font-weight: 700; color: #D97706; flex-shrink: 0; }
.dash-module-meta    { display: flex; justify-content: space-between; align-items: center; font-size: 11px; color: #9ca3af; }
.dash-module-continue { font-size: 11px; font-weight: 700; color: #F59E0B; display: flex; align-items: center; gap: 3px; }

/* ── Empty state ── */
.dash-empty { text-align: center; padding: 48px 24px; background: #fff; border: 1px dashed #fde68a; border-radius: 16px; }
.dash-empty i  { font-size: 48px; color: #fde68a; display: block; margin-bottom: 12px; }
.dash-empty h3 { font-size: 16px; font-weight: 700; color: #1C1917; margin-bottom: 6px; }
.dash-empty p  { font-size: 13px; color: #6b7280; }
.dash-empty .btn-primary {
  display: inline-flex; align-items: center; gap: 6px;
  background: linear-gradient(135deg, #F59E0B, #D97706);
  color: #1C1917; padding: 10px 22px; border-radius: 9px;
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
.dash-avail-info h3 { font-size: 12px; font-weight: 700; color: #1C1917; margin: 4px 0; line-height: 1.35; }
.dash-enroll-btn {
  font-size: 11px; font-weight: 700;
  background: linear-gradient(135deg, #F59E0B, #D97706);
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
      <a href="<?= SITE_URL ?>/favorites.php" class="user-nav-item"><i class="ti ti-heart"></i> Favoris</a>
      <a href="<?= SITE_URL ?>/notifications.php" class="user-nav-item active"><i class="ti ti-bell"></i> Notifications</a>
      <a href="<?= SITE_URL ?>/payment.php" class="user-nav-item"><i class="ti ti-credit-card"></i> Abonnement</a>
      <a href="<?= SITE_URL ?>/profil.php" class="user-nav-item"><i class="ti ti-user"></i> Mon profil</a>
    </nav>
    <div class="user-sidebar-footer">
      <a href="<?= SITE_URL ?>/logout.php" class="user-nav-item" style="color:#F0997B"><i class="ti ti-logout"></i> Déconnexion</a>
    </div>
  </aside>
  <main class="user-dash-main">
    <div class="dash-topbar" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
      <h1 class="dash-title"><i class="ti ti-bell"></i> Notifications</h1>
      <?php if (!empty($notifs)): ?>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="action" value="tout_lire">
        <button type="submit" style="padding:8px 16px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;background:#fff;cursor:pointer">
          <i class="ti ti-checks"></i> Tout marquer comme lu
        </button>
      </form>
      <?php endif; ?>
    </div>
    <?= flash() ?>

    <?php if (empty($notifs)): ?>
    <div class="dash-empty">
      <i class="ti ti-bell-off"></i>
      <h3>Aucune notification.</h3>
      <p>Vous recevrez ici les mises à jour importantes.</p>
    </div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:10px">
      <?php foreach ($notifs as $n):
        $icon = $typeIcons[$n['type']] ?? 'ti-info-circle';
        $color = $typeColors[$n['type']] ?? '#534AB7';
      ?>
      <div style="background:#fff;border:1px solid <?= $n['lu'] ? '#e5e7eb' : '#534AB7' ?>;border-radius:12px;padding:16px 20px;display:flex;align-items:flex-start;gap:14px;opacity:<?= $n['lu'] ? '.75' : '1' ?>">
        <div style="width:36px;height:36px;border-radius:50%;background:<?= $color ?>22;color:<?= $color ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:18px">
          <i class="ti <?= $icon ?>"></i>
        </div>
        <div style="flex:1;min-width:0">
          <div style="font-weight:600;font-size:14px;margin-bottom:3px"><?= h($n['titre']) ?></div>
          <?php if ($n['message']): ?>
            <div style="font-size:13px;color:var(--text-muted,#6b7280);line-height:1.5"><?= h($n['message']) ?></div>
          <?php endif; ?>
          <div style="font-size:11px;color:#9ca3af;margin-top:6px"><?= date('d/m/Y à H:i', strtotime($n['created_at'])) ?></div>
        </div>
        <?php if ($n['lien']): ?>
          <a href="<?= h($n['lien']) ?>" style="font-size:12px;color:#534AB7;white-space:nowrap;text-decoration:none;font-weight:600">Voir →</a>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </main>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>