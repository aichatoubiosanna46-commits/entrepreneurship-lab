<?php
// admin/module_add.php — Ajout d'un module à un cours
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();

$pdo      = getPDO();
$courseId = (int)($_GET['course_id'] ?? $_POST['course_id'] ?? 0);
if (!$courseId) { header('Location: ' . SITE_URL . '/admin/courses.php'); exit; }

$course = $pdo->prepare('SELECT * FROM courses WHERE id = ?');
$course->execute([$courseId]);
$course = $course->fetch();
if (!$course) { http_response_code(404); die('Cours introuvable.'); }

// Prochain ordre automatique
$maxOrdre = $pdo->prepare('SELECT COALESCE(MAX(ordre),0)+1 FROM modules WHERE course_id = ?');
$maxOrdre->execute([$courseId]);
$nextOrdre = (int)$maxOrdre->fetchColumn();

$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCSRF();

    $titre          = trim($_POST['titre']          ?? '');
    $description    = $_POST['description']         ?? '';   // HTML depuis Quill
    $ordre          = (int)($_POST['ordre']         ?? $nextOrdre);
    $actif          = isset($_POST['actif'])         ? 1 : 0;
    $nb_sequences   = (int)($_POST['nb_sequences']  ?? 0);
    $nb_activites   = (int)($_POST['nb_activites']  ?? 0);
    $duree_min      = (int)($_POST['duree_min']     ?? 0);
    $objectifs      = trim($_POST['objectifs']      ?? '');  // HTML depuis Quill

    if (!$titre) $erreurs[] = 'Le titre est requis.';

    if (empty($erreurs)) {
        $stmt = $pdo->prepare(
            'INSERT INTO modules
             (course_id, titre, description, objectifs, nb_sequences_prev,
              nb_activites_prev, duree_min, ordre, actif)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $courseId, $titre,
            $description ?: null,
            $objectifs   ?: null,
            $nb_sequences,
            $nb_activites,
            $duree_min ?: null,
            $ordre, $actif
        ]);
        $newId = $pdo->lastInsertId();
        redirect(
            SITE_URL . '/admin/sequences.php?module_id=' . $newId,
            'Module créé ! Ajoutez maintenant les séquences.',
            'success'
        );
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ajouter un module — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<!-- Quill Snow theme -->
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/dashboard.css">
<style>
/* ── Layout ── */
.ma-grid        { display:grid; grid-template-columns:1fr 320px; gap:24px; align-items:start; }
.ma-col         { display:flex; flex-direction:column; gap:20px; }
.ma-card        { background:var(--surface,#fff); border:1px solid var(--border,#e5e7eb);
                  border-radius:12px; padding:24px; }
.ma-card-title  { font-size:13px; font-weight:600; color:var(--text-muted,#6b7280);
                  text-transform:uppercase; letter-spacing:.06em; margin:0 0 20px; }

/* ── Compteurs séq / activités ── */
.count-grid     { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.count-card     { border:2px solid var(--border,#e5e7eb); border-radius:10px;
                  padding:16px 12px; text-align:center; cursor:pointer;
                  transition:.18s; background:var(--surface,#fff); }
.count-card:hover         { border-color:var(--primary,#534AB7); }
.count-card.active        { border-color:var(--primary,#534AB7);
                            background:var(--primary-light,#f5f3ff); }
.count-card .cc-icon      { font-size:24px; color:var(--primary,#534AB7); margin-bottom:6px; }
.count-card .cc-label     { font-size:12px; color:var(--text-muted); margin-bottom:10px; }
.count-card .cc-input     { width:64px; font-size:22px; font-weight:700;
                            text-align:center; border:none; background:transparent;
                            color:var(--text,#111); outline:none; }
.count-card .cc-input::-webkit-inner-spin-button { -webkit-appearance:none; }

/* ── Quill overrides ── */
.ql-container   { font-family:'Plus Jakarta Sans',sans-serif; font-size:14px;
                  border-radius:0 0 8px 8px !important; min-height:160px; }
.ql-toolbar     { border-radius:8px 8px 0 0 !important;
                  background:var(--surface-alt,#f9fafb); }
.ql-editor      { min-height:140px; }

/* ── Breadcrumb ── */
.breadcrumb     { display:flex; align-items:center; gap:8px;
                  font-size:13px; color:var(--text-muted); margin-bottom:4px; }
.breadcrumb a   { color:var(--primary,#534AB7); text-decoration:none; }
.breadcrumb a:hover { text-decoration:underline; }

/* ── Submit ── */
.btn-submit     { width:100%; padding:13px; font-size:15px; font-weight:600;
                  border:none; border-radius:10px; cursor:pointer;
                  background:var(--primary,#534AB7); color:#fff;
                  display:flex; align-items:center; justify-content:center; gap:8px;
                  transition:.2s; }
.btn-submit:hover { background:var(--primary-dark,#3d369a); }

/* ── Aperçu structure ── */
.structure-preview { border:1px solid var(--border,#e5e7eb); border-radius:10px;
                     padding:16px; background:var(--surface-alt,#f9fafb); }
.sp-course      { font-size:12px; font-weight:600; color:var(--text-muted);
                  text-transform:uppercase; letter-spacing:.05em; margin-bottom:8px; }
.sp-module      { display:flex; align-items:center; gap:8px; font-size:13px;
                  font-weight:600; color:var(--primary,#534AB7); margin-bottom:6px; }
.sp-seq         { font-size:12px; color:var(--text-muted); padding-left:20px;
                  margin-bottom:2px; }
.sp-seq::before { content:'└ '; }

@media(max-width:860px) { .ma-grid { grid-template-columns:1fr; } }
</style>
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<div class="admin-content">

  <!-- ── Topbar ── -->
  <div class="admin-topbar">
    <div>
      <nav class="breadcrumb">
        <a href="<?= SITE_URL ?>/admin/">Dashboard</a>
        <i class="ti ti-chevron-right" style="font-size:12px"></i>
        <a href="<?= SITE_URL ?>/admin/courses.php">Cours</a>
        <i class="ti ti-chevron-right" style="font-size:12px"></i>
        <a href="<?= SITE_URL ?>/admin/modules.php?course_id=<?= $courseId ?>"><?= h($course['titre']) ?></a>
        <i class="ti ti-chevron-right" style="font-size:12px"></i>
        <span>Nouveau module</span>
      </nav>
      <h1 class="admin-page-title">Ajouter un module</h1>
    </div>
    <a href="<?= SITE_URL ?>/admin/modules.php?course_id=<?= $courseId ?>" class="btn-outline">
      <i class="ti ti-arrow-left"></i> Retour
    </a>
  </div>

  <!-- ── Erreurs ── -->
  <?php if (!empty($erreurs)): ?>
  <div class="alert alert-error" style="margin-bottom:20px">
    <i class="ti ti-alert-circle"></i>
    <div><?php foreach ($erreurs as $e) echo '<div>' . h($e) . '</div>'; ?></div>
  </div>
  <?php endif; ?>

  <form method="POST" id="form-module">
    <input type="hidden" name="csrf_token"   value="<?= csrfToken() ?>">
    <input type="hidden" name="course_id"    value="<?= $courseId ?>">
    <!-- Champs cachés remplis par Quill avant soumission -->
    <input type="hidden" name="description"  id="field-description">
    <input type="hidden" name="objectifs"    id="field-objectifs">

    <div class="ma-grid">

      <!-- ══ COLONNE GAUCHE ══ -->
      <div class="ma-col">

        <!-- Infos générales -->
        <div class="ma-card">
          <p class="ma-card-title"><i class="ti ti-info-circle"></i> Informations du module</p>

          <div class="form-group">
            <label for="titre">Titre du module <span style="color:red">*</span></label>
            <input type="text" id="titre" name="titre"
                   value="<?= h($_POST['titre'] ?? '') ?>"
                   placeholder="Ex : Module 1 — Trouver ses premiers clients" required>
          </div>

          <!-- Description riche -->
          <div class="form-group">
            <label>Description du module</label>
            <div id="editor-description"><?= $_POST['description'] ?? '' ?></div>
          </div>

          <!-- Objectifs pédagogiques -->
          <div class="form-group" style="margin-top:20px">
            <label>
              <i class="ti ti-target" style="font-size:14px;color:var(--primary)"></i>
              Objectifs pédagogiques
            </label>
            <div id="editor-objectifs"><?= $_POST['objectifs'] ?? '' ?></div>
            <small style="color:var(--text-muted);font-size:11px">
              Ce que l'apprenant saura faire après ce module.
            </small>
          </div>
        </div>

        <!-- Structure prévue -->
        <div class="ma-card">
          <p class="ma-card-title"><i class="ti ti-layout-list"></i> Structure prévue</p>
          <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px">
            Indiquez le nombre de séquences et d'activités que ce module contiendra.
            Cela vous servira de guide lors de la création du contenu.
          </p>

          <div class="count-grid">
            <!-- Séquences -->
            <div class="count-card" id="card-seq" onclick="focusInput('nb_sequences')">
              <div class="cc-icon"><i class="ti ti-list-numbers"></i></div>
              <div class="cc-label">Séquences prévues</div>
              <input class="cc-input" type="number" id="nb_sequences" name="nb_sequences"
                     min="0" max="99" value="<?= h($_POST['nb_sequences'] ?? '3') ?>"
                     oninput="updatePreview()" onfocus="activateCard('card-seq')">
            </div>

            <!-- Activités -->
            <div class="count-card" id="card-act" onclick="focusInput('nb_activites')">
              <div class="cc-icon"><i class="ti ti-pencil-check"></i></div>
              <div class="cc-label">Activités prévues</div>
              <input class="cc-input" type="number" id="nb_activites" name="nb_activites"
                     min="0" max="99" value="<?= h($_POST['nb_activites'] ?? '1') ?>"
                     oninput="updatePreview()" onfocus="activateCard('card-act')">
            </div>
          </div>
        </div>

      </div><!-- /col gauche -->

      <!-- ══ COLONNE DROITE ══ -->
      <div class="ma-col">

        <!-- Paramètres -->
        <div class="ma-card">
          <p class="ma-card-title"><i class="ti ti-settings"></i> Paramètres</p>

          <div class="form-group">
            <label for="ordre">Ordre d'affichage</label>
            <input type="number" id="ordre" name="ordre" min="0"
                   value="<?= h($_POST['ordre'] ?? $nextOrdre) ?>">
            <small style="color:var(--text-muted);font-size:11px">
              Module n°<?= $nextOrdre ?> dans ce cours
            </small>
          </div>

          <div class="form-group">
            <label for="duree_min">
              <i class="ti ti-clock" style="font-size:13px"></i>
              Durée estimée (minutes)
            </label>
            <input type="number" id="duree_min" name="duree_min" min="0" step="5"
                   value="<?= h($_POST['duree_min'] ?? '') ?>"
                   placeholder="Ex : 45">
          </div>

          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" name="actif" value="1"
                     <?= (!isset($_POST['actif']) || $_POST['actif']) ? 'checked' : '' ?>>
              <span>Visible pour les apprenants</span>
            </label>
          </div>
        </div>

        <!-- Aperçu structure -->
        <div class="ma-card">
          <p class="ma-card-title"><i class="ti ti-eye"></i> Aperçu de la structure</p>
          <div class="structure-preview">
            <div class="sp-course"><?= h($course['titre']) ?></div>
            <div class="sp-module">
              <i class="ti ti-folder"></i>
              <span id="preview-titre">Titre du module</span>
            </div>
            <div id="preview-seqs"></div>
          </div>
          <p style="font-size:11px;color:var(--text-muted);margin-top:10px;text-align:center">
            Mis à jour en temps réel
          </p>
        </div>

        <!-- Submit -->
        <button type="submit" class="btn-submit" id="btn-submit">
          <i class="ti ti-device-floppy"></i> Créer le module
        </button>
        <p style="font-size:12px;color:var(--text-muted);text-align:center;margin-top:-8px">
          Vous serez redirigé vers les séquences du module.
        </p>

      </div><!-- /col droite -->

    </div>
  </form>
</div>

<!-- ── Quill JS ── -->
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script src="<?= SITE_URL ?>/assets/js/dashboard.js"></script>
<script>
// ── Config Quill partagée
const toolbarOptions = [
  [{ 'header': [1, 2, 3, false] }],
  ['bold', 'italic', 'underline', 'strike'],
  [{ 'color': [] }, { 'background': [] }],
  [{ 'list': 'ordered' }, { 'list': 'bullet' }],
  [{ 'align': [] }],
  ['blockquote', 'link', 'image'],
  ['clean']
];

// Éditeur description
const quillDesc = new Quill('#editor-description', {
  theme: 'snow',
  placeholder: 'Décrivez le contenu de ce module...',
  modules: { toolbar: toolbarOptions }
});

// Éditeur objectifs
const quillObj = new Quill('#editor-objectifs', {
  theme: 'snow',
  placeholder: "À la fin de ce module, l'apprenant sera capable de...",
  modules: { toolbar: toolbarOptions }
});

// ── Avant soumission : injecter le HTML dans les champs cachés
document.getElementById('form-module').addEventListener('submit', function(e) {
  document.getElementById('field-description').value = quillDesc.root.innerHTML;
  document.getElementById('field-objectifs').value   = quillObj.root.innerHTML;
});

// ── Aperçu titre en temps réel
document.getElementById('titre').addEventListener('input', function() {
  const v = this.value.trim() || 'Titre du module';
  document.getElementById('preview-titre').textContent = v;
});

// ── Aperçu séquences
function updatePreview() {
  const nb  = parseInt(document.getElementById('nb_sequences').value) || 0;
  const act = parseInt(document.getElementById('nb_activites').value) || 0;
  let html  = '';
  for (let i = 1; i <= Math.min(nb, 6); i++) {
    html += `<div class="sp-seq">Séquence ${i}</div>`;
  }
  if (nb > 6) html += `<div class="sp-seq" style="color:var(--primary)">+ ${nb - 6} autres…</div>`;
  if (act > 0) html += `<div class="sp-seq" style="color:var(--amber,#6C47D4)"><i class="ti ti-pencil-check"></i> ${act} activité${act>1?'s':''}</div>`;
  document.getElementById('preview-seqs').innerHTML = html;
}

// ── Focus helper
function focusInput(id) { document.getElementById(id).focus(); }
function activateCard(id) {
  document.querySelectorAll('.count-card').forEach(c => c.classList.remove('active'));
  document.getElementById(id).classList.add('active');
}

// Init
updatePreview();
document.getElementById('preview-titre').textContent =
  document.getElementById('titre').value || 'Titre du module';
</script>
</body>
</html>