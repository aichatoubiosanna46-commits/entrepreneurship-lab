<?php
// ============================================================
//  admin/index.php — Tableau de bord administration
//  Structure MOOC : courses > modules > sequences
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();

$pdo = getPDO();

$stats = [
    'users'       => $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'courses'     => $pdo->query('SELECT COUNT(*) FROM courses WHERE actif = 1')->fetchColumn(),
    'enrollments' => $pdo->query('SELECT COUNT(*) FROM enrollments')->fetchColumn(),
    'revenus'     => $pdo->query('SELECT COALESCE(SUM(montant),0) FROM payments WHERE statut = "valide"')->fetchColumn(),
];

$derniers = $pdo->query(
    'SELECT nom, prenom, email, ville, created_at FROM users
     ORDER BY created_at DESC LIMIT 6'
)->fetchAll();

$top_courses = $pdo->query(
    'SELECT c.titre, COUNT(e.id) as nb
     FROM courses c LEFT JOIN enrollments e ON e.course_id = c.id
     GROUP BY c.id ORDER BY nb DESC LIMIT 5'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard Admin — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/dashboard.css">
</head>
<body class="admin-layout">

<?php include __DIR__ . '/partials/sidebar.php'; ?>

<div class="admin-content">
  <div class="admin-topbar">
    <div>
      <h1 class="admin-page-title">Tableau de bord</h1>
      <p class="admin-page-sub">Bienvenue, <?= h($_SESSION['admin_nom']) ?></p>
    </div>
    <div style="display:flex;gap:10px;align-items:center">
      <span class="admin-badge-role">Admin</span>
      <a href="<?= SITE_URL ?>/admin/logout.php" class="btn-outline btn-sm">
        <i class="ti ti-logout" aria-hidden="true"></i> Déconnexion
      </a>
    </div>
  </div>

  <?= flash() ?>

  <!-- Métriques -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon" style="background:#EDE9FE;color:#6C47D4">
        <i class="ti ti-users" aria-hidden="true"></i>
      </div>
      <div>
        <p class="stat-label">Apprenants</p>
        <p class="stat-val"><?= number_format($stats['users']) ?></p>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#EAF3DE;color:#3B6D11">
        <i class="ti ti-school" aria-hidden="true"></i>
      </div>
      <div>
        <p class="stat-label">Cours actifs</p>
        <p class="stat-val"><?= number_format($stats['courses']) ?></p>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#EEEDFE;color:#534AB7">
        <i class="ti ti-clipboard-check" aria-hidden="true"></i>
      </div>
      <div>
        <p class="stat-label">Inscriptions</p>
        <p class="stat-val"><?= number_format($stats['enrollments']) ?></p>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#E1F5EE;color:#0F6E56">
        <i class="ti ti-coin" aria-hidden="true"></i>
      </div>
      <div>
        <p class="stat-label">Revenus (FCFA)</p>
        <p class="stat-val"><?= number_format((float)$stats['revenus'], 0, ',', ' ') ?></p>
      </div>
    </div>
  </div>

  <div class="admin-two-col">

    <!-- Derniers inscrits -->
    <div class="admin-card">
      <div class="admin-card-header">
        <h2>Derniers apprenants</h2>
        <a href="<?= SITE_URL ?>/admin/users.php" class="btn-link">Voir tout →</a>
      </div>
      <table class="admin-table">
        <thead>
          <tr><th>Nom</th><th>Email</th><th>Ville</th><th>Date</th></tr>
        </thead>
        <tbody>
          <?php foreach ($derniers as $u): ?>
          <tr>
            <td>
              <div class="user-cell">
                <div class="user-avatar"><?= strtoupper(mb_substr($u['prenom'],0,1).mb_substr($u['nom'],0,1)) ?></div>
                <?= h($u['prenom'].' '.$u['nom']) ?>
              </div>
            </td>
            <td><?= h($u['email']) ?></td>
            <td><?= h($u['ville'] ?: '—') ?></td>
            <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($derniers)): ?>
          <tr><td colspan="4" style="text-align:center;color:var(--text-muted)">Aucun apprenant pour l'instant</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Cours populaires -->
    <div class="admin-card">
      <div class="admin-card-header">
        <h2>Cours populaires</h2>
        <a href="<?= SITE_URL ?>/admin/courses.php" class="btn-link">Gérer →</a>
      </div>
      <?php foreach ($top_courses as $c): ?>
      <div class="module-rank-row">
        <span class="module-rank-title"><?= h($c['titre']) ?></span>
        <div style="display:flex;align-items:center;gap:10px;min-width:140px">
          <div class="mini-bar-track">
            <div class="mini-bar-fill" style="width:<?= min(100, ($c['nb'] / max(1, $stats['enrollments'])) * 100) ?>%"></div>
          </div>
          <span class="module-rank-count"><?= $c['nb'] ?></span>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (empty($top_courses)): ?>
        <p style="color:var(--text-muted);font-size:14px;padding:12px 0">Aucun cours créé</p>
      <?php endif; ?>

      <a href="<?= SITE_URL ?>/admin/course_add.php" class="btn-primary btn-sm" style="margin-top:16px;display:inline-flex;align-items:center;gap:6px">
        <i class="ti ti-plus" aria-hidden="true"></i> Ajouter un cours
      </a>
    </div>

  </div>

  <!-- Actions rapides -->
  <div class="admin-card" style="margin-top:0">
    <div class="admin-card-header"><h2>Actions rapides</h2></div>
    <div class="quick-actions">
      <a href="<?= SITE_URL ?>/admin/course_add.php" class="quick-action">
        <i class="ti ti-book-plus" aria-hidden="true"></i>
        <span>Nouveau cours</span>
      </a>
      <a href="<?= SITE_URL ?>/admin/users.php" class="quick-action">
        <i class="ti ti-users" aria-hidden="true"></i>
        <span>Utilisateurs</span>
      </a>
      <a href="<?= SITE_URL ?>/admin/slides.php" class="quick-action">
        <i class="ti ti-photo" aria-hidden="true"></i>
        <span>Slides accueil</span>
      </a>
      <a href="<?= SITE_URL ?>" target="_blank" class="quick-action">
        <i class="ti ti-external-link" aria-hidden="true"></i>
        <span>Voir le site</span>
      </a>
    </div>
  </div>

</div><!-- /.admin-content -->

<script src="<?= SITE_URL ?>/assets/js/dashboard.js"></script>
</body>
</html>
