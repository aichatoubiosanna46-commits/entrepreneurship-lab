<?php
// admin/course_add.php — Création d'un cours (version complète)
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();

$pdo        = getPDO();
$categories = $pdo->query('SELECT * FROM categories ORDER BY nom')->fetchAll();
$erreurs    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCSRF();

    // ── Informations générales
    $titre          = trim($_POST['titre']          ?? '');
    $sous_titre     = trim($_POST['sous_titre']     ?? '');
    $description    = trim($_POST['description']    ?? '');
    $category_id    = (int)($_POST['category_id']   ?? 0);
    $niveau         = $_POST['niveau']              ?? 'debutant';
    $langue         = trim($_POST['langue']         ?? 'Français');
    $duree_heures   = (float)($_POST['duree_heures'] ?? 0);
    $video_intro    = trim($_POST['video_intro']    ?? '');

    // ── Publication & accès
    $type           = $_POST['type']   ?? 'gratuit';
    $tarif          = $_POST['tarif']  ?? 'decouverte';
    $prix           = (float)($_POST['prix'] ?? 0);
    $statut         = $_POST['statut'] ?? 'brouillon';
    $date_publication = trim($_POST['date_publication'] ?? '');
    $actif          = isset($_POST['actif'])       ? 1 : 0;
    $certificat     = isset($_POST['certificat'])  ? 1 : 0;
    $quiz_final     = isset($_POST['quiz_final'])  ? 1 : 0;
    $note_min       = (int)($_POST['note_min']     ?? 70);

    // ── Validation
    if (!$titre)       $erreurs[] = 'Le titre est requis.';
    if (!$category_id) $erreurs[] = 'Veuillez choisir une catégorie.';
    if ($type === 'payant' && $prix <= 0) $erreurs[] = 'Indiquez un prix supérieur à 0 pour un cours payant.';

    // ── Upload miniature
    $miniature = null;
    if (!empty($_FILES['miniature']['name'])) {
        $miniature = uploadImage($_FILES['miniature'], 'courses');
        if (!$miniature) $erreurs[] = 'Image invalide (JPG/PNG/WEBP, max 2Mo).';
    }

    if (empty($erreurs)) {
        $slugBase = slugUnique($pdo, 'courses', 'slug', slug($titre));
        $stmt = $pdo->prepare(
            'INSERT INTO courses
             (category_id, titre, slug, description, miniature, video_intro,
              niveau, type, tarif, prix, duree_heures, certificat,
              actif, statut, ordre, created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,0,?)'
        );
        $stmt->execute([
            $category_id, $titre, $slugBase, $description ?: null,
            $miniature, $video_intro ?: null,
            $niveau, $type, $tarif,
            $type === 'gratuit' ? 0 : $prix,
            $duree_heures ?: null,
            $certificat, $actif, $statut,
            $_SESSION['admin_id']
        ]);
        $newId = $pdo->lastInsertId();
        redirect(
            SITE_URL . '/admin/modules.php?course_id=' . $newId,
            'Cours créé avec succès ! Ajoutez maintenant les modules.',
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
<title>Ajouter un cours — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/dashboard.css">
<style>
/* ── Layout ── */
.ca-grid          { display:grid; grid-template-columns:1fr 360px; gap:24px; align-items:start; }
.ca-col           { display:flex; flex-direction:column; gap:20px; }

/* ── Cards ── */
.ca-card          { background:var(--surface,#fff); border:1px solid var(--border,#e5e7eb);
                    border-radius:12px; padding:24px; }
.ca-card-title    { font-size:14px; font-weight:600; color:var(--text-muted,#6b7280);
                    text-transform:uppercase; letter-spacing:.06em; margin:0 0 20px; }

/* ── Sections internes ── */
.ca-section-label { font-size:12px; font-weight:600; color:var(--text-muted,#6b7280);
                    text-transform:uppercase; letter-spacing:.06em;
                    margin:24px 0 12px; padding-bottom:8px;
                    border-bottom:1px solid var(--border,#e5e7eb); }
.ca-section-label:first-child { margin-top:0; }

/* ── Form helpers ── */
.form-row-3       { display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; }
.form-row-2       { display:grid; grid-template-columns:1fr 1fr; gap:12px; }

/* ── Toggle prix ── */
#prix-group[hidden] { display:none; }

/* ── Certificat box ── */
.cert-box         { background:var(--primary-light,#f5f3ff); border:1px solid var(--primary,#534AB7);
                    border-radius:10px; padding:16px; }
.cert-box .form-group { margin-bottom:0; }

/* ── Upload zone ── */
.upload-zone      { border:2px dashed var(--border,#e5e7eb); border-radius:10px;
                    padding:24px 16px; text-align:center; cursor:pointer; transition:.2s;
                    min-height:120px; display:flex; flex-direction:column;
                    align-items:center; justify-content:center; gap:6px; }
.upload-zone:hover { border-color:var(--primary,#534AB7); background:var(--primary-light,#f5f3ff); }
#preview-img      { display:none; width:100%; border-radius:8px; margin-top:10px; object-fit:cover; max-height:160px; }

/* ── Breadcrumb ── */
.breadcrumb       { display:flex; align-items:center; gap:8px; font-size:13px; color:var(--text-muted,#6b7280); margin-bottom:4px; }
.breadcrumb a     { color:var(--primary,#534AB7); text-decoration:none; }
.breadcrumb a:hover { text-decoration:underline; }

/* ── Submit btn ── */
.btn-submit       { width:100%; padding:13px; font-size:15px; font-weight:600;
                    border:none; border-radius:10px; cursor:pointer;
                    background:var(--primary,#534AB7); color:#fff;
                    display:flex; align-items:center; justify-content:center; gap:8px;
                    transition:.2s; }
.btn-submit:hover { background:var(--primary-dark,#3d369a); }

@media (max-width:900px) {
  .ca-grid        { grid-template-columns:1fr; }
  .form-row-3     { grid-template-columns:1fr 1fr; }
}
@media (max-width:600px) {
  .form-row-3, .form-row-2 { grid-template-columns:1fr; }
}
</style>
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<div class="admin-content">

  <!-- ── Topbar ── -->
  <div class="admin-topbar">
    <div>
      <nav class="breadcrumb">
        <a href="<?= SITE_URL ?>/admin/">Tableau de bord</a>
        <i class="ti ti-chevron-right" style="font-size:12px"></i>
        <a href="<?= SITE_URL ?>/admin/courses.php">Cours</a>
        <i class="ti ti-chevron-right" style="font-size:12px"></i>
        <span>Nouveau cours</span>
      </nav>
      <h1 class="admin-page-title">Créer un nouveau cours</h1>
    </div>
    <a href="<?= SITE_URL ?>/admin/courses.php" class="btn-outline">
      <i class="ti ti-arrow-left"></i> Retour
    </a>
  </div>

  <!-- ── Erreurs ── -->
  <?php if (!empty($erreurs)): ?>
  <div class="alert alert-error" style="margin-bottom:20px">
    <i class="ti ti-alert-circle"></i>
    <div><?php foreach ($erreurs as $e): ?><div><?= h($e) ?></div><?php endforeach; ?></div>
  </div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

    <div class="ca-grid">

      <!-- ══════════════════════════════════════
           COLONNE GAUCHE
           ══════════════════════════════════════ -->
      <div class="ca-col">

        <!-- ── Informations générales ── -->
        <div class="ca-card">
          <p class="ca-card-title"><i class="ti ti-info-circle"></i> Informations générales</p>

          <div class="form-group">
            <label for="titre">Titre du cours <span style="color:red">*</span></label>
            <input type="text" id="titre" name="titre"
                   value="<?= h($_POST['titre'] ?? '') ?>"
                   placeholder="Ex : Lancer sa startup au Bénin" required>
          </div>

          <div class="form-group">
            <label for="sous_titre">Sous-titre</label>
            <input type="text" id="sous_titre" name="sous_titre"
                   value="<?= h($_POST['sous_titre'] ?? '') ?>"
                   placeholder="Une accroche courte et percutante">
          </div>

          <div class="form-group">
            <label for="description">Description complète</label>
            <textarea id="description" name="description" rows="5"
                      placeholder="Objectifs du cours, ce que l'apprenant va apprendre..."><?= h($_POST['description'] ?? '') ?></textarea>
          </div>

          <!-- Catégorie · Niveau · Langue -->
          <div class="form-row-3">
            <div class="form-group">
              <label for="category_id">Catégorie <span style="color:red">*</span></label>
              <select id="category_id" name="category_id" required>
                <option value="">— Choisir —</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= $cat['id'] ?>"
                    <?= (($_POST['category_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>>
                    <?= h($cat['nom']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="niveau">Niveau</label>
              <select id="niveau" name="niveau">
                <?php foreach (['debutant'=>'Débutant','intermediaire'=>'Intermédiaire','avance'=>'Avancé'] as $v=>$l): ?>
                  <option value="<?= $v ?>" <?= (($_POST['niveau'] ?? 'debutant') === $v) ? 'selected' : '' ?>><?= $l ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="langue">Langue</label>
              <select id="langue" name="langue">
                <?php foreach (['Français','Anglais','Fon','Yoruba','Dendi'] as $lg): ?>
                  <option value="<?= $lg ?>" <?= (($_POST['langue'] ?? 'Français') === $lg) ? 'selected' : '' ?>><?= $lg ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <!-- Durée · Vidéo intro -->
          <div class="form-row-2">
            <div class="form-group">
              <label for="duree_heures"><i class="ti ti-clock" style="font-size:14px"></i> Durée estimée (heures)</label>
              <input type="number" id="duree_heures" name="duree_heures"
                     min="0" step="0.5" value="<?= h($_POST['duree_heures'] ?? '') ?>"
                     placeholder="Ex : 4.5">
            </div>
            <div class="form-group">
              <label for="video_intro"><i class="ti ti-brand-youtube" style="font-size:14px"></i> Vidéo de présentation (URL)</label>
              <input type="url" id="video_intro" name="video_intro"
                     value="<?= h($_POST['video_intro'] ?? '') ?>"
                     placeholder="https://youtube.com/watch?v=...">
            </div>
          </div>
        </div>

        <!-- ── Certificat & Quiz final ── -->
        <div class="ca-card">
          <p class="ca-card-title"><i class="ti ti-certificate"></i> Certification</p>

          <label class="checkbox-label" style="margin-bottom:14px">
            <input type="checkbox" name="certificat" value="1" id="chk-cert"
                   <?= !empty($_POST['certificat']) ? 'checked' : '' ?>
                   onchange="toggleCertOptions()">
            <span>Ce cours génère un certificat à la complétion</span>
          </label>

          <div id="cert-options" style="<?= empty($_POST['certificat']) ? 'display:none' : '' ?>">
            <div class="cert-box">
              <div class="form-row-2" style="gap:12px">
                <div class="form-group" style="margin-bottom:0">
                  <label class="checkbox-label">
                    <input type="checkbox" name="quiz_final" value="1"
                           <?= !empty($_POST['quiz_final']) ? 'checked' : '' ?>>
                    <span>Exiger la réussite du quiz final</span>
                  </label>
                </div>
                <div class="form-group" style="margin-bottom:0">
                  <label for="note_min">Note minimale (%)</label>
                  <input type="number" id="note_min" name="note_min"
                         min="0" max="100" value="<?= h($_POST['note_min'] ?? '70') ?>"
                         placeholder="70">
                </div>
              </div>
              <p style="font-size:12px;color:var(--text-muted);margin:10px 0 0">
                <i class="ti ti-info-circle"></i>
                Le certificat sera généré automatiquement quand l'apprenant atteint 100% ET réussit le quiz (si activé).
              </p>
            </div>
          </div>
        </div>

      </div><!-- /col gauche -->

      <!-- ══════════════════════════════════════
           COLONNE DROITE
           ══════════════════════════════════════ -->
      <div class="ca-col">

        <!-- ── Publication ── -->
        <div class="ca-card">
          <p class="ca-card-title"><i class="ti ti-send"></i> Publication</p>

          <div class="form-group">
            <label for="statut">Statut</label>
            <select id="statut" name="statut">
              <?php foreach (['brouillon'=>'📝 Brouillon','publie'=>'✅ Publié','archive'=>'📦 Archivé'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= (($_POST['statut'] ?? 'brouillon') === $v) ? 'selected' : '' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="date_publication"><i class="ti ti-calendar" style="font-size:14px"></i> Date de publication</label>
            <input type="datetime-local" id="date_publication" name="date_publication"
                   value="<?= h($_POST['date_publication'] ?? '') ?>">
            <small style="color:var(--text-muted);font-size:11px">Laisser vide = publication immédiate</small>
          </div>

          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" name="actif" value="1"
                     <?= (!isset($_POST['actif']) || $_POST['actif']) ? 'checked' : '' ?>>
              <span>Visible sur le site</span>
            </label>
          </div>
        </div>

        <!-- ── Accès & prix ── -->
        <div class="ca-card">
          <p class="ca-card-title"><i class="ti ti-lock"></i> Accès & tarification</p>

          <!-- Tarif / Parcours -->
          <div class="form-group">
            <label for="tarif">
              <i class="ti ti-layers-subtract" style="font-size:14px"></i>
              Parcours (tarif) <span style="color:red">*</span>
            </label>
            <select id="tarif" name="tarif" onchange="onTarifChange(this.value)">
              <option value="decouverte"    <?= (($_POST['tarif']??'decouverte')==='decouverte')    ?'selected':'' ?>>💡 Découverte — Gratuit</option>
              <option value="business_plan" <?= (($_POST['tarif']??'')==='business_plan')?'selected':'' ?>>📊 Business Plan — 5 000 FCFA</option>
              <option value="lancement"     <?= (($_POST['tarif']??'')==='lancement')    ?'selected':'' ?>>🚀 Lancement — 8 000 FCFA</option>
            </select>
            <small style="color:var(--text-muted);font-size:11px">
              Ce cours sera visible uniquement pour les utilisateurs ayant ce parcours.
            </small>
          </div>

          <!-- Indicateur visuel du tarif -->
          <div id="tarif-info" style="border-radius:9px;padding:12px 14px;font-size:12px;margin-bottom:14px;transition:.2s;"></div>

          <div class="form-group">
            <label for="type">Type d'accès</label>
            <select id="type" name="type" onchange="togglePrix(this.value)">
              <option value="gratuit" <?= (($_POST['type'] ?? 'gratuit') === 'gratuit') ? 'selected' : '' ?>>🆓 Gratuit</option>
              <option value="payant"  <?= (($_POST['type'] ?? '') === 'payant')         ? 'selected' : '' ?>>💳 Payant</option>
            </select>
          </div>

          <div class="form-group" id="prix-group"
               <?= (($_POST['type'] ?? 'gratuit') !== 'payant') ? 'hidden' : '' ?>>
            <label for="prix">Prix (FCFA) <span style="color:red">*</span></label>
            <input type="number" id="prix" name="prix"
                   min="0" step="500" value="<?= h($_POST['prix'] ?? '5000') ?>"
                   placeholder="5000">
          </div>
        </div>

        <!-- ── Miniature ── -->
        <div class="ca-card">
          <p class="ca-card-title"><i class="ti ti-photo"></i> Image de couverture</p>

          <div class="upload-zone" id="upload-zone"
               onclick="document.getElementById('miniature').click()">
            <i class="ti ti-photo-plus" style="font-size:32px;color:var(--text-muted)"></i>
            <p style="font-size:13px;color:var(--text-muted);margin:0">
              Cliquer pour choisir une image
            </p>
            <small style="color:var(--text-muted)">JPG, PNG, WEBP — max 2 Mo</small>
            <img id="preview-img" alt="Aperçu miniature">
          </div>
          <input type="file" id="miniature" name="miniature"
                 accept="image/jpeg,image/png,image/webp"
                 style="display:none" onchange="previewImg(this)">
        </div>

        <!-- ── Bouton ── -->
        <button type="submit" class="btn-submit">
          <i class="ti ti-device-floppy"></i> Enregistrer le cours
        </button>
        <p style="font-size:12px;color:var(--text-muted);text-align:center;margin-top:-8px">
          Après création, vous serez redirigé vers les modules du cours.
        </p>

      </div><!-- /col droite -->

    </div><!-- /ca-grid -->
  </form>
</div><!-- /admin-content -->

<script src="<?= SITE_URL ?>/assets/js/dashboard.js"></script>
<script>
// ── Toggle prix
function togglePrix(val) {
  const g = document.getElementById('prix-group');
  val === 'payant' ? g.removeAttribute('hidden') : g.setAttribute('hidden', '');
}

// ── Info tarif
const tarifData = {
  decouverte:    { label:'💡 Découverte',    color:'#EAF3DE', border:'#97C459', text:'#27500A', hint:'Visible par tous sans paiement. Contenu d\'introduction.', type:'gratuit' },
  business_plan: { label:'📊 Business Plan', color:'#FEF3C7', border:'#F5C518', text:'#92400E', hint:'Réservé aux utilisateurs ayant souscrit au plan 5 000 FCFA.', type:'payant' },
  lancement:     { label:'🚀 Lancement',     color:'#D1FAE5', border:'#10b981', text:'#065F46', hint:'Réservé aux utilisateurs du plan complet 8 000 FCFA.', type:'payant' },
};

function onTarifChange(val) {
  const d   = tarifData[val];
  const box = document.getElementById('tarif-info');
  box.style.background   = d.color;
  box.style.border       = '1px solid ' + d.border;
  box.style.color        = d.text;
  box.innerHTML = `<strong>${d.label}</strong><br>${d.hint}`;
  // Suggérer le type
  const typeEl = document.getElementById('type');
  typeEl.value = d.type;
  togglePrix(d.type);
}

// ── Toggle options certificat
function toggleCertOptions() {
  const show = document.getElementById('chk-cert').checked;
  document.getElementById('cert-options').style.display = show ? '' : 'none';
}

// ── Aperçu image
function previewImg(input) {
  if (!input.files[0]) return;
  const reader = new FileReader();
  reader.onload = e => {
    const img  = document.getElementById('preview-img');
    const zone = document.getElementById('upload-zone');
    img.src   = e.target.result;
    img.style.display = 'block';
    zone.querySelector('i').style.display    = 'none';
    zone.querySelectorAll('p,small').forEach(el => el.style.display = 'none');
  };
  reader.readAsDataURL(input.files[0]);
}

// Init au chargement
document.addEventListener('DOMContentLoaded', () => {
  onTarifChange(document.getElementById('tarif').value);
});
</script>
</body>
</html>