<?php
// ============================================================
//  forum_topic.php — Affiche un topic et ses réponses
// ============================================================
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';


<?php
// AJAX réaction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'react') {
    header('Content-Type: application/json');
    if (!estConnecte()) { echo json_encode(['error' => 'Non connecté']); exit; }
    $topicId = (int)($_POST['topic_id'] ?? 0);
    $replyId = (int)($_POST['reply_id'] ?? 0) ?: null;
    $emoji   = trim($_POST['emoji'] ?? '👍');
    $userId  = $_SESSION['user_id'];
    try {
        // Toggle réaction
        $check = $pdo->prepare('SELECT id FROM forum_reactions WHERE topic_id=? AND reply_id IS ? AND user_id=? AND emoji=?');
        $check->execute([$topicId, $replyId, $userId, $emoji]);
        if ($check->fetch()) {
            $pdo->prepare('DELETE FROM forum_reactions WHERE topic_id=? AND reply_id IS ? AND user_id=? AND emoji=?')
                ->execute([$topicId, $replyId, $userId, $emoji]);
            $action = 'removed';
        } else {
            $pdo->prepare('INSERT IGNORE INTO forum_reactions (topic_id, reply_id, user_id, emoji) VALUES (?,?,?,?)')
                ->execute([$topicId, $replyId, $userId, $emoji]);
            $action = 'added';
        }
        // Compter
        $count = $pdo->prepare('SELECT COUNT(*) FROM forum_reactions WHERE topic_id=? AND reply_id IS ? AND emoji=?');
        $count->execute([$topicId, $replyId, $emoji]);
        echo json_encode(['action' => $action, 'count' => (int)$count->fetchColumn()]);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}
?>


require_once __DIR__ . '/includes/security.php';

sendSecurityHeaders();

$pdo = getPDO();
$id  = (int)($_GET['id'] ?? 0);

if (!$id) redirect(SITE_URL . '/forum.php');

$stmt = $pdo->prepare(
    'SELECT ft.*, u.nom, u.prenom, u.email,
            c.titre AS cours_titre, c.id AS cours_id
     FROM forum_topics ft
     JOIN users u ON u.id = ft.user_id
     LEFT JOIN courses c ON c.id = ft.course_id
     WHERE ft.id = ?'
);
$stmt->execute([$id]);
$topic = $stmt->fetch();

if (!$topic) redirect(SITE_URL . '/forum.php', 'Sujet introuvable.', 'error');

// Incrémenter le compteur de vues
$pdo->prepare('UPDATE forum_topics SET nb_vues = nb_vues + 1 WHERE id = ?')->execute([$id]);

// Charger les réponses
$repStmt = $pdo->prepare(
    'SELECT fr.*, u.nom, u.prenom FROM forum_replies fr
     JOIN users u ON u.id = fr.user_id
     WHERE fr.topic_id = ?
     ORDER BY fr.created_at ASC'
);
$repStmt->execute([$id]);
$reponses = $repStmt->fetchAll();

// Soumission réponse
$erreur = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contenu'])) {
    reqConnecte();
    verifierCSRF();

    if ($topic['ferme']) {
        $erreur = 'Ce sujet est fermé.';
    } else {
        $contenu = trim($_POST['contenu'] ?? '');
        if (strlen($contenu) < 5) {
            $erreur = 'La réponse est trop courte.';
        } else {
            $pdo->prepare(
                'INSERT INTO forum_replies (topic_id, user_id, contenu) VALUES (?, ?, ?)'
            )->execute([$id, $_SESSION['user_id'], $contenu]);
            $pdo->prepare('UPDATE forum_topics SET updated_at = NOW() WHERE id = ?')->execute([$id]);

            // Notifier l'auteur du topic
            if ($topic['user_id'] != $_SESSION['user_id']) {
                notifierUtilisateur(
                    $topic['user_id'],
                    'Nouvelle réponse sur votre sujet',
                    h($_SESSION['user_nom']) . ' a répondu à « ' . $topic['titre'] . ' »',
                    'info',
                    SITE_URL . '/forum_topic.php?id=' . $id
                );
            }
            redirect(SITE_URL . '/forum_topic.php?id=' . $id, 'Réponse publiée.', 'success');
        }
    }
}

$pageTitle = h($topic['titre']);
require_once __DIR__ . '/includes/header.php';
?>
<div class="container" style="max-width:880px;margin:0 auto;padding:32px 16px">

  <a href="<?= SITE_URL ?>/forum.php" style="font-size:13px;color:var(--text-muted);text-decoration:none;margin-bottom:16px;display:inline-flex;align-items:center;gap:4px">
    <i class="ti ti-arrow-left"></i> Retour au forum
  </a>

  <!-- Topic principal -->
  <div style="background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:24px;margin-top:12px;margin-bottom:24px">
    <div style="display:flex;gap:12px;align-items:flex-start">
      <div style="width:44px;height:44px;border-radius:50%;background:var(--primary-light,#EDE9FE);display:flex;align-items:center;justify-content:center;font-weight:600;color:var(--primary,#6C47D4);flex-shrink:0;font-size:18px">
        <?= mb_strtoupper(mb_substr($topic['nom'], 0, 1)) ?>
      </div>
      <div style="flex:1">
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:6px">
          <?php if ($topic['epingle']): ?>
            <span style="background:#FEF3C7;color:#92400E;font-size:11px;font-weight:600;padding:2px 8px;border-radius:99px"><i class="ti ti-pin"></i> Épinglé</span>
          <?php endif; ?>
          <?php if ($topic['ferme']): ?>
            <span style="background:#FEE2E2;color:#991B1B;font-size:11px;font-weight:600;padding:2px 8px;border-radius:99px"><i class="ti ti-lock"></i> Fermé</span>
          <?php endif; ?>
          <?php if ($topic['cours_titre']): ?>
            <span style="background:#EDE9FE;color:#6C47D4;font-size:11px;font-weight:600;padding:2px 8px;border-radius:99px"><?= h($topic['cours_titre']) ?></span>
          <?php endif; ?>
        </div>
        <h1 style="font-size:20px;font-weight:600;margin:0 0 8px"><?= h($topic['titre']) ?></h1>
        <div style="font-size:12px;color:var(--text-muted);margin-bottom:12px">
          Par <strong><?= h($topic['prenom'] . ' ' . $topic['nom']) ?></strong>
          · <?= date('d/m/Y à H:i', strtotime($topic['created_at'])) ?>
          · <i class="ti ti-eye"></i> <?= $topic['nb_vues'] ?> vues
        </div>
        <div style="font-size:15px;line-height:1.6;white-space:pre-wrap"><?= h($topic['contenu']) ?></div>
      </div>
    </div>
  </div>

  <!-- Réponses -->
  <?php if (!empty($reponses)): ?>
    <h2 style="font-size:16px;font-weight:600;margin-bottom:16px"><?= count($reponses) ?> réponse(s)</h2>
    <?php foreach ($reponses as $r): ?>
      <div style="background:<?= $r['est_solution'] ? '#EAF3DE' : '#fff' ?>;border:1px solid <?= $r['est_solution'] ? '#97C459' : 'var(--border,#e5e7eb)' ?>;border-radius:12px;padding:20px;margin-bottom:16px">
        <div style="display:flex;gap:12px;align-items:flex-start">
          <div style="width:36px;height:36px;border-radius:50%;background:#f3f4f6;display:flex;align-items:center;justify-content:center;font-weight:600;font-size:14px;flex-shrink:0">
            <?= mb_strtoupper(mb_substr($r['nom'], 0, 1)) ?>
          </div>
          <div style="flex:1">
            <div style="font-size:13px;color:var(--text-muted);margin-bottom:8px">
              <strong><?= h($r['prenom'] . ' ' . $r['nom']) ?></strong>
              · <?= date('d/m/Y à H:i', strtotime($r['created_at'])) ?>
              <?php if ($r['est_solution']): ?>
                <span style="background:#97C459;color:#fff;font-size:11px;padding:2px 8px;border-radius:99px;margin-left:8px"><i class="ti ti-check"></i> Solution</span>
              <?php endif; ?>
            </div>
            <div style="font-size:14px;line-height:1.6;white-space:pre-wrap"><?= h($r['contenu']) ?></div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <!-- Formulaire répondre -->
  <?php if (estConnecte() && !$topic['ferme']): ?>
    <div style="background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:24px;margin-top:24px">
      <h3 style="font-size:16px;font-weight:600;margin:0 0 16px">Ajouter une réponse</h3>
      <?php if ($erreur): ?>
        <div class="alert alert-error"><i class="ti ti-alert-circle"></i> <?= h($erreur) ?></div>
      <?php endif; ?>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <div class="form-group">
          <textarea name="contenu" rows="5" placeholder="Votre réponse..." required
                    style="width:100%;padding:12px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:14px;resize:vertical;font-family:inherit;box-sizing:border-box"></textarea>
        </div>
        <button type="submit" class="btn-primary">
          <i class="ti ti-send"></i> Publier ma réponse
        </button>
      </form>
    </div>
  <?php elseif (!estConnecte()): ?>
    <div style="background:#f9fafb;border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:24px;text-align:center;margin-top:24px">
      <p style="margin:0 0 12px;color:var(--text-muted)">Connectez-vous pour répondre à ce sujet.</p>
      <a href="<?= SITE_URL ?>/login.php" class="btn-primary">Se connecter</a>
    </div>
  <?php elseif ($topic['ferme']): ?>
    <div style="background:#FEE2E2;border:1px solid #FECACA;border-radius:8px;padding:12px 16px;margin-top:24px;color:#991B1B;font-size:14px">
      <i class="ti ti-lock"></i> Ce sujet est fermé aux nouvelles réponses.
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
