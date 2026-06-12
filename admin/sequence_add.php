<?php
// admin/sequence_add.php — Ajout d'une séquence à un module
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();

$pdo      = getPDO();
$moduleId = (int)($_GET['module_id'] ?? $_POST['module_id'] ?? 0);
if (!$moduleId) { header('Location: '.SITE_URL.'/admin/courses.php'); exit; }

$module = $pdo->prepare('SELECT m.*, c.titre as course_titre, c.id as course_id FROM modules m JOIN courses c ON c.id = m.course_id WHERE m.id = ?');
$module->execute([$moduleId]);
$module = $module->fetch();
if (!$module) { http_response_code(404); die('Module introuvable.'); }

$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCSRF();

    $titre     = trim($_POST['titre']     ?? '');
    $desc      = trim($_POST['description'] ?? '');
    $contenu   = trim($_POST['contenu']   ?? '');
    $videoUrl  = trim($_POST['video_url'] ?? '');
    $audioUrl  = trim($_POST['audio_url'] ?? '');
    $duree     = (int)($_POST['duree_min'] ?? 0);
    $ordre     = (int)($_POST['ordre']     ?? 0);
    $actif     = isset($_POST['actif']) ? 1 : 0;

    if (!$titre) $erreurs[] = 'Le titre est requis.';

    // Upload image séquence
    $imageSeq = null;
    if (!empty($_FILES['image_seq']['name'])) {
        $res = uploadImage($_FILES['image_seq'], 'sequences');
        if (!$res) $erreurs[] = 'Image invalide (JPG/PNG/WEBP, max 2Mo).';
        else $imageSeq = basename($res);
    }

    // Upload PDF
    $fichierPdf = null;
    if (!empty($_FILES['fichier_pdf']['name'])) {
        $res = uploadFichier($_FILES['fichier_pdf'], 'sequences/pdf');
        if (!$res) $erreurs[] = 'Fichier PDF invalide (max 10Mo).';
        else $fichierPdf = $res;
    }

    if (empty($erreurs)) {
        $slugBase = slugUnique($pdo, 'sequences', 'slug', slug($titre));
        $stmt = $pdo->prepare(
            'INSERT INTO sequences
             (module_id, titre, slug, description, contenu, video_url, audio_url, image_seq, fichier_pdf, duree_min, ordre, actif)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $moduleId, $titre, $slugBase,
            $desc ?: null, $contenu ?: null,
            $videoUrl ?: null, $audioUrl ?: null,
            $imageSeq, $fichierPdf,
            $duree ?: null, $ordre, $actif
        ]);
        redirect(SITE_URL.'/admin/sequences.php?module_id='.$moduleId,
                 'Séquence ajoutée !', 'success');
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Ajouter une séquence</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/dashboard.css">
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<div class="admin-content">
  <div class="admin-topbar">
    <div>
      <h1 class="admin-page-title">Ajouter une séquence</h1>
      <p class="admin-page-sub">
        <a href="<?= SITE_URL ?>/admin/modules.php?course_id=<?= $module['course_id'] ?>"><?= h($module['course_titre']) ?></a>
        &nbsp;/&nbsp;
        <a href="<?= SITE_URL ?>/admin/sequences.php?module_id=<?= $moduleId ?>">← <?= h($module['titre']) ?></a>
      </p>
    </div>
  </div>

  <?php if (!empty($erreurs)): ?>
  <div class="alert alert-error">
    <i class="ti ti-alert-circle"></i>
    <div><?php foreach ($erreurs as $e) echo '<div>'.h($e).'</div>'; ?></div>
  </div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <input type="hidden" name="module_id" value="<?= $moduleId ?>">

    <div class="admin-two-col" style="align-items:start">

      <!-- Colonne principale -->
      <div style="display:flex;flex-direction:column;gap:16px">
        <div class="admin-card">
          <h2 class="admin-card-title">Informations de la séquence</h2>

          <div class="form-group">
            <label for="titre">Titre *</label>
            <input type="text" id="titre" name="titre" value="<?= h($_POST['titre'] ?? '') ?>"
                   placeholder="Ex : Introduction à l'entrepreneuriat" required>
          </div>

          <div class="form-group">
            <label for="description">Description courte</label>
            <input type="text" id="description" name="description"
                   value="<?= h($_POST['description'] ?? '') ?>"
                   placeholder="Résumé de cette séquence">
          </div>

          <div class="form-group">
            <label for="contenu">Contenu (texte riche)</label>
            <textarea id="contenu" name="contenu" rows="8"
                      placeholder="Contenu pédagogique principal de cette séquence..."><?= h($_POST['contenu'] ?? '') ?></textarea>
          </div>

          <div class="form-group">
            <label for="video_url"><i class="ti ti-video" style="color:#6C47D4"></i> URL Vidéo (YouTube / Vimeo)</label>
            <input type="url" id="video_url" name="video_url"
                   value="<?= h($_POST['video_url'] ?? '') ?>"
                   placeholder="https://www.youtube.com/watch?v=...">
          </div>

          <div class="form-group">
            <label for="audio_url"><i class="ti ti-music" style="color:#3B6D11"></i> URL Audio</label>
            <input type="url" id="audio_url" name="audio_url"
                   value="<?= h($_POST['audio_url'] ?? '') ?>"
                   placeholder="https://...">
          </div>
        </div>
      </div>

      <!-- Colonne latérale -->
      <div style="display:flex;flex-direction:column;gap:16px">

        <div class="admin-card">
          <h2 class="admin-card-title"><i class="ti ti-photo" style="color:var(--amber)"></i> Image de séquence</h2>
          <div class="upload-zone" onclick="document.getElementById('image_seq').click()">
            <i class="ti ti-photo-plus" style="font-size:28px;color:var(--text-muted)"></i>
            <p style="font-size:13px;color:var(--text-muted);margin-top:6px">Cliquer pour choisir<br><small>JPG, PNG, WEBP — max 2Mo</small></p>
            <img id="preview" style="display:none;max-width:100%;margin-top:10px;border-radius:8px;max-height:160px;object-fit:cover">
          </div>
          <input type="file" id="image_seq" name="image_seq" accept="image/*" style="display:none"
                 onchange="previewImg(this)">
        </div>

        <div class="admin-card">
          <h2 class="admin-card-title"><i class="ti ti-file-type-pdf" style="color:#993C1D"></i> Document PDF</h2>
          <div class="form-group">
            <label for="fichier_pdf">Fichier PDF joint (max 10Mo)</label>
            <input type="file" id="fichier_pdf" name="fichier_pdf" accept=".pdf">
          </div>
        </div>

        <div class="admin-card">
          <h2 class="admin-card-title">Options</h2>
          <div class="form-row">
            <div class="form-group">
              <label for="duree_min">Durée (minutes)</label>
              <input type="number" id="duree_min" name="duree_min" min="0"
                     value="<?= h($_POST['duree_min'] ?? '') ?>" placeholder="15">
            </div>
            <div class="form-group">
              <label for="ordre">Ordre</label>
              <input type="number" id="ordre" name="ordre" min="0"
                     value="<?= h($_POST['ordre'] ?? '0') ?>">
            </div>
          </div>
          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" name="actif" value="1" <?= !isset($_POST['actif']) || $_POST['actif'] ? 'checked' : '' ?>>
              <span>Visible pour les apprenants</span>
            </label>
          </div>
        </div>

        <button type="submit" class="btn-primary btn-full">
          <i class="ti ti-device-floppy"></i> Enregistrer la séquence
        </button>
      </div>
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
