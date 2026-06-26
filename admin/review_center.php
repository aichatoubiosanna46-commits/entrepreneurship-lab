<?php
// admin/review_center.php — Centre de correction : toutes les soumissions d'activités
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();

$pdo = getPDO();

$filterCourse = (int)($_GET['course_id'] ?? 0);

$where  = ["sub.statut = 'soumis'"];
$params = [];
if ($filterCourse) {
    $where[]  = 'co.id = ?';
    $params[] = $filterCourse;
}
$whereSQL = 'WHERE ' . implode(' AND ', $where);

$countStmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM activity_submissions sub
     JOIN activities a ON a.id = sub.activity_id
     JOIN sequences s ON s.id = a.sequence_id
     JOIN modules mo ON mo.id = s.module_id
     JOIN courses co ON co.id = mo.course_id
     $whereSQL"
);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

[$offset, $pages, $pageCourante] = paginer($total, 20);

$stmt = $pdo->prepare(
    "SELECT sub.id, sub.activity_id, sub.created_at,
            u.nom, u.prenom, u.email,
            a.titre AS titre_devoir, a.note_max,
            s.titre AS sequence_titre,
            co.titre AS course_titre, co.id AS course_id
     FROM activity_submissions sub
     JOIN activities a ON a.id = sub.activity_id
     JOIN sequences s ON s.id = a.sequence_id
     JOIN modules mo ON mo.id = s.module_id
     JOIN courses co ON co.id = mo.course_id
     JOIN users u ON u.id = sub.user_id
     $whereSQL
     ORDER BY sub.created_at ASC
     LIMIT 20 OFFSET $offset"
);
$stmt->execute($params);
$submissions = $stmt->fetchAll();

$courses = $pdo->query('SELECT id, titre FROM courses WHERE actif = 1 ORDER BY titre')->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Review Center — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/dashboard.css">
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<div class="admin-content">
  <div class="admin-topbar">
    <div>
      <h1 class="admin-page-title">Review Center</h1>
      <p class="admin-page-sub"><?= $total ?> soumission<?= $total > 1 ? 's' : '' ?> en attente</p>
    </div>
  </div>

  <?= flash() ?>

  <div class="admin-card" style="margin-bottom:20px">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
      <select name="course_id">
        <option value="">Tous les cours</option>
        <?php foreach ($courses as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $filterCourse == $c['id'] ? 'selected' : '' ?>><?= h($c['titre']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn-primary btn-sm"><i class="ti ti-filter"></i> Filtrer</button>
      <?php if ($filterCourse): ?>
        <a href="review_center.php" class="btn-outline btn-sm">Réinitialiser</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="admin-card" style="padding:0;overflow:hidden">
    <table class="admin-table" style="margin:0">
      <thead>
        <tr><th>Étudiant</th><th>Activité</th><th>Cours</th><th>Soumis le</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php if (empty($submissions)): ?>
        <tr>
          <td colspan="5" style="text-align:center;padding:48px;color:var(--text-muted)">
            <i class="ti ti-circle-check" style="font-size:36px;display:block;margin-bottom:10px;color:#16a34a"></i>
            Aucune soumission en attente. Tout est à jour !
          </td>
        </tr>
        <?php else: ?>
        <?php foreach ($submissions as $sub): ?>
        <tr>
          <td>
            <div class="user-cell">
              <div class="user-avatar"><?= strtoupper(mb_substr($sub['prenom'],0,1)) ?></div>
              <div>
                <div style="font-weight:500"><?= h($sub['prenom'].' '.$sub['nom']) ?></div>
                <div style="font-size:11px;color:var(--text-muted)"><?= h($sub['email']) ?></div>
              </div>
            </div>
          </td>
          <td>
            <div style="font-weight:500;font-size:13px"><?= h($sub['titre_devoir']) ?></div>
            <div style="font-size:11px;color:var(--text-muted)">
              <?= h($sub['sequence_titre']) ?><?= $sub['note_max'] ? ' · /'.$sub['note_max'] : '' ?>
            </div>
          </td>
          <td><span class="badge badge-neutral"><?= h($sub['course_titre']) ?></span></td>
          <td style="color:var(--text-muted);font-size:12px"><?= date('d/m/Y H:i', strtotime($sub['created_at'])) ?></td>
          <td>
            <a href="submissions.php?activity_id=<?= $sub['activity_id'] ?>" class="btn-primary btn-sm">
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

<script src="<?= SITE_URL ?>/assets/js/dashboard.js"></script>
</body>
</html>
