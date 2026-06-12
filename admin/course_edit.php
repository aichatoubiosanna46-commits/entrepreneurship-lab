<?php
// admin/course_edit.php — Modifier un cours existant
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();

$pdo = getPDO();
$id  = (int)($_GET['id'] ?? 0);
if (!$id) { redirect(SITE_URL . '/admin/courses.php', 'Cours introuvable.', 'error'); }

$course = $pdo->prepare('SELECT * FROM courses WHERE id = ?');
$course->execute([$id]);
$course = $course->fetch();
if (!$course) { redirect(SITE_URL . '/admin/courses.php', 'Cours introuvable.', 'error'); }

$categories = $pdo->query('SELECT * FROM categories ORDER BY nom')->fetchAll();
$erreurs    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCSRF();

    $titre        = trim($_POST['titre']       ?? '');
    $description  = trim($_POST['description'] ?? '');
    $category_id  = (int)($_POST['category_id'] ?? 0);
    $niveau       = $_POST['niveau']   ?? 'debutant';
    $duree_heures = (float)($_POST['duree_heures'] ?? 0);
    $video_intro  = trim($_POST['video_intro'] ?? '');
    $type         = $_POST['type']     ?? 'gratuit';
    $tarif        = $_POST['tarif']    ?? 'decouverte';
    $prix         = (float)($_POST['prix'] ?? 0);
    $statut       = $_POST['statut']   ?? 'brouillon';
    $actif        = isset($_POST['actif'])      ? 1 : 0;
    $certificat   = isset($_POST['certificat']) ? 1 : 0;

    if (!$titre)       $erreurs[] = 'Le titre est requis.';
    if (!$category_id) $erreurs[] = 'Veuillez choisir une catégorie.';
    if ($type === 'payant' && $prix <= 0) $erreurs[] = 'Indiquez un prix supérieur à 0 pour un cours payant.';

    $miniature = $course['miniature'];
    if (!empty($_FILES['miniature']['name'])) {
        $newImg = uploadImage($_FILES['miniature'], 'courses');
        if (!$newImg) { $erreurs[] = 'Image invalide (JPG/PNG/WEBP, max 2Mo).'; }
        else { $miniature = $newImg; }
    }

    if (empty($erreurs)) {
        $pdo->prepare(
            'UPDATE courses SET category_id=?, titre=?, description=?, miniature=?, video_intro=?,
             niveau=?, type=?, tarif=?, prix=?, duree_heures=?, certificat=?, actif=?, statut=?
             WHERE id=?'
        )->execute([
            $category_id, $titre, $description ?: null, $miniature, $video_intro ?: null,
            $niveau, $type, $tarif,
            $type === 'gratuit' ? 0 : $prix,
            $duree_heures ?: null,
            $certificat, $actif, $statut, $id
        ]);
        redirect(SITE_URL . '/admin/courses.php', 'Cours mis à jour avec succès.', 'success');
    }

    $course = array_merge($course, [
        'titre' => $titre, 'description' => $description, 'category_id' => $category_id,
        'niveau' => $niveau, 'duree_heures' => $duree_heures, 'video_intro' => $video_intro,
        'type' => $type, 'tarif' => $tarif, 'prix' => $prix, 'statut' => $statut,
        'actif' => $actif, 'certificat' => $certificat,
    ]);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Modifier le cours — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/dashboard.css">
<style>
.ca-grid { display:grid; grid-template-columns:1fr 360px; gap:24px; align-items:start; }
.ca-col  { display:flex; flex-direction:column; gap:20px; }
.ca-card { background:#fff; border:1px solid var(--border); border-radius:12px; padding:24px; }
.ca-card-title { font-size:14px; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:.06em; margin:0 0 20px; }
.form-row-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; }
.form-row-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.upload-zone { border:2px dashed var(--border); border-radius:10px; padding:24px 16px; text-align:center; cursor:pointer; min-height:120px; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:6px; }
.upload-zone:hover { border-color:var(--amber); background:#f5f3ff; }
#preview-img { display:none; width:100%; border-radius:8px; margin-top:10px; object-fit:cover; max-height:160px; }
#prix-group[hidden] { display:none; }
.btn-submit { width:100%; padding:13px; font-size:15px; font-weight:600; border:none; border-radius:10px; cursor:pointer; background:var(--amber); color:#fff; display:flex; align-items:center; justify-content:center; gap:8px; transition:.2s; }
.btn-submit:hover { opacity:.9; }
@media (max-width:900px) { .ca-grid { grid-template-columns:1fr; } }
</style>
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<div class="admin-content">
  <div class="admin-topbar">
    <div>
      <h1 class="admin-page-title">Modifier le cours</h1>
      <p class="admin-page-sub"><?= h($course['titre']) ?></p>
    </div>
    <a href="<?= SITE_URL ?>/admin/courses.php" class="btn-outline">
      <i class="ti ti-arrow-left"></i> Retour
    </a>
  </div>

  <?= flash() ?>

  <?php if (!empty($erreurs)): ?>
  <div style="background:#FAECE7;border:1px solid #F0997B;color:#993C1D;padding:12px 16px;border-radius:8px;margin-bottom:20px">
    <?php foreach ($erreurs as $e): ?><div><i class="ti ti-alert-circle"></i> <?= h($e) ?></div><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <div class="ca-grid">

      <!-- Colonne gauche -->
      <div class="ca-col">
        <div class="ca-card">
          <p class="ca-card-title"><i class="ti ti-info-circle"></i> Informations générales</p>

          <div class="form-group">
            <label>Titre du cours <span style="color:red">*</span></label>
            <input type="text" name="titre" value="<?= h($course['titre']) ?>" required>
          </div>

          <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="5"><?= h($course['description'] ?? '') ?></textarea>
          </div>

          <div class="form-row-3">
            <div class="form-group">
              <label>Catégorie <span style="color:red">*</span></label>
              <select name="category_id" required>
                <option value="">— Choisir —</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= $cat['id'] ?>" <?= $course['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                    <?= h($cat['nom']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Niveau</label>
              <select name="niveau">
                <?php foreach (['debutant'=>'Débutant','intermediaire'=>'Intermédiaire','avance'=>'Avancé'] as $v=>$l): ?>
                  <option value="<?= $v ?>" <?= $course['niveau'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Durée (heures)</label>
              <input type="number" name="duree_heures" min="0" step="0.5" value="<?= $course['duree_heures'] ?? '' ?>">
            </div>
          </div>

          <div class="form-group">
            <label>Vidéo de présentation (URL YouTube)</label>
            <input type="url" name="video_intro" value="<?= h($course['video_intro'] ?? '') ?>" placeholder="https://youtube.com/watch?v=...">
          </div>
        </div>

        <div class="ca-card">
          <p class="ca-card-title"><i class="ti ti-certificate"></i> Certification</p>
          <label class="checkbox-label">
            <input type="checkbox" name="certificat" value="1" <?= $course['certificat'] ? 'checked' : '' ?>>
            <span>Ce cours génère un certificat à la complétion</span>
          </label>
        </div>
      </div>

      <!-- Colonne droite -->
      <div class="ca-col">
        <div class="ca-card">
          <p class="ca-card-title"><i class="ti ti-send"></i> Publication</p>
          <div class="form-group">
            <label>Statut</label>
            <select name="statut">
              <?php foreach (['brouillon'=>'📝 Brouillon','publie'=>'✅ Publié','archive'=>'📦 Archivé'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= $course['statut'] === $v ? 'selected' : '' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" name="actif" value="1" <?= $course['actif'] ? 'checked' : '' ?>>
              <span>Visible sur le site</span>
            </label>
          </div>
        </div>

        <div class="ca-card">
          <p class="ca-card-title"><i class="ti ti-lock"></i> Accès & tarification</p>
          <div class="form-group">
            <label>Parcours</label>
            <select name="tarif">
              <option value="decouverte"    <?= $course['tarif']==='decouverte'    ?'selected':'' ?>>💡 Découverte</option>
              <option value="business_plan" <?= $course['tarif']==='business_plan' ?'selected':'' ?>>📊 Business Plan</option>
              <option value="lancement"     <?= $course['tarif']==='lancement'     ?'selected':'' ?>>🚀 Lancement</option>
            </select>
          </div>
          <div class="form-group">
            <label>Type d'accès</label>
            <select name="type" id="type" onchange="togglePrix(this.value)">
              <option value="gratuit" <?= $course['type']==='gratuit' ?'selected':'' ?>>🆓 Gratuit</option>
              <option value="payant"  <?= $course['type']==='payant'  ?'selected':'' ?>>💳 Payant</option>
            </select>
          </div>
          <div class="form-group" id="prix-group" <?= $course['type']!=='payant' ? 'hidden' : '' ?>>
            <label>Prix (FCFA)</label>
            <input type="number" name="prix" min="0" step="500" value="<?= $course['prix'] ?? 0 ?>">
          </div>
        </div>

        <div class="ca-card">
          <p class="ca-card-title"><i class="ti ti-photo"></i> Image de couverture</p>
          <?php if ($course['miniature']): ?>
            <img src="<?= SITE_URL ?>/assets/uploads/<?= h($course['miniature']) ?>" style="width:100%;border-radius:8px;margin-bottom:12px;max-height:160px;object-fit:cover">
          <?php endif; ?>
          <div class="upload-zone" onclick="document.getElementById('miniature').click()">
            <i class="ti ti-photo-plus" style="font-size:28px;color:var(--text-muted)"></i>
            <p style="font-size:13px;color:var(--text-muted);margin:0">
              <?= $course['miniature'] ? 'Cliquer pour changer l\'image' : 'Cliquer pour choisir une image' ?>
            </p>
            <small style="color:var(--text-muted)">JPG, PNG, WEBP — max 2 Mo</small>
            <img id="preview-img" alt="">
          </div>
          <input type="file" id="miniature" name="miniature" accept="image/jpeg,image/png,image/webp" style="display:none" onchange="previewImg(this)">
        </div>

        <button type="submit" class="btn-submit">
          <i class="ti ti-device-floppy"></i> Enregistrer les modifications
        </button>
      </div>
    </div>
  </form>
</div>

<script>
function togglePrix(val) {
  const g = document.getElementById('prix-group');
  val === 'payant' ? g.removeAttribute('hidden') : g.setAttribute('hidden', '');
}
function previewImg(input) {
  if (!input.files[0]) return;
  const reader = new FileReader();
  reader.onload = e => {
    const img = document.getElementById('preview-img');
    img.src = e.target.result;
    img.style.display = 'block';
  };
  reader.readAsDataURL(input.files[0]);
}
</script>
</body>
</html>
