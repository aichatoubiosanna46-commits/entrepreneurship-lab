<?php
// admin/module_edit.php — Modifier un module
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();

$pdo      = getPDO();
$id       = (int)($_GET['id']        ?? 0);
$courseId = (int)($_GET['course_id'] ?? 0);
if (!$id) { redirect(SITE_URL . '/admin/courses.php', 'Module introuvable.', 'error'); }

$module = $pdo->prepare('SELECT * FROM modules WHERE id = ?');
$module->execute([$id]);
$module = $module->fetch();
if (!$module) { redirect(SITE_URL . '/admin/courses.php', 'Module introuvable.', 'error'); }

$courseId = $courseId ?: $module['course_id'];
$course   = $pdo->prepare('SELECT * FROM courses WHERE id = ?');
$course->execute([$courseId]);
$course   = $course->fetch();

$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCSRF();
    $titre       = trim($_POST['titre']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $ordre       = (int)($_POST['ordre']      ?? 0);
    $actif       = isset($_POST['actif']) ? 1 : 0;

    if (!$titre) $erreurs[] = 'Le titre est requis.';

    if (empty($erreurs)) {
        $pdo->prepare(
            'UPDATE modules SET titre=?, description=?, ordre=?, actif=? WHERE id=?'
        )->execute([$titre, $description ?: null, $ordre, $actif, $id]);

        redirect(
            SITE_URL . '/admin/modules.php?course_id=' . $courseId,
            'Module mis à jour avec succès.',
            'success'
        );
    }
    $module = array_merge($module, ['titre'=>$titre,'description'=>$description,'ordre'=>$ordre,'actif'=>$actif]);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Modifier le module — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/dashboard.css">
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<div class="admin-content">
  <div class="admin-topbar">
    <div>
      <p class="admin-page-sub">
        <a href="<?= SITE_URL ?>/admin/courses.php">Cours</a> /
        <a href="<?= SITE_URL ?>/admin/modules.php?course_id=<?= $courseId ?>"><?= h($course['titre'] ?? 'Cours') ?></a> /
        Modifier le module
      </p>
      <h1 class="admin-page-title">Modifier le module</h1>
    </div>
    <a href="<?= SITE_URL ?>/admin/modules.php?course_id=<?= $courseId ?>" class="btn-outline">
      <i class="ti ti-arrow-left"></i> Retour
    </a>
  </div>

  <?= flash() ?>

  <?php if (!empty($erreurs)): ?>
  <div style="background:#FAECE7;border:1px solid #F0997B;color:#993C1D;padding:12px 16px;border-radius:8px;margin-bottom:16px">
    <?php foreach ($erreurs as $e): ?><div>• <?= h($e) ?></div><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <form method="POST" class="admin-card" style="max-width:640px">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

    <div class="form-group">
      <label>Titre du module *</label>
      <input type="text" name="titre" value="<?= h($module['titre']) ?>" required placeholder="Ex : Introduction à l'entrepreneuriat">
    </div>

    <div class="form-group">
      <label>Description <span style="font-weight:400;color:var(--text-muted)">(optionnel)</span></label>
      <textarea name="description" rows="4" placeholder="Objectifs, contenu abordé dans ce module…"><?= h($module['description'] ?? '') ?></textarea>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
      <div class="form-group">
        <label>Ordre d'affichage</label>
        <input type="number" name="ordre" value="<?= $module['ordre'] ?>" min="0">
      </div>
      <div class="form-group" style="justify-content:flex-end;padding-top:24px">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px">
          <input type="checkbox" name="actif" value="1" <?= $module['actif'] ? 'checked' : '' ?>>
          Module actif (visible par les apprenants)
        </label>
      </div>
    </div>

    <div style="display:flex;gap:10px;margin-top:8px">
      <button type="submit" class="btn-primary"><i class="ti ti-check"></i> Enregistrer</button>
      <a href="<?= SITE_URL ?>/admin/modules.php?course_id=<?= $courseId ?>" class="btn-outline">Annuler</a>
    </div>
  </form>
</div>
</body>
</html>
