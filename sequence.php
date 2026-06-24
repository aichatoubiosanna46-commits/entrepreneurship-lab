<?php
// sequence.php — Lecteur de leçon (vidéo, texte, PDF, ressources)
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
reqConnecte();

$pdo    = getPDO();
$userId = $_SESSION['user_id'];
$seqId  = (int)($_GET['id'] ?? 0);
if (!$seqId) { header('Location: ' . SITE_URL . '/dashboard.php'); exit; }

// Récupérer la séquence avec son module et cours
$stmt = $pdo->prepare(
    'SELECT s.*, m.titre as module_titre, m.id as module_id, m.course_id,
            c.titre as course_titre, c.slug as course_slug, c.certificat as has_certificat, c.completion_rule, c.redirect_completion,
            cat.couleur as cat_couleur, cat.icone as cat_icone
     FROM sequences s
     JOIN modules m  ON m.id = s.module_id
     JOIN courses c  ON c.id = m.course_id
     JOIN categories cat ON cat.id = c.category_id
     WHERE s.id = ? AND s.actif = 1'
);
$stmt->execute([$seqId]);
$seq = $stmt->fetch();
if (!$seq) { http_response_code(404); echo 'Leçon introuvable.'; exit; }

// ============================================================
// VERROU SÉQUENTIEL CÔTÉ SERVEUR
// Vérifier que la séquence précédente est complétée
// ============================================================
if ($userId && $prevSeq) {
    $prevDone = $pdo->prepare('SELECT id FROM progress WHERE user_id=? AND sequence_id=? AND terminee=1');
    $prevDone->execute([$userId, $prevSeq['id']]);
    if (!$prevDone->fetch() && !($prevSeq['est_optionnel'] ?? 0)) {
        // Séquence précédente non complétée → redirection forcée
        redirect(
            SITE_URL . '/sequence.php?id=' . $prevSeq['id'],
            'Tu dois compléter la leçon précédente avant d\'accéder à celle-ci.',
            'info'
        );
    }
}

// Vérification mot de passe séquence
if (!empty($seq['mot_de_passe'])) {
    $sessionKey = 'seq_unlocked_' . $seq['id'];
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['seq_password'])) {
        if ($_POST['seq_password'] === $seq['mot_de_passe']) {
            $_SESSION[$sessionKey] = true;
        } else {
            $pwError = 'Mot de passe incorrect.';
        }
    }
    if (empty($_SESSION[$sessionKey])):
?>
<div style="max-width:400px;margin:60px auto;background:#fff;border-radius:16px;padding:32px;border:1px solid #e5e7eb;text-align:center">
  <i class="ti ti-lock" style="font-size:48px;color:#F59E0B;display:block;margin-bottom:16px"></i>
  <h2 style="font-size:18px;font-weight:700;color:#1C1917;margin-bottom:8px">Séquence protégée</h2>
  <p style="font-size:13px;color:#6b7280;margin-bottom:20px">Saisissez le mot de passe pour accéder à cette séquence.</p>
  <?php if (isset($pwError)): ?>
  <div style="background:#fef2f2;color:#dc2626;padding:10px;border-radius:8px;font-size:13px;margin-bottom:14px"><?= h($pwError) ?></div>
  <?php endif; ?>
  <form method="POST" style="display:flex;flex-direction:column;gap:12px">
    <input type="password" name="seq_password" placeholder="Mot de passe..." required
      style="padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:14px;font-family:inherit;text-align:center">
    <button type="submit" style="padding:12px;background:#F59E0B;color:#1C1917;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit">
      Déverrouiller
    </button>
  </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body></html>
<?php exit; endif; ?>
<?php } ?>
<?php

// Vérifier inscription
if (!estInscrit($userId, $seq['course_id'])) {
    
    // ── ATTRIBUTION XP ──────────────────────────────────────
    try {
        $xpStmt = $pdo->prepare('SELECT xp_reward FROM sequences WHERE id = ?');
        $xpStmt->execute([$sequence['id']]);
        $xpReward = (int)($xpStmt->fetchColumn() ?: 10);
        $pdo->prepare('UPDATE users SET xp_total = xp_total + ? WHERE id = ?')
            ->execute([$xpReward, $_SESSION['user_id']]);
        // Trigger automation course_completed si applicable
        $progCheck = progressionCours($_SESSION['user_id'], $sequence['course_id'] ?? 0);
        if ($progCheck === 100) {
            triggerAutomation('course_completed', $_SESSION['user_id'], $sequence['course_id'] ?? 0);
        }
    } catch (Exception $e) {}

redirect(SITE_URL . '/module.php?slug=' . urlencode($seq['course_slug']), 'Inscris-toi d\'abord au cours.', 'info');
}

// Séquences du module (navigation)
$siblingsStmt = $pdo->prepare(
    'SELECT id, titre, ordre FROM sequences WHERE module_id = ? AND actif = 1 ORDER BY ordre ASC, id ASC'
);
$siblingsStmt->execute([$seq['module_id']]);
$siblings = $siblingsStmt->fetchAll();

// Navigation précédent / suivant
$prevSeq = null; $nextSeq = null;
foreach ($siblings as $i => $sib) {
    if ($sib['id'] == $seqId) {
        $prevSeq = $siblings[$i - 1] ?? null;
        $nextSeq = $siblings[$i + 1] ?? null;
        break;
    }
}

// Ressources téléchargeables
$resources = $pdo->prepare('SELECT * FROM resources WHERE sequence_id = ? ORDER BY id ASC');
$resources->execute([$seqId]);
$resources = $resources->fetchAll();

// Quiz associé
$quiz = $pdo->prepare('SELECT * FROM quizzes WHERE sequence_id = ? AND actif = 1 LIMIT 1');
$quiz->execute([$seqId]);
$quiz = $quiz->fetch();

// Progression
$progStmt = $pdo->prepare('SELECT * FROM progress WHERE user_id = ? AND sequence_id = ?');
$progStmt->execute([$userId, $seqId]);
$progress = $progStmt->fetch();
$terminee = $progress && $progress['terminee'];

// Commentaires
$comments = $pdo->prepare(
    'SELECT c.*, u.prenom, u.nom, u.avatar
     FROM comments c JOIN users u ON u.id = c.user_id
     WHERE c.sequence_id = ? AND c.actif = 1 AND c.parent_id IS NULL
     ORDER BY c.created_at ASC'
);
$comments->execute([$seqId]);
$comments = $comments->fetchAll();

// Action : marquer comme terminée
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'marquer_terminee') {
    verifierCSRF();
    $pdo->prepare(
        'INSERT INTO progress (user_id, sequence_id, terminee) VALUES (?, ?, 1)
         ON DUPLICATE KEY UPDATE terminee = 1'
    )->execute([$userId, $seqId]);

    // Déclencher automation sequence_completed
    triggerAutomation('sequence_completed', $userId, $seqId);
    addXP($userId, 'sequence', $seq['xp_reward'] ?? 10, 'Séquence complétée : ' . $seq['titre']);

    // Vérifier complétion selon la règle configurée
    $completionRule = $seq['completion_rule'] ?? 'toutes_sequences';
    $coursComplete  = false;

    if ($completionRule === 'toutes_sequences') {
        $pct = progressionCours($userId, $seq['course_id']);
        $coursComplete = ($pct >= 100);
    } elseif ($completionRule === 'pourcentage_70') {
        $pct = progressionCours($userId, $seq['course_id']);
        $coursComplete = ($pct >= 70);
    } elseif ($completionRule === 'pourcentage_50') {
        $pct = progressionCours($userId, $seq['course_id']);
        $coursComplete = ($pct >= 50);
    } elseif ($completionRule === 'quiz_reussi') {
        try {
            $qCheck = $pdo->prepare(
                'SELECT COUNT(*) FROM quizzes qz
                 JOIN sequences s ON s.id = qz.sequence_id
                 JOIN modules m ON m.id = s.module_id
                 WHERE m.course_id = ? AND qz.actif = 1
                 AND qz.id NOT IN (SELECT quiz_id FROM quiz_results WHERE user_id = ? AND reussi = 1)'
            );
            $qCheck->execute([$seq['course_id'], $userId]);
            $coursComplete = ($qCheck->fetchColumn() == 0);
        } catch(Exception $e) { $coursComplete = false; }
    } elseif ($completionRule === 'assignment_accepte') {
        try {
            $aCheck = $pdo->prepare(
                'SELECT COUNT(*) FROM assignments a
                 JOIN sequences s ON s.id = a.sequence_id
                 JOIN modules m ON m.id = s.module_id
                 WHERE m.course_id = ?
                 AND a.id NOT IN (SELECT assignment_id FROM assignment_submissions WHERE user_id = ? AND statut = "accepte")'
            );
            $aCheck->execute([$seq['course_id'], $userId]);
            $coursComplete = ($aCheck->fetchColumn() == 0);
        } catch(Exception $e) { $coursComplete = false; }
    }

    if ($coursComplete && ($seq['has_certificat'] ?? false)) {
        genererCertificat($userId, $seq['course_id']);
    }

    // Redirection après complétion configurable
    $redirectCompletion = $seq['redirect_completion'] ?? null;

    if ($coursComplete && $redirectCompletion) {
        redirect($redirectCompletion, 'Cours complété ! 🎉', 'success');
    }
    $dest = $nextSeq
        ? SITE_URL . '/sequence.php?id=' . $nextSeq['id']
        : SITE_URL . '/module.php?slug=' . urlencode($seq['course_slug']);
    redirect($dest, 'Leçon marquée comme terminée !', 'success');
}

// Action : ajouter commentaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'commenter') {
    verifierCSRF();
    $contenu = trim($_POST['contenu'] ?? '');
    if ($contenu) {
        $pdo->prepare(
            'INSERT INTO comments (sequence_id, user_id, contenu) VALUES (?, ?, ?)'
        )->execute([$seqId, $userId, $contenu]);
    }
    redirect(SITE_URL . '/sequence.php?id=' . $seqId . '#comments');
}

// Progression globale
$pct = progressionCours($userId, $seq['course_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($seq['titre']) ?> — <?= SITE_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<style>
.seq-layout { display: grid; grid-template-columns: 280px 1fr; min-height: calc(100vh - 64px); }
.seq-sidebar {
  border-right: 1px solid var(--border,#e5e7eb);
  background: var(--surface,#fff);
  position: sticky; top: 0; height: 100vh; overflow-y: auto;
  padding-bottom: 24px;
}
.seq-sidebar-head {
  padding: 16px; border-bottom: 1px solid var(--border,#e5e7eb);
  background: var(--surface,#fff); position: sticky; top: 0; z-index: 2;
}
.seq-sidebar-course { font-size: 13px; font-weight: 600; color: var(--text,#111); margin-bottom: 6px; }
.seq-prog-bar { height: 6px; background: #e5e7eb; border-radius: 99px; overflow: hidden; margin-bottom: 4px; }
.seq-prog-fill { height: 100%; background: linear-gradient(90deg,#534AB7,#6C47D4); border-radius: 99px; }
.seq-prog-label { font-size: 11px; color: var(--text-muted,#6b7280); }
.seq-nav-list { padding: 8px 0; }
.seq-nav-item {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 16px; font-size: 13px; color: var(--text,#111);
  text-decoration: none; transition: background .15s;
  border-left: 3px solid transparent;
}
.seq-nav-item:hover { background: var(--surface-alt,#f9fafb); }
.seq-nav-item.active { background: #f0effc; border-left-color: #534AB7; color: #534AB7; font-weight: 600; }
.seq-nav-item.done { color: #16a34a; }
.seq-nav-check {
  width: 20px; height: 20px; border-radius: 50%; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  border: 2px solid #e5e7eb; font-size: 10px;
}
.seq-nav-check.done-c { background: #16a34a; border-color: #16a34a; color: #fff; }
.seq-nav-check.active-c { border-color: #534AB7; background: #f0effc; color: #534AB7; }

.seq-main { padding: 0; min-width: 0; display: flex; flex-direction: column; }
.seq-topbar {
  display: flex; align-items: center; gap: 12px;
  padding: 14px 24px; border-bottom: 1px solid var(--border,#e5e7eb);
  background: var(--surface,#fff); flex-wrap: wrap;
}
.seq-topbar-title { font-size: 16px; font-weight: 700; flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.seq-back { font-size: 13px; color: var(--primary,#534AB7); text-decoration: none; display: flex; align-items: center; gap: 4px; white-space: nowrap; }
.seq-content { padding: 32px 40px; max-width: 860px; }
.seq-content h1 { font-size: 24px; font-weight: 700; margin: 0 0 24px; }

/* Vidéo */
.video-wrap { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 12px; margin-bottom: 24px; background: #000; }
.video-wrap iframe, .video-wrap video { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none; }

/* Contenu texte */
.seq-text-body { font-size: 15px; line-height: 1.8; color: var(--text,#111); margin-bottom: 24px; }
.seq-text-body h2 { font-size: 20px; font-weight: 700; margin: 28px 0 12px; }
.seq-text-body h3 { font-size: 17px; font-weight: 600; margin: 20px 0 8px; }
.seq-text-body ul, .seq-text-body ol { padding-left: 24px; margin: 12px 0; }
.seq-text-body li { margin-bottom: 6px; }
.seq-text-body blockquote { border-left: 4px solid var(--amber,#6C47D4); padding: 10px 16px; background: #f5f3ff; margin: 16px 0; border-radius: 0 8px 8px 0; }

/* PDF */
.pdf-embed { width: 100%; height: 500px; border: 1px solid var(--border,#e5e7eb); border-radius: 10px; margin-bottom: 24px; }

/* Ressources */
.resources-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px; }
.resource-item {
  display: flex; align-items: center; gap: 12px;
  padding: 12px 16px; background: var(--surface-alt,#f9fafb);
  border: 1px solid var(--border,#e5e7eb); border-radius: 10px;
  text-decoration: none; color: var(--text,#111); transition: border-color .15s;
}
.resource-item:hover { border-color: var(--primary,#534AB7); }
.resource-icon { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 18px; }

/* Actions bas de page */
.seq-actions {
  display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
  padding: 24px 40px; border-top: 1px solid var(--border,#e5e7eb);
  background: var(--surface,#fff); margin-top: auto;
}
.btn-complete {
  padding: 12px 24px; font-size: 14px; font-weight: 700;
  border: none; border-radius: 10px; cursor: pointer;
  background: #16a34a; color: #fff; display: flex; align-items: center; gap: 8px;
}
.btn-complete:hover { background: #15803d; }
.btn-complete.done-btn { background: #e5e7eb; color: #6b7280; cursor: default; }
.btn-nav {
  padding: 12px 20px; font-size: 14px; font-weight: 600;
  border: 1px solid var(--border,#e5e7eb); border-radius: 10px;
  background: var(--surface,#fff); color: var(--text,#111);
  text-decoration: none; display: flex; align-items: center; gap: 6px; transition: .15s;
}
.btn-nav:hover { border-color: var(--primary,#534AB7); color: var(--primary,#534AB7); }
.btn-nav.primary { background: var(--primary,#534AB7); color: #fff; border-color: var(--primary,#534AB7); }
.btn-nav.primary:hover { background: #3d369a; }

/* Commentaires */
.comments-section { padding: 32px 40px; border-top: 1px solid var(--border,#e5e7eb); }
.comment-item { display: flex; gap: 12px; margin-bottom: 20px; }
.comment-avatar {
  width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
  background: var(--primary,#534AB7); color: #fff;
  display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px;
}
.comment-body { flex: 1; background: var(--surface-alt,#f9fafb); border-radius: 10px; padding: 12px 14px; }
.comment-meta { font-size: 12px; color: var(--text-muted,#6b7280); margin-bottom: 4px; }
.comment-text { font-size: 14px; line-height: 1.5; }
.comment-form textarea {
  width: 100%; border: 1px solid var(--border,#e5e7eb); border-radius: 10px;
  padding: 12px 14px; font-size: 14px; resize: vertical; min-height: 80px;
  font-family: inherit; box-sizing: border-box;
}
.comment-form textarea:focus { outline: none; border-color: var(--primary,#534AB7); }

/* Quiz banner */
.quiz-banner {
  background: linear-gradient(135deg,#534AB7,#6C47D4); color: #fff;
  border-radius: 12px; padding: 20px 24px; margin-bottom: 24px;
  display: flex; align-items: center; gap: 16px;
}
.quiz-banner i { font-size: 32px; flex-shrink: 0; }
.quiz-banner h3 { margin: 0 0 4px; font-size: 16px; }
.quiz-banner p { margin: 0; font-size: 13px; opacity: .85; }
.quiz-banner a {
  margin-left: auto; padding: 10px 20px; background: #fff; color: #534AB7;
  font-weight: 700; border-radius: 8px; text-decoration: none; font-size: 14px; white-space: nowrap;
}
.quiz-banner a:hover { background: #f0effc; }

@media (max-width: 860px) {
  .seq-layout { grid-template-columns: 1fr; }
  .seq-sidebar { display: none; }
  .seq-content, .seq-actions, .comments-section { padding: 20px 16px; }
}

<style>
/* Watermark vidéo */
.video-container { position: relative; }
.video-watermark {
  position: absolute; top: 10px; right: 10px;
  color: rgba(255,255,255,0.35); font-size: 12px;
  font-weight: 600; pointer-events: none; z-index: 10;
  text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
  user-select: none;
}
/* Anti clic droit */
.video-overlay {
  position: absolute; inset: 0; z-index: 5;
  background: transparent;
}
</style>
</style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>

<?php if (!empty($sequence['deadline']) && strtotime($sequence['deadline']) > time()): ?>
<div style="background:#FEF3C7;border:1px solid #fde68a;border-radius:10px;padding:12px 18px;margin:12px 24px;display:flex;align-items:center;gap:12px">
  <i class="ti ti-clock" style="font-size:20px;color:#D97706;flex-shrink:0"></i>
  <div>
    <div style="font-size:12px;font-weight:700;color:#92400e">Date limite</div>
    <div id="seq-countdown" style="font-size:14px;font-weight:800;color:#D97706"></div>
  </div>
</div>
<script>
(function(){
  const d = new Date("<?= $sequence['deadline'] ?>");
  function tick(){
    const diff = d - new Date();
    if(diff<=0){document.getElementById('seq-countdown').textContent='Délai expiré';return;}
    const days=Math.floor(diff/86400000),h=Math.floor((diff%86400000)/3600000),m=Math.floor((diff%3600000)/60000),s=Math.floor((diff%60000)/1000);
    document.getElementById('seq-countdown').textContent=(days>0?days+'j ':'')+String(h).padStart(2,'0')+'h '+String(m).padStart(2,'0')+'m '+String(s).padStart(2,'0')+'s';
  }
  tick(); setInterval(tick,1000);
})();
</script>
<?php endif; ?>

<div class="seq-layout">
  <!-- Sidebar navigation -->
  <aside class="seq-sidebar">
    <div class="seq-sidebar-head">
      <div class="seq-sidebar-course"><?= h($seq['course_titre']) ?></div>
      <div class="seq-prog-bar"><div class="seq-prog-fill" style="width:<?= $pct ?>%"></div></div>
      <div class="seq-prog-label"><?= $pct ?>% complété</div>
    </div>
    
      <?php if (!empty($seq['embed_code'])): ?>
      <!-- Contenu embarqué (iframe) -->
      <div style="margin-top:20px;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb">
        <?= $seq['embed_code'] ?>
      </div>
      <?php endif; ?>

      <?php if (!empty($seq['pdf_url'])): ?>
      <!-- PDF en lecture directe -->
      <div style="margin-top:20px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
          <div style="font-size:14px;font-weight:600;color:#1C1917"><i class="ti ti-file-type-pdf" style="color:#dc2626"></i> Document PDF</div>
          <a href="<?= h($seq['pdf_url']) ?>" target="_blank" download style="font-size:12px;color:#6b7280;text-decoration:none;padding:6px 12px;border:1px solid #e5e7eb;border-radius:6px">
            <i class="ti ti-download"></i> Télécharger
          </a>
        </div>
        <iframe src="<?= h($seq['pdf_url']) ?>#toolbar=0&navpanes=0" 
                style="width:100%;height:600px;border:1px solid #e5e7eb;border-radius:8px"
                oncontextmenu="return false">
        </iframe>
      </div>
      <?php endif; ?>

      
<?php
// Message félicitations si cours complété
if ($userId) {
    $pct_cours = progressionCours($userId, $course['id'] ?? 0);
    if ($pct_cours >= 100): ?>
<div style="background:linear-gradient(135deg,#FEF3C7,#FFFBEB);border:2px solid #F59E0B;border-radius:16px;padding:28px;margin:24px 0;text-align:center">
    <div style="font-size:48px;margin-bottom:12px">🎉</div>
    <h3 style="font-size:20px;font-weight:800;color:#1C1917;margin-bottom:8px">Félicitations <?= h(explode(' ', $_SESSION['user_nom'] ?? '')[0]) ?> !</h3>
    <p style="font-size:14px;color:#6b7280;margin-bottom:16px">Tu as complété l'intégralité de ce cours. Ton certificat est disponible !</p>
    <a href="<?= SITE_URL ?>/certificate.php?course=<?= $course['id'] ?? 0 ?>"
       style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:#F59E0B;color:#1C1917;border-radius:10px;font-size:14px;font-weight:700;text-decoration:none">
        <i class="ti ti-certificate"></i> Télécharger mon certificat
    </a>
    <a href="<?= SITE_URL ?>/satisfaction.php?course_id=<?= $course['id'] ?? 0 ?>"
       style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:#fff;border:2px solid #F59E0B;color:#D97706;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;margin-top:10px">
        <i class="ti ti-star"></i> Donner mon avis sur ce cours
    </a>
</div>
<?php endif; } ?>

      
      <?php
      // Commentaires horodatés
      if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['video_comment']) && $userId) {
          $timecode  = (int)($_POST['timecode'] ?? 0);
          $comment   = trim($_POST['commentaire'] ?? '');
          if ($comment) {
              try {
                  $pdo->prepare('INSERT INTO video_comments (sequence_id, user_id, timecode, commentaire) VALUES (?,?,?,?)')
                      ->execute([$seq['id'], $userId, $timecode, $comment]);
              } catch(Exception $e) {}
          }
      }
      // Charger commentaires
      try {
          $videoComments = $pdo->prepare(
              'SELECT vc.*, u.nom, u.prenom FROM video_comments vc
               JOIN users u ON u.id=vc.user_id
               WHERE vc.sequence_id=? ORDER BY vc.timecode ASC'
          );
          $videoComments->execute([$seq['id']]);
          $videoComments = $videoComments->fetchAll();
      } catch(Exception $e) { $videoComments = []; }
      ?>
      <?php if (!empty($seq['video_url']) && $userId): ?>
      <div style="margin-top:20px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:14px;padding:20px">
        <div style="font-size:13px;font-weight:700;color:#1C1917;margin-bottom:14px">
          <i class="ti ti-message-circle" style="color:#F59E0B"></i> Commentaires sur la vidéo
        </div>
        <!-- Formulaire -->
        <form method="POST" style="display:flex;gap:10px;margin-bottom:16px;align-items:flex-end">
          <input type="hidden" name="video_comment" value="1">
          <div style="flex:1">
            <label style="font-size:11px;color:#9ca3af;font-weight:600;display:block;margin-bottom:4px">TIMECODE (secondes)</label>
            <input type="number" name="timecode" min="0" value="0" id="videoTimecode"
              style="width:100%;padding:8px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px;font-family:inherit">
          </div>
          <div style="flex:3">
            <label style="font-size:11px;color:#9ca3af;font-weight:600;display:block;margin-bottom:4px">COMMENTAIRE</label>
            <input type="text" name="commentaire" placeholder="Votre commentaire à ce moment de la vidéo..." required
              style="width:100%;padding:8px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px;font-family:inherit;box-sizing:border-box">
          </div>
          <button type="submit" style="padding:8px 14px;background:#F59E0B;color:#1C1917;border:none;border-radius:6px;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap">
            <i class="ti ti-send"></i> Poster
          </button>
        </form>
        <!-- Liste commentaires -->
        <?php if (empty($videoComments)): ?>
        <p style="font-size:13px;color:#9ca3af;text-align:center;padding:12px">Soyez le premier à commenter cette vidéo !</p>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:8px">
          <?php foreach ($videoComments as $vc): ?>
          <?php $min=floor($vc['timecode']/60); $sec=$vc['timecode']%60; ?>
          <div style="display:flex;gap:10px;align-items:flex-start;padding:10px;background:#fff;border-radius:8px;border:1px solid #f3f4f6">
            <button type="button" onclick="seekVideo(<?= $vc['timecode'] ?>)"
              style="font-family:monospace;font-size:11px;font-weight:700;color:#F59E0B;background:#FEF3C7;padding:3px 8px;border-radius:4px;border:none;cursor:pointer;flex-shrink:0">
              <?= sprintf('%d:%02d', $min, $sec) ?>
            </button>
            <div style="flex:1">
              <span style="font-size:12px;font-weight:700;color:#1C1917"><?= h($vc['nom'].' '.$vc['prenom']) ?></span>
              <span style="font-size:11px;color:#9ca3af;margin-left:8px"><?= date('d/m/Y', strtotime($vc['created_at'])) ?></span>
              <p style="font-size:13px;color:#374151;margin:4px 0 0"><?= h($vc['commentaire']) ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <div class="seq-nav-list">
      <?php foreach ($siblings as $sib):
        $isCurrent = $sib['id'] == $seqId;
        $isDone = false;
        $progCheck = $pdo->prepare('SELECT terminee FROM progress WHERE user_id = ? AND sequence_id = ?');
        $progCheck->execute([$userId, $sib['id']]);
        $pr = $progCheck->fetch();
        $isDone = $pr && $pr['terminee'];
      ?>
      <a href="<?= SITE_URL ?>/sequence.php?id=<?= $sib['id'] ?>"
         class="seq-nav-item <?= $isCurrent ? 'active' : '' ?> <?= (!$isCurrent && $isDone) ? 'done' : '' ?>">
        <div class="seq-nav-check <?= $isCurrent ? 'active-c' : ($isDone ? 'done-c' : '') ?>">
          <?php if ($isDone): ?><i class="ti ti-check"></i>
          <?php elseif ($isCurrent): ?><i class="ti ti-player-play" style="font-size:9px"></i>
          <?php endif; ?>
        </div>
        <span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= h($sib['titre']) ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </aside>

  <!-- Contenu principal -->
  <div class="seq-main">
    <div class="seq-topbar">
      <a href="<?= SITE_URL ?>/module.php?slug=<?= h($seq['course_slug']) ?>" class="seq-back">
        <i class="ti ti-arrow-left"></i> <?= h($seq['course_titre']) ?>
      </a>
      <div class="seq-topbar-title"><?= h($seq['module_titre']) ?></div>
      <?php if ($terminee): ?>
        <span style="font-size:12px;color:#16a34a;display:flex;align-items:center;gap:4px">
          <i class="ti ti-check-circle"></i> Terminée
        </span>
      <?php endif; ?>
    </div>

    <div class="seq-content">
      <h1><?= h($seq['titre']) ?></h1>

      <?php if ($seq['video_url']): ?>
      <div class="video-wrap" style="position:relative">
        <?php
        $videoUrl = $seq['video_url'];
        $videoId = '';
        $isYoutube = false;
        $isVimeo = false;
        // YouTube
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $videoUrl, $m)) {
            $videoId = $m[1];
            $isYoutube = true;
            $videoUrl = 'https://www.youtube.com/embed/' . $m[1] . '?rel=0&enablejsapi=1';
        }
        // Vimeo
        if (preg_match('/vimeo\.com\/(\d+)/', $videoUrl, $m)) {
            $isVimeo = true;
            $videoUrl = 'https://player.vimeo.com/video/' . $m[1] . '?api=1';
        }
        // Chapitres
        try {
            $chapStmt = getPDO()->prepare('SELECT * FROM video_chapters WHERE sequence_id = ? ORDER BY timecode ASC');
            $chapStmt->execute([$seq['id']]);
            $chapters = $chapStmt->fetchAll();
        } catch(Exception $e) { $chapters = []; }
        ?>
        <div class="video-container" oncontextmenu="return false">
        <div class="video-overlay"></div>
        <?php if(estConnecte()): ?>
        <div class="video-watermark"><?= h($_SESSION['user_nom'] ?? '') ?></div>
        <?php endif; ?>
        <div style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;border-radius:12px;background:#000">
          <iframe id="videoPlayer"
                  src="<?= h($videoUrl) ?>"
                  style="position:absolute;top:0;left:0;width:100%;height:100%;border:0"
                  allowfullscreen allow="accelerometer;autoplay;clipboard-write;encrypted-media;gyroscope;picture-in-picture">
          </iframe>
        </div>

        <?php if (!empty($chapters)): ?>
        <!-- Chapitres vidéo -->
        <div style="margin-top:16px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:16px">
          <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af;margin-bottom:10px">
            <i class="ti ti-list" style="color:#F59E0B"></i> Chapitres
          </div>
          <div style="display:flex;flex-direction:column;gap:4px">
            <?php foreach ($chapters as $ch): ?>
            <?php
              $min = floor($ch['timecode'] / 60);
              $sec = $ch['timecode'] % 60;
              $label = sprintf('%d:%02d', $min, $sec);
            ?>
            <button type="button"
                    onclick="seekVideo(<?= $ch['timecode'] ?>)"
                    style="display:flex;align-items:center;gap:10px;padding:8px 10px;background:#fff;border:1px solid #e5e7eb;border-radius:8px;cursor:pointer;text-align:left;font-family:inherit;font-size:13px;transition:.15s"
                    onmouseover="this.style.background='#FFFBEB';this.style.borderColor='#F59E0B'"
                    onmouseout="this.style.background='#fff';this.style.borderColor='#e5e7eb'">
              <span style="font-family:monospace;font-size:11px;font-weight:700;color:#F59E0B;background:#FEF3C7;padding:2px 8px;border-radius:4px;flex-shrink:0"><?= $label ?></span>
              <span style="color:#374151"><?= h($ch['titre']) ?></span>
              <i class="ti ti-player-play" style="margin-left:auto;color:#9ca3af;font-size:14px"></i>
            </button>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <script>
        function seekVideo(seconds) {
          const iframe = document.getElementById('videoPlayer');
          <?php if ($isYoutube): ?>
          // YouTube postMessage API
          iframe.contentWindow.postMessage(JSON.stringify({event:'command',func:'seekTo',args:[seconds,true]}), '*');
          iframe.contentWindow.postMessage(JSON.stringify({event:'command',func:'playVideo',args:[]}), '*');
          <?php else: ?>
          // Recharger avec timecode
          const src = iframe.src.split('?')[0];
          iframe.src = src + '?t=' + seconds;
          <?php endif; ?>
        }
        </script>
      </div>
      <?php endif; ?>

      <?php if (!empty($seq['contenu_riche'])): ?>
      <div class="seq-text-body seq-rich-content" style="font-size:15px;line-height:1.8;color:#1C1917">
        <?= $seq['contenu_riche'] ?>
      </div>
      <?php elseif (!empty($seq['contenu'])): ?>
      <div class="seq-text-body" style="font-size:15px;line-height:1.8;color:#1C1917;white-space:pre-wrap">
        <?= h($seq['contenu']) ?>
      </div>
      <?php endif; ?>

      <?php if ($seq['fichier_pdf']): ?>
      <h3 style="font-size:16px;margin-bottom:12px"><i class="ti ti-file-text" style="color:#6C47D4"></i> Document PDF</h3>
      <embed class="pdf-embed" src="<?= SITE_URL ?>/assets/uploads/<?= h($seq['fichier_pdf']) ?>" type="application/pdf">
      <a href="<?= SITE_URL ?>/assets/uploads/<?= h($seq['fichier_pdf']) ?>" download class="btn-nav" style="margin-bottom:24px">
        <i class="ti ti-download"></i> Télécharger le PDF
      </a>
      <?php endif; ?>

      <?php if (!empty($resources)): ?>
      <h3 style="font-size:16px;margin-bottom:12px"><i class="ti ti-paperclip" style="color:#534AB7"></i> Ressources</h3>
      <div class="resources-list">
        <?php foreach ($resources as $r): ?>
        <?php $colors = ['pdf'=>'#F0997B','word'=>'#4472C4','ppt'=>'#D04423','excel'=>'#1D6F42','autre'=>'#6b7280']; ?>
        <a href="<?= SITE_URL ?>/assets/uploads/<?= h($r['fichier']) ?>" download class="resource-item">
          <div class="resource-icon" style="background:<?= $colors[$r['type']] ?? '#6b7280' ?>22;color:<?= $colors[$r['type']] ?? '#6b7280' ?>">
            <i class="ti ti-file"></i>
          </div>
          <div>
            <div style="font-weight:600;font-size:13px"><?= h($r['nom']) ?></div>
            <div style="font-size:11px;color:var(--text-muted)">
              <?= strtoupper($r['type']) ?>
              <?php if ($r['taille_ko']): ?> · <?= number_format($r['taille_ko'] / 1024, 1) ?> Mo<?php endif; ?>
            </div>
          </div>
          <i class="ti ti-download" style="margin-left:auto;color:var(--primary,#534AB7)"></i>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php if ($quiz): ?>
      <div class="quiz-banner">
        <i class="ti ti-help-circle"></i>
        <div>
          <h3><?= h($quiz['titre']) ?></h3>
          <p>Score minimum : <?= $quiz['score_min'] ?>% — Testez vos connaissances !</p>
        </div>
        <a href="<?= SITE_URL ?>/quiz.php?id=<?= $quiz['id'] ?>">Commencer le quiz</a>
      </div>
      <?php endif; ?>
    </div>

    <!-- Actions nav -->
    <div class="seq-actions">
      <?php if ($prevSeq): ?>
        <a href="<?= SITE_URL ?>/sequence.php?id=<?= $prevSeq['id'] ?>" class="btn-nav">
          <i class="ti ti-arrow-left"></i> Précédent
        </a>
      <?php endif; ?>

      <?php if (!$terminee): ?>
      <form method="POST" style="margin:0">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="action" value="marquer_terminee">
        <button type="submit" class="btn-complete" onclick="handleComplete(event)">
          <i class="ti ti-check"></i> Marquer comme terminée
        </button>
      </form>
      <script>
      function handleComplete(e) {
        <?php if ($nextSeq): ?>
        // Lancer auto-avancement si séquence suivante
        setTimeout(() => autoAdvance('<?= SITE_URL ?>/sequence.php?id=<?= $nextSeq['id'] ?>'), 100);
        <?php endif; ?>
      }
      </script>
      <?php else: ?>
        <span class="btn-complete done-btn"><i class="ti ti-check"></i> Leçon terminée</span>
      <?php endif; ?>

      <?php if ($nextSeq): ?>
        <a href="<?= SITE_URL ?>/sequence.php?id=<?= $nextSeq['id'] ?>" class="btn-nav primary" style="margin-left:auto">
          Suivant <i class="ti ti-arrow-right"></i>
        </a>
      <?php else: ?>
        <a href="<?= SITE_URL ?>/module.php?slug=<?= h($seq['course_slug']) ?>" class="btn-nav primary" style="margin-left:auto">
          Terminer le cours <i class="ti ti-flag"></i>
        </a>
      <?php endif; ?>
    </div>

    <!-- Commentaires -->
    <div class="comments-section" id="comments">
      <h2 style="font-size:18px;font-weight:700;margin:0 0 20px;display:flex;align-items:center;gap:8px">
        <i class="ti ti-message-circle" style="color:var(--primary,#534AB7)"></i>
        Commentaires (<?= count($comments) ?>)
      </h2>

      <?php foreach ($comments as $c): ?>
      <div class="comment-item">
        <div class="comment-avatar">
          <?php if ($c['avatar']): ?>
            <img src="<?= SITE_URL ?>/assets/uploads/<?= h($c['avatar']) ?>" alt="" style="width:100%;height:100%;border-radius:50%;object-fit:cover">
          <?php else: ?>
            <?= mb_strtoupper(mb_substr($c['prenom'], 0, 1)) ?>
          <?php endif; ?>
        </div>
        <div class="comment-body">
          <div class="comment-meta">
            <strong><?= h($c['prenom'] . ' ' . $c['nom']) ?></strong>
            · <?= date('d/m/Y à H:i', strtotime($c['created_at'])) ?>
          </div>
          <div class="comment-text"><?= h($c['contenu']) ?></div>
        </div>
      </div>
      <?php endforeach; ?>

      <form method="POST" class="comment-form" style="margin-top:16px">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="action" value="commenter">
        <textarea name="contenu" placeholder="Écris un commentaire ou pose une question..." required></textarea>
        <button type="submit" class="btn-complete" style="margin-top:10px">
          <i class="ti ti-send"></i> Publier
        </button>
      </form>
    </div>

  </div><!-- /seq-main -->
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>

<script>
// Auto-avancement après marquage comme complétée
function autoAdvance(nextUrl) {
  if (!nextUrl) return;
  const banner = document.createElement('div');
  banner.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#1C1917;color:#fff;padding:14px 24px;border-radius:12px;font-size:14px;font-weight:600;z-index:9999;display:flex;align-items:center;gap:12px;box-shadow:0 8px 32px rgba(0,0,0,.3)';
  banner.innerHTML = '<i class="ti ti-arrow-right" style="color:#F59E0B;font-size:18px"></i> Passage automatique à la séquence suivante... <span id="countdown" style="color:#F59E0B;font-weight:800">5</span>';
  document.body.appendChild(banner);
  let n = 5;
  const t = setInterval(() => {
    n--;
    const el = document.getElementById('countdown');
    if (el) el.textContent = n;
    if (n <= 0) { clearInterval(t); window.location.href = nextUrl; }
  }, 1000);
  banner.addEventListener('click', () => { clearInterval(t); banner.remove(); });
}
</script>

</body>
</html>
