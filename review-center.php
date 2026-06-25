<?php
// review-center.php — Espace coach : liste des devoirs à corriger
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

sendSecurityHeaders();
reqConnecte();
if (!estCoach()) {
    header('Location: ' . SITE_URL . '/dashboard.php?error=acces_refuse');
    exit;
}

$pdo = getPDO();

$filterCourse = (int)($_GET['course_id'] ?? 0);

$where  = ["asub.statut IN ('soumis','en_correction')"];
$params = [];
if ($filterCourse) {
    $where[]  = 'm.course_id = ?';
    $params[] = $filterCourse;
}
$whereSQL = 'WHERE ' . implode(' AND ', $where);

$countStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM assignment_submissions asub
     JOIN assignments a ON a.id = asub.assignment_id
     JOIN sequences s ON s.id = a.sequence_id
     JOIN modules m ON m.id = s.module_id
     $whereSQL"
);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

[$offset, $pages, $pageCourante] = paginer($total, 20);

$stmt = $pdo->prepare(
    "SELECT asub.*, a.titre AS assignment_titre, a.note_max, a.type,
            s.titre AS sequence_titre,
            co.titre AS course_titre, co.id AS course_id,
            u.nom, u.prenom, u.email
     FROM assignment_submissions asub
     JOIN assignments a ON a.id = asub.assignment_id
     JOIN sequences s ON s.id = a.sequence_id
     JOIN modules m ON m.id = s.module_id
     JOIN courses co ON co.id = m.course_id
     JOIN users u ON u.id = asub.user_id
     $whereSQL
     ORDER BY asub.created_at ASC
     LIMIT 20 OFFSET $offset"
);
$stmt->execute($params);
$submissions = $stmt->fetchAll();

$courses = $pdo->query('SELECT id, titre FROM courses WHERE actif = 1 ORDER BY titre')->fetchAll();

$pageTitle = 'Review Center';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Review Center — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css?v=2">
<style>
.rc-wrap { max-width: 1100px; margin: 40px auto; padding: 0 20px; }
.rc-card { background:#fff; border:1px solid #eee; border-radius:14px; padding:18px 20px; }
.rc-table { width:100%; border-collapse:collapse; }
.rc-table th, .rc-table td { padding:12px 10px; border-bottom:1px solid #f1f1f1; text-align:left; font-size:13px; }
.rc-badge { display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:700; padding:3px 10px; border-radius:100px; }
</style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="rc-wrap">
  <h1 style="font-family:'Syne',sans-serif;font-size:26px;margin-bottom:4px"><i class="ti ti-clipboard-check"></i> Review Center</h1>
  <p style="color:var(--text-muted);margin-bottom:20px"><?= $total ?> soumission<?= $total > 1 ? 's' : '' ?> en attente de correction</p>

  <?= flash() ?>

  <div class="rc-card" style="margin-bottom:20px">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
      <select name="course_id" style="padding:8px;border-radius:8px;border:1px solid #ddd">
        <option value="">Tous les cours</option>
        <?php foreach ($courses as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $filterCourse == $c['id'] ? 'selected' : '' ?>><?= h($c['titre']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn-primary btn-sm"><i class="ti ti-filter"></i> Filtrer</button>
      <?php if ($filterCourse): ?>
        <a href="review-center.php" class="btn-outline btn-sm">Réinitialiser</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="rc-card" style="padding:0;overflow:hidden">
    <table class="rc-table">
      <thead>
        <tr><th>Étudiant</th><th>Devoir</th><th>Cours</th><th>Statut</th><th>Soumis le</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php if (empty($submissions)): ?>
        <tr><td colspan="6" style="text-align:center;padding:48px;color:var(--text-muted)">
          <i class="ti ti-circle-check" style="font-size:36px;display:block;margin-bottom:10px;color:#16a34a"></i>
          Aucune soumission en attente. Tout est à jour !
        </td></tr>
        <?php else: ?>
        <?php foreach ($submissions as $sub): ?>
        <tr>
          <td>
            <div style="font-weight:500"><?= h($sub['prenom'] . ' ' . $sub['nom']) ?></div>
            <div style="font-size:11px;color:var(--text-muted)"><?= h($sub['email']) ?></div>
          </td>
          <td>
            <div style="font-weight:500"><?= h($sub['assignment_titre']) ?></div>
            <div style="font-size:11px;color:var(--text-muted)"><?= h($sub['sequence_titre']) ?> · /<?= $sub['note_max'] ?></div>
          </td>
          <td><span class="rc-badge" style="background:#f4f4f4;color:#666"><?= h($sub['course_titre']) ?></span></td>
          <td>
            <?php if ($sub['statut'] === 'en_correction'): ?>
              <span class="rc-badge" style="background:#FBE3DA;color:#C04A22">En correction</span>
            <?php else: ?>
              <span class="rc-badge" style="background:#f4f4f4;color:#666">Soumis</span>
            <?php endif; ?>
          </td>
          <td style="color:var(--text-muted);font-size:12px"><?= date('d/m/Y H:i', strtotime($sub['created_at'])) ?></td>
          <td>
            <a href="review-submission.php?id=<?= $sub['id'] ?>" class="btn-primary btn-sm">
              <i class="ti ti-pencil"></i> Corriger
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pages > 1): ?>
  <div style="display:flex;gap:6px;justify-content:center;margin-top:20px">
    <?php for ($p = 1; $p <= $pages; $p++): ?>
      <a href="?page=<?= $p ?><?= $filterCourse ? '&course_id='.$filterCourse : '' ?>"
         class="btn-outline <?= $p === $pageCourante ? 'active' : '' ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
