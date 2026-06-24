<?php
// coaching.php — Mes sessions de coaching (vue étudiant)
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
reqConnecte();

$pdo    = getPDO();
$userId = $_SESSION['user_id'];

$sessions = $pdo->prepare(
    'SELECT cs.*, a.prenom as coach_prenom, a.nom as coach_nom
     FROM coaching_sessions cs
     LEFT JOIN admins a ON a.id = cs.formateur_id
     WHERE cs.user_id = ? ORDER BY cs.date_heure DESC'
);
$sessions->execute([$userId]);
$sessions = $sessions->fetchAll();

// Lien Calendly depuis settings
try { $calendlyUrl = $pdo->query("SELECT valeur FROM settings WHERE cle='calendly_url'")->fetchColumn(); } catch(Exception $e){$calendlyUrl='';}

$statusColors = ['planifie'=>'#D97706','confirme'=>'#16a34a','annule'=>'#dc2626','termine'=>'#6b7280'];
$statusLabels = ['planifie'=>'En attente','confirme'=>'Confirmé ✓','annule'=>'Annulé','termine'=>'Terminé'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Mes sessions coaching — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/user-dashboard.css">
<style>
:root{--amber:#F59E0B;--amber-light:#FEF3C7;--amber-dark:#D97706;--text-muted:#6b7280}
.user-dash-page{background:#FFFBEB}
.user-dash-layout{display:flex;max-width:1200px;margin:0 auto;padding:28px 20px;gap:24px;align-items:flex-start}
.user-sidebar{width:220px;flex-shrink:0;background:#fff;border:1px solid #e5e7eb;border-radius:16px;overflow:hidden;position:sticky;top:80px}
.user-sidebar-profile{padding:24px 16px 16px;text-align:center;background:linear-gradient(135deg,#1C1917,#292524)}
.user-avatar-ring{width:64px;height:64px;border-radius:50%;margin:0 auto 10px;border:3px solid #F59E0B;overflow:hidden;display:flex;align-items:center;justify-content:center}
.user-avatar-placeholder{width:100%;height:100%;background:linear-gradient(135deg,#F59E0B,#EF4444);color:#fff;font-size:22px;font-weight:800;display:flex;align-items:center;justify-content:center}
.user-sidebar-name{font-size:14px;font-weight:700;color:#FEF3C7}
.user-sidebar-nav{padding:8px 0}
.user-nav-item{display:flex;align-items:center;gap:10px;padding:10px 16px;font-size:13px;color:#6b7280;text-decoration:none;transition:.15s;border-left:3px solid transparent}
.user-nav-item i{font-size:17px;flex-shrink:0}
.user-nav-item:hover{background:#FFFBEB;color:#D97706}
.user-nav-item.active{background:#FEF3C7;color:#D97706;font-weight:600;border-left-color:#F59E0B}
.user-sidebar-footer{border-top:1px solid #f3f4f6;padding:6px 0}
.user-dash-main{flex:1;min-width:0}
.dash-title{font-size:22px;font-weight:800;color:#1C1917;margin-bottom:20px}
@media(max-width:768px){.user-dash-layout{flex-direction:column}.user-sidebar{width:100%;position:static}}
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
      <a href="<?= SITE_URL ?>/search.php" class="user-nav-item"><i class="ti ti-book"></i> Formations</a>
      <a href="<?= SITE_URL ?>/coaching.php" class="user-nav-item active"><i class="ti ti-video"></i> Mes sessions</a>
      <a href="<?= SITE_URL ?>/favorites.php" class="user-nav-item"><i class="ti ti-heart"></i> Favoris</a>
      <a href="<?= SITE_URL ?>/notifications.php" class="user-nav-item"><i class="ti ti-bell"></i> Notifications</a>
      <a href="<?= SITE_URL ?>/profil.php" class="user-nav-item"><i class="ti ti-user"></i> Mon profil</a>
    </nav>
    <div class="user-sidebar-footer">
      <a href="<?= SITE_URL ?>/logout.php" class="user-nav-item" style="color:#F0997B"><i class="ti ti-logout"></i> Déconnexion</a>
    </div>
  </aside>

  <main class="user-dash-main">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
      <h1 class="dash-title" style="margin:0">Mes sessions coaching 1:1</h1>
      <?php if ($calendlyUrl): ?>
      <a href="<?= h($calendlyUrl) ?>" target="_blank" style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:linear-gradient(135deg,#F59E0B,#D97706);color:#1C1917;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none">
        <i class="ti ti-calendar-plus"></i> Réserver une session
      </a>
      <?php endif; ?>
    </div>

    <?= flash() ?>

    <?php if (empty($sessions)): ?>
    <div style="text-align:center;padding:60px 24px;background:#fff;border:1px dashed #fde68a;border-radius:16px">
      <i class="ti ti-video-off" style="font-size:48px;color:#fde68a;display:block;margin-bottom:12px"></i>
      <h3 style="font-size:18px;font-weight:700;color:#1C1917;margin-bottom:8px">Aucune session planifiée</h3>
      <p style="font-size:13px;color:#6b7280;margin-bottom:20px">Réservez une session coaching 1:1 avec votre mentor via Calendly.</p>
      <?php if ($calendlyUrl): ?>
      <a href="<?= h($calendlyUrl) ?>" target="_blank" style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:#F59E0B;color:#1C1917;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none">
        <i class="ti ti-calendar-plus"></i> Réserver maintenant →
      </a>
      <?php endif; ?>
    </div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:14px">
      <?php foreach ($sessions as $s): ?>
      <?php $isPast = strtotime($s['date_heure']) < time(); ?>
      <div style="background:#fff;border:1px solid <?= $s['statut']==='confirme' ? '#86efac' : '#e5e7eb' ?>;border-radius:16px;padding:20px;display:flex;align-items:flex-start;gap:16px">
        <div style="width:52px;height:52px;border-radius:14px;background:<?= $s['statut']==='confirme' ? '#ECFDF5' : '#FEF3C7' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <i class="ti ti-video" style="font-size:22px;color:<?= $statusColors[$s['statut']] ?? '#D97706' ?>"></i>
        </div>
        <div style="flex:1;min-width:0">
          <div style="font-size:16px;font-weight:700;color:#1C1917;margin-bottom:4px"><?= h($s['titre']) ?></div>
          <div style="font-size:13px;color:#6b7280;margin-bottom:10px">
            Avec <?= h(($s['coach_prenom']??'').' '.($s['coach_nom']??'Votre mentor')) ?>
          </div>
          <div style="display:flex;gap:16px;flex-wrap:wrap;font-size:13px">
            <span style="display:flex;align-items:center;gap:5px;color:#374151">
              <i class="ti ti-calendar" style="color:#F59E0B"></i>
              <?= date('l d F Y', strtotime($s['date_heure'])) ?>
            </span>
            <span style="display:flex;align-items:center;gap:5px;color:#374151">
              <i class="ti ti-clock" style="color:#F59E0B"></i>
              <?= date('H:i', strtotime($s['date_heure'])) ?> · <?= $s['duree_minutes'] ?> min
            </span>
            <span style="padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700;background:<?= $s['statut']==='confirme'?'#ECFDF5':($s['statut']==='annule'?'#fef2f2':'#FEF3C7') ?>;color:<?= $statusColors[$s['statut']] ?? '#D97706' ?>">
              <?= $statusLabels[$s['statut']] ?>
            </span>
          </div>
        </div>
        <?php if ($s['lien_zoom'] && !$isPast && $s['statut']==='confirme'): ?>
        <a href="<?= h($s['lien_zoom']) ?>" target="_blank" style="display:inline-flex;align-items:center;gap:6px;padding:10px 16px;background:#2D8CFF;color:#fff;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;flex-shrink:0">
          <i class="ti ti-video"></i> Rejoindre
        </a>
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
