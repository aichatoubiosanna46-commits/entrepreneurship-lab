<?php
// quiz.php — Quiz interactif avec feedback immédiat par question
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
reqConnecte();

$pdo    = getPDO();
$userId = $_SESSION['user_id'];
$quizId = (int)($_GET['id'] ?? 0);

if (!$quizId) redirect(SITE_URL . '/dashboard.php');

// Quiz
$quiz = $pdo->prepare('SELECT q.*, c.id as course_id, c.slug as course_slug FROM quizzes q LEFT JOIN sequences s ON s.id = q.sequence_id LEFT JOIN modules m ON m.id = s.module_id LEFT JOIN courses c ON c.id = m.course_id WHERE q.id = ? AND q.actif = 1 LIMIT 1');
$quiz->execute([$quizId]);
$quiz = $quiz->fetch();
if (!$quiz) redirect(SITE_URL . '/dashboard.php');

// Questions + réponses
$qStmt = $pdo->prepare('SELECT * FROM questions WHERE quiz_id = ? ORDER BY ordre ASC, id ASC');
$qStmt->execute([$quizId]);
$questions = $qStmt->fetchAll();

foreach ($questions as &$q) {
    $aStmt = $pdo->prepare('SELECT * FROM answers WHERE question_id = ? ORDER BY ordre ASC, id ASC');
    $aStmt->execute([$q['id']]);
    $q['answers'] = $aStmt->fetchAll();
}
unset($q);

// Tirage aléatoire depuis la banque de questions — figé en session pour la durée de la tentative
if ((int)($quiz['bank_nb_questions'] ?? 0) > 0) {
    $sessKey = 'quiz_bank_' . $quizId;
    if (!empty($_SESSION[$sessKey])) {
        $ids   = $_SESSION[$sessKey];
        $place = implode(',', array_fill(0, count($ids), '?'));
        $bStmt = $pdo->prepare("SELECT * FROM question_bank WHERE id IN ($place)");
        $bStmt->execute($ids);
        $byId  = [];
        foreach ($bStmt->fetchAll() as $r) { $byId[$r['id']] = $r; }
        $bankRows = array_values(array_filter(array_map(fn($id) => $byId[$id] ?? null, $ids)));
    } else {
        if (!empty($quiz['bank_categorie'])) {
            $bStmt = $pdo->prepare('SELECT * FROM question_bank WHERE categorie = ? ORDER BY RAND() LIMIT ' . (int)$quiz['bank_nb_questions']);
            $bStmt->execute([$quiz['bank_categorie']]);
        } else {
            $bStmt = $pdo->query('SELECT * FROM question_bank ORDER BY RAND() LIMIT ' . (int)$quiz['bank_nb_questions']);
        }
        $bankRows = $bStmt->fetchAll();
        $_SESSION[$sessKey] = array_column($bankRows, 'id');
    }
    foreach ($bankRows as $br) {
        $aStmt = $pdo->prepare('SELECT * FROM question_bank_answers WHERE question_id = ?');
        $aStmt->execute([$br['id']]);
        $br['answers'] = $aStmt->fetchAll();
        $br['id'] = 'qb' . $br['id'];
        $questions[] = $br;
    }
}

// Soumission finale (mode non-interactif fallback)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_final') {
    verifierCSRF();
    $score = 0; $maxScore = 0;
    foreach ($questions as $q) {
        $maxScore += $q['points'];
        $given = (array)($_POST['rep'][$q['id']] ?? []);
        $corrects = array_filter($q['answers'], fn($a) => $a['est_correct']);
        $correctIds = array_column($corrects, 'id');
        sort($given); sort($correctIds);
        if ($given == array_map('strval', $correctIds)) $score += $q['points'];
    }
    $pct   = $maxScore > 0 ? round($score / $maxScore * 100) : 0;
    $reussi = $pct >= ($quiz['score_min'] ?? 70);
    // Correction fill_blank: comparer texte saisi avec bonne réponse
    foreach ($questions as $q) {
        if ($q['type'] === 'fill_blank' && !empty($q['answers'])) {
            $repSaisie = strtolower(trim($_POST['fill_text'][$q['id']] ?? ''));
            $bonneRep  = strtolower(trim($q['answers'][0]['texte'] ?? ''));
            if ($repSaisie && ($repSaisie === $bonneRep || similar_text($repSaisie, $bonneRep) / max(strlen($bonneRep), 1) * 100 > 80)) {
                $score += $q['points'];
            }
        }
    }
    $pct    = $maxScore > 0 ? round($score / $maxScore * 100) : 0;
    $reussi = $pct >= ($quiz['score_min'] ?? 70);

    $pdo->prepare('INSERT INTO quiz_results (user_id,quiz_id,score,reussi,created_at) VALUES (?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE score=VALUES(score),reussi=VALUES(reussi),created_at=NOW()')
        ->execute([$userId, $quizId, $pct, $reussi ? 1 : 0]);

    // Déclencher automations
    triggerAutomation('quiz_submitted', $userId, $quizId);
    if ($reussi) {
        triggerAutomation('quiz_passed', $userId, $quizId);
        // XP bonus si quiz réussi
        addXP($userId, 'quiz_reussi', $quiz['points_xp'] ?? 20, 'Quiz réussi : ' . $quiz['titre']);
        checkAndAwardBadges($userId);
    }
    unset($_SESSION['quiz_bank_' . $quizId]);
    redirect(SITE_URL . '/quiz_result.php?quiz_id=' . $quizId . '&score=' . $pct . '&reussi=' . ($reussi ? 1 : 0));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($quiz['titre']) ?> — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css?v=2">
<style>
body { background: #F5F0E8; font-family: 'Plus Jakarta Sans', sans-serif; }
.quiz-wrap { max-width: 720px; margin: 0 auto; padding: 32px 20px 60px; }
.quiz-header { background: linear-gradient(135deg,#1A1A18,#292524); border-radius: 16px; padding: 28px; margin-bottom: 28px; color: #fff; }
.quiz-header h1 { font-size: 22px; font-weight: 800; margin-bottom: 6px; }
.quiz-header p { font-size: 13px; color: rgba(255,255,255,.6); }
.quiz-progress-wrap { margin-top: 16px; }
.quiz-progress-bar { height: 6px; background: rgba(255,255,255,.2); border-radius: 3px; overflow: hidden; }
.quiz-progress-fill { height: 100%; background: #D85A30; border-radius: 3px; transition: width .4s; }
.quiz-progress-text { font-size: 12px; color: rgba(255,255,255,.6); margin-top: 6px; }

.q-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 28px; margin-bottom: 20px; display: none; }
.q-card.active { display: block; }
.q-num { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #9ca3af; margin-bottom: 8px; letter-spacing: .06em; }
.q-text { font-size: 18px; font-weight: 700; color: #1A1A18; margin-bottom: 20px; line-height: 1.45; }
.q-image { width: 100%; border-radius: 10px; margin-bottom: 16px; max-height: 220px; object-fit: cover; }

/* Réponses */
.answers-list { display: flex; flex-direction: column; gap: 10px; }
.answer-btn {
  display: flex; align-items: center; gap: 12px;
  padding: 14px 16px; border: 2px solid #e5e7eb;
  border-radius: 12px; cursor: pointer; transition: .15s;
  background: #fff; text-align: left; font-size: 14px;
  font-family: inherit; width: 100%; color: #1A1A18;
}
.answer-btn:hover:not(:disabled) { border-color: #D85A30; background: #F5F0E8; }
.answer-btn .answer-letter {
  width: 32px; height: 32px; border-radius: 50%;
  background: #f3f4f6; display: flex; align-items: center;
  justify-content: center; font-size: 12px; font-weight: 700;
  flex-shrink: 0; transition: .15s;
}
.answer-btn.selected { border-color: #D85A30; background: #F5F0E8; }
.answer-btn.selected .answer-letter { background: #D85A30; color: #1A1A18; }
.answer-btn.correct { border-color: #16a34a !important; background: #ECFDF5 !important; }
.answer-btn.correct .answer-letter { background: #16a34a !important; color: #fff !important; }
.answer-btn.wrong { border-color: #dc2626 !important; background: #F0F9F5 !important; }
.answer-btn.wrong .answer-letter { background: #dc2626 !important; color: #fff !important; }
.answer-btn:disabled { cursor: default; }

/* Feedback */
.q-feedback { display: none; margin-top: 16px; padding: 14px 16px; border-radius: 10px; font-size: 13px; line-height: 1.6; }
.q-feedback.show { display: flex; align-items: flex-start; gap: 10px; }
.q-feedback.correct-fb { background: #ECFDF5; border: 1px solid #86efac; color: #15803d; }
.q-feedback.wrong-fb { background: #F0F9F5; border: 1px solid #fca5a5; color: #dc2626; }
.q-feedback i { font-size: 18px; flex-shrink: 0; margin-top: 1px; }

/* Navigation */
.q-nav { display: flex; justify-content: space-between; align-items: center; margin-top: 20px; gap: 10px; }
.btn-validate {
  padding: 12px 24px; background: linear-gradient(135deg,#D85A30,#C04A22);
  color: #1A1A18; border: none; border-radius: 10px;
  font-size: 14px; font-weight: 700; cursor: pointer; font-family: inherit;
}
.btn-next {
  padding: 12px 24px; background: #1A1A18; color: #fff;
  border: none; border-radius: 10px; font-size: 14px;
  font-weight: 700; cursor: pointer; display: none; font-family: inherit;
}
.btn-finish {
  padding: 12px 24px; background: #16a34a; color: #fff;
  border: none; border-radius: 10px; font-size: 14px;
  font-weight: 700; cursor: pointer; display: none; font-family: inherit;
}

/* Score final */
.score-card { background: #fff; border-radius: 16px; padding: 40px; text-align: center; display: none; }
.score-circle {
  width: 120px; height: 120px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 36px; font-weight: 800; margin: 0 auto 20px;
}
</style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>


<?php
// Écran de démarrage quiz
$quizStarted = isset($_SESSION['quiz_started_' . $quizId]);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'start_quiz') {
    $_SESSION['quiz_started_' . $quizId] = true;
    $quizStarted = true;
}
if (!$quizStarted && !empty($questions)): ?>
<div class="quiz-wrap" style="max-width:600px;margin:60px auto;padding:0 24px">
  <div style="background:#fff;border-radius:20px;padding:40px;border:1px solid #e5e7eb;box-shadow:0 4px 24px rgba(0,0,0,.06);text-align:center">
    <div style="width:80px;height:80px;background:linear-gradient(135deg,#D85A30,#C04A22);border-radius:20px;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;font-size:36px;color:#fff">
      <i class="ti ti-help"></i>
    </div>
    <h1 style="font-size:22px;font-weight:800;color:#1A1A18;margin-bottom:10px"><?= h($quiz['titre']) ?></h1>
    <?php if ($quiz['description']): ?>
    <p style="font-size:14px;color:#6b7280;margin-bottom:24px;line-height:1.7"><?= h($quiz['description']) ?></p>
    <?php endif; ?>

    <div style="display:flex;justify-content:center;gap:20px;margin-bottom:28px;flex-wrap:wrap">
      <div style="text-align:center">
        <div style="font-size:24px;font-weight:800;color:#1A1A18"><?= count($questions) ?></div>
        <div style="font-size:12px;color:#9ca3af">questions</div>
      </div>
      <?php if ($quiz['duree_minutes'] ?? 0): ?>
      <div style="text-align:center">
        <div style="font-size:24px;font-weight:800;color:#D85A30"><?= $quiz['duree_minutes'] ?></div>
        <div style="font-size:12px;color:#9ca3af">minutes</div>
      </div>
      <?php endif; ?>
      <div style="text-align:center">
        <div style="font-size:24px;font-weight:800;color:#16a34a"><?= $quiz['score_min'] ?? 70 ?>%</div>
        <div style="font-size:12px;color:#9ca3af">pour réussir</div>
      </div>
      <?php if ($quiz['tentatives_max'] ?? 0): ?>
      <div style="text-align:center">
        <div style="font-size:24px;font-weight:800;color:#6C47D4"><?= $quiz['tentatives_max'] ?></div>
        <div style="font-size:12px;color:#9ca3af">tentatives max</div>
      </div>
      <?php endif; ?>
    </div>

    <div style="background:#FBE3DA;border-radius:10px;padding:14px;margin-bottom:24px;font-size:13px;color:#92400E;text-align:left">
      <i class="ti ti-info-circle" style="margin-right:6px"></i>
      <?php if ($quiz['duree_minutes'] ?? 0): ?>
      Le chronomètre démarre dès que vous cliquez sur "Commencer". Répondez à toutes les questions avant la fin du temps imparti.
      <?php else: ?>
      Prenez votre temps. Vous recevrez un feedback immédiat après chaque réponse.
      <?php endif; ?>
    </div>

    <form method="POST">
      <input type="hidden" name="action" value="start_quiz">
      <button type="submit" style="width:100%;padding:14px;background:linear-gradient(135deg,#D85A30,#C04A22);color:#1A1A18;border:none;border-radius:12px;font-size:16px;font-weight:800;cursor:pointer;font-family:inherit">
        <i class="ti ti-player-play"></i> Commencer le quiz
      </button>
    </form>
    <a href="<?= SITE_URL ?>/dashboard.php" style="display:block;margin-top:14px;font-size:13px;color:#9ca3af;text-decoration:none">← Retour au tableau de bord</a>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body></html>
<?php exit; endif; ?>

<div class="quiz-wrap">
  <div class="quiz-header">
  <?php if ($quiz['duree_minutes'] ?? 0): ?>
  <div id="quiz-timer-wrap" style="display:flex;align-items:center;gap:8px;background:rgba(255,255,255,0.15);border-radius:8px;padding:8px 14px;margin-bottom:16px;width:fit-content">
    <i class="ti ti-clock" style="color:#D85A30;font-size:18px"></i>
    <span style="font-size:14px;font-weight:700;color:#fff" id="timer-display">--:--</span>
    <span style="font-size:12px;color:rgba(255,255,255,.6)">restant</span>
  </div>
  <script>
  (function(){
    let secs = <?= (int)$quiz['duree_minutes'] * 60 ?>;
    const display = document.getElementById('timer-display');
    const wrap = document.getElementById('quiz-timer-wrap');
    function tick() {
      if (secs <= 0) { finishQuiz(); return; }
      const m = Math.floor(secs/60);
      const s = secs % 60;
      display.textContent = String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
      if (secs <= 60) wrap.style.background = 'rgba(220,38,38,0.4)';
      secs--;
    }
    tick();
    setInterval(tick, 1000);
  })();
  </script>
  <?php endif; ?>
    <h1><?= h($quiz['titre']) ?></h1>
    <?php if ($quiz['description']): ?>
    <p><?= h($quiz['description']) ?></p>
    <?php endif; ?>
    <div class="quiz-progress-wrap">
      <div class="quiz-progress-bar">
        <div class="quiz-progress-fill" id="progressFill" style="width:0%"></div>
      </div>
      <div class="quiz-progress-text" id="progressText">Question 1 / <?= count($questions) ?></div>
    </div>
  </div>

  <?php if (empty($questions)): ?>
  <div style="text-align:center;padding:40px;background:#fff;border-radius:16px;color:#6b7280">
    <i class="ti ti-help-circle" style="font-size:48px;display:block;margin-bottom:12px;color:#e5e7eb"></i>
    Ce quiz n'a pas encore de questions.
  </div>
  <?php else: ?>

  <!-- Questions interactives -->
  <?php foreach ($questions as $i => $q): ?>
  <div class="q-card <?= $i === 0 ? 'active' : '' ?>"
       data-index="<?= $i ?>"
       data-question-id="<?= $q['id'] ?>"
       data-type="<?= h($q['type']) ?>"
       data-points="<?= $q['points'] ?>">

    <div class="q-num">Question <?= $i + 1 ?> / <?= count($questions) ?> · <?= $q['points'] ?> point<?= $q['points'] > 1 ? 's' : '' ?></div>

    <?php if (!empty($q['image_url'])): ?>
    <img src="<?= h($q['image_url']) ?>" class="q-image" alt="">
    <?php endif; ?>

    <div class="q-text"><?= h($q['question']) ?></div>

    <?php if ($q['type'] === 'fill_blank'): ?>
    <!-- Complétion de texte -->
    <div style="margin-bottom:12px">
      <p style="font-size:13px;color:#6b7280;margin-bottom:10px">Complétez le texte avec le(s) mot(s) manquant(s) :</p>
      <input type="text"
             name="fill_text[<?= $q['id'] ?>]"
             id="fill-<?= $q['id'] ?>"
             style="width:100%;padding:12px 14px;border:2px solid #e5e7eb;border-radius:10px;font-size:15px;font-family:inherit;box-sizing:border-box"
             placeholder="Votre réponse..."
             onchange="setFillBlank(<?= $q['id'] ?>, this.value)">
      <input type="hidden" id="fill-ans-<?= $q['id'] ?>" value="<?= h($q['answers'][0]['id'] ?? '') ?>">
    </div>
    <?php elseif ($q['type'] === 'correspondance'): ?>
    <!-- Correspondance -->
    <div style="margin-bottom:12px">
      <p style="font-size:13px;color:#6b7280;margin-bottom:10px">Associez chaque élément à sa correspondance :</p>
      <?php
      $corrects  = array_filter($q['answers'], fn($a) => $a['est_correct']);
      $incorrects = array_filter($q['answers'], fn($a) => !$a['est_correct']);
      $shuffled  = $q['answers']; shuffle($shuffled);
      ?>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div>
          <div style="font-size:11px;font-weight:700;color:#9ca3af;margin-bottom:8px">ÉLÉMENTS</div>
          <?php foreach ($corrects as $i => $ans): ?>
          <div style="padding:10px 12px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:6px;font-size:13px">
            <?= h($ans['texte']) ?>
          </div>
          <?php endforeach; ?>
        </div>
        <div>
          <div style="font-size:11px;font-weight:700;color:#9ca3af;margin-bottom:8px">CORRESPONDANCES</div>
          <?php foreach ($shuffled as $ans): ?>
          <div draggable="true"
               style="padding:10px 12px;background:#FBE3DA;border:1px solid #fde68a;border-radius:8px;margin-bottom:6px;font-size:13px;cursor:grab">
            <?= h($ans['feedback'] ?: $ans['texte']) ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <!-- Pour la correspondance, sélectionner toutes les bonnes réponses -->
      <?php foreach ($q['answers'] as $ans): ?>
      <?php if ($ans['est_correct']): ?>
      <input type="hidden" class="corr-preselect" data-qid="<?= $q['id'] ?>" data-aid="<?= $ans['id'] ?>">
      <?php endif; ?>
      <?php endforeach; ?>
    </div>
    <?php elseif ($q['type'] === 'texte_libre'): ?>
    <!-- Texte libre -->
    <div style="margin-bottom:12px">
      <p style="font-size:13px;color:#6b7280;margin-bottom:10px">Rédigez votre réponse (correction manuelle par le coach) :</p>
      <textarea id="libre-<?= $q['id'] ?>"
                style="width:100%;padding:12px 14px;border:2px solid #e5e7eb;border-radius:10px;font-size:14px;font-family:inherit;resize:vertical;min-height:100px;box-sizing:border-box"
                placeholder="Votre réponse..."
                onchange="setTexteLibre(<?= $q['id'] ?>, this.value)"></textarea>
    </div>
    <?php else: ?>
    <div class="answers-list" id="answers-<?= $q['id'] ?>">
      <?php $letters = ['A','B','C','D','E','F']; ?>
      <?php foreach ($q['answers'] as $ai => $ans): ?>
      <button type="button"
              class="answer-btn"
              data-answer-id="<?= $ans['id'] ?>"
              data-correct="<?= $ans['est_correct'] ?>"
              data-feedback="<?= h($ans['feedback'] ?? '') ?>"
              onclick="selectAnswer(this, <?= $q['id'] ?>, '<?= $q['type'] ?>')">
        <span class="answer-letter"><?= $letters[$ai] ?? ($ai+1) ?></span>
        <span><?= h($ans['texte']) ?></span>
      </button>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Feedback -->
    <div class="q-feedback" id="feedback-<?= $q['id'] ?>">
      <i class="ti"></i>
      <div id="feedback-text-<?= $q['id'] ?>"></div>
    </div>

    <?php if (!empty($q['explication'])): ?>
    <div id="explication-<?= $q['id'] ?>" style="display:none;margin-top:12px;padding:12px 14px;background:#F0F9FF;border:1px solid #BAE6FD;border-radius:8px;font-size:13px;color:#0369a1;line-height:1.6">
      <i class="ti ti-bulb" style="margin-right:6px;color:#D85A30"></i>
      <?= h($q['explication']) ?>
    </div>
    <?php endif; ?>

    <div class="q-nav">
      <button type="button" class="btn-validate" id="validate-<?= $q['id'] ?>" onclick="validateAnswer(<?= $q['id'] ?>, <?= $i ?>, <?= count($questions) ?>)">
        <i class="ti ti-check"></i> Valider ma réponse
      </button>
      <?php if ($i < count($questions) - 1): ?>
      <button type="button" class="btn-next" id="next-<?= $q['id'] ?>" onclick="goNext(<?= $i ?>)">
        Question suivante <i class="ti ti-arrow-right"></i>
      </button>
      <?php else: ?>
      <button type="button" class="btn-finish" id="next-<?= $q['id'] ?>" onclick="finishQuiz()">
        <i class="ti ti-trophy"></i> Voir mes résultats
      </button>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>

  <!-- Résultats finaux -->
  <div class="score-card" id="scoreCard">
    <div class="score-circle" id="scoreCircle"></div>
    <h2 id="scoreTitle" style="font-size:22px;font-weight:800;margin-bottom:8px"></h2>
    <p id="scoreDesc" style="font-size:14px;color:#6b7280;margin-bottom:24px"></p>
    <div id="scoreDetails" style="margin-bottom:24px"></div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="action" value="submit_final">
      <input type="hidden" name="final_score" id="finalScoreInput" value="0">
      <?php foreach ($questions as $q): ?>
      <input type="hidden" name="rep[<?= $q['id'] ?>][]" id="hidden-rep-<?= $q['id'] ?>" value="">
      <?php endforeach; ?>
      <button type="submit" class="btn-validate" style="width:100%">
        <i class="ti ti-device-floppy"></i> Enregistrer mon score
      </button>
    </form>
    <a href="<?= SITE_URL ?>/dashboard.php" style="display:block;margin-top:12px;font-size:13px;color:#6b7280;text-decoration:none">
      ← Retour au tableau de bord
    </a>
  </div>

  <?php endif; ?>
</div>

<script>
const totalQuestions = <?= count($questions) ?>;
const seuil = <?= $quiz['score_min'] ?? 70 ?>;
let scores = {}; // question_id => points gagnés
let selectedAnswers = {}; // question_id => [answer_ids]

function updateProgress(currentIndex) {
  const pct = Math.round((currentIndex / totalQuestions) * 100);
  document.getElementById('progressFill').style.width = pct + '%';
  document.getElementById('progressText').textContent =
    'Question ' + (currentIndex + 1) + ' / ' + totalQuestions;
}

function setFillBlank(questionId, value) {
  const ansId = document.getElementById('fill-ans-' + questionId);
  if (!selectedAnswers) selectedAnswers = {};
  selectedAnswers[questionId] = ansId ? [ansId.value] : [];
  scores[questionId] = 0; // sera corrigé à la soumission
}

function setTexteLibre(questionId, value) {
  if (!selectedAnswers) selectedAnswers = {};
  selectedAnswers[questionId] = ['texte_libre:' + value];
  scores[questionId] = 0; // correction manuelle coach
}

function selectAnswer(btn, questionId, type) {
  const container = document.getElementById('answers-' + questionId);
  const btns = container.querySelectorAll('.answer-btn');

  if (type === 'choix_multiple') {
    btn.classList.toggle('selected');
    btn.querySelector('.answer-letter').style.background =
      btn.classList.contains('selected') ? '#D85A30' : '';
    btn.querySelector('.answer-letter').style.color =
      btn.classList.contains('selected') ? '#1A1A18' : '';
  } else {
    btns.forEach(b => {
      b.classList.remove('selected');
      b.querySelector('.answer-letter').style.background = '';
      b.querySelector('.answer-letter').style.color = '';
    });
    btn.classList.add('selected');
    btn.querySelector('.answer-letter').style.background = '#D85A30';
    btn.querySelector('.answer-letter').style.color = '#1A1A18';
  }
}

function validateAnswer(questionId, index, total) {
  const container = document.getElementById('answers-' + questionId);
  const btns = container.querySelectorAll('.answer-btn');
  const selected = container.querySelectorAll('.answer-btn.selected');
  const feedback = document.getElementById('feedback-' + questionId);
  const explication = document.getElementById('explication-' + questionId);
  const validateBtn = document.getElementById('validate-' + questionId);
  const nextBtn = document.getElementById('next-' + questionId);

  if (selected.length === 0) {
    feedback.className = 'q-feedback show wrong-fb';
    feedback.querySelector('i').className = 'ti ti-alert-circle';
    document.getElementById('feedback-text-' + questionId).textContent = 'Sélectionne au moins une réponse.';
    return;
  }

  // Désactiver tous les boutons
  btns.forEach(b => b.disabled = true);
  validateBtn.style.display = 'none';

  // Calculer le résultat
  let allCorrect = true;
  let anyWrong = false;
  let selectedIds = [];
  const card = document.querySelector('[data-question-id="' + questionId + '"]');
  const points = parseInt(card.dataset.points);

  btns.forEach(btn => {
    const isCorrect = btn.dataset.correct === '1';
    const isSelected = btn.classList.contains('selected');
    if (isSelected) selectedIds.push(btn.dataset.answerId);

    if (isSelected && isCorrect) {
      btn.classList.add('correct');
    } else if (isSelected && !isCorrect) {
      btn.classList.add('wrong');
      anyWrong = true; allCorrect = false;
    } else if (!isSelected && isCorrect) {
      btn.classList.add('correct'); // Montrer la bonne réponse
      allCorrect = false;
    }
  });

  // Score
  scores[questionId] = allCorrect ? points : 0;
  selectedAnswers[questionId] = selectedIds;

  // Mettre à jour le champ hidden
  const hiddenInput = document.getElementById('hidden-rep-' + questionId);
  if (hiddenInput) hiddenInput.value = selectedIds.join(',');

  // Feedback avec message personnalisé
  const selectedBtn = container.querySelector('.answer-btn.selected');
  const customFeedback = selectedBtn ? selectedBtn.dataset.feedback : '';

  if (allCorrect) {
    feedback.className = 'q-feedback show correct-fb';
    feedback.querySelector('i').className = 'ti ti-check-circle';
    document.getElementById('feedback-text-' + questionId).textContent =
      customFeedback || '✓ Bonne réponse ! +' + points + ' point' + (points > 1 ? 's' : '');
  } else {
    feedback.className = 'q-feedback show wrong-fb';
    feedback.querySelector('i').className = 'ti ti-x';
    document.getElementById('feedback-text-' + questionId).textContent =
      customFeedback || 'Ce n\'est pas la bonne réponse. La bonne réponse est mise en vert.';
  }

  if (explication) explication.style.display = 'block';
  if (nextBtn) nextBtn.style.display = 'inline-flex';

  // Mettre à jour progression
  updateProgress(index + 1);
}

function goNext(currentIndex) {
  const cards = document.querySelectorAll('.q-card');
  if (cards[currentIndex]) cards[currentIndex].classList.remove('active');
  if (cards[currentIndex + 1]) {
    cards[currentIndex + 1].classList.add('active');
    cards[currentIndex + 1].scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
}

function finishQuiz() {
  const cards = document.querySelectorAll('.q-card');
  cards.forEach(c => c.classList.remove('active'));

  const totalScore = Object.values(scores).reduce((a, b) => a + b, 0);
  const maxScore = <?= array_sum(array_column($questions, 'points')) ?>;
  const pct = maxScore > 0 ? Math.round(totalScore / maxScore * 100) : 0;
  const reussi = pct >= seuil;

  document.getElementById('finalScoreInput').value = pct;
  document.getElementById('progressFill').style.width = '100%';
  document.getElementById('progressText').textContent = 'Quiz terminé !';

  const circle = document.getElementById('scoreCircle');
  circle.style.background = reussi ? '#ECFDF5' : '#F0F9F5';
  circle.style.color = reussi ? '#16a34a' : '#dc2626';
  circle.style.border = reussi ? '4px solid #16a34a' : '4px solid #dc2626';
  circle.textContent = pct + '%';

  document.getElementById('scoreTitle').textContent = reussi ? 'Quiz réussi !' : 'Quiz non validé';
  document.getElementById('scoreDesc').textContent = reussi
    ? 'Félicitations ! Tu as obtenu ' + pct + '% — seuil de réussite : ' + seuil + '%'
    : 'Tu as obtenu ' + pct + '% — il faut ' + seuil + '% pour valider. Tu peux réessayer !';

  document.getElementById('scoreDetails').innerHTML =
    '<div style="background:#f9fafb;border-radius:10px;padding:16px;font-size:13px;color:#374151">' +
    '<strong>' + totalScore + ' / ' + maxScore + ' points</strong> · ' +
    Object.keys(scores).length + ' questions répondues' +
    '</div>';

  document.getElementById('scoreCard').style.display = 'block';
  document.getElementById('scoreCard').scrollIntoView({ behavior: 'smooth' });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
