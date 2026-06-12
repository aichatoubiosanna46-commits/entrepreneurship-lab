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
$typeColors = ['info'=>'#534AB7','success'=>'#16a34a','warning'=>'#BA7517','error'=>'#dc2626'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications — <?= SITE_NAME ?></title>
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
