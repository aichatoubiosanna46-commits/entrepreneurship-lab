<?php
// dashboard.php — Espace personnel de l'utilisateur connecté
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
reqConnecte();

$pdo  = getPDO();
$user = utilisateurCourant();

// Modules inscrits avec progression
$mesModules = $pdo->prepare(
    'SELECT m.*, c.nom as categorie, c.icone as cat_icone, c.couleur as cat_couleur,
            (SELECT COUNT(*) FROM sequences l
             JOIN modules mo ON mo.id = l.module_id
             WHERE mo.course_id = m.id AND l.actif = 1) as nb_lecons,
            i.created_at as inscrit_le
     FROM enrollments i
     JOIN courses m ON m.id = i.course_id
     JOIN categories c ON c.id = m.category_id
     WHERE i.user_id = ? AND i.statut = "actif"
     ORDER BY i.created_at DESC'
);
$mesModules->execute([$_SESSION['user_id']]);
$mesModules = $mesModules->fetchAll();

// Tous les cours disponibles (non inscrits)
$autresModules = $pdo->prepare(
    'SELECT m.*, c.nom as categorie, c.icone as cat_icone, c.couleur as cat_couleur,
            (SELECT COUNT(*) FROM sequences l
             JOIN modules mo ON mo.id = l.module_id
             WHERE mo.course_id = m.id AND l.actif = 1) as nb_lecons
     FROM courses m
     JOIN categories c ON c.id = m.category_id
     WHERE m.actif = 1
       AND m.id NOT IN (SELECT course_id FROM enrollments WHERE user_id = ? AND statut = "actif")
     ORDER BY m.ordre ASC, m.created_at DESC'
);
$autresModules->execute([$_SESSION['user_id']]);
$autresModules = $autresModules->fetchAll();

$pageTitle = 'Mon espace';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mon espace  <?= SITE_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
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

/* ── Fix couleurs nav items ── */
.user-nav-item,
.user-nav-item i,
.user-sidebar-nav a,
.user-sidebar-nav a i {
  color: #6b7280 !important;
  text-decoration: none !important;
}
.user-nav-item:hover,
.user-nav-item:hover i {
  color: #D97706 !important;
  background: #FFFBEB !important;
}
.user-nav-item.active,
.user-nav-item.active i {
  color: #D97706 !important;
  background: #FEF3C7 !important;
  font-weight: 600 !important;
}

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

/* FORCE couleurs sidebar - override style.css */
aside.user-sidebar nav a.user-nav-item { color: #6b7280 !important; }
aside.user-sidebar nav a.user-nav-item * { color: #6b7280 !important; }
aside.user-sidebar nav a.user-nav-item:hover { color: #D97706 !important; background: #FFFBEB !important; }
aside.user-sidebar nav a.user-nav-item:hover * { color: #D97706 !important; }
aside.user-sidebar nav a.user-nav-item.active { color: #D97706 !important; background: #FEF3C7 !important; }
aside.user-sidebar nav a.user-nav-item.active * { color: #D97706 !important; }
</style>
</head>
<body class="user-dash-page">

<?php include __DIR__ . '/includes/header.php'; ?>

<?= flash() ?>

<div class="user-dash-layout">
  <!-- Sidebar utilisateur -->
  <aside class="user-sidebar">
    <div class="user-sidebar-profile">
      <div class="user-avatar-ring">
        <?php if ($user['avatar']): ?>
          <img src="<?= SITE_URL ?>/assets/uploads/<?= h($user['avatar']) ?>" alt="Avatar">
        <?php else: ?>
          <div class="user-avatar-placeholder"><?= mb_strtoupper(mb_substr($user['prenom'], 0, 1)) ?></div>
        <?php endif; ?>
      </div>
      <div class="user-sidebar-name"><?= h($user['prenom'].' '.$user['nom']) ?></div>
      <div class="user-sidebar-email"><?= h($user['email']) ?></div>
    </div>
    <nav class="user-sidebar-nav">
  <div onclick="location='<?= SITE_URL ?>/dashboard.php'" class="user-nav-item active" style="cursor:pointer;color:#D97706;background:#FEF3C7;border-left:3px solid #F59E0B">
    <i class="ti ti-layout-dashboard" style="color:#D97706"></i> Tableau de bord
  </div>
  <div onclick="location='<?= SITE_URL ?>/favorites.php'" class="user-nav-item" style="cursor:pointer;color:#6b7280">
    <i class="ti ti-heart" style="color:#6b7280"></i> Mes favoris
  </div>
  <div onclick="location='<?= SITE_URL ?>/notifications.php'" class="user-nav-item" style="cursor:pointer;color:#6b7280">
    <i class="ti ti-bell" style="color:#6b7280"></i> Notifications
  </div>
  <div onclick="location='<?= SITE_URL ?>/resources.php'" class="user-nav-item" style="cursor:pointer;color:#6b7280">
    <i class="ti ti-library" style="color:#6b7280"></i> Bibliothèque
  </div>
  <div onclick="location='<?= SITE_URL ?>/payment.php'" class="user-nav-item" style="cursor:pointer;color:#6b7280">
    <i class="ti ti-credit-card" style="color:#6b7280"></i> Abonnement
  </div>
  <div onclick="location='<?= SITE_URL ?>/search.php'" class="user-nav-item" style="cursor:pointer;color:#6b7280">
    <i class="ti ti-book" style="color:#6b7280"></i> Toutes les formations
  </div>
  <div onclick="location='<?= SITE_URL ?>/profil.php'" class="user-nav-item" style="cursor:pointer;color:#6b7280">
    <i class="ti ti-user" style="color:#6b7280"></i> Mon profil
  </div>
</nav>
    <div class="user-sidebar-footer">
      <a href="<?= SITE_URL ?>/logout.php" class="user-nav-item" style="color:#F0997B">
        <i class="ti ti-logout"></i> Déconnexion
      </a>
    </div>
  </aside>

  <!-- Contenu principal -->
  <main class="user-dash-main">

    <!-- Bienvenue + stats -->
    <div class="dash-topbar">
      <div>
        <h1 class="dash-title">Mon tableau de bord</h1>
        <p class="dash-sub">Bonjour <?= h($user['prenom']) ?>, bienvenue dans ton espace d'apprentissage.</p>
      </div>
      <?php if (estInstructeur()): ?>
      <a href="<?= SITE_URL ?>/admin/courses.php" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#534AB7;color:#fff;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none">
        <i class="ti ti-school"></i> Espace instructeur
      </a>
      <?php endif; ?>
    </div>

    <?php
    $completedCount = 0;
    foreach ($mesModules as $mm) {
        if (progressionCours($_SESSION['user_id'], $mm['id']) === 100) $completedCount++;
    }
    $certStmt = $pdo->prepare('SELECT COUNT(*) FROM certificates WHERE user_id = ?');
    $certStmt->execute([$_SESSION['user_id']]);
    $nbCerts = (int)$certStmt->fetchColumn();
    $quizStmt = $pdo->prepare('SELECT COUNT(*) FROM quiz_results WHERE user_id = ? AND reussi = 1');
    $quizStmt->execute([$_SESSION['user_id']]);
    $nbQuizReussis = (int)$quizStmt->fetchColumn();
    ?>

    <!-- ── DÉFI DE LA SEMAINE ── -->
    <?php
    $defiSemaine = null;
    try {
        $defiStmt = $pdo->prepare(
            "SELECT * FROM challenges WHERE actif=1 AND semaine <= CURDATE() ORDER BY semaine DESC LIMIT 1"
        );
        $defiStmt->execute();
        $defiSemaine = $defiStmt->fetch();
    } catch (Exception $e) {}
    ?>
    <?php if ($defiSemaine): ?>
    <div style="background:linear-gradient(135deg,#1C1917,#292524);border-radius:16px;padding:24px;margin-bottom:28px;display:flex;align-items:flex-start;gap:18px">
      <div style="width:48px;height:48px;border-radius:12px;background:#F59E0B;display:flex;align-items:center;justify-content:center;flex-shrink:0">
        <i class="ti ti-trophy" style="font-size:22px;color:#1C1917"></i>
      </div>
      <div style="flex:1">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#F59E0B;margin-bottom:6px">🏆 Défi de la semaine</div>
        <div style="font-size:16px;font-weight:700;color:#fff;margin-bottom:6px"><?= h($defiSemaine['titre']) ?></div>
        <div style="font-size:13px;color:rgba(255,255,255,.6);line-height:1.6;margin-bottom:12px"><?= h(substr($defiSemaine['description'],0,150)) ?>...</div>
        <div style="display:flex;align-items:center;gap:12px">
          <a href="<?= SITE_URL ?>/forum.php" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#F59E0B;color:#1C1917;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none">
            <i class="ti ti-send"></i> Soumettre ma réponse
          </a>
          <span style="font-size:12px;color:#F59E0B"><i class="ti ti-star"></i> +<?= $defiSemaine['xp_reward'] ?> XP</span>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <div class="dash-stats-row">
      <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background:var(--amber-light);color:var(--amber)">
          <i class="ti ti-book"></i>
        </div>
        <div>
          <div class="dash-stat-val"><?= count($mesModules) ?></div>
          <div class="dash-stat-label">Formation<?= count($mesModules) != 1 ? 's' : '' ?> en cours</div>
        </div>
      </div>
      <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background:#EAF3DE;color:#3B6D11">
          <i class="ti ti-check-circle"></i>
        </div>
        <div>
          <div class="dash-stat-val"><?= $completedCount ?></div>
          <div class="dash-stat-label">Terminée<?= $completedCount != 1 ? 's' : '' ?></div>
        </div>
      </div>
      <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background:#FEF3C7;color:#D97706">
          <i class="ti ti-certificate"></i>
        </div>
        <div>
          <div class="dash-stat-val"><?= $nbCerts ?></div>
          <div class="dash-stat-label">Certificat<?= $nbCerts != 1 ? 's' : '' ?></div>
        </div>
      </div>
      <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background:#FEF2F2;color:#EF4444">
          <i class="ti ti-help-circle"></i>
        </div>
        <div>
          <div class="dash-stat-val"><?= $nbQuizReussis ?></div>
          <div class="dash-stat-label">Quiz réussi<?= $nbQuizReussis != 1 ? 's' : '' ?></div>
        </div>
      </div>
    </div>

    <!-- Certificats obtenus -->
    <?php if ($nbCerts > 0):
      $certsStmt = $pdo->prepare('SELECT cert.*, c.titre as course_titre FROM certificates cert JOIN courses c ON c.id = cert.course_id WHERE cert.user_id = ? ORDER BY cert.delivre_le DESC');
      $certsStmt->execute([$_SESSION['user_id']]);
      $certs = $certsStmt->fetchAll();
    ?>
    <section class="dash-section">
      <h2 class="dash-section-title"><i class="ti ti-certificate" style="color:#F59E0B"></i> Mes certificats</h2>
      <div style="display:flex;flex-wrap:wrap;gap:12px">
        <?php foreach ($certs as $cert): ?>
        <a href="<?= SITE_URL ?>/certificate.php?code=<?= h($cert['code_unique']) ?>"
           style="display:flex;align-items:center;gap:10px;padding:12px 16px;background:#FFFBEB;border:1px solid #F59E0B;border-radius:10px;text-decoration:none;color:inherit;transition:.15s"
           onmouseover="this.style.background='#FEF3C7'" onmouseout="this.style.background='#FFFBEB'">
          <i class="ti ti-certificate" style="font-size:24px;color:#F59E0B"></i>
          <div>
            <div style="font-weight:600;font-size:14px"><?= h($cert['course_titre']) ?></div>
            <div style="font-size:11px;color:var(--text-muted)">Obtenu le <?= date('d/m/Y', strtotime($cert['delivre_le'])) ?></div>
          </div>
          <i class="ti ti-download" style="margin-left:8px;color:#F59E0B"></i>
        </a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- Mes modules inscrits -->
    <?php if (!empty($mesModules)): ?>
    <section class="dash-section">
      <h2 class="dash-section-title">
        <i class="ti ti-books"></i> Mes modules
      </h2>
      <div class="dash-modules-grid">
        <?php foreach ($mesModules as $m): ?>
        <?php $pct = progressionCours($_SESSION['user_id'], $m['id']); ?>
        <a href="<?= SITE_URL ?>/module.php?slug=<?= h($m['slug']) ?>" class="dash-module-card">
          <div class="dash-module-thumb">
            <?php if ($m['miniature']): ?>
              <img src="<?= SITE_URL ?>/assets/uploads/<?= h($m['miniature']) ?>" alt="<?= h($m['titre']) ?>">
            <?php else: ?>
              <div class="dash-module-thumb-ph" style="background:<?= h($m['cat_couleur']) ?>22">
                <i class="ti <?= h($m['cat_icone']) ?>" style="color:<?= h($m['cat_couleur']) ?>"></i>
              </div>
            <?php endif; ?>
          </div>
          <div class="dash-module-info">
            <span class="dash-module-cat" style="color:<?= h($m['cat_couleur']) ?>"><?= h($m['categorie']) ?></span>
            <h3><?= h($m['titre']) ?></h3>
            <div class="dash-progress-wrap">
              <div class="dash-progress-bar"><div class="dash-progress-fill" style="width:<?= $pct ?>%"></div></div>
              <span class="dash-pct"><?= $pct ?>%</span>
            </div>
            <div class="dash-module-meta">
              <span><i class="ti ti-list"></i> <?= $m['nb_lecons'] ?> séquence<?= $m['nb_lecons'] != 1 ? 's' : '' ?></span>
              <span class="dash-module-continue">Continuer <i class="ti ti-arrow-right"></i></span>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php else: ?>
    <div class="dash-empty">
      <i class="ti ti-books"></i>
      <h3>Tu n'es encore inscrit à aucun module.</h3>
      <p>Explore nos formations et inscris-toi gratuitement !</p>
      <a href="<?= SITE_URL ?>/index.php#cours" class="btn-primary" style="margin-top:16px">
        Découvrir les modules
      </a>
    </div>
    <?php endif; ?>

    <!-- Modules disponibles -->
    <?php if (!empty($autresModules)): ?>
    <section class="dash-section">
      <h2 class="dash-section-title">
        <i class="ti ti-sparkles"></i> Modules disponibles
      </h2>
      <div class="dash-available-grid">
        <?php foreach ($autresModules as $m): ?>
        <a href="<?= SITE_URL ?>/module.php?slug=<?= h($m['slug']) ?>" class="dash-available-card">
          <div class="dash-avail-thumb">
            <?php if ($m['miniature']): ?>
              <img src="<?= SITE_URL ?>/assets/uploads/<?= h($m['miniature']) ?>" alt="">
            <?php else: ?>
              <div class="dash-module-thumb-ph" style="background:<?= h($m['cat_couleur']) ?>22">
                <i class="ti <?= h($m['cat_icone']) ?>" style="color:<?= h($m['cat_couleur']) ?>"></i>
              </div>
            <?php endif; ?>
          </div>
          <div class="dash-avail-info">
            <span class="dash-module-cat" style="color:<?= h($m['cat_couleur']) ?>"><?= h($m['categorie']) ?></span>
            <h3><?= h($m['titre']) ?></h3>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:8px">
              <span style="font-size:12px;color:var(--text-muted)"><i class="ti ti-list"></i> <?= $m['nb_lecons'] ?> séq.</span>
              <span class="dash-enroll-btn">S'inscrire</span>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

  </main>
</div>

<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>