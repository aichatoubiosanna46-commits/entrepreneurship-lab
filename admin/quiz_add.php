<?php
// admin/quiz_add.php — Créer un quiz lié à une séquence
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();

$pdo    = getPDO();
$seqId  = (int)($_GET['sequence_id'] ?? 0);
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre       = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $seuil       = (int)($_POST['seuil_reussite'] ?? 60);
    $actif       = isset($_POST['actif']) ? 1 : 0;
    $seqIdPost   = (int)($_POST['sequence_id'] ?? 0);
    $qtextes     = $_POST['q_texte']       ?? [];
    $qtypes      = $_POST['q_type']        ?? [];
    $qpoints     = $_POST['q_points']      ?? [];
    $qexplics    = $_POST['q_explication'] ?? [];
    $areponses   = $_POST['a_texte']       ?? [];
    $acorrects   = $_POST['a_correct']     ?? [];
    $afeedbacks  = $_POST['a_feedback']    ?? [];

    if (!$titre) {
        $erreur = 'Le titre est requis.';
    } else {
        $dureeMin   = (int)($_POST['duree_minutes'] ?? 0);
        $tentMax    = (int)($_POST['tentatives_max'] ?? 0);
        $aleatoire  = isset($_POST['ordre_aleatoire']) ? 1 : 0;
        $pdo->prepare(
            'INSERT INTO quizzes (sequence_id, module_id, titre, description, seuil_reussite, actif, duree_minutes, tentatives_max, ordre_aleatoire) VALUES (?,NULL,?,?,?,?,?,?,?)'
        )->execute([$seqIdPost ?: null, $titre, $description, $seuil, $actif, $dureeMin ?: null, $tentMax ?: null, $aleatoire]);
        $quizId = (int)$pdo->lastInsertId();

        foreach ($qtextes as $qi => $qtext) {
            $qtext = trim($qtext);
            if (!$qtext) continue;
            $pdo->prepare(
                'INSERT INTO questions (quiz_id, question, type, ordre, points, explication) VALUES (?,?,?,?,?,?)'
            )->execute([
                $quizId, $qtext,
                $qtypes[$qi] ?? 'choix_unique',
                $qi,
                (int)($qpoints[$qi] ?? 1),
                trim($qexplics[$qi] ?? '')
            ]);
            $qId = (int)$pdo->lastInsertId();

            $repTextes   = $areponses[$qi]  ?? [];
            $repCorrects = $acorrects[$qi]  ?? [];
            $repFeedback = $afeedbacks[$qi] ?? [];

            foreach ($repTextes as $ai => $atext) {
                $atext = trim($atext);
                if (!$atext) continue;
                $isCorrect = isset($repCorrects[$ai]) ? 1 : 0;
                $pdo->prepare(
                    'INSERT INTO answers (question_id, texte, est_correct, ordre, feedback) VALUES (?,?,?,?,?)'
                )->execute([
                    $qId, $atext, $isCorrect, $ai,
                    trim($repFeedback[$ai] ?? '')
                ]);
            }
        }

        // Lier le quiz à la séquence automatiquement
        if ($seqIdPost) {
            $pdo->prepare('UPDATE sequences SET quiz_id = ? WHERE id = ?')
                ->execute([$quizId, $seqIdPost]);
        }

        header('Location: ' . SITE_URL . '/admin/quizzes.php');
        exit;
    }
}

// Récupérer toutes les séquences avec leur module et cours
$sequences = $pdo->query(
    'SELECT s.id, s.titre as seq_titre,
            m.titre as module_titre,
            c.titre as cours_titre
     FROM sequences s
     JOIN modules m ON m.id = s.module_id
     JOIN courses c ON c.id = m.course_id
     WHERE s.actif = 1
     ORDER BY c.titre, m.ordre, s.ordre'
)->fetchAll();

$currentPage  = 'quiz_add.php';
$NB_QUESTIONS = 5;
$NB_REPONSES  = 4;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Créer un quiz — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
<style>
.q-block{background:#f9fafb;border:1.5px solid #e5e7eb;border-radius:14px;padding:20px;margin-bottom:16px}
.q-num{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;background:#F59E0B;color:#1C1917;border-radius:50%;font-size:12px;font-weight:800;margin-right:8px}
.ans-grid{display:grid;grid-template-columns:auto 1fr 1fr auto;gap:8px;align-items:center;padding:8px;background:#fff;border:1px solid #f3f4f6;border-radius:8px;margin-bottom:6px}
.fld{width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;font-family:inherit;box-sizing:border-box}
.fld:focus{outline:none;border-color:#F59E0B}
</style>
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <div>
      <div class="admin-page-title">Créer un quiz</div>
      <div class="admin-page-sub">Le quiz sera lié à une séquence — l'apprenant le fait à la fin de la séquence</div>
    </div>
    <a href="<?= SITE_URL ?>/admin/quizzes.php" class="btn-outline"><i class="ti ti-arrow-left"></i> Retour</a>
  </div>

  <?php if ($erreur): ?>
  <div class="alert alert-error"><i class="ti ti-alert-circle"></i> <?= h($erreur) ?></div>
  <?php endif; ?>

  <form method="POST" action="">

    <!-- Infos générales -->
    <div class="admin-card" style="margin-bottom:20px">
      <div class="admin-card-title"><i class="ti ti-help-circle" style="color:var(--primary)"></i> Informations du quiz</div>

      <div class="form-group">
        <label>Titre du quiz *</label>
        <input type="text" name="titre" class="fld" required placeholder="Ex: Quiz — Valider son idée">
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea name="description" class="fld" rows="2" placeholder="Instructions pour les étudiants..."></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Séquence associée</label>
          <select name="sequence_id" class="fld">
            <option value="">— Aucune séquence —</option>
            <?php foreach ($sequences as $s): ?>
            <option value="<?= $s['id'] ?>" <?= $s['id']==$seqId?'selected':'' ?>>
              <?= h($s['cours_titre']) ?> → <?= h($s['module_titre']) ?> → <?= h($s['seq_titre']) ?>
            </option>
            <?php endforeach; ?>
          </select>
          <small style="color:var(--text-muted);font-size:11px">
            Le bouton "Faire le quiz" apparaîtra à la fin de cette séquence
          </small>
        </div>
        <div class="form-group">
          <label>Seuil de réussite (%)</label>
          <input type="number" name="seuil_reussite" value="60" min="0" max="100" class="fld">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Durée limite (minutes) <small style="font-weight:400;color:#6b7280">0 = illimitée</small></label>
          <input type="number" name="duree_minutes" value="0" min="0" max="180" class="fld">
        </div>
        <div class="form-group">
          <label>Tentatives max <small style="font-weight:400;color:#6b7280">0 = illimitées</small></label>
          <input type="number" name="tentatives_max" value="0" min="0" max="99" class="fld">
        </div>
      </div>
      <div class="form-group">
        <label class="checkbox-label">
          <input type="checkbox" name="ordre_aleatoire" value="1"> Mélanger l'ordre des questions
        </label>
      </div>
      <div class="form-group">
        <label class="checkbox-label">
          <input type="checkbox" name="actif" value="1" checked> Quiz actif
        </label>
      </div>
    </div>

    <!-- Questions -->
    <div class="admin-card" style="margin-bottom:20px">
      <div class="admin-card-title">
        <i class="ti ti-list-numbers" style="color:var(--primary)"></i>
        Questions
        <small style="font-weight:400;color:var(--text-muted);margin-left:8px">— Laissez vides les questions non utilisées</small>
      </div>

      <?php for ($qi = 0; $qi < $NB_QUESTIONS; $qi++): ?>
      <div class="q-block">
        <div style="font-size:14px;font-weight:700;color:#1C1917;margin-bottom:14px">
          <span class="q-num"><?= $qi+1 ?></span> Question <?= $qi+1 ?>
        </div>

        <div class="form-group">
          <label>Texte de la question</label>
          <textarea name="q_texte[<?= $qi ?>]" class="fld" rows="2"
            placeholder="Ex: Quelle est la première étape pour valider une idée ?"></textarea>
        </div>

        <div class="form-row" style="margin-bottom:14px">
          <div class="form-group">
            <label>Type</label>
            <select name="q_type[<?= $qi ?>]" class="fld">
              <option value="choix_unique">Choix unique</option>
              <option value="choix_multiple">Choix multiple</option>
              <option value="vrai_faux">Vrai / Faux</option>
              <option value="texte_libre">Texte libre (correction manuelle)</option>
              <option value="fill_blank">Complétion de texte (remplir le trou)</option>
              <option value="correspondance">Correspondance (associer éléments)</option>
            </select>
          </div>
          <div class="form-group">
            <label>Points</label>
            <input type="number" name="q_points[<?= $qi ?>]" value="1" min="1" max="10" class="fld">
          </div>
        </div>

        <div style="font-size:12px;font-weight:600;color:#6b7280;margin-bottom:8px">
          RÉPONSES — cochez ✓ la/les bonne(s) réponse(s)
        </div>

        <?php for ($ai = 0; $ai < $NB_REPONSES; $ai++): ?>
        <div class="ans-grid">
          <div style="display:flex;flex-direction:column;align-items:center;gap:3px">
            <span style="width:24px;height:24px;background:#f3f4f6;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700">
              <?= chr(65+$ai) ?>
            </span>
            <input type="checkbox" name="a_correct[<?= $qi ?>][<?= $ai ?>]" value="1"
              style="width:16px;height:16px;accent-color:#F59E0B;cursor:pointer" title="Bonne réponse">
            <small style="font-size:9px;color:#9ca3af">Correct</small>
          </div>
          <input type="text" name="a_texte[<?= $qi ?>][<?= $ai ?>]"
            placeholder="Réponse <?= chr(65+$ai) ?>..." class="fld">
          <input type="text" name="a_feedback[<?= $qi ?>][<?= $ai ?>]"
            placeholder="Feedback si choisie (optionnel)..." class="fld" style="font-size:11px;color:#6b7280">
        </div>
        <?php endfor; ?>

        <div class="form-group" style="margin-top:12px">
          <label>💡 Explication (affichée après validation)</label>
          <textarea name="q_explication[<?= $qi ?>]" class="fld" rows="2"
            placeholder="Pourquoi cette réponse est correcte..."></textarea>
        </div>
      </div>
      <?php endfor; ?>
    </div>

    <button type="submit" class="btn-primary btn-full" style="padding:14px;font-size:15px">
      <i class="ti ti-device-floppy"></i> Créer le quiz
    </button>
  </form>
</div>
</body>
</html>