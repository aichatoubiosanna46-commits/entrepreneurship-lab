<?php
// admin/submissions.php — Corriger les soumissions d'une activité
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();

$pdo = getPDO();
$activityId = (int)($_GET['activity_id'] ?? $_POST['activity_id'] ?? 0);
if (!$activityId) { header('Location: '.SITE_URL.'/admin/courses.php'); exit; }

$activity = $pdo->prepare(
    'SELECT a.*, s.titre as sequence_titre, s.id as sequence_id
     FROM activities a JOIN sequences s ON s.id = a.sequence_id WHERE a.id = ?'
);
$activity->execute([$activityId]);
$activity = $activity->fetch();
if (!$activity) { http_response_code(404); die('Activité introuvable.'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'corriger') {
    verifierCSRF();
    $subId   = (int)($_POST['submission_id'] ?? 0);
    $note    = ($_POST['note'] ?? '') !== '' ? (int)$_POST['note'] : null;
    $feedback = trim($_POST['feedback'] ?? '');

    $pdo->prepare(
        'UPDATE activity_submissions SET note=?, feedback=?, statut="corrige", corrected_at=NOW() WHERE id=?'
    )->execute([$note, $feedback ?: null, $subId]);

    $sub = $pdo->prepare('SELECT user_id FROM activity_submissions WHERE id = ?');
    $sub->execute([$subId]);
    $sub = $sub->fetch();
    if ($sub) {
        notifierUtilisateur(
            (int)$sub['user_id'], 'Devoir corrigé ✅',
            'Ta soumission « ' . $activity['titre'] . ' » a été corrigée' . ($note !== null ? ' — Note : ' . $note . ($activity['note_max'] ? '/'.$activity['note_max'] : '') : '') . '.',
            'success', SITE_URL . '/sequence.php?id=' . $activity['sequence_id'] . '#activites'
        );
    }

    redirect(SITE_URL.'/admin/submissions.php?activity_id='.$activityId, 'Correction enregistrée !', 'success');
}

$submissions = $pdo->prepare(
    'SELECT sub.*, u.nom, u.prenom, u.email
     FROM activity_submissions sub JOIN users u ON u.id = sub.user_id
     WHERE sub.activity_id = ? ORDER BY sub.created_at DESC'
);
$submissions->execute([$activityId]);
$submissions = $submissions->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Soumissions — <?= h($activity['titre']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/dashboard.css">
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<div class="admin-content">
  <div class="admin-topbar">
    <div>
      <h1 class="admin-page-title">Soumissions — <?= h($activity['titre']) ?></h1>
      <p class="admin-page-sub">
        <a href="<?= SITE_URL ?>/admin/activities.php?sequence_id=<?= $activity['sequence_id'] ?>">← <?= h($activity['sequence_titre']) ?></a>
      </p>
    </div>
  </div>

  <?= flash() ?>

  <?php if (!empty($activity['bareme'])): ?>
  <div class="alert alert-info" style="margin-bottom:20px">
    <i class="ti ti-clipboard-text"></i>
    <div><strong>Grille de notation :</strong> <?= nl2br(h($activity['bareme'])) ?></div>
  </div>
  <?php endif; ?>

  <?php if (empty($submissions)): ?>
  <div style="text-align:center;padding:56px;color:var(--text-muted)">
    <i class="ti ti-inbox" style="font-size:52px;display:block;margin-bottom:16px;opacity:.3"></i>
    <h3 style="color:var(--text);font-weight:500">Aucune soumission pour l'instant</h3>
  </div>
  <?php else: ?>
  <div style="display:flex;flex-direction:column;gap:14px">
    <?php foreach ($submissions as $s): ?>
    <div class="admin-card">
      <div style="display:flex;justify-content:space-between;align-items:start;gap:12px;margin-bottom:10px">
        <div>
          <div style="font-weight:600;font-size:14px"><?= h($s['prenom'].' '.$s['nom']) ?></div>
          <div style="font-size:12px;color:var(--text-muted)"><?= h($s['email']) ?> · <?= date('d/m/Y H:i', strtotime($s['created_at'])) ?></div>
        </div>
        <?php if ($s['statut'] === 'corrige'): ?>
          <span class="badge badge-success">Corrigé<?= $s['note'] !== null ? ' — '.$s['note'].($activity['note_max'] ? '/'.$activity['note_max'] : '') : '' ?></span>
        <?php else: ?>
          <span class="badge" style="background:#FEF3C7;color:#92400E">À corriger</span>
        <?php endif; ?>
      </div>

      <?php if ($s['choix']): ?>
        <p style="font-size:13px;margin-bottom:6px"><strong>Choix :</strong> <?= h($s['choix']) ?></p>
      <?php endif; ?>
      <?php if ($s['contenu']): ?>
        <div style="background:var(--surface-alt,#f9fafb);border-radius:8px;padding:12px;font-size:13px;margin-bottom:10px"><?= nl2br(h($s['contenu'])) ?></div>
      <?php endif; ?>
      <?php if ($s['fichier']): ?>
        <p style="margin-bottom:10px"><a href="<?= SITE_URL ?>/assets/uploads/<?= h($s['fichier']) ?>" download><i class="ti ti-download"></i> Télécharger le fichier</a></p>
      <?php endif; ?>

      <form method="POST" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;border-top:1px solid var(--border,#e5e7eb);padding-top:12px">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="action" value="corriger">
        <input type="hidden" name="activity_id" value="<?= $activityId ?>">
        <input type="hidden" name="submission_id" value="<?= $s['id'] ?>">
        <div class="form-group" style="margin-bottom:0">
          <label>Note <?= $activity['note_max'] ? '(/'.$activity['note_max'].')' : '' ?></label>
          <input type="number" name="note" min="0" max="<?= h((string)($activity['note_max'] ?? 100)) ?>" value="<?= h((string)($s['note'] ?? '')) ?>" style="width:100px">
        </div>
        <div class="form-group" style="margin-bottom:0;flex:1;min-width:240px">
          <label>Feedback</label>
          <input type="text" name="feedback" value="<?= h($s['feedback'] ?? '') ?>" style="width:100%">
        </div>
        <button type="submit" class="btn-primary btn-sm"><i class="ti ti-check"></i> Valider</button>
      </form>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<script src="<?= SITE_URL ?>/assets/js/dashboard.js"></script>
</body>
</html>
