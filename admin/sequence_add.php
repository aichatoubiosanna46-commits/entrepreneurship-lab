<?php
// admin/sequence_add.php — Créer une séquence avec éditeur riche
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqInstructeurOuAdmin();

$pdo      = getPDO();
$moduleId = (int)($_GET['module_id'] ?? $_POST['module_id'] ?? 0);
if ($moduleId && !moduleAppartientInstructeur($moduleId)) { redirect(SITE_URL . '/admin/courses.php', 'Accès refusé : ce module ne vous appartient pas.', 'error'); }
$module   = $moduleId ? $pdo->query("SELECT m.*, c.titre as cours FROM modules m JOIN courses c ON c.id=m.course_id WHERE m.id=$moduleId LIMIT 1")->fetch() : null;

$erreur = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCSRF();
    $titre       = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type        = $_POST['type_contenu'] ?? 'video';
    $videoUrl    = trim($_POST['video_url'] ?? '');
    $audioUrl    = trim($_POST['audio_url'] ?? '');
    $contenu     = $_POST['contenu'] ?? '';
    $contenuRiche = $_POST['contenu_riche'] ?? '';
    $duree       = (int)($_POST['duree_min'] ?? 0);
    $ordre       = (int)($_POST['ordre'] ?? 0);
    $actif       = isset($_POST['actif']) ? 1 : 0;
    $xpReward    = (int)($_POST['xp_reward'] ?? 10);
    $deadline    = $_POST['deadline'] ?: null;
    $assignTitre = trim($_POST['assignment_titre'] ?? $titre);
    $assignType  = in_array($_POST['assignment_type'] ?? '', ['texte','fichier','video','audio']) ? $_POST['assignment_type'] : 'texte';
    $noteMax     = (float)($_POST['note_max'] ?? 20);
    $noteMin     = (float)($_POST['note_min'] ?? 10);
    $resoumission = isset($_POST['resoumission']) ? 1 : 0;
    $criteresNom    = $_POST['criteres_nom'] ?? [];
    $criteresPoints = $_POST['criteres_points'] ?? [];

    if (!$titre || !$moduleId) {
        $erreur = 'Titre et module sont requis.';
    } else {
        // Upload image
        $imageSeq = null;
        if (!empty($_FILES['image_seq']['tmp_name'])) {
            $ext  = strtolower(pathinfo($_FILES['image_seq']['name'], PATHINFO_EXTENSION));
            $name = 'seq_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['image_seq']['tmp_name'], __DIR__ . '/../assets/uploads/' . $name);
            $imageSeq = $name;
        }
        // Upload PDF
        $fichierPdf = null;
        if (!empty($_FILES['fichier_pdf']['tmp_name'])) {
            $name = 'pdf_' . uniqid() . '.pdf';
            move_uploaded_file($_FILES['fichier_pdf']['tmp_name'], __DIR__ . '/../assets/uploads/' . $name);
            $fichierPdf = $name;
        }

        $slug = preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($titre)) . '-' . uniqid();
        $pdo->prepare(
            'INSERT INTO sequences (module_id, titre, slug, description, contenu, contenu_riche, video_url, audio_url, image_seq, fichier_pdf, type_contenu, duree_min, ordre, actif, xp_reward, deadline)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        )->execute([$moduleId, $titre, $slug, $description, $contenu, $contenuRiche, $videoUrl ?: null, $audioUrl ?: null, $imageSeq, $fichierPdf, $type, $duree, $ordre, $actif, $xpReward, $deadline]);

        $newSeqId = (int)$pdo->lastInsertId();

        // Création du livrable (assignment) associé si type = assignment
        if ($type === 'assignment' && $contenu) {
            $pdo->prepare(
                'INSERT INTO assignments (sequence_id, titre, consigne, type, note_min, note_max) VALUES (?,?,?,?,?,?)'
            )->execute([$newSeqId, $assignTitre ?: $titre, $contenu, $assignType, $noteMin, $noteMax]);
            $newAssignmentId = (int)$pdo->lastInsertId();

            // Grille de rubrique (optionnelle)
            $criteres = [];
            foreach ($criteresNom as $i => $nomCritere) {
                $nomCritere = trim($nomCritere);
                $pts        = (float)($criteresPoints[$i] ?? 0);
                if ($nomCritere !== '' && $pts > 0) {
                    $criteres[] = ['nom' => $nomCritere, 'points' => $pts];
                }
            }
            if (!empty($criteres)) {
                $pdo->prepare(
                    'INSERT INTO rubriques (assignment_id, nom, titre, criteres) VALUES (?,?,?,?)'
                )->execute([$newAssignmentId, $assignTitre ?: $titre, $assignTitre ?: $titre, json_encode($criteres, JSON_UNESCAPED_UNICODE)]);
            }
        }

        redirect(SITE_URL . '/admin/sequences.php?module_id=' . $moduleId, 'Séquence créée !', 'success');
    }
}

$currentPage = 'sequence_add.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Nouvelle séquence — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
<!-- Quill.js éditeur riche - open source sans clé API -->
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<style>
.ql-container { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 14px; min-height: 300px; }
.ql-toolbar { border-radius: 8px 8px 0 0; background: #f9fafb; }
.ql-container { border-radius: 0 0 8px 8px; }
.ql-editor { min-height: 300px; line-height: 1.7; }
</style>
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <div>
      <div class="admin-page-title">Nouvelle séquence</div>
      <div class="admin-page-sub">
        <?php if ($module): ?>
          <?= h($module['cours']) ?> → <?= h($module['titre']) ?>
        <?php endif; ?>
      </div>
    </div>
    <?php if ($moduleId): ?>
    <a href="<?= SITE_URL ?>/admin/sequences.php?module_id=<?= $moduleId ?>" class="btn-outline">
      <i class="ti ti-arrow-left"></i> Retour
    </a>
    <?php endif; ?>
  </div>

  <?php if ($erreur): ?><div class="alert alert-error"><i class="ti ti-alert-circle"></i> <?= h($erreur) ?></div><?php endif; ?>

  <form method="POST" enctype="multipart/form-data" id="seqForm">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <input type="hidden" name="module_id" value="<?= $moduleId ?>">

    <div style="display:grid;grid-template-columns:1fr 320px;gap:20px">
      <div>
        <!-- Infos générales -->
        <div class="admin-card" style="margin-bottom:20px">
          <div class="admin-card-title"><i class="ti ti-file-text" style="color:var(--primary)"></i> Informations générales</div>
          <div class="form-group"><label>Titre *</label><input type="text" name="titre" required placeholder="Ex: Leçon 1 — Identifier ton marché cible"></div>
          <div class="form-group"><label>Description courte</label><input type="text" name="description" placeholder="Résumé en 1 ligne..."></div>

          <!-- Type de contenu -->
          <div class="form-group">
            <label>Type de contenu</label>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
              <?php $types = ['video'=>'🎬 Vidéo','texte'=>'📄 Texte','ebook'=>'📚 eBook riche','audio'=>'🎵 Audio','quiz'=>'❓ Quiz','assignment'=>'📋 Livrable']; ?>
              <?php foreach ($types as $val => $lbl): ?>
              <label style="display:flex;align-items:center;gap:6px;padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:8px;cursor:pointer;font-size:13px;transition:.15s" onclick="switchType('<?= $val ?>')">
                <input type="radio" name="type_contenu" value="<?= $val ?>" <?= $val==='video'?'checked':'' ?> style="accent-color:#F59E0B">
                <?= $lbl ?>
              </label>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- Vidéo -->
        <div class="admin-card type-panel" id="panel-video" style="margin-bottom:20px">
          <div class="admin-card-title"><i class="ti ti-video" style="color:#6C47D4"></i> Contenu vidéo</div>
          <div class="form-group">
            <label>URL YouTube ou Vimeo</label>
            <input type="url" name="video_url" placeholder="https://www.youtube.com/watch?v=...">
            <small style="color:var(--text-muted);font-size:11px">Colle l'URL de la vidéo YouTube ou Vimeo. Elle sera automatiquement embarquée.</small>
          </div>
          <div class="form-group">
            <label>Transcription / Notes de cours <small style="font-weight:400;color:var(--text-muted)">(optionnel)</small></label>
            <textarea name="contenu" rows="4" placeholder="Transcription ou résumé de la vidéo..."></textarea>
          </div>
        </div>

        <!-- Texte / eBook riche - TinyMCE -->
        <div class="admin-card type-panel" id="panel-texte" style="margin-bottom:20px;display:none">
          <div class="admin-card-title"><i class="ti ti-file-text" style="color:#16a34a"></i> Contenu texte</div>
          <div class="form-group">
            <label>Contenu pédagogique</label>
            <div id="editor-texte" style="min-height:300px"></div>
            <input type="hidden" name="contenu_riche" id="contenu-riche-texte">
          </div>
        </div>

        <div class="admin-card type-panel" id="panel-ebook" style="margin-bottom:20px;display:none">
          <div class="admin-card-title"><i class="ti ti-book" style="color:#D97706"></i> eBook interactif</div>
          <div class="alert alert-info" style="margin-bottom:16px"><i class="ti ti-info-circle"></i> L'éditeur eBook permet d'intégrer textes, images, encadrés, listes, tableaux et plus encore.</div>
          <div class="form-group">
            <div id="editor-ebook" style="min-height:400px"></div>
            <input type="hidden" name="contenu_riche" id="contenu-riche-ebook">
          </div>
          <div class="form-group">
            <label>Document PDF complémentaire</label>
            <input type="file" name="fichier_pdf" accept=".pdf">
          </div>
        </div>

        <!-- Audio -->
        <div class="admin-card type-panel" id="panel-audio" style="margin-bottom:20px;display:none">
          <div class="admin-card-title"><i class="ti ti-music" style="color:#EF4444"></i> Contenu audio</div>
          <div class="form-group">
            <label>URL du fichier audio (MP3, OGG)</label>
            <input type="url" name="audio_url" placeholder="https://...">
          </div>
          <div class="form-group">
            <label>Transcription</label>
            <textarea name="contenu" rows="5" placeholder="Transcription de l'audio..."></textarea>
          </div>
        </div>

        <!-- Quiz / Assignment -->
        <div class="admin-card type-panel" id="panel-quiz" style="margin-bottom:20px;display:none">
          <div class="admin-card-title"><i class="ti ti-help-circle" style="color:#F59E0B"></i> Quiz associé</div>
          <p style="font-size:13px;color:var(--text-muted)">Crée d'abord le quiz depuis <a href="<?= SITE_URL ?>/admin/quiz_add.php" style="color:var(--primary)">Admin → Quiz</a>, puis associe-le ici.</p>
        </div>
        <div class="admin-card type-panel" id="panel-assignment" style="margin-bottom:20px;display:none">
          <div class="admin-card-title"><i class="ti ti-clipboard" style="color:#6b7280"></i> Livrable / Assignment</div>
          <div class="form-group">
            <label>Instructions du livrable *</label>
            <textarea name="contenu" rows="5" placeholder="Décris ce que l'étudiant doit produire et soumettre..."></textarea>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Note maximale</label>
              <input type="number" name="note_max" value="20" min="1" max="100"
                style="width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;font-family:inherit">
            </div>
            <div class="form-group">
              <label>Note minimale <small style="font-weight:400;color:#6b7280">(refus auto en dessous)</small></label>
              <input type="number" name="note_min" value="10" min="0" max="100"
                style="width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;font-family:inherit">
            </div>
          </div>
          <div class="form-group">
            <label>Titre du livrable</label>
            <input type="text" name="assignment_titre" placeholder="Laisser vide pour reprendre le titre de la séquence">
          </div>
          <div class="form-group">
            <label>Type de rendu accepté</label>
            <select name="assignment_type" style="width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;font-family:inherit">
              <option value="texte">Texte seulement</option>
              <option value="fichier">Fichier (PDF, DOCX...)</option>
              <option value="video">Vidéo</option>
              <option value="audio">Audio (enregistrement micro)</option>
            </select>
          </div>
          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" name="resoumission" value="1" checked>
              Autoriser la resoumission si refusé
            </label>
          </div>

          <!-- Grille de rubrique -->
          <div class="form-group" style="margin-top:16px;border-top:1px solid #e5e7eb;padding-top:14px">
            <label><i class="ti ti-table" style="color:#6C47D4"></i> Grille de rubrique (critères d'évaluation) <small style="font-weight:400;color:#6b7280">(optionnel)</small></label>
            <div id="rubrique-rows">
              <div class="rubrique-row" style="display:flex;gap:8px;align-items:center;margin-bottom:8px">
                <input type="text" name="criteres_nom[]" placeholder="Nom du critère (ex: Clarté)" style="flex:2;padding:7px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px">
                <input type="number" name="criteres_points[]" placeholder="Points max" min="0" step="0.5" style="width:100px;padding:7px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px">
                <button type="button" onclick="this.parentElement.remove()" style="padding:6px;background:none;border:none;cursor:pointer;color:#dc2626"><i class="ti ti-x"></i></button>
              </div>
            </div>
            <button type="button" onclick="addRubriqueRow()" class="btn-outline btn-sm" style="margin-top:4px">
              <i class="ti ti-plus"></i> Ajouter un critère
            </button>
          </div>
        </div>

      </div>

      <!-- Colonne droite - Paramètres -->
      <div>
        <div class="admin-card" style="margin-bottom:20px;position:sticky;top:80px">
          <div class="admin-card-title"><i class="ti ti-settings" style="color:var(--primary)"></i> Paramètres</div>
          <div class="form-group">
            <label>Image de couverture</label>
            <input type="file" name="image_seq" accept="image/*">
          </div>
          <div class="form-row">
            <div class="form-group"><label>Durée (min)</label><input type="number" name="duree_min" value="0" min="0"></div>
            <div class="form-group"><label>Ordre</label><input type="number" name="ordre" value="0" min="0"></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label>XP récompense</label><input type="number" name="xp_reward" value="10" min="0" max="500"></div>
          </div>
          <div class="form-group">
            <label>Date limite (deadline)</label>
            <input type="datetime-local" name="deadline">
          </div>
          <div class="form-group">
            <label class="checkbox-label"><input type="checkbox" name="actif" value="1" checked> Séquence active</label>
          </div>
          <button type="submit" class="btn-primary btn-full" style="margin-top:8px">
            <i class="ti ti-device-floppy"></i> Créer la séquence
          </button>
        </div>
      </div>
    </div>
  </form>
</div>

<script>
// Quill.js éditeurs
const toolbarOptions = [
  ['bold', 'italic', 'underline', 'strike'],
  ['blockquote', 'code-block'],
  [{ 'header': [1, 2, 3, false] }],
  [{ 'list': 'ordered'}, { 'list': 'bullet' }],
  [{ 'indent': '-1'}, { 'indent': '+1' }],
  [{ 'color': [] }, { 'background': [] }],
  [{ 'align': [] }],
  ['link', 'image'],
  ['clean']
];

const quillTexte = new Quill('#editor-texte', {
  theme: 'snow',
  modules: { toolbar: toolbarOptions },
  placeholder: 'Écris le contenu de la leçon...'
});

const quillEbook = new Quill('#editor-ebook', {
  theme: 'snow',
  modules: { toolbar: toolbarOptions },
  placeholder: 'Écris le contenu de ton eBook...'
});

// Gestion upload image dans Quill
function imageUploadHandler(quillInstance) {
  const input = document.createElement('input');
  input.setAttribute('type', 'file');
  input.setAttribute('accept', 'image/*');
  input.click();
  input.onchange = async () => {
    const file = input.files[0];
    if (!file) return;
    const fd = new FormData();
    fd.append('file', file);
    try {
      const res = await fetch('<?= SITE_URL ?>/admin/upload_image.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.location) {
        const range = quillInstance.getSelection();
        quillInstance.insertEmbed(range.index, 'image', data.location);
      } else {
        alert('Erreur upload: ' + (data.error || 'Inconnu'));
      }
    } catch(e) { alert('Erreur réseau lors de l'upload'); }
  };
}

quillTexte.getModule('toolbar').addHandler('image', () => imageUploadHandler(quillTexte));
quillEbook.getModule('toolbar').addHandler('image', () => imageUploadHandler(quillEbook));

// Avant soumission du formulaire - récupérer le HTML de Quill
document.getElementById('seqForm').addEventListener('submit', function() {
  const activeType = document.querySelector('input[name="type_contenu"]:checked').value;
  if (activeType === 'texte') {
    document.getElementById('contenu-riche-texte').value = quillTexte.root.innerHTML;
  } else if (activeType === 'ebook') {
    document.getElementById('contenu-riche-ebook').value = quillEbook.root.innerHTML;
  }
});

function switchType(type) {
  document.querySelectorAll('.type-panel').forEach(p => p.style.display = 'none');
  const panel = document.getElementById('panel-' + type);
  if (panel) panel.style.display = 'block';
}

function addRubriqueRow() {
  const wrap = document.getElementById('rubrique-rows');
  const div = document.createElement('div');
  div.className = 'rubrique-row';
  div.style.cssText = 'display:flex;gap:8px;align-items:center;margin-bottom:8px';
  div.innerHTML = '<input type="text" name="criteres_nom[]" placeholder="Nom du critère" style="flex:2;padding:7px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px">'
    + '<input type="number" name="criteres_points[]" placeholder="Points max" min="0" step="0.5" style="width:100px;padding:7px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px">'
    + '<button type="button" onclick="this.parentElement.remove()" style="padding:6px;background:none;border:none;cursor:pointer;color:#dc2626"><i class="ti ti-x"></i></button>';
  wrap.appendChild(div);
}
</script>
</body>
</html>