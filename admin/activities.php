<?php
// admin/activities.php — Activités (devoirs, auto-évaluation...) d'une séquence
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();

$pdo   = getPDO();
$seqId = (int)($_GET['sequence_id'] ?? 0);
if (!$seqId) { header('Location: '.SITE_URL.'/admin/courses.php'); exit; }

$seq = $pdo->prepare(
    'SELECT s.*, m.titre as module_titre, m.id as module_id, m.course_id, c.titre as course_titre
     FROM sequences s JOIN modules m ON m.id = s.module_id JOIN courses c ON c.id = m.course_id
     WHERE s.id = ?'
);
$seq->execute([$seqId]);
$seq = $seq->fetch();
if (!$seq) { http_response_code(404); die('Séquence introuvable.'); }

$activities = $pdo->prepare('SELECT * FROM activities WHERE sequence_id = ? ORDER BY id ASC');
$activities->execute([$seqId]);
$activities = $activities->fetchAll();

$labels = [
    'devoir' => 'Devoir noté', 'exercice' => 'Exercice', 'cas_pratique' => 'Cas pratique',
    'travail_pratique' => 'Travail pratique', 'auto_evaluation' => 'Auto-évaluation',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Activités — <?= h($seq['titre']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/dashboard.css">
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<div class="admin-content">
  <div class="admin-topbar">
    <div>
      <h1 class="admin-page-title">Activités de la séquence</h1>
      <p class="admin-page-sub">
        <a href="<?= SITE_URL ?>/admin/modules.php?course_id=<?= $seq['course_id'] ?>"><?= h($seq['course_titre']) ?></a>
        &nbsp;/&nbsp;
        <a href="<?= SITE_URL ?>/admin/sequences.php?module_id=<?= $seq['module_id'] ?>"><?= h($seq['module_titre']) ?></a>
        &nbsp;/&nbsp; <?= h($seq['titre']) ?>
      </p>
    </div>
    <a href="<?= SITE_URL ?>/admin/activity_add.php?sequence_id=<?= $seqId ?>" class="btn-primary btn-sm">
      <i class="ti ti-plus"></i> Nouvelle activité
    </a>
  </div>

  <?= flash() ?>

  <?php if (empty($activities)): ?>
  <div style="text-align:center;padding:56px;color:var(--text-muted)">
    <i class="ti ti-pencil-check" style="font-size:52px;display:block;margin-bottom:16px;opacity:.3"></i>
    <h3 style="margin-bottom:8px;font-weight:500;color:var(--text)">Aucune activité pour cette séquence</h3>
    <p>Devoir noté, exercice, ou question d'auto-évaluation.</p>
  </div>
  <?php else: ?>
  <div class="admin-card" style="padding:0;overflow:hidden">
    <table class="admin-table" style="margin:0">
      <thead>
        <tr><th>Titre</th><th>Type</th><th>Soumissions</th><th>Statut</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($activities as $a):
          $cnt = $pdo->prepare('SELECT COUNT(*) FROM activity_submissions WHERE activity_id = ?');
          $cnt->execute([$a['id']]);
          $nbSub = (int)$cnt->fetchColumn();
          $aCorriger = $pdo->prepare("SELECT COUNT(*) FROM activity_submissions WHERE activity_id = ? AND statut='soumis'");
          $aCorriger->execute([$a['id']]);
          $nbACorriger = (int)$aCorriger->fetchColumn();
        ?>
        <tr>
          <td><div style="font-weight:500;font-size:13px"><?= h($a['titre']) ?></div></td>
          <td><span class="badge badge-neutral"><?= h($labels[$a['type']] ?? $a['type']) ?></span></td>
          <td>
            <?= $nbSub ?> soumission<?= $nbSub != 1 ? 's' : '' ?>
            <?php if ($nbACorriger > 0): ?>
              <span class="badge" style="background:#FEF3C7;color:#92400E"><?= $nbACorriger ?> à corriger</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($a['actif']): ?>
              <span class="badge badge-success"><i class="ti ti-eye" style="font-size:11px"></i> Actif</span>
            <?php else: ?>
              <span class="badge badge-neutral"><i class="ti ti-eye-off" style="font-size:11px"></i> Masqué</span>
            <?php endif; ?>
          </td>
          <td>
            <div style="display:flex;gap:6px">
              <a href="<?= SITE_URL ?>/admin/submissions.php?activity_id=<?= $a['id'] ?>" class="btn-icon" title="Soumissions / corriger">
                <i class="ti ti-checklist"></i>
              </a>
              <a href="<?= SITE_URL ?>/admin/activity_add.php?sequence_id=<?= $seqId ?>&id=<?= $a['id'] ?>" class="btn-icon" title="Modifier">
                <i class="ti ti-edit"></i>
              </a>
              <a href="<?= SITE_URL ?>/admin/activity_delete.php?id=<?= $a['id'] ?>&sequence_id=<?= $seqId ?>&csrf=<?= csrfToken() ?>"
                 class="btn-icon btn-icon-danger" title="Supprimer" onclick="return confirm('Supprimer cette activité ?')">
                <i class="ti ti-trash"></i>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<script src="<?= SITE_URL ?>/assets/js/dashboard.js"></script>
</body>
</html>
