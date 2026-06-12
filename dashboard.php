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
<title>Mon espace — <?= SITE_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/home.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/user-dashboard.css">
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
      <a href="<?= SITE_URL ?>/dashboard.php" class="user-nav-item active">
        <i class="ti ti-layout-dashboard"></i> Tableau de bord
      </a>
      <a href="<?= SITE_URL ?>/index.php#cours" class="user-nav-item">
        <i class="ti ti-book"></i> Tous les modules
      </a>
      <a href="<?= SITE_URL ?>/profil.php" class="user-nav-item">
        <i class="ti ti-user"></i> Mon profil
      </a>
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
    </div>

    <div class="dash-stats-row">
      <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background:var(--amber-light);color:var(--amber)">
          <i class="ti ti-book"></i>
        </div>
        <div>
          <div class="dash-stat-val"><?= count($mesModules) ?></div>
          <div class="dash-stat-label">Module<?= count($mesModules) != 1 ? 's' : '' ?> en cours</div>
        </div>
      </div>
      <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background:#EAF3DE;color:#3B6D11">
          <i class="ti ti-check-circle"></i>
        </div>
        <div>
          <?php
          $completedCount = 0;
          foreach ($mesModules as $mm) {
            if (progressionCours($_SESSION['user_id'], $mm['id']) === 100) $completedCount++;
          }
          ?>
          <div class="dash-stat-val"><?= $completedCount ?></div>
          <div class="dash-stat-label">Terminé<?= $completedCount != 1 ? 's' : '' ?></div>
        </div>
      </div>
      <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background:#EEEDFE;color:#534AB7">
          <i class="ti ti-layout-grid"></i>
        </div>
        <div>
          <div class="dash-stat-val"><?= count($autresModules) ?></div>
          <div class="dash-stat-label">Disponible<?= count($autresModules) != 1 ? 's' : '' ?></div>
        </div>
      </div>
    </div>

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