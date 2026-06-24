<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
reqConnecte();

$pdo      = getPDO();
$userId   = $_SESSION['user_id'];
$courseId = (int)($_GET['course_id'] ?? 0);
if (!$courseId) redirect(SITE_URL . '/dashboard.php');

$course = $pdo->prepare('SELECT * FROM courses WHERE id = ? LIMIT 1');
$course->execute([$courseId]);
$course = $course->fetch();
if (!$course) redirect(SITE_URL . '/dashboard.php');

$succes = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reponses = $_POST['reponses'] ?? [];
    try {
        // Chercher ou créer le formulaire de satisfaction pour ce cours
        $form = $pdo->prepare('SELECT id FROM satisfaction_forms WHERE course_id = ? LIMIT 1');
        $form->execute([$courseId]);
        $form = $form->fetch();

        if (!$form) {
            $questions = json_encode([
                ['q' => 'Comment évaluez-vous la qualité globale du cours ?', 'type' => 'stars'],
                ['q' => 'Le contenu était-il adapté à vos besoins ?', 'type' => 'stars'],
                ['q' => 'Recommanderiez-vous ce cours à un ami ?', 'type' => 'nps'],
                ['q' => 'Qu\'avez-vous le plus apprécié dans ce cours ?', 'type' => 'text'],
                ['q' => 'Qu\'est-ce qui pourrait être amélioré ?', 'type' => 'text'],
            ]);
            $pdo->prepare('INSERT INTO satisfaction_forms (course_id, questions) VALUES (?,?)')->execute([$courseId, $questions]);
            $formId = $pdo->lastInsertId();
        } else {
            $formId = $form['id'];
        }

        $pdo->prepare('INSERT INTO satisfaction_reponses (form_id, user_id, reponses) VALUES (?,?,?)')
            ->execute([$formId, $userId, json_encode($reponses)]);
        $succes = true;
    } catch (Exception $e) {
        // silent
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Votre avis — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<style>
body{background:#FFFBEB;font-family:'Plus Jakarta Sans',sans-serif}
.sat-wrap{max-width:600px;margin:0 auto;padding:48px 24px}
.sat-card{background:#fff;border-radius:20px;padding:40px;border:1px solid #e5e7eb;box-shadow:0 4px 24px rgba(0,0,0,.06)}
.sat-card h1{font-size:22px;font-weight:800;color:#1C1917;margin-bottom:6px}
.sat-card p{font-size:14px;color:#6b7280;margin-bottom:28px}
.question{margin-bottom:24px}
.question label{font-size:14px;font-weight:600;color:#1C1917;display:block;margin-bottom:10px}
.stars{display:flex;gap:8px}
.star-btn{font-size:28px;cursor:pointer;opacity:.4;transition:.15s;background:none;border:none;padding:0}
.star-btn.active,.star-btn:hover{opacity:1}
.nps-grid{display:flex;gap:6px;flex-wrap:wrap}
.nps-btn{width:40px;height:40px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;background:#fff;transition:.15s;font-family:inherit}
.nps-btn.active{background:#F59E0B;border-color:#F59E0B;color:#1C1917}
.text-answer{width:100%;padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;font-family:inherit;resize:vertical;min-height:80px;box-sizing:border-box}
.text-answer:focus{outline:none;border-color:#F59E0B}
.btn-submit{width:100%;padding:14px;background:linear-gradient(135deg,#F59E0B,#D97706);color:#1C1917;border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;margin-top:8px}
</style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="sat-wrap">
  <?php if ($succes): ?>
  <div class="sat-card" style="text-align:center">
    <div style="font-size:64px;margin-bottom:16px">🙏</div>
    <h1>Merci pour votre avis !</h1>
    <p>Votre retour nous aide à améliorer continuellement la qualité des formations Ariziki.</p>
    <a href="<?= SITE_URL ?>/dashboard.php" style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:#F59E0B;color:#1C1917;border-radius:10px;font-size:14px;font-weight:700;text-decoration:none;margin-top:16px">
      <i class="ti ti-arrow-left"></i> Retour au tableau de bord
    </a>
  </div>
  <?php else: ?>
  <div class="sat-card">
    <div style="font-size:32px;margin-bottom:12px">📋</div>
    <h1>Votre avis sur "<?= h($course['titre']) ?>"</h1>
    <p>3 minutes pour nous aider à améliorer votre expérience. Merci !</p>

    <form method="POST" id="satForm">
      <div class="question">
        <label>1. Comment évaluez-vous la qualité globale du cours ?</label>
        <div class="stars" id="stars-1">
          <?php for($i=1;$i<=5;$i++): ?>
          <button type="button" class="star-btn" data-val="<?= $i ?>" onclick="setStar(1,<?= $i ?>)">⭐</button>
          <?php endfor; ?>
        </div>
        <input type="hidden" name="reponses[qualite]" id="r-qualite" value="">
      </div>

      <div class="question">
        <label>2. Le contenu était-il adapté à vos besoins ?</label>
        <div class="stars" id="stars-2">
          <?php for($i=1;$i<=5;$i++): ?>
          <button type="button" class="star-btn" data-val="<?= $i ?>" onclick="setStar(2,<?= $i ?>)">⭐</button>
          <?php endfor; ?>
        </div>
        <input type="hidden" name="reponses[pertinence]" id="r-pertinence" value="">
      </div>

      <div class="question">
        <label>3. Sur 10, recommanderiez-vous ce cours à un ami ?</label>
        <div class="nps-grid">
          <?php for($i=0;$i<=10;$i++): ?>
          <button type="button" class="nps-btn" onclick="setNPS(<?= $i ?>)"><?= $i ?></button>
          <?php endfor; ?>
        </div>
        <input type="hidden" name="reponses[nps]" id="r-nps" value="">
        <div style="display:flex;justify-content:space-between;font-size:11px;color:#9ca3af;margin-top:4px">
          <span>Pas du tout</span><span>Absolument</span>
        </div>
      </div>

      <div class="question">
        <label>4. Qu'avez-vous le plus apprécié dans ce cours ?</label>
        <textarea class="text-answer" name="reponses[positif]" placeholder="Ce qui m'a le plus aidé..."></textarea>
      </div>

      <div class="question">
        <label>5. Qu'est-ce qui pourrait être amélioré ?</label>
        <textarea class="text-answer" name="reponses[amelioration]" placeholder="Je suggère..."></textarea>
      </div>

      <button type="submit" class="btn-submit">
        <i class="ti ti-send"></i> Envoyer mon avis
      </button>
    </form>
  </div>
  <?php endif; ?>
</div>

<script>
function setStar(group, val) {
  const container = document.getElementById('stars-' + group);
  const btns = container.querySelectorAll('.star-btn');
  const fields = {1: 'r-qualite', 2: 'r-pertinence'};
  document.getElementById(fields[group]).value = val;
  btns.forEach((b, i) => b.classList.toggle('active', i < val));
}
function setNPS(val) {
  document.querySelectorAll('.nps-btn').forEach((b,i) => b.classList.toggle('active', i === val));
  document.getElementById('r-nps').value = val;
}
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
