<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();
$pdo = getPDO();

$cohorte  = trim($_GET['cohorte'] ?? '');
$courseId = (int)($_GET['course_id'] ?? 0);

try {
    $cohortes = $pdo->query('SELECT DISTINCT cohorte FROM users WHERE cohorte IS NOT NULL AND cohorte != "" ORDER BY cohorte')->fetchAll(PDO::FETCH_COLUMN);
    $courses  = $pdo->query('SELECT id, titre FROM courses WHERE actif=1 ORDER BY titre')->fetchAll();
} catch(Exception $e) { $cohortes = []; $courses = []; }

$students = [];
$stats    = ['total' => 0, 'inscrits' => 0, 'completes' => 0, 'avg_progress' => 0];

if ($cohorte) {
    try {
        $stmt = $pdo->prepare(
            'SELECT u.id, u.nom, u.prenom, u.email, u.cohorte,
             COUNT(DISTINCT e.course_id) as nb_cours,
             u.xp_total, u.created_at
             FROM users u
             LEFT JOIN enrollments e ON e.user_id=u.id
             WHERE u.cohorte = ?
             GROUP BY u.id ORDER BY u.nom'
        );
        $stmt->execute([$cohorte]);
        $students = $stmt->fetchAll();
        $stats['total'] = count($students);
        $stats['inscrits'] = count(array_filter($students, fn($s) => $s['nb_cours'] > 0));
    } catch(Exception $e) {}
}

// Export CSV cohorte
if (isset($_GET['export']) && $cohorte) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="cohorte-' . $cohorte . '-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($out, ['Nom', 'Prénom', 'Email', 'Cohorte', 'Cours inscrits', 'XP Total', 'Date inscription'], ';');
    foreach ($students as $s) {
        fputcsv($out, [$s['nom'], $s['prenom'], $s['email'], $s['cohorte'], $s['nb_cours'], $s['xp_total'], date('d/m/Y', strtotime($s['created_at']))], ';');
    }
    fclose($out);
    exit;
}

$currentPage = 'rapport_cohorte.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Rapport cohorte — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <div><h1 class="admin-page-title">Rapport par cohorte</h1></div>
    <?php if ($cohorte): ?>
    <a href="?cohorte=<?= urlencode($cohorte) ?>&export=1" class="btn-outline">
      <i class="ti ti-download"></i> Exporter CSV
    </a>
    <?php endif; ?>
  </div>

  <!-- Filtres -->
  <div class="admin-card" style="margin-bottom:20px">
    <form method="GET" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
      <select name="cohorte" style="padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;font-family:inherit;min-width:200px">
        <option value="">— Choisir une cohorte —</option>
        <?php foreach ($cohortes as $co): ?>
        <option value="<?= h($co) ?>" <?= $co===$cohorte?'selected':'' ?>><?= h($co) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn-primary" style="padding:9px 18px">Voir le rapport</button>
      <?php if (empty($cohortes)): ?>
      <span style="font-size:12px;color:#9ca3af">Aucune cohorte — assignez une cohorte aux étudiants depuis leur profil admin</span>
      <?php endif; ?>
    </form>
  </div>

  <?php if ($cohorte && !empty($students)): ?>
  <!-- Stats -->
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px">
    <div class="admin-card" style="text-align:center">
      <div style="font-size:32px;font-weight:800;color:#F59E0B"><?= $stats['total'] ?></div>
      <div style="font-size:13px;color:#6b7280">Étudiants dans la cohorte</div>
    </div>
    <div class="admin-card" style="text-align:center">
      <div style="font-size:32px;font-weight:800;color:#16a34a"><?= $stats['inscrits'] ?></div>
      <div style="font-size:13px;color:#6b7280">Inscrits à au moins 1 cours</div>
    </div>
    <div class="admin-card" style="text-align:center">
      <div style="font-size:32px;font-weight:800;color:var(--primary-mid)"><?= round(array_sum(array_column($students,'xp_total'))/max(1,count($students))) ?></div>
      <div style="font-size:13px;color:#6b7280">XP moyen par étudiant</div>
    </div>
  </div>

  <!-- Liste étudiants -->
  <div class="admin-card">
    <div class="admin-card-title">Étudiants — Cohorte "<?= h($cohorte) ?>"</div>
    <table class="admin-table">
      <thead><tr><th>Étudiant</th><th>Email</th><th>Cours inscrits</th><th>XP Total</th><th>Inscrit le</th></tr></thead>
      <tbody>
        <?php foreach ($students as $s): ?>
        <tr>
          <td><strong><?= h($s['nom'].' '.$s['prenom']) ?></strong></td>
          <td><?= h($s['email']) ?></td>
          <td style="text-align:center"><span style="background:#FEF3C7;color:#D97706;padding:2px 8px;border-radius:12px;font-size:12px;font-weight:700"><?= $s['nb_cours'] ?></span></td>
          <td style="text-align:center"><strong><?= number_format($s['xp_total']) ?> XP</strong></td>
          <td style="font-size:12px;color:#9ca3af"><?= date('d/m/Y', strtotime($s['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php elseif ($cohorte): ?>
  <div class="admin-card" style="text-align:center;padding:40px;color:#9ca3af">
    Aucun étudiant dans cette cohorte.
  </div>
  <?php endif; ?>
</div>
</body>
</html>
