<?php
// quiz_result.php — Résultat d'un quiz
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
reqConnecte();

$pdo    = getPDO();
$userId = $_SESSION['user_id'];
$resId  = (int)($_GET['id'] ?? 0);

$result = $pdo->prepare(
    'SELECT qr.*, qz.titre as quiz_titre, qz.score_min, qz.sequence_id,
            s.titre as seq_titre, c.titre as course_titre, c.slug as course_slug
     FROM quiz_results qr
     JOIN quizzes qz ON qz.id = qr.quiz_id
     JOIN sequences s ON s.id = qz.sequence_id
     JOIN modules m ON m.id = s.module_id
     JOIN courses c ON c.id = m.course_id
     WHERE qr.id = ? AND qr.user_id = ?'
);
$result->execute([$resId, $userId]);
$result = $result->fetch();
if (!$result) { header('Location: ' . SITE_URL . '/dashboard.php'); exit; }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Résultat du quiz — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<style>
.result-wrap { max-width: 600px; margin: 60px auto; padding: 0 20px; text-align: center; }
.score-circle {
  width: 140px; height: 140px; border-radius: 50%; margin: 0 auto 24px;
  display: flex; align-items: center; justify-content: center; flex-direction: column;
  font-size: 36px; font-weight: 800; border: 6px solid;
}
.score-circle.success { border-color: #16a34a; color: #16a34a; background: #f0fdf4; }
.score-circle.fail    { border-color: #dc2626; color: #dc2626; background: #fef2f2; }
.result-title { font-size: 24px; font-weight: 700; margin-bottom: 8px; }
.result-sub { color: var(--text-muted,#6b7280); margin-bottom: 32px; }
.result-actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
.btn { padding: 12px 24px; border-radius: 10px; font-weight: 700; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
.btn-primary { background: #534AB7; color: #fff; border: none; cursor: pointer; }
.btn-primary:hover { background: #3d369a; }
.btn-outline { border: 1px solid var(--border,#e5e7eb); color: var(--text,#111); background: #fff; }
.btn-outline:hover { border-color: #534AB7; }
</style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<div class="result-wrap">
  <div class="score-circle <?= $result['reussi'] ? 'success' : 'fail' ?>">
    <span><?= $result['score'] ?>%</span>
  </div>

  <h1 class="result-title">
    <?= $result['reussi'] ? 'Félicitations ! 🎉' : 'Pas encore...' ?>
  </h1>
  <p class="result-sub">
    <?php if ($result['reussi']): ?>
      Tu as réussi le quiz <strong><?= h($result['quiz_titre']) ?></strong> avec un score de <?= $result['score'] ?>%.
      Le score minimum requis était <?= $result['score_min'] ?>%.
    <?php else: ?>
      Tu as obtenu <?= $result['score'] ?>% au quiz <strong><?= h($result['quiz_titre']) ?></strong>.
      Il te faut au moins <?= $result['score_min'] ?>% pour valider. Réessaie !
    <?php endif; ?>
  </p>

  <div class="result-actions">
    <a href="<?= SITE_URL ?>/sequence.php?id=<?= $result['sequence_id'] ?>" class="btn btn-outline">
      <i class="ti ti-arrow-left"></i> Retour à la leçon
    </a>
    <?php if (!$result['reussi']): ?>
    <a href="<?= SITE_URL ?>/quiz.php?id=<?= $result['quiz_id'] ?>" class="btn btn-primary">
      <i class="ti ti-refresh"></i> Réessayer
    </a>
    <?php endif; ?>
    <a href="<?= SITE_URL ?>/module.php?slug=<?= h($result['course_slug']) ?>" class="btn btn-outline">
      <i class="ti ti-book"></i> Retour au cours
    </a>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
