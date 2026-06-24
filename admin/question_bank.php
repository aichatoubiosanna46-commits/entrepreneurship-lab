<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();
$pdo = getPDO();

// Suppression
if (isset($_GET['delete'])) {
    $pdo->prepare('DELETE FROM question_bank WHERE id=?')->execute([(int)$_GET['delete']]);
    redirect(SITE_URL . '/admin/question_bank.php', 'Question supprimée.', 'success');
}

// Ajout
$erreur = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question   = trim($_POST['question'] ?? '');
    $type       = $_POST['type'] ?? 'choix_unique';
    $categorie  = trim($_POST['categorie'] ?? '');
    $points     = (int)($_POST['points'] ?? 1);
    $explication = trim($_POST['explication'] ?? '');
    $atextes    = $_POST['a_texte'] ?? [];
    $acorrects  = $_POST['a_correct'] ?? [];

    if (!$question) { $erreur = 'La question est requise.'; }
    else {
        $pdo->prepare('INSERT INTO question_bank (question, type, categorie, points, explication) VALUES (?,?,?,?,?)')
            ->execute([$question, $type, $categorie, $points, $explication]);
        $qId = (int)$pdo->lastInsertId();
        foreach ($atextes as $ai => $atext) {
            $atext = trim($atext);
            if (!$atext) continue;
            $pdo->prepare('INSERT INTO question_bank_answers (question_id, texte, est_correct) VALUES (?,?,?)')
                ->execute([$qId, $atext, isset($acorrects[$ai]) ? 1 : 0]);
        }
        redirect(SITE_URL . '/admin/question_bank.php', 'Question ajoutée à la banque !', 'success');
    }
}

$questions = [];
try {
    $questions = $pdo->query('SELECT q.*, COUNT(a.id) as nb_answers FROM question_bank q LEFT JOIN question_bank_answers a ON a.question_id=q.id GROUP BY q.id ORDER BY q.created_at DESC')->fetchAll();
} catch(Exception $e) {}
$currentPage = 'question_bank.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Banque de questions — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
<style>
.fld{width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;font-family:inherit;box-sizing:border-box}
</style>
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <div><h1 class="admin-page-title">Banque de questions</h1><p class="admin-page-sub">Questions réutilisables dans vos quiz</p></div>
  </div>
  <?= flash() ?>
  <?php if ($erreur): ?><div class="alert alert-error"><?= h($erreur) ?></div><?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 380px;gap:20px">
    <!-- Liste -->
    <div class="admin-card">
      <div class="admin-card-title">Questions enregistrées (<?= count($questions) ?>)</div>
      <?php if (empty($questions)): ?>
      <p style="text-align:center;color:#9ca3af;padding:32px">Aucune question dans la banque</p>
      <?php else: ?>
      <table class="admin-table">
        <thead><tr><th>Question</th><th>Type</th><th>Catégorie</th><th>Réponses</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($questions as $q): ?>
          <tr>
            <td style="max-width:300px;font-size:13px"><?= h(mb_substr($q['question'],0,80)) ?>...</td>
            <td><span class="badge badge-neutral"><?= h($q['type']) ?></span></td>
            <td style="font-size:12px;color:#6b7280"><?= h($q['categorie'] ?? '—') ?></td>
            <td style="text-align:center"><?= $q['nb_answers'] ?></td>
            <td>
              <a href="?delete=<?= $q['id'] ?>" class="btn-outline btn-sm" style="color:#dc2626"
                 onclick="return confirm('Supprimer cette question ?')">
                <i class="ti ti-trash"></i>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>

    <!-- Formulaire ajout -->
    <div class="admin-card" style="position:sticky;top:80px;height:fit-content">
      <div class="admin-card-title">Ajouter une question</div>
      <form method="POST">
        <div class="form-group">
          <label>Question *</label>
          <textarea name="question" class="fld" rows="3" placeholder="Texte de la question..." required></textarea>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
          <div class="form-group">
            <label>Type</label>
            <select name="type" class="fld">
              <option value="choix_unique">Choix unique</option>
              <option value="choix_multiple">Choix multiple</option>
              <option value="vrai_faux">Vrai / Faux</option>
              <option value="texte_libre">Texte libre</option>
            </select>
          </div>
          <div class="form-group">
            <label>Points</label>
            <input type="number" name="points" value="1" min="1" max="10" class="fld">
          </div>
        </div>
        <div class="form-group">
          <label>Catégorie</label>
          <input type="text" name="categorie" class="fld" placeholder="Ex: Entrepreneuriat, Finance...">
        </div>
        <div style="font-size:12px;font-weight:600;color:#6b7280;margin:12px 0 8px">RÉPONSES (coche = correcte)</div>
        <?php for ($ai=0; $ai<4; $ai++): ?>
        <div style="display:flex;gap:8px;align-items:center;margin-bottom:8px">
          <input type="checkbox" name="a_correct[<?= $ai ?>]" value="1" style="width:16px;height:16px;accent-color:#F59E0B;flex-shrink:0">
          <input type="text" name="a_texte[<?= $ai ?>]" placeholder="Réponse <?= chr(65+$ai) ?>..." class="fld">
        </div>
        <?php endfor; ?>
        <div class="form-group">
          <label>Explication</label>
          <textarea name="explication" class="fld" rows="2" placeholder="Explication après réponse..."></textarea>
        </div>
        <button type="submit" class="btn-primary btn-full" style="margin-top:8px">
          <i class="ti ti-plus"></i> Ajouter à la banque
        </button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
