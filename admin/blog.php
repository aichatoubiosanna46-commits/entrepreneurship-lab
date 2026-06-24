<?php
// ============================================================
//  admin/blog.php — Liste des articles de blog
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

sendSecurityHeaders();
reqAdmin();

$pdo = getPDO();

// Suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_article'])) {
    verifierCSRF();
    $pdo->prepare('DELETE FROM blog_articles WHERE id = ?')->execute([(int)$_POST['article_id']]);
    redirect(SITE_URL . '/admin/blog.php', 'Article supprimé.', 'success');
}

[$offset, $pages, $pageCourante] = paginer(
    (int)$pdo->query('SELECT COUNT(*) FROM blog_articles')->fetchColumn()
);

$articles = $pdo->prepare(
    'SELECT ba.*, u.nom, u.prenom FROM blog_articles ba
     LEFT JOIN users u ON u.id = ba.auteur_id
     ORDER BY ba.created_at DESC LIMIT 20 OFFSET ?'
);
$articles->execute([$offset]);
$articles = $articles->fetchAll();

$pageTitle = 'Blog';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $pageTitle ?> — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
</head>
<body class="admin-layout">
<?php require_once __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <h1 class="admin-page-title"><i class="ti ti-news"></i> Blog</h1>
    <a href="blog_add.php" class="btn-primary"><i class="ti ti-plus"></i> Nouvel article</a>
  </div>
  <div class="admin-content">
    <?= flash() ?>

    <div style="background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:12px;overflow:hidden">
      <table style="width:100%;border-collapse:collapse">
        <thead style="background:#f9fafb">
          <tr>
            <th style="padding:12px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--text-muted)">Titre</th>
            <th style="padding:12px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--text-muted)">Auteur</th>
            <th style="padding:12px 16px;text-align:center;font-size:12px;font-weight:600;color:var(--text-muted)">Statut</th>
            <th style="padding:12px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--text-muted)">Date</th>
            <th style="padding:12px 16px;text-align:center;font-size:12px;font-weight:600;color:var(--text-muted)">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($articles)): ?>
            <tr><td colspan="5" style="padding:40px;text-align:center;color:var(--text-muted)">Aucun article. <a href="blog_add.php">Créer le premier</a></td></tr>
          <?php else: ?>
            <?php foreach ($articles as $a): ?>
              <tr style="border-top:1px solid var(--border,#e5e7eb)">
                <td style="padding:12px 16px">
                  <div style="font-weight:500;font-size:13px"><?= h($a['titre']) ?></div>
                  <div style="font-size:11px;color:var(--text-muted);font-family:monospace"><?= h($a['slug']) ?></div>
                </td>
                <td style="padding:12px 16px;font-size:13px;color:var(--text-muted)"><?= $a['prenom'] ? h($a['prenom'] . ' ' . $a['nom']) : 'N/A' ?></td>
                <td style="padding:12px 16px;text-align:center">
                  <span style="font-size:11px;padding:3px 10px;border-radius:99px;font-weight:600;<?= $a['statut'] === 'publie' ? 'background:#EAF3DE;color:#27500A' : 'background:#f3f4f6;color:#6b7280' ?>">
                    <?= $a['statut'] === 'publie' ? 'Publié' : 'Brouillon' ?>
                  </span>
                </td>
                <td style="padding:12px 16px;font-size:12px;color:var(--text-muted)"><?= date('d/m/Y', strtotime($a['created_at'])) ?></td>
                <td style="padding:12px 16px;text-align:center">
                  <div style="display:flex;gap:6px;justify-content:center">
                    <a href="blog_edit.php?id=<?= $a['id'] ?>" class="btn-outline" style="font-size:12px;padding:5px 10px"><i class="ti ti-pencil"></i></a>
                    <?php if ($a['statut'] === 'publie'): ?>
                      <a href="<?= SITE_URL ?>/blog_article.php?slug=<?= h($a['slug']) ?>" target="_blank" class="btn-outline" style="font-size:12px;padding:5px 10px"><i class="ti ti-external-link"></i></a>
                    <?php endif; ?>
                    <form method="POST" onsubmit="return confirm('Supprimer cet article ?')">
                      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                      <input type="hidden" name="article_id" value="<?= $a['id'] ?>">
                      <button type="submit" name="delete_article" class="btn-icon btn-icon-danger">
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

    <?php if ($pages > 1): ?>
      <div style="display:flex;gap:6px;justify-content:center;margin-top:16px">
        <?php for ($p = 1; $p <= $pages; $p++): ?>
          <a href="?page=<?= $p ?>" style="padding:6px 12px;border-radius:6px;font-size:13px;text-decoration:none;<?= $p === $pageCourante ? 'background:var(--primary);color:#fff' : 'background:#f3f4f6;color:var(--text)' ?>"><?= $p ?></a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
