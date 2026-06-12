<?php
// quiz.php — Passer un quiz
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
reqConnecte();

$pdo    = getPDO();
$userId = $_SESSION['user_id'];
$quizId = (int)($_GET['id'] ?? 0);
if (!$quizId) { header('Location: ' . SITE_URL . '/dashboard.php'); exit; }

$quiz = $pdo->prepare(
    'SELECT qz.*, s.titre as seq_titre, s.id as seq_id, c.titre as course_titre, c.slug as course_slug
     FROM quizzes qz
     JOIN sequences s ON s.id = qz.sequence_id
     JOIN modules m   ON m.id = s.module_id
     JOIN courses c   ON c.id = m.course_id
     WHERE qz.id = ? AND qz.actif = 1'
);
$quiz->execute([$quizId]);
$quiz = $quiz->fetch();
if (!$quiz) { http_response_code(404); echo 'Quiz introuvable.'; exit; }

if (!estInscrit($userId, null)) {
    // Vérifier via la séquence
    $check = $pdo->prepare(
        'SELECT e.id FROM enrollments e
         JOIN modules m ON m.course_id = e.course_id
         JOIN sequences s ON s.module_id = m.id
         WHERE s.id = ? AND e.user_id = ? AND e.statut = "actif"'
    );
    $check->execute([$quiz['seq_id'], $userId]);
    if (!$check->fetch()) {
        redirect(SITE_URL . '/dashboard.php', 'Accès non autorisé.', 'error');
    }
}

$questions = $pdo->prepare(
    'SELECT * FROM questions WHERE quiz_id = ? ORDER BY ordre ASC, id ASC'
);
$questions->execute([$quizId]);
$questions = $questions->fetchAll();

foreach ($questions as &$q) {
    $ans = $pdo->prepare('SELECT * FROM answers WHERE question_id = ? ORDER BY ordre ASC, id ASC');
    $ans->execute([$q['id']]);
    $q['answers'] = $ans->fetchAll();
}
unset($q);

// Soumission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCSRF();
    $score = 0; $total = 0;
    foreach ($questions as $q) {
        if (in_array($q['type'], ['choix_unique', 'vrai_faux'])) {
            $total += $q['points'];
            $userAns = (int)($_POST['q_' . $q['id']] ?? 0);
            $correct = false;
            foreach ($q['answers'] as $a) {
                if ($a['id'] == $userAns && $a['est_correct']) { $correct = true; break; }
            }
            if ($correct) $score += $q['points'];
        } elseif ($q['type'] === 'choix_multiple') {
            $total += $q['points'];
            $userAns = array_map('intval', (array)($_POST['q_' . $q['id']] ?? []));
            $correctIds = array_column(array_filter($q['answers'], fn($a) => $a['est_correct']), 'id');
            sort($userAns); sort($correctIds);
            if ($userAns === $correctIds) $score += $q['points'];
        }
    }
    $maxScore = array_sum(array_column($questions, 'points'));
    $pct = $maxScore > 0 ? round($score / $maxScore * 100) : 0;
    $reussi = $pct >= $quiz['score_min'];
    $pdo->prepare(
        'INSERT INTO quiz_results (user_id, quiz_id, score, total, reussi) VALUES (?, ?, ?, ?, ?)'
    )->execute([$userId, $quizId, $pct, 100, $reussi ? 1 : 0]);
    $lastId = $pdo->lastInsertId();
    redirect(SITE_URL . '/quiz_result.php?id=' . $lastId);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($quiz['titre']) ?> — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<style>
.quiz-wrap { max-width: 760px; margin: 40px auto; padding: 0 20px 60px; }
.quiz-header { text-align: center; margin-bottom: 36px; }
.quiz-header h1 { font-size: 26px; font-weight: 700; margin: 0 0 8px; }
.quiz-header p { color: var(--text-muted,#6b7280); font-size: 14px; }
.quiz-score-req { display: inline-block; background: #f0effc; color: #534AB7; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; margin-top: 8px; }
.question-card { background: var(--surface,#fff); border: 1px solid var(--border,#e5e7eb); border-radius: 14px; padding: 24px; margin-bottom: 20px; }
.question-num { font-size: 11px; color: var(--text-muted,#6b7280); font-weight: 600; text-transform: uppercase; margin-bottom: 8px; }
.question-text { font-size: 16px; font-weight: 600; margin-bottom: 16px; line-height: 1.5; }
.answer-option { display: flex; align-items: center; gap: 10px; padding: 10px 14px; border: 1px solid var(--border,#e5e7eb); border-radius: 8px; margin-bottom: 8px; cursor: pointer; transition: .15s; }
.answer-option:hover { border-color: #534AB7; background: #f0effc; }
.answer-option input { flex-shrink: 0; }
.quiz-submit { width: 100%; padding: 16px; font-size: 16px; font-weight: 700; background: #534AB7; color: #fff; border: none; border-radius: 12px; cursor: pointer; margin-top: 8px; }
.quiz-submit:hover { background: #3d369a; }
</style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<div class="quiz-wrap">
  <div class="quiz-header">
    <div style="margin-bottom:12px">
      <a href="<?= SITE_URL ?>/sequence.php?id=<?= $quiz['seq_id'] ?>" style="font-size:13px;color:var(--primary,#534AB7);text-decoration:none">
        <i class="ti ti-arrow-left"></i> Retour à la leçon
      </a>
    </div>
    <h1><?= h($quiz['titre']) ?></h1>
    <?php if ($quiz['description']): ?>
    <p><?= h($quiz['description']) ?></p>
    <?php endif; ?>
    <span class="quiz-score-req"><i class="ti ti-target"></i> Score minimum : <?= $quiz['score_min'] ?>%</span>
  </div>

  <?php if (empty($questions)): ?>
    <p style="text-align:center;color:var(--text-muted,#6b7280)">Ce quiz n'a pas encore de questions.</p>
  <?php else: ?>
  <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <?php foreach ($questions as $i => $q): ?>
    <div class="question-card">
      <div class="question-num">Question <?= $i + 1 ?> / <?= count($questions) ?></div>
      <div class="question-text"><?= h($q['question']) ?></div>

      <?php if ($q['type'] === 'choix_unique' || $q['type'] === 'vrai_faux'): ?>
        <?php foreach ($q['answers'] as $a): ?>
        <label class="answer-option">
          <input type="radio" name="q_<?= $q['id'] ?>" value="<?= $a['id'] ?>" required>
          <?= h($a['texte']) ?>
        </label>
        <?php endforeach; ?>

      <?php elseif ($q['type'] === 'choix_multiple'): ?>
        <?php foreach ($q['answers'] as $a): ?>
        <label class="answer-option">
          <input type="checkbox" name="q_<?= $q['id'] ?>[]" value="<?= $a['id'] ?>">
          <?= h($a['texte']) ?>
        </label>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <button type="submit" class="quiz-submit"><i class="ti ti-send"></i> Soumettre mes réponses</button>
  </form>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
