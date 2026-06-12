<?php
// admin/stats.php — Statistiques globales
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();
$pdo = getPDO();

// Revenus par mois (12 derniers mois)
$revenusParMois = $pdo->query(
    'SELECT DATE_FORMAT(created_at, "%Y-%m") as mois, SUM(montant) as total
     FROM payments WHERE statut = "valide"
     AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
     GROUP BY mois ORDER BY mois ASC'
)->fetchAll();

// Taux de complétion global
$totalSeq = (int)$pdo->query('SELECT COUNT(*) FROM sequences WHERE actif = 1')->fetchColumn();
$totalProgress = (int)$pdo->query('SELECT COUNT(*) FROM progress WHERE terminee = 1')->fetchColumn();

// Top cours par inscriptions
$topCourses = $pdo->query(
    'SELECT c.titre, COUNT(e.id) as nb_inscrits,
            (SELECT COUNT(*) FROM sequences s JOIN modules m ON m.id = s.module_id WHERE m.course_id = c.id AND s.actif = 1) as nb_seq
     FROM courses c LEFT JOIN enrollments e ON e.course_id = c.id
     GROUP BY c.id ORDER BY nb_inscrits DESC LIMIT 8'
)->fetchAll();

// Stats quiz
$quizStats = [
    'total'  => $pdo->query('SELECT COUNT(*) FROM quiz_results')->fetchColumn(),
    'reussi' => $pdo->query('SELECT COUNT(*) FROM quiz_results WHERE reussi = 1')->fetchColumn(),
    'score_moyen' => $pdo->query('SELECT ROUND(AVG(score),1) FROM quiz_results')->fetchColumn() ?: 0,
];

// Inscriptions par mois
$inscritsParMois = $pdo->query(
    'SELECT DATE_FORMAT(created_at, "%Y-%m") as mois, COUNT(*) as total
     FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
     GROUP BY mois ORDER BY mois ASC'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Statistiques — Admin <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/dashboard.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <h1 class="admin-page-title">Statistiques globales</h1>
  </div>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon" style="background:#EDE9FE;color:#6C47D4"><i class="ti ti-users"></i></div>
      <div><p class="stat-label">Apprenants</p><p class="stat-val"><?= $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() ?></p></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#EAF3DE;color:#3B6D11"><i class="ti ti-school"></i></div>
      <div><p class="stat-label">Formations</p><p class="stat-val"><?= $pdo->query('SELECT COUNT(*) FROM courses WHERE actif=1')->fetchColumn() ?></p></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#EEEDFE;color:#534AB7"><i class="ti ti-certificate"></i></div>
      <div><p class="stat-label">Certificats</p><p class="stat-val"><?= $pdo->query('SELECT COUNT(*) FROM certificates')->fetchColumn() ?></p></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#E1F5EE;color:#0F6E56"><i class="ti ti-help-circle"></i></div>
      <div>
        <p class="stat-label">Taux de réussite quiz</p>
        <p class="stat-val"><?= $quizStats['total'] > 0 ? round($quizStats['reussi'] / $quizStats['total'] * 100) : 0 ?>%</p>
      </div>
    </div>
  </div>

  <div class="admin-two-col">
    <!-- Revenus par mois -->
    <div class="admin-card">
      <div class="admin-card-header"><h2>Revenus mensuels (FCFA)</h2></div>
      <canvas id="revenusChart" height="200"></canvas>
    </div>

    <!-- Inscriptions par mois -->
    <div class="admin-card">
      <div class="admin-card-header"><h2>Nouveaux apprenants par mois</h2></div>
      <canvas id="inscritsChart" height="200"></canvas>
    </div>
  </div>

  <!-- Top cours -->
  <div class="admin-card">
    <div class="admin-card-header"><h2>Top formations par inscriptions</h2></div>
    <?php foreach ($topCourses as $c): ?>
    <div class="module-rank-row">
      <span class="module-rank-title"><?= h($c['titre']) ?></span>
      <div style="display:flex;align-items:center;gap:10px;min-width:160px">
        <div class="mini-bar-track">
          <div class="mini-bar-fill" style="width:<?= min(100, ($c['nb_inscrits'] / max(1, $topCourses[0]['nb_inscrits'])) * 100) ?>%"></div>
        </div>
        <span class="module-rank-count"><?= $c['nb_inscrits'] ?> inscrits</span>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($topCourses)): ?><p style="color:var(--text-muted);font-size:14px">Aucun cours</p><?php endif; ?>
  </div>

  <!-- Stats quiz -->
  <div class="admin-card">
    <div class="admin-card-header"><h2>Quiz</h2></div>
    <div class="stats-grid" style="margin-bottom:0">
      <div class="stat-card"><div class="stat-icon" style="background:#EEEDFE;color:#534AB7"><i class="ti ti-help-circle"></i></div><div><p class="stat-label">Tentatives</p><p class="stat-val"><?= $quizStats['total'] ?></p></div></div>
      <div class="stat-card"><div class="stat-icon" style="background:#EAF3DE;color:#3B6D11"><i class="ti ti-check"></i></div><div><p class="stat-label">Réussies</p><p class="stat-val"><?= $quizStats['reussi'] ?></p></div></div>
      <div class="stat-card"><div class="stat-icon" style="background:#EDE9FE;color:#6C47D4"><i class="ti ti-chart-bar"></i></div><div><p class="stat-label">Score moyen</p><p class="stat-val"><?= $quizStats['score_moyen'] ?>%</p></div></div>
    </div>
  </div>
</div>

<script>
const revenusData = <?= json_encode($revenusParMois) ?>;
const inscritsData = <?= json_encode($inscritsParMois) ?>;

new Chart(document.getElementById('revenusChart'), {
  type: 'bar',
  data: {
    labels: revenusData.map(r => r.mois),
    datasets: [{
      label: 'FCFA',
      data: revenusData.map(r => r.total),
      backgroundColor: '#6C47D4',
      borderRadius: 6,
    }]
  },
  options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});

new Chart(document.getElementById('inscritsChart'), {
  type: 'line',
  data: {
    labels: inscritsData.map(r => r.mois),
    datasets: [{
      label: 'Apprenants',
      data: inscritsData.map(r => r.total),
      borderColor: '#534AB7',
      backgroundColor: '#534AB720',
      fill: true,
      tension: 0.4,
    }]
  },
  options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
</script>
</body>
</html>
