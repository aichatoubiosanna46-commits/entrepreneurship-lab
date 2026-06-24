<?php
// ============================================================
//  blog.php — Liste des articles de blog
// ============================================================
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

sendSecurityHeaders();

$pdo = getPDO();

$countStmt = $pdo->query('SELECT COUNT(*) FROM blog_articles WHERE statut = "publie"');
$total     = (int)$countStmt->fetchColumn();

[$offset, $pages, $pageCourante] = paginer($total, 9);

$stmt = $pdo->prepare(
    'SELECT ba.*, u.nom, u.prenom
     FROM blog_articles ba
     LEFT JOIN users u ON u.id = ba.auteur_id
     WHERE ba.statut = "publie"
     ORDER BY ba.published_at DESC
     LIMIT 9 OFFSET ?'
);
$stmt->execute([$offset]);
$articles = $stmt->fetchAll();

$pageTitle = 'Blog';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container" style="max-width:1100px;margin:0 auto;padding:32px 16px">

  <div style="margin-bottom:32px">
    <h1 style="font-size:28px;font-weight:700;margin:0 0 8px">Blog</h1>
    <p style="color:var(--text-muted);margin:0">Actualités, conseils et ressources pour entrepreneurs</p>
  </div>

  <?php if (empty($articles)): ?>
    <div style="text-align:center;padding:60px;color:var(--text-muted)">
      <i class="ti ti-news" style="font-size:48px;display:block;margin-bottom:12px;opacity:.4"></i>
      Aucun article publié pour le moment.
    </div>
  <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:24px;margin-bottom:32px">
      <?php foreach ($articles as $a): ?>
        <a href="<?= SITE_URL ?>/blog_article.php?slug=<?= h($a['slug']) ?>"
           style="background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:12px;overflow:hidden;text-decoration:none;color:inherit;display:flex;flex-direction:column;transition:.2s"
           onmouseover="this.style.boxShadow='0 4px 20px rgba(0,0,0,.08)'" onmouseout="this.style.boxShadow=''">

          <?php if ($a['image_cover']): ?>
            <img src="<?= SITE_URL ?>/assets/uploads/<?= h($a['image_cover']) ?>"
                 alt="<?= h($a['titre']) ?>"
                 style="width:100%;height:200px;object-fit:cover">
          <?php else: ?>
            <div style="height:160px;background:linear-gradient(135deg,#6C47D4,#4C1D95);display:flex;align-items:center;justify-content:center">
              <i class="ti ti-news" style="font-size:48px;color:rgba(255,255,255,.4)"></i>
            </div>
          <?php endif; ?>

          <div style="padding:20px;flex:1;display:flex;flex-direction:column">
            <h2 style="font-size:17px;font-weight:600;margin:0 0 8px;line-height:1.4"><?= h($a['titre']) ?></h2>
            <?php if ($a['resume']): ?>
              <p style="font-size:13px;color:var(--text-muted);margin:0 0 12px;line-height:1.5;flex:1">
                <?= h(mb_substr($a['resume'], 0, 120)) ?>...
              </p>
            <?php endif; ?>
            <div style="font-size:12px;color:var(--text-muted);margin-top:auto;display:flex;justify-content:space-between">
              <span><?= $a['prenom'] ? h($a['prenom'] . ' ' . $a['nom']) : SITE_NAME ?></span>
              <span><?= $a['published_at'] ? date('d/m/Y', strtotime($a['published_at'])) : '' ?></span>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
      <div style="display:flex;gap:6px;justify-content:center">
        <?php for ($p = 1; $p <= $pages; $p++): ?>
          <a href="?page=<?= $p ?>" style="padding:6px 12px;border-radius:6px;font-size:13px;text-decoration:none;<?= $p === $pageCourante ? 'background:var(--primary,#6C47D4);color:#fff' : 'background:#f3f4f6;color:var(--text)' ?>">
            <?= $p ?>
          </a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
