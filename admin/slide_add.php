<?php
// admin/slide_add.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();

$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCSRF();

    $titre     = trim($_POST['titre']     ?? '');
    $sous_titre= trim($_POST['sous_titre']?? '');
    $lien      = trim($_POST['lien']      ?? '');
    $texte_btn = trim($_POST['texte_btn'] ?? 'En savoir plus');
    $ordre     = (int)($_POST['ordre']    ?? 0);
    $actif     = isset($_POST['actif']) ? 1 : 0;

    if (!$titre) $erreurs[] = 'Le titre est requis.';

    if (empty($_FILES['image']['name'])) {
        $erreurs[] = 'L\'image est requise.';
        $image = null;
    } else {
        $image = uploadImage($_FILES['image'], 'slides', 5);
        if (!$image) $erreurs[] = 'Image invalide (JPG/PNG/WEBP, max 5Mo).';
        else $image = basename($image);
    }

    if (empty($erreurs)) {
        $pdo = getPDO();
        $pdo->prepare(
            'INSERT INTO slides (titre, sous_titre, image, lien, texte_btn, ordre, actif)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([$titre, $sous_titre, $image, $lien, $texte_btn, $ordre, $actif]);
        redirect(SITE_URL . '/admin/slides.php', 'Slide ajouté avec succès !', 'success');
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Ajouter un slide — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/dashboard.css">
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<div class="admin-content">
  <div class="admin-topbar">
    <div>
      <h1 class="admin-page-title">Ajouter un slide</h1>
      <p class="admin-page-sub"><a href="<?= SITE_URL ?>/admin/slides.php">← Retour aux slides</a></p>
    </div>
  </div>

  <?php if (!empty($erreurs)): ?>
    <div class="alert alert-error">
      <i class="ti ti-alert-circle"></i>
      <?php foreach ($erreurs as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" style="max-width:600px">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

    <div class="admin-card">
      <div class="form-group">
        <label for="titre">Titre du slide *</label>
        <input type="text" id="titre" name="titre" value="<?= h($_POST['titre'] ?? '') ?>"
               placeholder="Apprends à entreprendre" required>
      </div>
      <div class="form-group">
        <label for="sous_titre">Sous-titre</label>
        <input type="text" id="sous_titre" name="sous_titre" value="<?= h($_POST['sous_titre'] ?? '') ?>"
               placeholder="Formations en ligne pour entrepreneurs africains">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="lien">Lien du bouton</label>
          <input type="url" id="lien" name="lien" value="<?= h($_POST['lien'] ?? '') ?>"
                 placeholder="https://...">
        </div>
        <div class="form-group">
          <label for="texte_btn">Texte du bouton</label>
          <input type="text" id="texte_btn" name="texte_btn"
                 value="<?= h($_POST['texte_btn'] ?? 'En savoir plus') ?>">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="ordre">Ordre d'affichage</label>
          <input type="number" id="ordre" name="ordre" min="0" value="<?= h($_POST['ordre'] ?? 0) ?>">
        </div>
        <div class="form-group" style="display:flex;align-items:flex-end;padding-bottom:2px">
          <label class="checkbox-label">
            <input type="checkbox" name="actif" value="1" <?= !isset($_POST['actif']) || $_POST['actif'] ? 'checked' : '' ?>>
            <span>Afficher ce slide</span>
          </label>
        </div>
      </div>

      <div class="form-group">
        <label>Image (recommandé : 1400×500px) *</label>
        <div class="upload-zone" onclick="document.getElementById('image').click()">
          <i class="ti ti-photo" style="font-size:28px;color:var(--text-muted)"></i>
          <p style="font-size:13px;color:var(--text-muted);margin-top:6px">Cliquer pour choisir<br><small>JPG, PNG, WEBP — max 5Mo</small></p>
          <img id="preview" style="display:none;max-width:100%;margin-top:10px;border-radius:8px">
        </div>
        <input type="file" id="image" name="image" accept="image/*" style="display:none"
               onchange="previewImg(this)" required>
      </div>

      <button type="submit" class="btn-primary btn-full">
        <i class="ti ti-device-floppy" aria-hidden="true"></i> Enregistrer le slide
      </button>
    </div>
  </form>
</div>

<script>
function previewImg(input) {
  if (!input.files[0]) return;
  const reader = new FileReader();
  reader.onload = e => {
    const img = document.getElementById('preview');
    img.src = e.target.result;
    img.style.display = 'block';
  };
  reader.readAsDataURL(input.files[0]);
}
</script>
<script src="<?= SITE_URL ?>/assets/js/dashboard.js"></script>
</body>
</html>
