<?php
// admin/quizzes.php — Liste des quiz
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();
$pdo = getPDO();

// Reset tentatives
if (isset($_GET['reset_attempts'])) {
    $qzId = (int)$_GET['reset_attempts'];
    $userId = (int)($_GET['user_id'] ?? 0);
    if ($userId) {
        $pdo->prepare('DELETE FROM quiz_results WHERE quiz_id=? AND user_id=?')->execute([$qzId, $userId]);
    } else {
        $pdo->prepare('DELETE FROM quiz_results WHERE quiz_id=?')->execute([$qzId]);
    }
    header('Location: ' . SITE_URL . '/admin/quizzes.php');
    exit;
}

// Suppression
if (isset($_GET['delete'])) {
    $pdo->prepare('DELETE FROM quizzes WHERE id = ?')->execute([(int)$_GET['delete']]);
    header('Location: ' . SITE_URL . '/admin/quizzes.php');
    exit;
}

$quizzes = $pdo->query(
    'SELECT qz.*,
            s.titre as module_titre,
            c.titre as course_titre,
            (SELECT COUNT(*) FROM questions q WHERE q.quiz_id = qz.id) as nb_questions,
            (SELECT COUNT(*) FROM quiz_results qr WHERE qr.quiz_id = qz.id) as nb_resultats
     FROM quizzes qz
     LEFT JOIN sequences s ON s.id = qz.sequence_id
     LEFT JOIN modules mo ON mo.id = s.module_id
     LEFT JOIN courses c ON c.id = mo.course_id
     ORDER BY qz.created_at DESC'
)->fetchAll();

$currentPage = 'quizzes.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Quiz — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <div>
      <h1 class="admin-page-title">Quiz</h1>
      <p class="admin-page-sub"><?= count($quizzes) ?> quiz au total</p>
    </div>
    <a href="<?= SITE_URL ?>/admin/quiz_add.php" class="btn-primary">
      <i class="ti ti-plus"></i> Ajouter un quiz
    </a>
  </div>

  <?= flash() ?>

  <?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
  <div class="alert alert-success" style="margin-bottom:16px">
    <i class="ti ti-check-circle"></i> Quiz créé avec succès !
  </div>
  <?php endif; ?>

  <div class="admin-card">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Titre</th>
          <th>Module</th>
          <th>Cours</th>
          <th>Questions</th>
          <th>Résultats</th>
          <th>Seuil</th>
          <th>Statut</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($quizzes)): ?>
        <tr>
          <td colspan="8" style="text-align:center;color:var(--text-muted);padding:40px">
            <i class="ti ti-help-circle" style="font-size:40px;display:block;margin-bottom:10px;color:#e5e7eb"></i>
            Aucun quiz créé
          </td>
        </tr>
        <?php else: ?>
        <?php foreach ($quizzes as $q): ?>
        <tr>
          <td><strong><?= h($q['titre']) ?></strong></td>
          <td><?= $q['module_titre'] ? h($q['module_titre']) : '<span style="color:#9ca3af">—</span>' ?></td>
          <td><?= $q['course_titre'] ? h($q['course_titre']) : '<span style="color:#9ca3af">—</span>' ?></td>
          <td style="text-align:center">
            <span style="background:#FEF3C7;color:#D97706;padding:2px 10px;border-radius:20px;font-size:12px;font-weight:700">
              <?= $q['nb_questions'] ?>
            </span>
          </td>
          <td style="text-align:center"><?= $q['nb_resultats'] ?></td>
          <td style="text-align:center"><?= $q['score_min'] ?>%</td>
          <td>
            <span class="badge <?= $q['actif'] ? 'badge-success' : 'badge-neutral' ?>">
              <?= $q['actif'] ? 'Actif' : 'Inactif' ?>
            </span>
          </td>
          <td style="display:flex;gap:6px">
            <a href="<?= SITE_URL ?>/admin/quiz_edit.php?id=<?= $q['id'] ?>" class="btn-outline btn-sm">
              <i class="ti ti-edit"></i>
            </a>
            <a href="<?= SITE_URL ?>/quiz.php?id=<?= $q['id'] ?>" target="_blank" class="btn-outline btn-sm">
              <i class="ti ti-eye"></i>
            </a>
            <a href="?reset_attempts=<?= $q['id'] ?>"
               class="btn-outline btn-sm"
               style="color:#6b7280"
               onclick="return confirm('Réinitialiser toutes les tentatives pour ce quiz ?')"
               title="Réinitialiser tentatives">
              <i class="ti ti-refresh"></i>
            </a>
            <a href="?delete=<?= $q['id'] ?>"
               class="btn-outline btn-sm"
               style="color:#dc2626;border-color:#fca5a5"
               onclick="return confirm('Supprimer ce quiz ?')">
              <i class="ti ti-trash"></i>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>