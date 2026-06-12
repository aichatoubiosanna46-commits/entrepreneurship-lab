<?php
// admin/sequence_edit.php — Modification d'une séquence
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();

$pdo      = getPDO();
$id       = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$moduleId = (int)($_GET['module_id'] ?? $_POST['module_id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT s.*, m.titre as module_titre, m.course_id,
            c.titre as course_titre
     FROM sequences s
     JOIN modules m ON m.id = s.module_id
     JOIN courses c ON c.id = m.course_id
     WHERE s.id = ?'
);
$stmt->execute([$id]);
$seq = $stmt->fetch();
if (!$seq) { http_response_code(404); die('Séquence introuvable.'); }

$moduleId = $moduleId ?: $seq['module_id'];
$erreurs  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCSRF();

    $titre    = trim($_POST['titre']       ?? '');
    $desc     = trim($_POST['description'] ?? '');
    $contenu  = trim($_POST['contenu']     ?? '');
    $videoUrl = trim($_POST['video_url']   ?? '');
    $audioUrl = trim($_POST['audio_url']   ?? '');
    $duree    = (int)($_POST['duree_min']  ?? 0);
    $ordre    = (int)($_POST['ordre']      ?? 0);
    $actif    = isset($_POST['actif']) ? 1 : 0;

    if (!$titre) $erreurs[] = 'Le titre est requis.';

    $imageSeq = $seq['image_seq'];
    if (!empty($_FILES['image_seq']['name'])) {
        $res = uploadImage($_FILES['image_seq'], 'sequences');
        if (!$res) $erreurs[] = 'Image invalide (JPG/PNG/WEBP, max 2Mo).';
        else $imageSeq = basename($res);
    }

    $fichierPdf = $seq['fichier_pdf'];
    if (!empty($_FILES['fichier_pdf']['name'])) {
        $res = uploadFichier($_FILES['fichier_pdf'], 'sequences/pdf');
        if (!$res) $erreurs[] = 'Fichier PDF invalide (max 10Mo).';
        else $fichierPdf = $res;
    }

    if (empty($erreurs)) {
        $stmt = $pdo->prepare(
            'UPDATE sequences SET titre=?, description=?, contenu=?, video_url=?, audio_url=?,
             image_seq=?, fichier_pdf=?, duree_min=?, ordre=?, actif=? WHERE id=?'
        );
        $stmt->execute([
            $titre, $desc ?: null, $contenu ?: null,
            $videoUrl ?: null, $audioUrl ?: null,
            $imageSeq, $fichierPdf,
            $duree ?: null, $ordre, $actif, $id
        ]);
        redirect(SITE_URL.'/admin/sequences.php?module_id='.$moduleId,
                 'Séquence mise à jour !', 'success');
    }
    // Recharger les données du formulaire depuis POST
    $seq = array_merge($seq, $_POST);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Modifier séquence — <?= h($seq['titre']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/dashboard.css">
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<div class="admin-content">
  <div class="admin-topbar">
    <div>
      <h1 class="admin-page-title">Modifier la séquence</h1>
      <p class="admin-page-sub">
        <a href="<?= SITE_URL ?>/admin/modules.php?course_id=<?= $seq['course_id'] ?>"><?= h($seq['course_titre']) ?></a>
        &nbsp;/&nbsp;
        <a href="<?= SITE_URL ?>/admin/sequences.php?module_id=<?= $moduleId ?>">← <?= h($seq['module_titre']) ?></a>
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
    <input type="hidden" name="id" value="<?= $id ?>">
    <input type="hidden" name="module_id" value="<?= $moduleId ?>">

    <div class="admin-two-col" style="align-items:start">
      <div style="display:flex;flex-direction:column;gap:16px">
        <div class="admin-card">
          <h2 class="admin-card-title">Informations de la séquence</h2>

          <div class="form-group">
            <label for="titre">Titre *</label>
            <input type="text" id="titre" name="titre" value="<?= h($seq['titre']) ?>" required>
          </div>
          <div class="form-group">
            <label for="description">Description courte</label>
            <input type="text" id="description" name="description" value="<?= h($seq['description'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label for="contenu">Contenu</label>
            <textarea id="contenu" name="contenu" rows="8"><?= h($seq['contenu'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label for="video_url"><i class="ti ti-video" style="color:#6C47D4"></i> URL Vidéo</label>
            <input type="url" id="video_url" name="video_url" value="<?= h($seq['video_url'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label for="audio_url"><i class="ti ti-music" style="color:#3B6D11"></i> URL Audio</label>
            <input type="url" id="audio_url" name="audio_url" value="<?= h($seq['audio_url'] ?? '') ?>">
          </div>
        </div>
      </div>

      <div style="display:flex;flex-direction:column;gap:16px">
        <div class="admin-card">
          <h2 class="admin-card-title">Image de séquence</h2>
          <?php if ($seq['image_seq']): ?>
            <img src="<?= SITE_URL ?>/assets/uploads/sequences/<?= h($seq['image_seq']) ?>"
                 style="max-width:100%;border-radius:8px;margin-bottom:10px;max-height:160px;object-fit:cover">
          <?php endif; ?>
          <div class="upload-zone" onclick="document.getElementById('image_seq').click()">
            <i class="ti ti-photo-plus" style="font-size:24px;color:var(--text-muted)"></i>
            <p style="font-size:12px;color:var(--text-muted)">Nouvelle image (remplace l'actuelle)</p>
            <img id="preview" style="display:none;max-width:100%;border-radius:8px;margin-top:8px">
          </div>
          <input type="file" id="image_seq" name="image_seq" accept="image/*" style="display:none"
                 onchange="previewImg(this)">
        </div>

        <div class="admin-card">
          <h2 class="admin-card-title"><i class="ti ti-file-type-pdf" style="color:#993C1D"></i> PDF</h2>
          <?php if ($seq['fichier_pdf']): ?>
            <p style="font-size:12px;color:var(--text-muted);margin-bottom:8px">Fichier actuel : <?= h(basename($seq['fichier_pdf'])) ?></p>
          <?php endif; ?>
          <input type="file" name="fichier_pdf" accept=".pdf">
        </div>

        <div class="admin-card">
          <h2 class="admin-card-title">Options</h2>
          <div class="form-row">
            <div class="form-group">
              <label for="duree_min">Durée (min)</label>
              <input type="number" id="duree_min" name="duree_min" min="0" value="<?= h($seq['duree_min'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label for="ordre">Ordre</label>
              <input type="number" id="ordre" name="ordre" min="0" value="<?= h($seq['ordre']) ?>">
            </div>
          </div>
          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" name="actif" value="1" <?= $seq['actif'] ? 'checked' : '' ?>>
              <span>Visible pour les apprenants</span>
            </label>
          </div>
        </div>

        <button type="submit" class="btn-primary btn-full">
          <i class="ti ti-device-floppy"></i> Enregistrer les modifications
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
