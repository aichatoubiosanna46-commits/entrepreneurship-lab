<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();
$pdo = getPDO();

$courseId = (int)($_GET['course_id'] ?? 0);
$courses  = $pdo->query('SELECT id, titre FROM courses WHERE actif=1 ORDER BY titre')->fetchAll();

$reponses = [];
$avgQualite = 0;
$avgNPS = 0;

if ($courseId) {
    try {
        $stmt = $pdo->prepare(
            'SELECT sr.*, u.nom, u.prenom FROM satisfaction_reponses sr
             JOIN satisfaction_forms sf ON sf.id = sr.form_id
             LEFT JOIN users u ON u.id = sr.user_id
             WHERE sf.course_id = ? ORDER BY sr.created_at DESC'
        );
        $stmt->execute([$courseId]);
        $reponses = $stmt->fetchAll();

        if ($reponses) {
            $totalQ = $totalN = 0;
            foreach ($reponses as $r) {
                $data = json_decode($r['reponses'], true);
                $totalQ += (int)($data['qualite'] ?? 0);
                $totalN += (int)($data['nps'] ?? 0);
            }
            $avgQualite = round($totalQ / count($reponses), 1);
            $avgNPS     = round($totalN / count($reponses), 1);
        }
    } catch(Exception $e) {}
}
$currentPage = 'satisfaction_results.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Satisfaction — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <div><h1 class="admin-page-title">Formulaires de satisfaction</h1></div>
  </div>

  <div class="admin-card" style="margin-bottom:20px">
    <form method="GET" style="display:flex;gap:12px;align-items:center">
      <select name="course_id" style="padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;font-family:inherit;min-width:280px">
        <option value="">— Choisir un cours —</option>
        <?php foreach ($courses as $co): ?>
        <option value="<?= $co['id'] ?>" <?= $co['id']==$courseId?'selected':'' ?>><?= h($co['titre']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn-primary" style="padding:9px 18px">Voir les résultats</button>
    </form>
  </div>

  <?php if ($courseId && !empty($reponses)): ?>

  <!-- Stats globales -->
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px">
    <div class="admin-card" style="text-align:center">
      <div style="font-size:36px;font-weight:800;color:#D85A30"><?= count($reponses) ?></div>
      <div style="font-size:13px;color:#6b7280">Réponses reçues</div>
    </div>
    <div class="admin-card" style="text-align:center">
      <div style="font-size:36px;font-weight:800;color:#16a34a"><?= $avgQualite ?>/5</div>
      <div style="font-size:13px;color:#6b7280">Qualité moyenne</div>
      <div style="font-size:20px;margin-top:4px;color:#D85A30"><?= str_repeat('<i class="ti ti-star-filled"></i>', round($avgQualite)) ?></div>
    </div>
    <div class="admin-card" style="text-align:center">
      <div style="font-size:36px;font-weight:800;color:var(--primary-mid)"><?= $avgNPS ?>/10</div>
      <div style="font-size:13px;color:#6b7280">Score NPS moyen</div>
    </div>
  </div>

  <!-- Réponses texte -->
  <div class="admin-card">
    <div class="admin-card-title">Réponses détaillées</div>
    <table class="admin-table">
      <thead><tr><th>Étudiant</th><th>Qualité</th><th>NPS</th><th>Positif</th><th>À améliorer</th><th>Date</th></tr></thead>
      <tbody>
        <?php foreach ($reponses as $r): ?>
        <?php $data = json_decode($r['reponses'], true); ?>
        <tr>
          <td><?= $r['nom'] ? h($r['nom'].' '.$r['prenom']) : '<em style="color:#9ca3af">Anonyme</em>' ?></td>
          <td style="text-align:center;color:#D85A30"><?= str_repeat('<i class="ti ti-star-filled"></i>', (int)($data['qualite']??0)) ?></td>
          <td style="text-align:center"><strong><?= $data['nps'] ?? '—' ?>/10</strong></td>
          <td style="font-size:12px;max-width:200px"><?= h(mb_substr($data['positif']??'—',0,80)) ?></td>
          <td style="font-size:12px;max-width:200px"><?= h(mb_substr($data['amelioration']??'—',0,80)) ?></td>
          <td style="font-size:11px;color:#9ca3af"><?= date('d/m/Y', strtotime($r['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php elseif ($courseId): ?>
  <div class="admin-card" style="text-align:center;padding:40px;color:#9ca3af">
    <i class="ti ti-mood-smile" style="font-size:48px;display:block;margin-bottom:12px"></i>
    Aucune réponse de satisfaction pour ce cours.
  </div>
  <?php endif; ?>
</div>
</body>
</html>
