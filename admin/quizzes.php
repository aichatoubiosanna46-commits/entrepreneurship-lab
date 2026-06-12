<?php
// admin/quizzes.php — Liste des quiz
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();
$pdo = getPDO();

$quizzes = $pdo->query(
    'SELECT qz.*, s.titre as seq_titre, c.titre as course_titre,
            (SELECT COUNT(*) FROM questions q WHERE q.quiz_id = qz.id) as nb_questions,
            (SELECT COUNT(*) FROM quiz_results qr WHERE qr.quiz_id = qz.id) as nb_resultats
     FROM quizzes qz
     JOIN sequences s ON s.id = qz.sequence_id
     JOIN modules m ON m.id = s.module_id
     JOIN courses c ON c.id = m.course_id
     ORDER BY qz.created_at DESC'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Quiz — Admin <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/dashboard.css">
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <div>
      <h1 class="admin-page-title">Quiz</h1>
      <p class="admin-page-sub"><?= count($quizzes) ?> quiz au total</p>
    </div>
    <a href="<?= SITE_URL ?>/admin/quiz_add.php" class="btn-primary"><i class="ti ti-plus"></i> Ajouter un quiz</a>
  </div>
  <?= flash() ?>
  <div class="admin-card">
    <table class="admin-table">
      <thead><tr><th>Titre</th><th>Séquence</th><th>Cours</th><th>Questions</th><th>Résultats</th><th>Score min</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($quizzes as $q): ?>
        <tr>
          <td><strong><?= h($q['titre']) ?></strong></td>
          <td><?= h($q['seq_titre']) ?></td>
          <td><?= h($q['course_titre']) ?></td>
          <td style="text-align:center"><?= $q['nb_questions'] ?></td>
          <td style="text-align:center"><?= $q['nb_resultats'] ?></td>
          <td style="text-align:center"><?= $q['score_min'] ?>%</td>
          <td>
            <a href="<?= SITE_URL ?>/admin/quiz_edit.php?id=<?= $q['id'] ?>" class="btn-outline btn-sm"><i class="ti ti-edit"></i> Éditer</a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($quizzes)): ?>
        <tr><td colspan="7" style="text-align:center;color:var(--text-muted)">Aucun quiz créé</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
