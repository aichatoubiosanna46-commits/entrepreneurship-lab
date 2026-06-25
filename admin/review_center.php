<?php
// ============================================================
//  admin/review_center.php — Centre de correction des assignments
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

sendSecurityHeaders();
// Réservé à l'admin — les coachs utilisent l'espace dédié /review-center.php
if (!estAdmin()) {
    if (estCoach()) { header('Location: ' . SITE_URL . '/review-center.php'); exit; }
    header('Location: ' . SITE_URL . '/dashboard.php?error=acces_refuse');
    exit;
}

$pdo = getPDO();

// Filtres
$filterCourse  = (int)($_GET['course_id'] ?? 0);
$filterUser    = (int)($_GET['user_id'] ?? 0);

$where   = ["asub.statut IN ('soumis','en_correction')"];
$params  = [];

if ($filterCourse) {
    $where[]  = 'm.course_id = ?';
    $params[] = $filterCourse;
}
if ($filterUser) {
    $where[]  = 'asub.user_id = ?';
    $params[] = $filterUser;
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
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $pageTitle ?> — Admin <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
</head>
<body class="admin-layout">
<?php require_once __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">

  <div class="admin-topbar">
    <div>
      <h1 class="admin-page-title">Review Center</h1>
      <p class="admin-page-sub"><?= $total ?> soumission<?= $total > 1 ? 's' : '' ?> en attente</p>
    </div>
  </div>

  <?= flash() ?>

  <!-- Filtres -->
  <div class="admin-card" style="margin-bottom:20px">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
      <select name="course_id">
        <option value="">Tous les cours</option>
        <?php foreach ($courses as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $filterCourse == $c['id'] ? 'selected' : '' ?>>
            <?= h($c['titre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn-primary btn-sm">
        <i class="ti ti-filter"></i> Filtrer
      </button>
      <?php if ($filterCourse): ?>
        <a href="review_center.php" class="btn-outline btn-sm">Réinitialiser</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- Stats -->
  <div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card">
      <div class="stat-icon stat-icon-amber">
        <i class="ti ti-clock"></i>
      </div>
      <div>
        <div class="stat-label">En attente de correction</div>
        <div class="stat-val"><?= $total ?></div>
      </div>
    </div>
  </div>

  <!-- Table -->
  <div class="admin-card" style="padding:0;overflow:hidden">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Étudiant</th>
          <th>Devoir</th>
          <th>Cours</th>
          <th>Statut</th>
          <th>Soumis le</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($submissions)): ?>
          <tr>
            <td colspan="6" style="text-align:center;padding:48px;color:var(--text-muted)">
              <i class="ti ti-circle-check" style="font-size:36px;display:block;margin-bottom:10px;color:#16a34a"></i>
              Aucune soumission en attente. Tout est à jour !
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($submissions as $sub): ?>
            <tr>
              <td>
                <div class="user-cell">
                  <div class="user-avatar">
                    <?= strtoupper(substr($sub['prenom'], 0, 1)) ?>
                  </div>
                  <div>
                    <div style="font-weight:500"><?= h($sub['prenom'] . ' ' . $sub['nom']) ?></div>
                    <div style="font-size:11px;color:var(--text-muted)"><?= h($sub['email']) ?></div>
                  </div>
                </div>
              </td>
              <td>
                <div style="font-weight:500;font-size:13px"><?= h($sub['assignment_titre']) ?></div>
                <div style="font-size:11px;color:var(--text-muted)">
                  <?= h($sub['sequence_titre']) ?> · /<?= $sub['note_max'] ?>
                </div>
              </td>
              <td>
                <span class="badge badge-neutral"><?= h($sub['course_titre']) ?></span>
              </td>
              <td>
                <?php if ($sub['statut'] === 'en_correction'): ?>
                  <span class="badge badge-amber">En correction</span>
                <?php else: ?>
                  <span class="badge badge-neutral">Soumis</span>
                <?php endif; ?>
              </td>
              <td style="color:var(--text-muted);font-size:12px">
                <?= date('d/m/Y H:i', strtotime($sub['created_at'])) ?>
              </td>
              <td>
                <a href="review_submission.php?id=<?= $sub['id'] ?>" class="btn-primary btn-sm">
                  <i class="ti ti-pencil"></i> Corriger
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($pages > 1): ?>
    <div style="display:flex;gap:6px;justify-content:center;margin-top:20px">
      <?php for ($p = 1; $p <= $pages; $p++): ?>
        <a href="?page=<?= $p ?><?= $filterCourse ? '&course_id='.$filterCourse : '' ?>"
           class="btn-outline <?= $p === $pageCourante ? 'active' : '' ?>">
          <?= $p ?>
        </a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>

</div>

<script>
// Templates feedback prédéfinis
const feedbackTemplates = [
  {titre: 'Bon travail', contenu: 'Votre travail est de bonne qualité. Continuez dans cette direction.'},
  {titre: 'À améliorer', contenu: 'Votre livrable nécessite des améliorations. Veuillez revoir les points suivants.'},
  {titre: 'Excellent', contenu: 'Excellent travail ! Votre analyse est pertinente et bien structurée.'},
  {titre: 'Incomplet', contenu: 'Votre livrable est incomplet. Merci de soumettre à nouveau avec tous les éléments requis.'},
];

function insertTemplate(idx) {
  const t = feedbackTemplates[idx];
  const fb = document.querySelector('textarea[name="feedback"]');
  if (fb) fb.value = t.contenu;
}
</script>

</body>
</html>