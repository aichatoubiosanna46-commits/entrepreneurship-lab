<?php
// admin/sequences.php — Séquences d'un module
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();

$pdo      = getPDO();
$moduleId = (int)($_GET['module_id'] ?? 0);
if (!$moduleId) { header('Location: '.SITE_URL.'/admin/courses.php'); exit; }

$module = $pdo->prepare('SELECT m.*, c.titre as course_titre, c.id as course_id FROM modules m JOIN courses c ON c.id = m.course_id WHERE m.id = ?');
$module->execute([$moduleId]);
$module = $module->fetch();
if (!$module) { http_response_code(404); die('Module introuvable.'); }

$sequences = $pdo->prepare('SELECT * FROM sequences WHERE module_id = ? ORDER BY ordre ASC, id ASC');
$sequences->execute([$moduleId]);
$sequences = $sequences->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Séquences — <?= h($module['titre']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/dashboard.css">
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<div class="admin-content">
  <div class="admin-topbar">
    <div>
      <h1 class="admin-page-title">Séquences du module</h1>
      <p class="admin-page-sub">
        <a href="<?= SITE_URL ?>/admin/courses.php">← Cours</a>
        &nbsp;/&nbsp;
        <a href="<?= SITE_URL ?>/admin/modules.php?course_id=<?= $module['course_id'] ?>"><?= h($module['course_titre']) ?></a>
        &nbsp;/&nbsp; <?= h($module['titre']) ?>
        &nbsp;·&nbsp; <?= count($sequences) ?> séquence(s)
      </p>
    </div>
    <a href="<?= SITE_URL ?>/admin/sequence_add.php?module_id=<?= $moduleId ?>" class="btn-primary btn-sm">
      <i class="ti ti-plus"></i> Nouvelle séquence
    </a>
  </div>

  <?= flash() ?>

  <?php if (empty($sequences)): ?>
  <div style="text-align:center;padding:56px;color:var(--text-muted)">
    <i class="ti ti-list-search" style="font-size:52px;display:block;margin-bottom:16px;opacity:.3"></i>
    <h3 style="margin-bottom:8px;font-weight:500;color:var(--text)">Aucune séquence pour ce module</h3>
    <p>Ajoutez des séquences pour que les apprenants puissent commencer.</p>
    <a href="<?= SITE_URL ?>/admin/sequence_add.php?module_id=<?= $moduleId ?>" class="btn-primary" style="margin-top:20px;display:inline-flex">
      <i class="ti ti-plus"></i> Créer la première séquence
    </a>
  </div>
  <?php else: ?>
  <div class="admin-card" style="padding:0;overflow:hidden">
    <table class="admin-table" style="margin:0">
      <thead>
        <tr>
          <th style="width:40px">#</th>
          <th>Séquence</th>
          <th>Contenu</th>
          <th>Durée</th>
          <th>Statut</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($sequences as $i => $s): ?>
        <tr>
          <td style="color:var(--text-muted);font-weight:600"><?= $i + 1 ?></td>
          <td>
            <div style="font-weight:500;font-size:13px"><?= h($s['titre']) ?></div>
          </td>
          <td>
            <div style="display:flex;gap:6px;flex-wrap:wrap">
              <?php if ($s['contenu']): ?>
                <span title="Texte" style="font-size:18px;color:#534AB7"><i class="ti ti-text-size"></i></span>
              <?php endif; ?>
              <?php if ($s['video_url']): ?>
                <span title="Vidéo" style="font-size:18px;color:#BA7517"><i class="ti ti-video"></i></span>
              <?php endif; ?>
              <?php if ($s['audio_url']): ?>
                <span title="Audio" style="font-size:18px;color:#3B6D11"><i class="ti ti-music"></i></span>
              <?php endif; ?>
              <?php if ($s['fichier_pdf']): ?>
                <span title="PDF" style="font-size:18px;color:#993C1D"><i class="ti ti-file-type-pdf"></i></span>
              <?php endif; ?>
              <?php if ($s['image_seq']): ?>
                <span title="Image" style="font-size:18px;color:#0F6E56"><i class="ti ti-photo"></i></span>
              <?php endif; ?>
            </div>
          </td>
          <td>
            <?= $s['duree_min'] ? $s['duree_min'].' min' : '<span style="color:var(--text-muted)">—</span>' ?>
          </td>
          <td>
            <?php if ($s['actif']): ?>
              <span class="badge badge-success"><i class="ti ti-eye" style="font-size:11px"></i> Actif</span>
            <?php else: ?>
              <span class="badge badge-neutral"><i class="ti ti-eye-off" style="font-size:11px"></i> Masqué</span>
            <?php endif; ?>
          </td>
          <td>
            <div style="display:flex;gap:6px">
              <a href="<?= SITE_URL ?>/admin/sequence_edit.php?id=<?= $s['id'] ?>&module_id=<?= $moduleId ?>" class="btn-icon" title="Modifier">
                <i class="ti ti-edit"></i>
              </a>
              <a href="<?= SITE_URL ?>/admin/sequence_delete.php?id=<?= $s['id'] ?>&module_id=<?= $moduleId ?>&csrf=<?= csrfToken() ?>"
                 class="btn-icon btn-icon-danger" title="Supprimer"
                 onclick="return confirm('Supprimer cette séquence ?')">
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
