<?php
// admin/modules.php — Modules d'un cours
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();

$pdo      = getPDO();
$courseId = (int)($_GET['course_id'] ?? 0);
if (!$courseId) { header('Location: '.SITE_URL.'/admin/courses.php'); exit; }

$course = $pdo->prepare('SELECT * FROM courses WHERE id = ?');
$course->execute([$courseId]);
$course = $course->fetch();
if (!$course) { http_response_code(404); die('Cours introuvable.'); }

$modules = $pdo->prepare(
    'SELECT m.*,
            (SELECT COUNT(*) FROM sequences s WHERE s.module_id = m.id AND s.actif = 1) as nb_sequences
     FROM modules m
     WHERE m.course_id = ?
     ORDER BY m.ordre ASC, m.id ASC'
);
$modules->execute([$courseId]);
$modules = $modules->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Modules — <?= h($course['titre']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/dashboard.css">
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<div class="admin-content">
  <div class="admin-topbar">
    <div>
      <h1 class="admin-page-title">Modules du cours</h1>
      <p class="admin-page-sub">
        <a href="<?= SITE_URL ?>/admin/courses.php">← Cours</a>
        &nbsp;/&nbsp; <?= h($course['titre']) ?>
        &nbsp;·&nbsp; <?= count($modules) ?> module(s)
        <?php
        $tarifLabels = ['decouverte'=>'🆓 Découverte','essentiel'=>'⭐ Essentiel','business_plan'=>'📊 Business Plan','lancement'=>'🚀 Lancement'];
        $tLabel = $tarifLabels[$course['tarif'] ?? 'decouverte'] ?? '—';
        ?>
        &nbsp;·&nbsp; <strong style="color:var(--amber)"><?= $tLabel ?></strong>
      </p>
    </div>
    <a href="<?= SITE_URL ?>/admin/module_add.php?course_id=<?= $courseId ?>" class="btn-primary btn-sm">
      <i class="ti ti-plus"></i> Nouveau module
    </a>
  </div>

  <?= flash() ?>

  <?php if (empty($modules)): ?>
  <div style="text-align:center;padding:56px;color:var(--text-muted)">
    <i class="ti ti-layout-list" style="font-size:52px;display:block;margin-bottom:16px;opacity:.3"></i>
    <h3 style="margin-bottom:8px;font-weight:500;color:var(--text)">Aucun module pour ce cours</h3>
    <p>Les modules regroupent les séquences pédagogiques.</p>
    <a href="<?= SITE_URL ?>/admin/module_add.php?course_id=<?= $courseId ?>" class="btn-primary" style="margin-top:20px;display:inline-flex">
      <i class="ti ti-plus"></i> Créer le premier module
    </a>
  </div>
  <?php else: ?>
  <div class="admin-card" style="padding:0;overflow:hidden">
    <table class="admin-table" style="margin:0">
      <thead>
        <tr>
          <th style="width:40px">#</th>
          <th>Module</th>
          <th>Séquences</th>
          <th>Statut</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($modules as $i => $m): ?>
        <tr>
          <td style="color:var(--text-muted);font-weight:600"><?= $i + 1 ?></td>
          <td>
            <div style="font-weight:500;font-size:13px"><?= h($m['titre']) ?></div>
            <?php if ($m['description']): ?>
            <div style="font-size:12px;color:var(--text-muted);margin-top:2px">
              <?= h(mb_substr($m['description'], 0, 80)) ?>...
            </div>
            <?php endif; ?>
          </td>
          <td>
            <a href="<?= SITE_URL ?>/admin/sequences.php?module_id=<?= $m['id'] ?>"
               style="font-weight:600;color:var(--amber)">
              <?= $m['nb_sequences'] ?> séq.
            </a>
          </td>
          <td>
            <?php if ($m['actif']): ?>
              <span class="badge badge-success"><i class="ti ti-eye" style="font-size:11px"></i> Actif</span>
            <?php else: ?>
              <span class="badge badge-neutral"><i class="ti ti-eye-off" style="font-size:11px"></i> Masqué</span>
            <?php endif; ?>
          </td>
          <td>
            <div style="display:flex;gap:6px">
              <a href="<?= SITE_URL ?>/admin/sequences.php?module_id=<?= $m['id'] ?>" class="btn-icon" title="Séquences" style="color:#534AB7">
                <i class="ti ti-list-numbers"></i>
              </a>
              <a href="<?= SITE_URL ?>/admin/module_edit.php?id=<?= $m['id'] ?>&course_id=<?= $courseId ?>" class="btn-icon" title="Modifier">
                <i class="ti ti-edit"></i>
              </a>
              <a href="<?= SITE_URL ?>/admin/module_delete.php?id=<?= $m['id'] ?>&course_id=<?= $courseId ?>&csrf=<?= csrfToken() ?>"
                 class="btn-icon btn-icon-danger"
                 onclick="return confirm('Supprimer ce module et toutes ses séquences ?')" title="Supprimer">
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
