<?php
// ============================================================
//  admin/badge_add.php — Ajouter / modifier un badge
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

sendSecurityHeaders();
reqAdmin();

$pdo    = getPDO();
$editId = (int)($_GET['edit'] ?? 0);
$badge  = null;
$erreur = '';

if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM badges WHERE id = ?');
    $stmt->execute([$editId]);
    $badge = $stmt->fetch();
    if (!$badge) redirect(SITE_URL . '/admin/badges.php', 'Badge introuvable.', 'error');
}

$courses = $pdo->query('SELECT id, titre FROM courses WHERE actif = 1 ORDER BY titre')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCSRF();

    $nom           = trim($_POST['nom'] ?? '');
    $description   = trim($_POST['description'] ?? '');
    $conditionType = $_POST['condition_type'] ?? 'manuel';
    $conditionVal  = (int)($_POST['condition_valeur'] ?? 0) ?: null;
    $conditionCourse = (int)($_POST['condition_course_id'] ?? 0) ?: null;
    $actif         = isset($_POST['actif']) ? 1 : 0;
    $image         = $badge['image'] ?? null;

    if (strlen($nom) < 2) {
        $erreur = 'Le nom est trop court.';
    } else {
        if (!empty($_FILES['image']['name'])) {
            $uploaded = uploadImage($_FILES['image'], 'badges');
            if (!$uploaded) {
                $erreur = 'Image invalide (PNG/JPG/WebP, max 2 Mo).';
            } else {
                $image = $uploaded;
            }
        }

        if (!$erreur) {
            if ($editId) {
                $pdo->prepare(
                    'UPDATE badges SET nom = ?, description = ?, image = ?, condition_type = ?, condition_valeur = ?, condition_course_id = ?, actif = ? WHERE id = ?'
                )->execute([$nom, $description, $image, $conditionType, $conditionVal, $conditionCourse, $actif, $editId]);
                redirect(SITE_URL . '/admin/badges.php', 'Badge modifié.', 'success');
            } else {
                $pdo->prepare(
                    'INSERT INTO badges (nom, description, image, condition_type, condition_valeur, condition_course_id, actif) VALUES (?, ?, ?, ?, ?, ?, ?)'
                )->execute([$nom, $description, $image, $conditionType, $conditionVal, $conditionCourse, $actif]);
                redirect(SITE_URL . '/admin/badges.php', 'Badge créé.', 'success');
            }
        }
    }
}

$pageTitle = $editId ? 'Modifier le badge' : 'Nouveau badge';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $pageTitle ?> — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
</head>
<body class="admin-layout">
<?php require_once __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <h1 class="admin-page-title"><i class="ti ti-award"></i> <?= $pageTitle ?></h1>
    <a href="badges.php" class="btn-outline" style="font-size:13px"><i class="ti ti-arrow-left"></i> Retour</a>
  </div>
  <div class="admin-content" style="max-width:700px">
    <?php if ($erreur): ?>
      <div class="alert alert-error"><i class="ti ti-alert-circle"></i> <?= h($erreur) ?></div>
    <?php endif; ?>

    <div style="background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:28px">
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <div class="form-group">
          <label>Nom du badge <span style="color:#dc2626">*</span></label>
          <input type="text" name="nom" value="<?= h($badge['nom'] ?? $_POST['nom'] ?? '') ?>" required maxlength="120"
                 style="width:100%;padding:10px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:14px;box-sizing:border-box">
        </div>

        <div class="form-group">
          <label>Description</label>
          <textarea name="description" rows="3"
                    style="width:100%;padding:10px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:14px;resize:vertical;font-family:inherit;box-sizing:border-box"><?= h($badge['description'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
          <label>Image du badge (PNG recommandé, max 2 Mo)</label>
          <?php if (!empty($badge['image'])): ?>
            <img src="<?= SITE_URL ?>/assets/uploads/<?= h($badge['image']) ?>" alt="" style="width:64px;height:64px;object-fit:contain;border:1px solid var(--border,#e5e7eb);border-radius:8px;margin-bottom:8px;display:block">
          <?php endif; ?>
          <input type="file" name="image" accept="image/*">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
          <div class="form-group">
            <label>Type de condition</label>
            <select name="condition_type" id="condType" onchange="updateCondFields()"
                    style="width:100%;padding:10px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:14px">
              <?php foreach (['manuel' => 'Manuel (attribution manuelle)', 'completion_cours' => 'Complétion d\'un cours', 'score_quiz' => 'Score quiz (pts)', 'xp_total' => 'Total XP'] as $val => $lbl): ?>
                <option value="<?= $val ?>" <?= ($badge['condition_type'] ?? 'manuel') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" id="fieldValeur">
            <label>Valeur seuil</label>
            <input type="number" name="condition_valeur" min="0"
                   value="<?= h($badge['condition_valeur'] ?? '') ?>"
                   style="width:100%;padding:10px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:14px;box-sizing:border-box">
          </div>
        </div>

        <div class="form-group" id="fieldCours">
          <label>Cours associé (pour completion_cours)</label>
          <select name="condition_course_id" style="width:100%;padding:10px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:14px">
            <option value="">-- Aucun --</option>
            <?php foreach ($courses as $c): ?>
              <option value="<?= $c['id'] ?>" <?= ($badge['condition_course_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= h($c['titre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group" style="display:flex;align-items:center;gap:8px">
          <input type="checkbox" id="actif" name="actif" value="1" <?= ($badge['actif'] ?? 1) ? 'checked' : '' ?>>
          <label for="actif" style="margin:0">Badge actif (visible aux étudiants)</label>
        </div>

        <button type="submit" class="btn-primary">
          <i class="ti ti-check"></i> <?= $editId ? 'Enregistrer les modifications' : 'Créer le badge' ?>
        </button>
      </form>
    </div>
  </div>
</div>
<script>
function updateCondFields() {
  const type = document.getElementById('condType').value;
  document.getElementById('fieldValeur').style.display = (type === 'manuel' || type === 'completion_cours') ? 'none' : '';
  document.getElementById('fieldCours').style.display  = type === 'completion_cours' ? '' : 'none';
}
updateCondFields();
</script>
</body>
</html>
