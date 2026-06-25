<?php
// admin/sequence_edit.php — Modification d'une séquence
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqInstructeurOuAdmin();

$pdo      = getPDO();
$id       = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$moduleId = (int)($_GET['module_id'] ?? $_POST['module_id'] ?? 0);
if (!sequenceAppartientInstructeur($id)) { redirect(SITE_URL . '/admin/courses.php', 'Accès refusé : cette séquence ne vous appartient pas.', 'error'); }

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

// Charger l'assignment existant (le cas échéant) + sa rubrique
$assignment = null;
$rubrique   = null;
if ($seq['type_contenu'] === 'assignment') {
    $aStmt = $pdo->prepare('SELECT * FROM assignments WHERE sequence_id = ? LIMIT 1');
    $aStmt->execute([$id]);
    $assignment = $aStmt->fetch();
    if ($assignment) {
        $rStmt = $pdo->prepare('SELECT * FROM rubriques WHERE assignment_id = ? LIMIT 1');
        $rStmt->execute([$assignment['id']]);
        $rubrique = $rStmt->fetch();
    }
}

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
        $embedCode = trim($_POST['embed_code'] ?? '');
        $pdfUrl    = trim($_POST['pdf_url']    ?? '');
        $stmt = $pdo->prepare(
            'UPDATE sequences SET titre=?, description=?, contenu=?, video_url=?, audio_url=?,
             image_seq=?, fichier_pdf=?, duree_min=?, ordre=?, actif=?, embed_code=?, pdf_url=? WHERE id=?'
        );
        $stmt->execute([
            $titre, $desc ?: null, $contenu ?: null,
            $videoUrl ?: null, $audioUrl ?: null,
            $imageSeq, $fichierPdf,
            $duree ?: null, $ordre, $actif,
            $embedCode ?: null, $pdfUrl ?: null, $id
        ]);

        // Mise à jour / création de l'assignment lié (livrable)
        if ($seq['type_contenu'] === 'assignment') {
            $assignTitre = trim($_POST['assignment_titre'] ?? $titre) ?: $titre;
            $assignType  = in_array($_POST['assignment_type'] ?? '', ['texte','fichier','video','audio']) ? $_POST['assignment_type'] : 'texte';
            $noteMax     = (float)($_POST['note_max'] ?? 20);
            $noteMin     = (float)($_POST['note_min'] ?? 10);
            $consigne    = trim($_POST['assignment_consigne'] ?? $contenu);

            if ($assignment) {
                $pdo->prepare(
                    'UPDATE assignments SET titre=?, consigne=?, type=?, note_min=?, note_max=? WHERE id=?'
                )->execute([$assignTitre, $consigne, $assignType, $noteMin, $noteMax, $assignment['id']]);
                $assignmentId = $assignment['id'];
            } else {
                $pdo->prepare(
                    'INSERT INTO assignments (sequence_id, titre, consigne, type, note_min, note_max) VALUES (?,?,?,?,?,?)'
                )->execute([$id, $assignTitre, $consigne, $assignType, $noteMin, $noteMax]);
                $assignmentId = (int)$pdo->lastInsertId();
            }

            // Rubrique : reconstruire à partir des champs soumis
            $criteresNom    = $_POST['criteres_nom'] ?? [];
            $criteresPoints = $_POST['criteres_points'] ?? [];
            $criteres = [];
            foreach ($criteresNom as $i => $nomCritere) {
                $nomCritere = trim($nomCritere);
                $pts        = (float)($criteresPoints[$i] ?? 0);
                if ($nomCritere !== '' && $pts > 0) {
                    $criteres[] = ['nom' => $nomCritere, 'points' => $pts];
                }
            }
            if (!empty($criteres)) {
                if ($rubrique) {
                    $pdo->prepare('UPDATE rubriques SET nom=?, titre=?, criteres=? WHERE id=?')
                        ->execute([$assignTitre, $assignTitre, json_encode($criteres, JSON_UNESCAPED_UNICODE), $rubrique['id']]);
                } else {
                    $pdo->prepare('INSERT INTO rubriques (assignment_id, nom, titre, criteres) VALUES (?,?,?,?)')
                        ->execute([$assignmentId, $assignTitre, $assignTitre, json_encode($criteres, JSON_UNESCAPED_UNICODE)]);
                }
            } elseif ($rubrique) {
                // Plus aucun critère soumis : supprimer la rubrique existante
                $pdo->prepare('DELETE FROM rubriques WHERE id=?')->execute([$rubrique['id']]);
            }
        }

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
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<style>
.ql-container { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 14px; }
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
            <label for="contenu">Contenu texte simple (optionnel)</label>
            <textarea id="contenu" name="contenu" rows="4"><?= h($seq['contenu'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label>Contenu riche (eBook / leçon formatée)</label>
            <div id="quill-editor" style="min-height:300px;background:#fff"></div>
            <input type="hidden" name="contenu_riche" id="contenu_riche_hidden">
            <textarea id="contenu_riche_raw" style="display:none"><?= $seq['contenu_riche'] ?? '' ?></textarea>
          </div>
          <div class="form-group">
            <label for="video_url"><i class="ti ti-video" style="color:#6C47D4"></i> URL Vidéo</label>
            <input type="url" id="video_url" name="video_url" value="<?= h($seq['video_url'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label for="audio_url"><i class="ti ti-music" style="color:#3B6D11"></i> URL Audio</label>
            <input type="url" id="audio_url" name="audio_url" value="<?= h($seq['audio_url'] ?? '') ?>">
          </div>

          <div class="form-group" style="margin-top:16px">
            <label for="embed_code"><i class="ti ti-code" style="color:#6C47D4"></i> Code embed / iframe</label>
            <textarea id="embed_code" name="embed_code" rows="3" placeholder="<iframe src='...' width='100%' height='400'></iframe>"><?= h($seq['embed_code'] ?? '') ?></textarea>
            <small style="color:var(--text-muted);font-size:11px">Coller le code embed de Genially, Google Forms, Typeform, Padlet, Miro...</small>
          </div>
          <div class="form-group">
            <label for="pdf_url"><i class="ti ti-file-type-pdf" style="color:#dc2626"></i> URL PDF à afficher</label>
            <input type="url" id="pdf_url" name="pdf_url" value="<?= h($seq['pdf_url'] ?? '') ?>" placeholder="https://...document.pdf">
            <small style="color:var(--text-muted);font-size:11px">Le PDF sera affiché directement dans la séquence (lecture sans téléchargement obligatoire)</small>
          </div>

        </div>

        <?php if ($seq['type_contenu'] === 'assignment'): ?>
        <div class="admin-card">
          <h2 class="admin-card-title"><i class="ti ti-clipboard" style="color:#6b7280"></i> Livrable / Assignment</h2>
          <div class="form-group">
            <label>Titre du livrable</label>
            <input type="text" name="assignment_titre" value="<?= h($assignment['titre'] ?? $seq['titre']) ?>">
          </div>
          <div class="form-group">
            <label>Instructions du livrable *</label>
            <textarea name="assignment_consigne" rows="5"><?= h($assignment['consigne'] ?? '') ?></textarea>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Note maximale</label>
              <input type="number" name="note_max" value="<?= h($assignment['note_max'] ?? 20) ?>" min="1" max="100"
                style="width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;font-family:inherit">
            </div>
            <div class="form-group">
              <label>Note minimale <small style="font-weight:400;color:#6b7280">(refus auto en dessous)</small></label>
              <input type="number" name="note_min" value="<?= h($assignment['note_min'] ?? 10) ?>" min="0" max="100"
                style="width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;font-family:inherit">
            </div>
          </div>
          <div class="form-group">
            <label>Type de rendu accepté</label>
            <select name="assignment_type" style="width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;font-family:inherit">
              <?php $atypes = ['texte'=>'Texte seulement','fichier'=>'Fichier (PDF, DOCX...)','video'=>'Vidéo','audio'=>'Audio (enregistrement micro)']; ?>
              <?php foreach ($atypes as $val=>$lbl): ?>
              <option value="<?= $val ?>" <?= ($assignment['type'] ?? 'texte')===$val?'selected':'' ?>><?= $lbl ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Grille de rubrique -->
          <div class="form-group" style="margin-top:16px;border-top:1px solid #e5e7eb;padding-top:14px">
            <label><i class="ti ti-table" style="color:#6C47D4"></i> Grille de rubrique <small style="font-weight:400;color:#6b7280">(optionnel)</small></label>
            <?php
            $existingCriteres = [];
            if ($rubrique && !empty($rubrique['criteres'])) {
                $existingCriteres = json_decode($rubrique['criteres'], true) ?: [];
            }
            ?>
            <div id="rubrique-rows">
              <?php if (!empty($existingCriteres)): foreach ($existingCriteres as $crit): ?>
              <div class="rubrique-row" style="display:flex;gap:8px;align-items:center;margin-bottom:8px">
                <input type="text" name="criteres_nom[]" value="<?= h($crit['nom'] ?? '') ?>" placeholder="Nom du critère" style="flex:2;padding:7px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px">
                <input type="number" name="criteres_points[]" value="<?= h($crit['points'] ?? '') ?>" placeholder="Points max" min="0" step="0.5" style="width:100px;padding:7px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px">
                <button type="button" onclick="this.parentElement.remove()" style="padding:6px;background:none;border:none;cursor:pointer;color:#dc2626"><i class="ti ti-x"></i></button>
              </div>
              <?php endforeach; else: ?>
              <div class="rubrique-row" style="display:flex;gap:8px;align-items:center;margin-bottom:8px">
                <input type="text" name="criteres_nom[]" placeholder="Nom du critère" style="flex:2;padding:7px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px">
                <input type="number" name="criteres_points[]" placeholder="Points max" min="0" step="0.5" style="width:100px;padding:7px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px">
                <button type="button" onclick="this.parentElement.remove()" style="padding:6px;background:none;border:none;cursor:pointer;color:#dc2626"><i class="ti ti-x"></i></button>
              </div>
              <?php endif; ?>
            </div>
            <button type="button" onclick="addRubriqueRowEdit()" class="btn-outline btn-sm" style="margin-top:4px">
              <i class="ti ti-plus"></i> Ajouter un critère
            </button>
          </div>
        </div>
        <?php endif; ?>
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

          <div class="form-group" style="margin-top:12px">
            <label><i class="ti ti-lock" style="color:#6b7280"></i> Mot de passe d'accès <small style="font-weight:400;color:var(--text-muted)">(optionnel)</small></label>
            <input type="text" name="mot_de_passe" value="<?= h($seq['mot_de_passe'] ?? '') ?>" placeholder="Laisser vide = pas de protection">
            <small style="color:var(--text-muted);font-size:11px">L'étudiant devra saisir ce mot de passe pour accéder à la séquence</small>
          </div>

            <label class="checkbox-label">
              <input type="checkbox" name="actif" value="1" <?= $seq['actif'] ? 'checked' : '' ?>>
              <span>Visible pour les apprenants</span>
            </label>
            <label class="checkbox-label" style="margin-top:10px">
              <input type="checkbox" name="est_optionnel" value="1" <?= ($seq['est_optionnel']??0) ? 'checked' : '' ?>>
              <span>Activité optionnelle <small style="color:var(--text-muted);font-weight:400">(ne bloque pas la progression)</small></span>
            </label>
          </div>
        </div>

        
        <div style="border-top:1px solid var(--border);padding-top:16px;margin-top:4px">
          <div style="font-size:13px;font-weight:600;color:var(--text);margin-bottom:12px"><i class="ti ti-settings"></i> Paramètres avancés</div>
          <div class="form-row">
            <div class="form-group">
              <label>XP récompense</label>
              <input type="number" name="xp_reward" value="<?= h($sequence['xp_reward'] ?? 10) ?>" min="0" max="500">
              <small style="color:var(--text-muted);font-size:11px">Points XP attribués à la complétion</small>
            </div>
            <div class="form-group">
              <label>Date limite (deadline)</label>
              <input type="datetime-local" name="deadline" value="<?= h($sequence['deadline'] ?? '') ?>">
              <small style="color:var(--text-muted);font-size:11px">Optionnel — affiche un compte à rebours</small>
            </div>
          </div>
        </div>

        
        <!-- Chapitres vidéo -->
        <div style="border-top:1px solid var(--border);padding-top:20px;margin-top:4px">
          <div style="font-size:14px;font-weight:600;color:var(--text);margin-bottom:14px">
            <i class="ti ti-movie"></i> Chapitres vidéo <small style="font-weight:400;color:var(--text-muted)">(optionnel)</small>
          </div>
          <?php
          try {
            $chapStmt = $pdo->prepare('SELECT * FROM video_chapters WHERE sequence_id = ? ORDER BY timecode ASC');
            $chapStmt->execute([$sequence['id'] ?? 0]);
            $existingChapters = $chapStmt->fetchAll();
          } catch(Exception $e) { $existingChapters = []; }
          ?>
          <div id="chapters-wrap">
            <?php foreach ($existingChapters as $ch): ?>
            <div class="chapter-row" style="display:flex;gap:8px;align-items:center;margin-bottom:8px">
              <input type="number" name="chapters_time[]" value="<?= $ch['timecode'] ?>" placeholder="Secondes" style="width:80px;padding:7px 8px;border:1px solid var(--border);border-radius:6px;font-size:13px" min="0">
              <input type="text" name="chapters_title[]" value="<?= h($ch['titre']) ?>" placeholder="Titre du chapitre" style="flex:1;padding:7px 10px;border:1px solid var(--border);border-radius:6px;font-size:13px">
              <input type="hidden" name="chapters_id[]" value="<?= $ch['id'] ?>">
              <button type="button" onclick="this.parentElement.remove()" style="padding:6px;background:none;border:none;cursor:pointer;color:#dc2626"><i class="ti ti-x"></i></button>
            </div>
            <?php endforeach; ?>
          </div>
          <button type="button" onclick="addChapter()" class="btn-outline btn-sm" style="margin-top:6px">
            <i class="ti ti-plus"></i> Ajouter un chapitre
          </button>
          <small style="display:block;margin-top:6px;color:var(--text-muted);font-size:11px">Entrez le timecode en secondes (ex: 120 = 2:00)</small>
        </div>
        <script>
        function addChapter() {
          const wrap = document.getElementById('chapters-wrap');
          const div = document.createElement('div');
          div.className = 'chapter-row';
          div.style.cssText = 'display:flex;gap:8px;align-items:center;margin-bottom:8px';
          div.innerHTML = '<input type="number" name="chapters_time[]" placeholder="Secondes" style="width:80px;padding:7px 8px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px" min="0"><input type="text" name="chapters_title[]" placeholder="Titre du chapitre" style="flex:1;padding:7px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px"><input type="hidden" name="chapters_id[]" value="0"><button type="button" onclick="this.parentElement.remove()" style="padding:6px;background:none;border:none;cursor:pointer;color:#dc2626"><i class="ti ti-x"></i></button>';
          wrap.appendChild(div);
        }
        </script>

        <button type="submit" class="btn-primary btn-full">
          <i class="ti ti-device-floppy"></i> Enregistrer les modifications
        </button>
      </div>
    </div>
  </form>
</div>

<script>
function addRubriqueRowEdit() {
  const wrap = document.getElementById('rubrique-rows');
  const div = document.createElement('div');
  div.className = 'rubrique-row';
  div.style.cssText = 'display:flex;gap:8px;align-items:center;margin-bottom:8px';
  div.innerHTML = '<input type="text" name="criteres_nom[]" placeholder="Nom du critère" style="flex:2;padding:7px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px">'
    + '<input type="number" name="criteres_points[]" placeholder="Points max" min="0" step="0.5" style="width:100px;padding:7px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px">'
    + '<button type="button" onclick="this.parentElement.remove()" style="padding:6px;background:none;border:none;cursor:pointer;color:#dc2626"><i class="ti ti-x"></i></button>';
  wrap.appendChild(div);
}
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
<script>
const toolbarOptions = [
  ['bold', 'italic', 'underline', 'strike'],
  ['blockquote'],
  [{ 'header': [1, 2, 3, false] }],
  [{ 'list': 'ordered'}, { 'list': 'bullet' }],
  [{ 'color': [] }, { 'background': [] }],
  [{ 'align': [] }],
  ['link', 'image'],
  ['clean']
];

const quill = new Quill('#quill-editor', {
  theme: 'snow',
  modules: { toolbar: toolbarOptions },
  placeholder: 'Contenu de la séquence...'
});

// Charger le contenu existant
const existingContent = document.getElementById('contenu_riche_raw').value;
if (existingContent) quill.root.innerHTML = existingContent;

// Upload image
quill.getModule('toolbar').addHandler('image', () => {
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
        const range = quill.getSelection();
        quill.insertEmbed(range ? range.index : 0, 'image', data.location);
      } else { alert('Erreur: ' + (data.error || 'Upload échoué')); }
    } catch(e) { alert('Erreur réseau'); }
  };
});

// Avant soumission - récupérer HTML
document.querySelector('form').addEventListener('submit', function() {
  document.getElementById('contenu_riche_hidden').value = quill.root.innerHTML;
});
</script>
</body>
</html>