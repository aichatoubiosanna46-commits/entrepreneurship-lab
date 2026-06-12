<?php
// admin/quiz_edit.php — Modifier un quiz et ses questions/réponses
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();
$pdo = getPDO();

$quizId = (int)($_GET['id'] ?? 0);
if (!$quizId) { redirect(SITE_URL . '/admin/quizzes.php', 'Quiz introuvable.', 'error'); }

$quiz = $pdo->prepare('SELECT * FROM quizzes WHERE id = ?');
$quiz->execute([$quizId]);
$quiz = $quiz->fetch();
if (!$quiz) { redirect(SITE_URL . '/admin/quizzes.php', 'Quiz introuvable.', 'error'); }

// Supprimer question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_question') {
    verifierCSRF();
    $qId = (int)($_POST['question_id'] ?? 0);
    $pdo->prepare('DELETE FROM questions WHERE id = ? AND quiz_id = ?')->execute([$qId, $quizId]);
    redirect(SITE_URL . '/admin/quiz_edit.php?id=' . $quizId, 'Question supprimée.', 'success');
}

// Sauvegarder infos quiz
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_quiz') {
    verifierCSRF();
    $titre    = trim($_POST['titre'] ?? '');
    $scoreMin = max(0, min(100, (int)($_POST['score_min'] ?? 70)));
    if ($titre) {
        $pdo->prepare('UPDATE quizzes SET titre=?, score_min=? WHERE id=?')->execute([$titre, $scoreMin, $quizId]);
    }
    redirect(SITE_URL . '/admin/quiz_edit.php?id=' . $quizId, 'Quiz mis à jour.', 'success');
}

// Ajouter question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_question') {
    verifierCSRF();
    $qtext = trim($_POST['question'] ?? '');
    $qtype = $_POST['type'] ?? 'choix_unique';
    $qpts  = max(1, (int)($_POST['points'] ?? 1));
    if ($qtext) {
        $pdo->prepare('INSERT INTO questions (quiz_id, question, type, points) VALUES (?, ?, ?, ?)')->execute([$quizId, $qtext, $qtype, $qpts]);
        $newQid = $pdo->lastInsertId();
        $answers = (array)($_POST['answers'] ?? []);
        $corrects = (array)($_POST['correct'] ?? []);
        foreach ($answers as $ai => $atexte) {
            $atexte = trim($atexte);
            if (!$atexte) continue;
            $isC = in_array((string)$ai, array_map('strval', $corrects)) ? 1 : 0;
            $pdo->prepare('INSERT INTO answers (question_id, texte, est_correct, ordre) VALUES (?, ?, ?, ?)')->execute([$newQid, $atexte, $isC, $ai]);
        }
    }
    redirect(SITE_URL . '/admin/quiz_edit.php?id=' . $quizId, 'Question ajoutée.', 'success');
}

$questions = $pdo->prepare('SELECT * FROM questions WHERE quiz_id = ? ORDER BY ordre ASC, id ASC');
$questions->execute([$quizId]);
$questions = $questions->fetchAll();
foreach ($questions as &$q) {
    $a = $pdo->prepare('SELECT * FROM answers WHERE question_id = ? ORDER BY ordre ASC');
    $a->execute([$q['id']]);
    $q['answers'] = $a->fetchAll();
}
unset($q);

// Stats résultats
$stats = $pdo->prepare('SELECT COUNT(*) as total, SUM(reussi) as reussis, ROUND(AVG(score),1) as moy FROM quiz_results WHERE quiz_id = ?');
$stats->execute([$quizId]);
$stats = $stats->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Éditer quiz — Admin <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/dashboard.css">
<style>
.q-card { background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:16px;margin-bottom:12px; }
.ans-row { display:flex;align-items:center;gap:8px;padding:6px 0;font-size:13px; }
.ans-correct { color:#16a34a;font-weight:600; }
</style>
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <div>
      <h1 class="admin-page-title">Éditer le quiz</h1>
      <p class="admin-page-sub"><?= h($quiz['titre']) ?></p>
    </div>
    <a href="<?= SITE_URL ?>/admin/quizzes.php" class="btn-outline">← Retour</a>
  </div>
  <?= flash() ?>

  <div class="admin-two-col" style="align-items:start">
    <!-- Infos quiz + stats -->
    <div>
      <div class="admin-card">
        <h3 style="font-size:15px;font-weight:700;margin:0 0 16px">Informations du quiz</h3>
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="action" value="save_quiz">
          <div class="form-field" style="margin-bottom:12px"><label>Titre</label><input type="text" name="titre" value="<?= h($quiz['titre']) ?>" required></div>
          <div class="form-field" style="margin-bottom:12px"><label>Score minimum (%)</label><input type="number" name="score_min" min="0" max="100" value="<?= $quiz['score_min'] ?>"></div>
          <button type="submit" class="btn-primary btn-sm"><i class="ti ti-check"></i> Sauvegarder</button>
        </form>
      </div>
      <?php if ($stats['total'] > 0): ?>
      <div class="admin-card" style="margin-top:16px">
        <h3 style="font-size:15px;font-weight:700;margin:0 0 12px">Statistiques</h3>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px">
          <div style="text-align:center;padding:12px;background:#f9fafb;border-radius:8px">
            <div style="font-size:22px;font-weight:800"><?= $stats['total'] ?></div>
            <div style="font-size:11px;color:var(--text-muted)">Tentatives</div>
          </div>
          <div style="text-align:center;padding:12px;background:#EAF3DE;border-radius:8px">
            <div style="font-size:22px;font-weight:800;color:#16a34a"><?= $stats['reussis'] ?></div>
            <div style="font-size:11px;color:var(--text-muted)">Réussites</div>
          </div>
          <div style="text-align:center;padding:12px;background:#EDE9FE;border-radius:8px">
            <div style="font-size:22px;font-weight:800;color:#6C47D4"><?= $stats['moy'] ?>%</div>
            <div style="font-size:11px;color:var(--text-muted)">Score moyen</div>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Questions existantes -->
    <div>
      <div class="admin-card">
        <h3 style="font-size:15px;font-weight:700;margin:0 0 16px">Questions (<?= count($questions) ?>)</h3>
        <?php foreach ($questions as $i => $q): ?>
        <div class="q-card">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px">
            <div>
              <div style="font-size:11px;color:var(--text-muted);margin-bottom:4px">Q<?= $i+1 ?> · <?= h($q['type']) ?> · <?= $q['points'] ?> pt<?= $q['points']>1?'s':'' ?></div>
              <div style="font-weight:600;font-size:14px"><?= h($q['question']) ?></div>
            </div>
            <form method="POST" style="margin:0">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="action" value="delete_question">
              <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
              <button type="submit" class="btn-icon btn-icon-danger" onclick="return confirm('Supprimer cette question ?')" title="Supprimer"><i class="ti ti-trash"></i></button>
            </form>
          </div>
          <?php foreach ($q['answers'] as $a): ?>
          <div class="ans-row">
            <?php if ($a['est_correct']): ?>
              <i class="ti ti-check-circle" style="color:#16a34a;flex-shrink:0"></i>
              <span class="ans-correct"><?= h($a['texte']) ?></span>
            <?php else: ?>
              <i class="ti ti-circle" style="color:#e5e7eb;flex-shrink:0"></i>
              <span><?= h($a['texte']) ?></span>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
        <?php if (empty($questions)): ?>
          <p style="color:var(--text-muted);font-size:13px">Aucune question. Ajoutez-en ci-dessous.</p>
        <?php endif; ?>

        <!-- Ajouter une question -->
        <h3 style="font-size:14px;font-weight:700;margin:20px 0 12px;border-top:1px solid #e5e7eb;padding-top:16px">Ajouter une question</h3>
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="action" value="add_question">
          <div style="display:grid;grid-template-columns:1fr auto auto;gap:10px;margin-bottom:10px;align-items:end">
            <div class="form-field"><label>Question *</label><input type="text" name="question" required placeholder="Votre question..."></div>
            <div class="form-field"><label>Type</label><select name="type"><option value="choix_unique">Choix unique</option><option value="choix_multiple">Choix multiple</option><option value="vrai_faux">Vrai/Faux</option></select></div>
            <div class="form-field"><label>Points</label><input type="number" name="points" value="1" min="1" style="width:60px"></div>
          </div>
          <div style="font-size:12px;font-weight:600;margin-bottom:8px;color:var(--text-muted)">Réponses (cochez les correctes)</div>
          <?php for ($i = 0; $i < 4; $i++): ?>
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
            <input type="checkbox" name="correct[]" value="<?= $i ?>">
            <input type="text" name="answers[<?= $i ?>]" placeholder="Réponse <?= $i+1 ?>..." style="flex:1;padding:7px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px">
          </div>
          <?php endfor; ?>
          <button type="submit" class="btn-primary btn-sm" style="margin-top:10px"><i class="ti ti-plus"></i> Ajouter</button>
        </form>
      </div>
    </div>
  </div>
</div>
</body>
</html>
