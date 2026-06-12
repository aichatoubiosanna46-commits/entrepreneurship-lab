<?php
// includes/header.php — En-tête adaptatif : avant / après connexion
if (!defined('SITE_NAME')) {
    require_once __DIR__ . '/../config/database.php';
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

    <?php if (estConnecte()): ?>
    <!-- ===== HEADER CONNECTÉ ===== -->
    <div class="nav-links">
      <a href="<?= SITE_URL ?>/dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'nav-link-active' : '' ?>">
        <i class="ti ti-layout-dashboard"></i> Mon espace
      </a>
      <a href="<?= SITE_URL ?>/index.php#cours" class="nav-link">
        <i class="ti ti-book"></i> Modules
      </a>
    </div>
    <div class="nav-actions">
      <div class="nav-user-chip">
        <div class="nav-user-avatar"><?= mb_strtoupper(mb_substr($_SESSION['user_nom'] ?? 'U', 0, 1)) ?></div>
        <span class="nav-user-name"><?= h(explode(' ', $_SESSION['user_nom'] ?? '')[0]) ?></span>
        <i class="ti ti-chevron-down" style="font-size:13px;color:var(--text-muted)"></i>
        <div class="nav-user-dropdown">
          <a href="<?= SITE_URL ?>/dashboard.php"><i class="ti ti-layout-dashboard"></i> Tableau de bord</a>
          <a href="<?= SITE_URL ?>/profil.php"><i class="ti ti-user"></i> Mon profil</a>
          <div class="dropdown-divider"></div>
          <a href="<?= SITE_URL ?>/logout.php" style="color:#993C1D"><i class="ti ti-logout"></i> Déconnexion</a>
        </div>
      </div>
    </div>

    <?php else: ?>
    <!-- ===== HEADER NON-CONNECTÉ ===== -->
    <div class="nav-links">
      <a href="<?= SITE_URL ?>/index.php#cours" class="nav-link">Cours</a>
      <a href="<?= SITE_URL ?>/index.php#parcours" class="nav-link">Parcours</a>
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
    <?php if (estConnecte()): ?>
      <div style="padding:8px 0;font-weight:500;color:var(--amber)"><?= h($_SESSION['user_nom'] ?? '') ?></div>
      <a href="<?= SITE_URL ?>/dashboard.php">Mon espace</a>
      <a href="<?= SITE_URL ?>/index.php#cours">Modules</a>
      <a href="<?= SITE_URL ?>/profil.php">Mon profil</a>
      <a href="<?= SITE_URL ?>/logout.php" style="color:#993C1D">Déconnexion</a>
    <?php else: ?>
      <a href="<?= SITE_URL ?>/index.php#cours">Cours</a>
      <a href="<?= SITE_URL ?>/index.php#parcours">Parcours</a>
      <a href="<?= SITE_URL ?>/login.php">Se connecter</a>
      <a href="<?= SITE_URL ?>/register.php" class="btn-primary" style="display:inline-block;margin-top:8px">S'inscrire</a>
    <?php endif; ?>
  </div>
</nav>

<main>
<?= flash() ?>
