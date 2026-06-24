<?php
// admin/analytics.php — Analytics pédagogiques et commerciales
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();
$pdo = getPDO();

// Période
$periode = $_GET['periode'] ?? '30';
$dateFrom = date('Y-m-d', strtotime("-{$periode} days"));

// KPIs globaux
try {
    $nbUsers    = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE actif = 1')->fetchColumn();
    $nbCours    = (int)$pdo->query('SELECT COUNT(*) FROM courses WHERE actif = 1')->fetchColumn();
    $nbEnroll   = (int)$pdo->query("SELECT COUNT(*) FROM enrollments WHERE statut='actif'")->fetchColumn();
    $nbCerts    = (int)$pdo->query('SELECT COUNT(*) FROM certificates')->fetchColumn();
    $revenus    = (float)$pdo->query("SELECT COALESCE(SUM(montant),0) FROM payments WHERE statut='confirme'")->fetchColumn();
    $revPeriode = (float)$pdo->query("SELECT COALESCE(SUM(montant),0) FROM payments WHERE statut='confirme' AND created_at >= '$dateFrom'")->fetchColumn();
} catch (Exception $e) { $nbUsers=$nbCours=$nbEnroll=$nbCerts=0; $revenus=$revPeriode=0; }

// Taux de complétion par cours
try {
    $completions = $pdo->query(
        'SELECT c.titre, c.id,
         COUNT(DISTINCT e.user_id) as inscrits,
         COUNT(DISTINCT cert.user_id) as certifies
         FROM courses c
         LEFT JOIN enrollments e ON e.course_id = c.id AND e.statut = "actif"
         LEFT JOIN certificates cert ON cert.course_id = c.id
         WHERE c.actif = 1
         GROUP BY c.id ORDER BY inscrits DESC LIMIT 10'
    )->fetchAll();
} catch (Exception $e) { $completions = []; }

// Revenus par cours
try {
    $revCours = $pdo->query(
        'SELECT c.titre, COALESCE(SUM(p.montant),0) as total, COUNT(p.id) as nb
         FROM courses c
         LEFT JOIN payments p ON p.plan = c.slug AND p.statut = "confirme"
         WHERE c.actif = 1 AND c.type = "payant"
         GROUP BY c.id ORDER BY total DESC LIMIT 5'
    )->fetchAll();
} catch (Exception $e) { $revCours = []; }

// Nouveaux inscrits par jour (30j)
try {
    $inscritsJour = $pdo->query(
        "SELECT DATE(created_at) as jour, COUNT(*) as nb
         FROM users WHERE created_at >= '$dateFrom'
         GROUP BY DATE(created_at) ORDER BY jour ASC"
    )->fetchAll();
} catch (Exception $e) { $inscritsJour = []; }

// Quiz : taux de réussite
try {
    $quizStats = $pdo->query(
        'SELECT q.titre,
         COUNT(qr.id) as nb_tentatives,
         SUM(CASE WHEN qr.reussi = 1 THEN 1 ELSE 0 END) as nb_reussis,
         AVG(qr.score) as score_moyen
         FROM quizzes q
         LEFT JOIN quiz_results qr ON qr.quiz_id = q.id
         GROUP BY q.id ORDER BY nb_tentatives DESC LIMIT 8'
    )->fetchAll();
} catch (Exception $e) { $quizStats = []; }

$currentPage = 'analytics.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Analytics — Admin Ariziki</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">

  <div class="admin-topbar">
    <div>
      <div class="admin-page-title">Analytics</div>
      <div class="admin-page-sub">Performance pédagogique et financière de la plateforme</div>
    </div>
    <div style="display:flex;gap:8px">
      <?php foreach ([7=>'7j',30=>'30j',90=>'90j'] as $p=>$lbl): ?>
      <a href="?periode=<?= $p ?>" class="btn-outline <?= $periode==$p?'active':'' ?>" style="<?= $periode==$p?'background:var(--primary);color:#fff;border-color:var(--primary)':'' ?>"><?= $lbl ?></a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- KPIs -->
  <div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card"><div class="stat-icon stat-icon-purple"><i class="ti ti-users"></i></div><div><div class="stat-label">Apprenants</div><div class="stat-val"><?= number_format($nbUsers) ?></div></div></div>
    <div class="stat-card"><div class="stat-icon stat-icon-green"><i class="ti ti-school"></i></div><div><div class="stat-label">Inscriptions actives</div><div class="stat-val"><?= number_format($nbEnroll) ?></div></div></div>
    <div class="stat-card"><div class="stat-icon stat-icon-blue"><i class="ti ti-certificate"></i></div><div><div class="stat-label">Certificats émis</div><div class="stat-val"><?= number_format($nbCerts) ?></div></div></div>
    <div class="stat-card"><div class="stat-icon stat-icon-amber"><i class="ti ti-credit-card"></i></div><div><div class="stat-label">Revenus (<?= $periode ?>j)</div><div class="stat-val"><?= number_format($revPeriode,0,',',' ') ?> F</div></div></div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">

    <!-- Graphique inscriptions -->
    <div class="admin-card">
      <div class="admin-card-title"><i class="ti ti-chart-line" style="color:var(--primary)"></i> Nouveaux inscrits (<?= $periode ?>j)</div>
      <canvas id="chartInscrits" height="200"></canvas>
    </div>

    <!-- Revenus par cours -->
    <div class="admin-card">
      <div class="admin-card-title"><i class="ti ti-coin" style="color:var(--primary)"></i> Revenus par formation</div>
      <?php if (empty($revCours)): ?>
      <p style="color:var(--text-muted);text-align:center;padding:32px">Aucune transaction enregistrée.</p>
      <?php else: ?>
      <?php foreach ($revCours as $r): ?>
      <div class="module-rank-row">
        <div class="module-rank-title"><?= h($r['titre']) ?></div>
        <div class="module-rank-count"><?= number_format($r['total'],0,',',' ') ?> F</div>
        <div class="mini-bar-track"><div class="mini-bar-fill" style="width:<?= $revCours[0]['total']>0 ? round($r['total']/$revCours[0]['total']*100) : 0 ?>%"></div></div>
      </div>
      <?php endforeach; ?>
      <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border);display:flex;justify-content:space-between;font-size:14px">
        <span style="color:var(--text-muted)">Total cumulé</span>
        <strong><?= number_format($revenus,0,',',' ') ?> FCFA</strong>
      </div>
      <?php endif; ?>
    </div>

  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

    <!-- Taux de complétion -->
    <div class="admin-card">
      <div class="admin-card-title"><i class="ti ti-chart-bar" style="color:var(--primary)"></i> Taux de complétion par cours</div>
      <table class="admin-table">
        <thead><tr><th>Cours</th><th>Inscrits</th><th>Certifiés</th><th>Taux</th></tr></thead>
        <tbody>
          <?php foreach ($completions as $c): ?>
          <?php $taux = $c['inscrits']>0 ? round($c['certifies']/$c['inscrits']*100) : 0; ?>
          <tr>
            <td style="font-size:12px"><?= h(substr($c['titre'],0,35)) ?>...</td>
            <td><?= $c['inscrits'] ?></td>
            <td><?= $c['certifies'] ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:8px">
                <div class="mini-bar-track" style="width:60px"><div class="mini-bar-fill" style="width:<?= $taux ?>%"></div></div>
                <span style="font-size:12px;font-weight:700;color:var(--primary)"><?= $taux ?>%</span>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Quiz stats -->
    <div class="admin-card">
      <div class="admin-card-title"><i class="ti ti-help-circle" style="color:var(--primary)"></i> Statistiques Quiz</div>
      <table class="admin-table">
        <thead><tr><th>Quiz</th><th>Tentatives</th><th>Réussite</th><th>Moy.</th></tr></thead>
        <tbody>
          <?php foreach ($quizStats as $q): ?>
          <?php $taux = $q['nb_tentatives']>0 ? round($q['nb_reussis']/$q['nb_tentatives']*100) : 0; ?>
          <tr>
            <td style="font-size:12px"><?= h(substr($q['titre'],0,30)) ?></td>
            <td><?= $q['nb_tentatives'] ?></td>
            <td><span class="badge <?= $taux>=60?'badge-success':'badge-amber' ?>"><?= $taux ?>%</span></td>
            <td><?= round($q['score_moyen'] ?? 0) ?>%</td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($quizStats)): ?>
          <tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:20px">Aucun quiz soumis.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>

</div>

<script>
const labels = <?= json_encode(array_map(fn($r)=>date('d/m',strtotime($r['jour'])), $inscritsJour)) ?>;
const data   = <?= json_encode(array_map(fn($r)=>(int)$r['nb'], $inscritsJour)) ?>;

new Chart(document.getElementById('chartInscrits'), {
  type: 'line',
  data: {
    labels,
    datasets: [{
      label: 'Nouveaux inscrits',
      data,
      borderColor: '#10B981',
      backgroundColor: 'rgba(16,185,129,.1)',
      tension: .4,
      fill: true,
      pointBackgroundColor: '#10B981',
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
      y: { beginAtZero: true, ticks: { stepSize: 1 } },
      x: { grid: { display: false } }
    }
  }
});
</script>
</body>
</html>
