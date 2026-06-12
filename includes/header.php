<?php
// includes/header.php — En-tête avec notifications, recherche, favoris
if (!defined('SITE_NAME')) {
    require_once __DIR__ . '/../config/database.php';
}

// Nombre de notifications non lues
$nbNotifs = 0;
if (estConnecte()) {
    try {
        $pdo = getPDO();
        $nStmt = $pdo->prepare('SELECT COUNT(*) FROM user_notifications WHERE user_id = ? AND lu = 0');
        $nStmt->execute([$_SESSION['user_id']]);
        $nbNotifs = (int)$nStmt->fetchColumn();
    } catch (Exception $e) { $nbNotifs = 0; }
}
?>
<nav class="navbar <?= estConnecte() ? 'navbar-connected' : 'navbar-public' ?>">
  <div class="nav-container">

    <a href="<?= SITE_URL ?>/index.php" class="nav-logo">
      <div class="logo-mark">E</div>
      <div class="logo-text">
        <span class="logo-name"><?= SITE_NAME ?></span>
        <span class="logo-tagline">Apprendre · Créer · Réussir</span>
      </div>
    </a>

    <!-- Barre de recherche -->
    <form class="nav-search" action="<?= SITE_URL ?>/search.php" method="GET">
      <i class="ti ti-search nav-search-icon"></i>
      <input type="search" name="q" placeholder="Rechercher une formation..." class="nav-search-input"
             autocomplete="off" value="<?= h($_GET['q'] ?? '') ?>">
    </form>

    <?php if (estConnecte()): ?>
    <div class="nav-links">
      <a href="<?= SITE_URL ?>/dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'nav-link-active' : '' ?>">
        <i class="ti ti-layout-dashboard"></i> Mon espace
      </a>
      <a href="<?= SITE_URL ?>/search.php" class="nav-link">
        <i class="ti ti-book"></i> Formations
      </a>
      <a href="<?= SITE_URL ?>/resources.php" class="nav-link">
        <i class="ti ti-library"></i> Bibliothèque
      </a>
    </div>
    <div class="nav-actions">
      <!-- Favoris -->
      <a href="<?= SITE_URL ?>/favorites.php" class="nav-icon-btn" title="Mes favoris">
        <i class="ti ti-heart"></i>
      </a>
      <!-- Cloche notifications -->
      <a href="<?= SITE_URL ?>/notifications.php" class="nav-icon-btn nav-bell" title="Notifications">
        <i class="ti ti-bell"></i>
        <?php if ($nbNotifs > 0): ?>
          <span class="nav-notif-badge"><?= $nbNotifs > 9 ? '9+' : $nbNotifs ?></span>
        <?php endif; ?>
      </a>
      <!-- Avatar dropdown -->
      <div class="nav-user-chip">
        <div class="nav-user-avatar"><?= mb_strtoupper(mb_substr($_SESSION['user_nom'] ?? 'U', 0, 1)) ?></div>
        <span class="nav-user-name"><?= h(explode(' ', $_SESSION['user_nom'] ?? '')[0]) ?></span>
        <i class="ti ti-chevron-down" style="font-size:13px;color:var(--text-muted)"></i>
        <div class="nav-user-dropdown">
          <a href="<?= SITE_URL ?>/dashboard.php"><i class="ti ti-layout-dashboard"></i> Tableau de bord</a>
          <a href="<?= SITE_URL ?>/favorites.php"><i class="ti ti-heart"></i> Mes favoris</a>
          <a href="<?= SITE_URL ?>/payment.php"><i class="ti ti-credit-card"></i> Abonnement</a>
          <a href="<?= SITE_URL ?>/profil.php"><i class="ti ti-user"></i> Mon profil</a>
          <div class="dropdown-divider"></div>
          <a href="<?= SITE_URL ?>/logout.php" style="color:#993C1D"><i class="ti ti-logout"></i> Déconnexion</a>
        </div>
      </div>
    </div>

    <?php else: ?>
    <div class="nav-links">
      <a href="<?= SITE_URL ?>/index.php#cours" class="nav-link">Cours</a>
      <a href="<?= SITE_URL ?>/index.php#parcours" class="nav-link">Parcours</a>
      <a href="<?= SITE_URL ?>/resources.php" class="nav-link">Bibliothèque</a>
    </div>
    <div class="nav-actions">
      <a href="<?= SITE_URL ?>/login.php"    class="btn-outline">Se connecter</a>
      <a href="<?= SITE_URL ?>/register.php" class="btn-primary">S'inscrire gratuitement</a>
    </div>
    <?php endif; ?>

    <button class="nav-toggle" aria-label="Menu" onclick="document.querySelector('.nav-mobile').classList.toggle('open')">
      <i class="ti ti-menu-2"></i>
    </button>
  </div>

  <div class="nav-mobile">
    <form action="<?= SITE_URL ?>/search.php" method="GET" style="padding:8px 0">
      <input type="search" name="q" placeholder="Rechercher..." style="width:100%;padding:8px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:14px;box-sizing:border-box">
    </form>
    <?php if (estConnecte()): ?>
      <div style="padding:8px 0;font-weight:500;color:var(--amber)"><?= h($_SESSION['user_nom'] ?? '') ?></div>
      <a href="<?= SITE_URL ?>/dashboard.php">Mon espace</a>
      <a href="<?= SITE_URL ?>/search.php">Formations</a>
      <a href="<?= SITE_URL ?>/favorites.php">Favoris</a>
      <a href="<?= SITE_URL ?>/resources.php">Bibliothèque</a>
      <a href="<?= SITE_URL ?>/notifications.php">
        Notifications <?= $nbNotifs > 0 ? "<span style='background:#dc2626;color:#fff;border-radius:99px;padding:1px 6px;font-size:11px'>$nbNotifs</span>" : '' ?>
      </a>
      <a href="<?= SITE_URL ?>/payment.php">Abonnement</a>
      <a href="<?= SITE_URL ?>/profil.php">Mon profil</a>
      <a href="<?= SITE_URL ?>/logout.php" style="color:#993C1D">Déconnexion</a>
    <?php else: ?>
      <a href="<?= SITE_URL ?>/index.php#cours">Cours</a>
      <a href="<?= SITE_URL ?>/index.php#parcours">Parcours</a>
      <a href="<?= SITE_URL ?>/resources.php">Bibliothèque</a>
      <a href="<?= SITE_URL ?>/login.php">Se connecter</a>
      <a href="<?= SITE_URL ?>/register.php" class="btn-primary" style="display:inline-block;margin-top:8px">S'inscrire</a>
    <?php endif; ?>
  </div>
</nav>

<style>
.nav-search { display:flex;align-items:center;gap:6px;background:var(--surface-alt,#f9fafb);border:1px solid var(--border,#e5e7eb);border-radius:24px;padding:6px 14px;flex:1;max-width:320px;margin:0 16px; }
.nav-search-icon { color:var(--text-muted,#6b7280);font-size:16px;flex-shrink:0; }
.nav-search-input { border:none;background:transparent;font-size:13px;width:100%;outline:none; }
.nav-icon-btn { width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--text,#111);text-decoration:none;transition:.15s;position:relative;font-size:18px; }
.nav-icon-btn:hover { background:var(--surface-alt,#f9fafb); }
.nav-bell { position:relative; }
.nav-notif-badge { position:absolute;top:4px;right:4px;background:#dc2626;color:#fff;font-size:9px;font-weight:800;border-radius:99px;padding:1px 4px;min-width:14px;text-align:center;line-height:1.4; }
@media(max-width:860px){ .nav-search{display:none;} }
</style>

<main>
<?= flash() ?>
