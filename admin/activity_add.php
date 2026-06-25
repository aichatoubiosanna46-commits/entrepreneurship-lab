<?php
// admin/activity_add.php — Créer/modifier une activité (devoir noté, exercice, auto-évaluation...)
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();

$pdo   = getPDO();
$seqId = (int)($_GET['sequence_id'] ?? $_POST['sequence_id'] ?? 0);
$id    = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if (!$seqId) { header('Location: '.SITE_URL.'/admin/courses.php'); exit; }

$seq = $pdo->prepare(
    'SELECT s.*, m.titre as module_titre, m.id as module_id, m.course_id, c.titre as course_titre
     FROM sequences s JOIN modules m ON m.id = s.module_id JOIN courses c ON c.id = m.course_id WHERE s.id = ?'
);
$seq->execute([$seqId]);
$seq = $seq->fetch();
if (!$seq) { http_response_code(404); die('Séquence introuvable.'); }

$activity = ['titre'=>'','type'=>'exercice','consigne'=>'','note_max'=>100,'bareme'=>'','actif'=>1];
$boutonsBrut = '';
$reponseLibre = false;
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM activities WHERE id = ? AND sequence_id = ?');
    $stmt->execute([$id, $seqId]);
    $found = $stmt->fetch();
    if ($found) {
        $activity = $found;
        if ($activity['options_json']) {
            $opts = json_decode($activity['options_json'], true) ?: [];
            $reponseLibre = !empty($opts['reponse_libre']);
            foreach (($opts['boutons'] ?? []) as $b) {
                $boutonsBrut .= $b . ' => ' . ($opts['messages'][$b] ?? '') . "\n";
            }
        }
    }
}

$rappelActiviteId = null;
if ($activity['options_json'] ?? null) {
    $optsExisting = is_array($activity['options_json']) ? $activity['options_json'] : json_decode($activity['options_json'], true);
    $rappelActiviteId = $optsExisting['rappel_activite_id'] ?? null;
}

$autresActivites = $pdo->prepare(
    "SELECT a.id, a.titre, s.titre as sequence_titre FROM activities a
     JOIN sequences s ON s.id = a.sequence_id
     WHERE a.id != ?
     ORDER BY s.ordre ASC, a.id ASC"
);
$autresActivites->execute([$id ?: 0]);
$autresActivites = $autresActivites->fetchAll();

$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCSRF();

    $titre    = trim($_POST['titre']    ?? '');
    $type     = $_POST['type']          ?? 'exercice';
    $consigne = trim($_POST['consigne'] ?? '');
    $noteMax  = ($_POST['note_max'] ?? '') !== '' ? (int)$_POST['note_max'] : null;
    $bareme   = trim($_POST['bareme']   ?? '');
    $actif    = isset($_POST['actif']) ? 1 : 0;

    if (!$titre)    $erreurs[] = 'Le titre est requis.';
    if (!$consigne) $erreurs[] = 'La consigne est requise.';

    $fichier = $activity['fichier'] ?? null;
    if (!empty($_FILES['fichier']['name'])) {
        $res = uploadFichier($_FILES['fichier'], 'activities');
        if (!$res) $erreurs[] = 'Fichier invalide (max 10Mo).';
        else $fichier = $res;
    }

    $optionsData = [];
    if ($type === 'auto_evaluation') {
        $boutons = []; $messages = [];
        foreach (preg_split('/\r?\n/', trim($_POST['boutons_brut'] ?? '')) as $ligne) {
            if (!trim($ligne)) continue;
            $parts = preg_split('/=>/', $ligne, 2);
            $label = trim($parts[0] ?? '');
            $msg   = trim($parts[1] ?? '');
            if ($label === '') continue;
            $boutons[] = $label;
            $messages[$label] = $msg;
        }
        $optionsData['boutons'] = $boutons;
        $optionsData['messages'] = $messages;
        $optionsData['reponse_libre'] = isset($_POST['reponse_libre']);
    }
    $rappelId = (int)($_POST['rappel_activite_id'] ?? 0);
    if ($rappelId) $optionsData['rappel_activite_id'] = $rappelId;
    $optionsJson = $optionsData ? json_encode($optionsData, JSON_UNESCAPED_UNICODE) : null;

    if (empty($erreurs)) {
        if ($id) {
            $pdo->prepare(
                'UPDATE activities SET titre=?, type=?, consigne=?, fichier=?, note_max=?, bareme=?, options_json=?, actif=? WHERE id=?'
            )->execute([$titre, $type, $consigne, $fichier, $noteMax, $bareme ?: null, $optionsJson, $actif, $id]);
            redirect(SITE_URL.'/admin/activities.php?sequence_id='.$seqId, 'Activité mise à jour !', 'success');
        } else {
            $pdo->prepare(
                'INSERT INTO activities (sequence_id, titre, type, consigne, fichier, note_max, bareme, options_json, actif)
                 VALUES (?,?,?,?,?,?,?,?,?)'
            )->execute([$seqId, $titre, $type, $consigne, $fichier, $noteMax, $bareme ?: null, $optionsJson, $actif]);
            redirect(SITE_URL.'/admin/activities.php?sequence_id='.$seqId, 'Activité créée !', 'success');
        }
    }
    $activity = array_merge($activity, $_POST);
    $boutonsBrut = $_POST['boutons_brut'] ?? $boutonsBrut;
    $reponseLibre = isset($_POST['reponse_libre']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $id ? 'Modifier' : 'Ajouter' ?> une activité</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/dashboard.css">
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<div class="admin-content">
  <div class="admin-topbar">
    <div>
      <h1 class="admin-page-title"><?= $id ? 'Modifier' : 'Ajouter' ?> une activité</h1>
      <p class="admin-page-sub">
        <a href="<?= SITE_URL ?>/admin/activities.php?sequence_id=<?= $seqId ?>">← <?= h($seq['titre']) ?></a>
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
    <input type="hidden" name="sequence_id" value="<?= $seqId ?>">
    <?php if ($id): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

    <div class="admin-two-col" style="align-items:start">
      <div style="display:flex;flex-direction:column;gap:16px">
        <div class="admin-card">
          <h2 class="admin-card-title">Informations</h2>
          <div class="form-group">
            <label for="titre">Titre *</label>
            <input type="text" id="titre" name="titre" value="<?= h($activity['titre']) ?>" required>
          </div>
          <div class="form-group">
            <label for="type">Type d'activité</label>
            <select id="type" name="type" onchange="toggleType(this.value)">
              <option value="exercice"         <?= $activity['type']==='exercice'?'selected':'' ?>>Exercice</option>
              <option value="devoir"           <?= $activity['type']==='devoir'?'selected':'' ?>>Devoir noté (soumission + correction)</option>
              <option value="cas_pratique"     <?= $activity['type']==='cas_pratique'?'selected':'' ?>>Cas pratique</option>
              <option value="travail_pratique" <?= $activity['type']==='travail_pratique'?'selected':'' ?>>Travail pratique</option>
              <option value="auto_evaluation"  <?= $activity['type']==='auto_evaluation'?'selected':'' ?>>Auto-évaluation (boutons + message conditionnel)</option>
            </select>
          </div>
          <div class="form-group">
            <label for="consigne">Consigne / question *</label>
            <textarea id="consigne" name="consigne" rows="5" required><?= h($activity['consigne']) ?></textarea>
          </div>
        </div>

        <div class="admin-card" id="card-auto-eval" style="display:none">
          <h2 class="admin-card-title">Boutons d'auto-évaluation</h2>
          <p style="font-size:12px;color:var(--text-muted);margin-bottom:10px">
            Un bouton par ligne, format : <code>Label => message affiché si choisi</code>
          </p>
          <div class="form-group">
            <textarea name="boutons_brut" rows="5" placeholder="Oui, ça m'est arrivé => Parfait. C'est de l'entrepreneuriat informel !
Non, jamais vraiment => Pas de problème, tu es exactement là où il faut être.
J'y réfléchis => C'est déjà une bonne question, continue !"><?= h($boutonsBrut) ?></textarea>
          </div>
          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" name="reponse_libre" value="1" <?= $reponseLibre ? 'checked' : '' ?>>
              <span>Ajouter un champ de réponse libre (sauvegardée, rappelable plus tard)</span>
            </label>
          </div>
        </div>

        <div class="admin-card" id="card-devoir">
          <h2 class="admin-card-title">Notation</h2>
          <div class="form-row">
            <div class="form-group">
              <label for="note_max">Note maximale</label>
              <input type="number" id="note_max" name="note_max" min="0" value="<?= h((string)($activity['note_max'] ?? 100)) ?>">
            </div>
          </div>
          <div class="form-group">
            <label for="bareme">Grille de notation (visible par l'admin lors de la correction)</label>
            <textarea id="bareme" name="bareme" rows="4"><?= h($activity['bareme'] ?? '') ?></textarea>
          </div>
        </div>
      </div>

      <div style="display:flex;flex-direction:column;gap:16px">
        <div class="admin-card">
          <h2 class="admin-card-title">Fichier joint (consigne)</h2>
          <?php if (!empty($activity['fichier'])): ?>
            <p style="font-size:12px;color:var(--text-muted);margin-bottom:8px">Fichier actuel : <?= h(basename($activity['fichier'])) ?></p>
          <?php endif; ?>
          <input type="file" name="fichier">
        </div>

        <div class="admin-card">
          <h2 class="admin-card-title">Options</h2>
          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" name="actif" value="1" <?= !isset($activity['actif']) || $activity['actif'] ? 'checked' : '' ?>>
              <span>Visible pour les apprenants</span>
            </label>
          </div>
          <div class="form-group">
            <label for="rappel_activite_id">Rappel dynamique</label>
            <select id="rappel_activite_id" name="rappel_activite_id">
              <option value="">— Aucun —</option>
              <?php foreach ($autresActivites as $a): ?>
                <option value="<?= $a['id'] ?>" <?= ((int)$rappelActiviteId === (int)$a['id']) ? 'selected' : '' ?>>
                  <?= h($a['sequence_titre'] . ' — ' . $a['titre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <small style="color:var(--text-muted);font-size:11px">Si choisi, la réponse donnée par l'apprenant à cette activité antérieure sera rappelée ici.</small>
          </div>
        </div>

        <button type="submit" class="btn-primary btn-full">
          <i class="ti ti-device-floppy"></i> Enregistrer
        </button>
      </div>
    </div>
  </form>
</div>

<script>
function toggleType(val) {
  document.getElementById('card-auto-eval').style.display = (val === 'auto_evaluation') ? '' : 'none';
  document.getElementById('card-devoir').style.display     = (val === 'auto_evaluation') ? 'none' : '';
}
toggleType(document.getElementById('type').value);
</script>
<script src="<?= SITE_URL ?>/assets/js/dashboard.js"></script>
</body>
</html>
