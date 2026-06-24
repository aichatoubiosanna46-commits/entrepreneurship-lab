<?php
// admin/bundle_edit.php — Modifier un bundle (titre, description, prix, cours + ordre)
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();
$pdo = getPDO();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM bundles WHERE id = ?');
$stmt->execute([$id]);
$bundle = $stmt->fetch();
if (!$bundle) redirect(SITE_URL . '/admin/bundles.php', 'Bundle introuvable.', 'error');

$erreur = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCSRF();
    $titre       = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $prix        = (float)($_POST['prix'] ?? 0);
    $actif       = isset($_POST['actif']) ? 1 : 0;
    $courseIds   = array_map('intval', $_POST['course_ids'] ?? []);
    $ordres      = $_POST['ordre'] ?? [];

    if (!$titre) {
        $erreur = 'Le titre est requis.';
    } elseif (count($courseIds) < 2) {
        $erreur = 'Sélectionnez au moins 2 cours.';
    } else {
        $pdo->prepare('UPDATE bundles SET titre=?, description=?, prix=?, actif=? WHERE id=?')
            ->execute([$titre, $description, $prix, $actif, $id]);

        $pdo->prepare('DELETE FROM bundle_courses WHERE bundle_id=?')->execute([$id]);
        foreach ($courseIds as $cId) {
            $ordre = (int)($ordres[$cId] ?? 0);
            $pdo->prepare('INSERT IGNORE INTO bundle_courses (bundle_id, course_id, ordre) VALUES (?,?,?)')
                ->execute([$id, $cId, $ordre]);
        }
        redirect(SITE_URL . '/admin/bundles.php', 'Bundle mis à jour avec succès !', 'success');
    }
}

$selectedCourses = $pdo->prepare(
    'SELECT bc.course_id, bc.ordre, c.titre FROM bundle_courses bc JOIN courses c ON c.id=bc.course_id WHERE bc.bundle_id=? ORDER BY bc.ordre'
);
$selectedCourses->execute([$id]);
$selectedCourses = $selectedCourses->fetchAll();
$selectedIds = array_column($selectedCourses, 'course_id');

$courses = $pdo->query('SELECT id, titre FROM courses WHERE actif=1 ORDER BY titre')->fetchAll();

$currentPage = 'bundles.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Modifier bundle — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
<style>
.fld{width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;font-family:inherit;box-sizing:border-box}
.course-row{display:flex;gap:8px;align-items:center;padding:8px;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:6px}
.course-row label{flex:1;font-size:13px}
.course-row input[type=number]{width:70px}
</style>
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <div><h1 class="admin-page-title">Modifier le bundle</h1><p class="admin-page-sub"><?= h($bundle['titre']) ?></p></div>
    <a href="bundles.php" class="btn-outline"><i class="ti ti-arrow-left"></i> Retour</a>
  </div>

  <?php if ($erreur): ?><div class="alert alert-error"><?= h($erreur) ?></div><?php endif; ?>

  <div class="admin-card" style="max-width:680px">
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="id" value="<?= $id ?>">
      <div class="form-group">
        <label>Titre *</label>
        <input type="text" name="titre" class="fld" required value="<?= h($bundle['titre']) ?>">
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea name="description" class="fld" rows="2"><?= h($bundle['description'] ?? '') ?></textarea>
      </div>
      <div class="form-group">
        <label>Prix (FCFA)</label>
        <input type="number" name="prix" class="fld" value="<?= h($bundle['prix']) ?>" min="0">
      </div>
      <div class="form-group">
        <label>Cours inclus * <small style="font-weight:400;color:#6b7280">(cocher + ordre d'affichage)</small></label>
        <?php foreach ($courses as $co): ?>
          <?php
            $checked = in_array($co['id'], $selectedIds);
            $ordreVal = 0;
            foreach ($selectedCourses as $sc) { if ($sc['course_id'] == $co['id']) { $ordreVal = $sc['ordre']; break; } }
          ?>
          <div class="course-row">
            <input type="checkbox" name="course_ids[]" value="<?= $co['id'] ?>" id="c<?= $co['id'] ?>" <?= $checked ? 'checked' : '' ?>>
            <label for="c<?= $co['id'] ?>"><?= h($co['titre']) ?></label>
            <input type="number" name="ordre[<?= $co['id'] ?>]" value="<?= $ordreVal ?>" min="0" class="fld" placeholder="Ordre">
          </div>
        <?php endforeach; ?>
      </div>
      <div class="form-group">
        <label class="checkbox-label"><input type="checkbox" name="actif" value="1" <?= $bundle['actif'] ? 'checked' : '' ?>> Bundle actif</label>
      </div>
      <button type="submit" class="btn-primary btn-full"><i class="ti ti-device-floppy"></i> Enregistrer</button>
    </form>
  </div>
</div>
</body>
</html>
