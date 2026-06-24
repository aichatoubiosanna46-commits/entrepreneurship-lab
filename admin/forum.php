<?php
// ============================================================
//  admin/forum.php — Modération du forum
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

sendSecurityHeaders();
reqAdmin();

$pdo = getPDO();

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCSRF();
    $topicId = (int)($_POST['topic_id'] ?? 0);
    $replyId = (int)($_POST['reply_id'] ?? 0);
    $action  = $_POST['action'] ?? '';

    if ($topicId) {
        switch ($action) {
            case 'epingle':
                $t = $pdo->prepare('SELECT epingle FROM forum_topics WHERE id = ?');
                $t->execute([$topicId]);
                $row = $t->fetch();
                $pdo->prepare('UPDATE forum_topics SET epingle = ? WHERE id = ?')->execute([!$row['epingle'], $topicId]);
                redirect(SITE_URL . '/admin/forum.php', 'Statut épinglé mis à jour.', 'success');
                break;
            case 'ferme':
                $t = $pdo->prepare('SELECT ferme FROM forum_topics WHERE id = ?');
                $t->execute([$topicId]);
                $row = $t->fetch();
                $pdo->prepare('UPDATE forum_topics SET ferme = ? WHERE id = ?')->execute([!$row['ferme'], $topicId]);
                redirect(SITE_URL . '/admin/forum.php', 'Statut fermé mis à jour.', 'success');
                break;
            case 'delete_topic':
                $pdo->prepare('DELETE FROM forum_topics WHERE id = ?')->execute([$topicId]);
                redirect(SITE_URL . '/admin/forum.php', 'Topic supprimé.', 'success');
                break;
        }
    }
    if ($replyId && $action === 'delete_reply') {
        $pdo->prepare('DELETE FROM forum_replies WHERE id = ?')->execute([$replyId]);
        redirect(SITE_URL . '/admin/forum.php', 'Réponse supprimée.', 'success');
    }
}

[$offset, $pages, $pageCourante] = paginer(
    (int)$pdo->query('SELECT COUNT(*) FROM forum_topics')->fetchColumn()
);

$topics = $pdo->prepare(
    'SELECT ft.*, u.nom, u.prenom,
            (SELECT COUNT(*) FROM forum_replies fr WHERE fr.topic_id = ft.id) AS nb_reponses,
            c.titre AS cours_titre
     FROM forum_topics ft
     JOIN users u ON u.id = ft.user_id
     LEFT JOIN courses c ON c.id = ft.course_id
     ORDER BY ft.epingle DESC, ft.updated_at DESC
     LIMIT 20 OFFSET ?'
);
$topics->execute([$offset]);
$topics = $topics->fetchAll();

$pageTitle = 'Modération Forum';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $pageTitle ?> — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
</head>
<body class="admin-layout">
<?php require_once __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <h1 class="admin-page-title"><i class="ti ti-messages"></i> Modération du Forum</h1>
    <a href="<?= SITE_URL ?>/forum.php" target="_blank" class="btn-outline" style="font-size:13px">
      <i class="ti ti-external-link"></i> Voir le forum
    </a>
  </div>
  <div class="admin-content">
    <?= flash() ?>

    <div style="background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:12px;overflow:hidden">
      <table style="width:100%;border-collapse:collapse">
        <thead style="background:#f9fafb">
          <tr>
            <th style="padding:12px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--text-muted)">Sujet</th>
            <th style="padding:12px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--text-muted)">Auteur</th>
            <th style="padding:12px 16px;text-align:center;font-size:12px;font-weight:600;color:var(--text-muted)">Rép.</th>
            <th style="padding:12px 16px;text-align:center;font-size:12px;font-weight:600;color:var(--text-muted)">Statut</th>
            <th style="padding:12px 16px;text-align:center;font-size:12px;font-weight:600;color:var(--text-muted)">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($topics)): ?>
            <tr><td colspan="5" style="padding:40px;text-align:center;color:var(--text-muted)">Aucun topic.</td></tr>
          <?php else: ?>
            <?php foreach ($topics as $t): ?>
              <tr style="border-top:1px solid var(--border,#e5e7eb)">
                <td style="padding:12px 16px">
                  <a href="<?= SITE_URL ?>/forum_topic.php?id=<?= $t['id'] ?>" target="_blank" style="font-weight:500;font-size:13px;color:inherit">
                    <?= h(mb_substr($t['titre'], 0, 60)) ?><?= strlen($t['titre']) > 60 ? '...' : '' ?>
                  </a>
                  <?php if ($t['cours_titre']): ?>
                    <div style="font-size:11px;color:var(--text-muted)"><?= h($t['cours_titre']) ?></div>
                  <?php endif; ?>
                </td>
                <td style="padding:12px 16px;font-size:13px;color:var(--text-muted)"><?= h($t['prenom'] . ' ' . $t['nom']) ?></td>
                <td style="padding:12px 16px;text-align:center;font-size:13px"><?= $t['nb_reponses'] ?></td>
                <td style="padding:12px 16px;text-align:center">
                  <?php if ($t['epingle']): ?><span style="font-size:11px;background:#FBE3DA;color:#92400E;padding:2px 8px;border-radius:99px;margin:2px"><i class="ti ti-pin"></i></span><?php endif; ?>
                  <?php if ($t['ferme']): ?><span style="font-size:11px;background:#FEE2E2;color:#991B1B;padding:2px 8px;border-radius:99px;margin:2px"><i class="ti ti-lock"></i></span><?php endif; ?>
                  <?php if (!$t['epingle'] && !$t['ferme']): ?><span style="color:#9ca3af;font-size:12px">Ouvert</span><?php endif; ?>
                </td>
                <td style="padding:12px 16px;text-align:center">
                  <div style="display:flex;gap:4px;justify-content:center">
                    <form method="POST">
                      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                      <input type="hidden" name="topic_id" value="<?= $t['id'] ?>">
                      <button type="submit" name="action" value="epingle" title="<?= $t['epingle'] ? 'Désépingler' : 'Épingler' ?>"
                              style="background:none;border:1px solid #e5e7eb;border-radius:6px;padding:5px 8px;cursor:pointer;font-size:14px" title="Épingler/Désépingler">
                        <i class="ti <?= $t['epingle'] ? 'ti-pin-off' : 'ti-pin' ?>"></i>
                      </button>
                    </form>
                    <form method="POST">
                      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                      <input type="hidden" name="topic_id" value="<?= $t['id'] ?>">
                      <button type="submit" name="action" value="ferme" title="<?= $t['ferme'] ? 'Ouvrir' : 'Fermer' ?>"
                              style="background:none;border:1px solid #e5e7eb;border-radius:6px;padding:5px 8px;cursor:pointer;font-size:14px">
                        <i class="ti <?= $t['ferme'] ? 'ti-lock-open' : 'ti-lock' ?>"></i>
                      </button>
                    </form>
                    <form method="POST" onsubmit="return confirm('Supprimer ce topic et ses réponses ?')">
                      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                      <input type="hidden" name="topic_id" value="<?= $t['id'] ?>">
                      <button type="submit" name="action" value="delete_topic"
                              style="background:#FEE2E2;border:1px solid #FECACA;border-radius:6px;padding:5px 8px;cursor:pointer;font-size:14px;color:#991B1B">
                        <i class="ti ti-trash"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
      <div style="display:flex;gap:6px;justify-content:center;margin-top:16px">
        <?php for ($p = 1; $p <= $pages; $p++): ?>
          <a href="?page=<?= $p ?>" style="padding:6px 12px;border-radius:6px;font-size:13px;text-decoration:none;<?= $p === $pageCourante ? 'background:var(--primary);color:#fff' : 'background:#f3f4f6;color:var(--text)' ?>">
            <?= $p ?>
          </a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
