<?php
// admin/quiz_add.php — Créer un quiz avec questions/réponses
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();
$pdo = getPDO();

// Toutes les séquences
$sequences = $pdo->query(
    'SELECT s.id, s.titre, c.titre as course_titre, m.titre as module_titre
     FROM sequences s
     JOIN modules m ON m.id = s.module_id
     JOIN courses c ON c.id = m.course_id
     WHERE s.actif = 1 ORDER BY c.titre, m.ordre, s.ordre'
)->fetchAll();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCSRF();
    $seqId    = (int)($_POST['sequence_id'] ?? 0);
    $titre    = trim($_POST['titre'] ?? '');
    $desc     = trim($_POST['description'] ?? '');
    $scoreMin = max(0, min(100, (int)($_POST['score_min'] ?? 70)));

    if (!$seqId) $errors[] = 'Sélectionnez une séquence.';
    if (!$titre) $errors[] = 'Le titre est obligatoire.';

    if (empty($errors)) {
        $pdo->prepare(
            'INSERT INTO quizzes (sequence_id, titre, description, score_min) VALUES (?, ?, ?, ?)'
        )->execute([$seqId, $titre, $desc, $scoreMin]);
        $quizId = $pdo->lastInsertId();

        // Questions
        $questions = $_POST['questions'] ?? [];
        foreach ($questions as $qi => $qdata) {
            $qtext = trim($qdata['question'] ?? '');
            $qtype = $qdata['type'] ?? 'choix_unique';
            $qpts  = max(1, (int)($qdata['points'] ?? 1));
            if (!$qtext) continue;
            $pdo->prepare(
                'INSERT INTO questions (quiz_id, question, type, ordre, points) VALUES (?, ?, ?, ?, ?)'
            )->execute([$quizId, $qtext, $qtype, $qi, $qpts]);
            $qId = $pdo->lastInsertId();

            // Réponses
            $answers = $qdata['answers'] ?? [];
            $corrects = (array)($qdata['correct'] ?? []);
            foreach ($answers as $ai => $atexte) {
                $atexte = trim($atexte);
                if (!$atexte) continue;
                $isCorrect = in_array((string)$ai, array_map('strval', $corrects)) ? 1 : 0;
                $pdo->prepare(
                    'INSERT INTO answers (question_id, texte, est_correct, ordre) VALUES (?, ?, ?, ?)'
                )->execute([$qId, $atexte, $isCorrect, $ai]);
            }
        }
        redirect(SITE_URL . '/admin/quizzes.php', 'Quiz créé avec succès !', 'success');
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Créer un quiz — Admin <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/dashboard.css">
<style>
.question-block { background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:20px;margin-bottom:16px;position:relative; }
.answer-row { display:flex;align-items:center;gap:8px;margin-bottom:8px; }
.answer-row input[type=text] { flex:1;padding:8px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px; }
.btn-add-ans { background:none;border:1px dashed #e5e7eb;color:#534AB7;padding:6px 12px;border-radius:6px;cursor:pointer;font-size:12px;font-weight:600; }
</style>
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <div>
      <h1 class="admin-page-title">Créer un quiz</h1>
      <a href="<?= SITE_URL ?>/admin/quizzes.php" class="btn-link">← Retour aux quiz</a>
    </div>
  </div>
  <?= flash() ?>
  <?php if ($errors): ?>
  <div class="flash flash-error" style="margin-bottom:16px">
    <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <form method="POST" class="admin-card" style="max-width:900px">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

    <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
      <div class="form-field">
        <label>Séquence associée *</label>
        <select name="sequence_id" required>
          <option value="">-- Choisir une séquence --</option>
          <?php foreach ($sequences as $s): ?>
          <option value="<?= $s['id'] ?>"><?= h($s['course_titre'].' › '.$s['module_titre'].' › '.$s['titre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-field">
        <label>Titre du quiz *</label>
        <input type="text" name="titre" placeholder="Ex: Quiz Module 1" required value="<?= h($_POST['titre']??'') ?>">
      </div>
      <div class="form-field">
        <label>Description</label>
        <input type="text" name="description" value="<?= h($_POST['description']??'') ?>">
      </div>
      <div class="form-field">
        <label>Score minimum pour réussir (%)</label>
        <input type="number" name="score_min" min="0" max="100" value="<?= $_POST['score_min']??70 ?>">
      </div>
    </div>

    <h3 style="font-size:16px;font-weight:700;margin:20px 0 12px">Questions</h3>
    <div id="questions-wrap"></div>

    <button type="button" onclick="addQuestion()" class="btn-outline" style="margin-bottom:24px">
      <i class="ti ti-plus"></i> Ajouter une question
    </button>

    <div style="border-top:1px solid #e5e7eb;padding-top:16px">
      <button type="submit" class="btn-primary"><i class="ti ti-check"></i> Créer le quiz</button>
    </div>
  </form>
</div>
<script>
let qCount = 0;
function addQuestion() {
  const qi = qCount++;
  const wrap = document.getElementById('questions-wrap');
  const div = document.createElement('div');
  div.className = 'question-block';
  div.id = 'q-' + qi;
  div.innerHTML = `
    <button type="button" onclick="this.closest('.question-block').remove()" style="position:absolute;top:10px;right:10px;background:none;border:none;cursor:pointer;color:#dc2626;font-size:18px"><i class="ti ti-x"></i></button>
    <div style="display:grid;grid-template-columns:1fr auto auto;gap:10px;margin-bottom:12px;align-items:end">
      <div>
        <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px">Question *</label>
        <input type="text" name="questions[${qi}][question]" placeholder="Entrez votre question..." style="width:100%;padding:8px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px;box-sizing:border-box" required>
      </div>
      <div>
        <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px">Type</label>
        <select name="questions[${qi}][type]" style="padding:8px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px">
          <option value="choix_unique">Choix unique</option>
          <option value="choix_multiple">Choix multiple</option>
          <option value="vrai_faux">Vrai / Faux</option>
        </select>
      </div>
      <div>
        <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px">Points</label>
        <input type="number" name="questions[${qi}][points]" value="1" min="1" style="width:60px;padding:8px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px">
      </div>
    </div>
    <div id="answers-${qi}">
      ${makeAnswer(qi, 0)}
      ${makeAnswer(qi, 1)}
    </div>
    <button type="button" class="btn-add-ans" onclick="addAnswer(${qi})"><i class="ti ti-plus"></i> Ajouter réponse</button>
  `;
  wrap.appendChild(div);
}
let ansCount = {};
function makeAnswer(qi, ai) {
  return `<div class="answer-row">
    <input type="checkbox" name="questions[${qi}][correct][]" value="${ai}" title="Correcte ?">
    <input type="text" name="questions[${qi}][answers][${ai}]" placeholder="Réponse ${ai+1}...">
  </div>`;
}
function addAnswer(qi) {
  if (!ansCount[qi]) ansCount[qi] = 2;
  const ai = ansCount[qi]++;
  const wrap = document.getElementById('answers-' + qi);
  wrap.insertAdjacentHTML('beforeend', makeAnswer(qi, ai));
}
addQuestion();
</script>
</body>
</html>
