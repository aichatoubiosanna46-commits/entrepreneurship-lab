<?php
// ============================================================
//  blog_article.php — Article complet avec SEO
// ============================================================
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

sendSecurityHeaders();

$pdo  = getPDO();
$slug = trim($_GET['slug'] ?? '');

if (!$slug) redirect(SITE_URL . '/blog.php');

$stmt = $pdo->prepare(
    'SELECT ba.*, u.nom, u.prenom
     FROM blog_articles ba
     LEFT JOIN users u ON u.id = ba.auteur_id
     WHERE ba.slug = ? AND ba.statut = "publie"
     LIMIT 1'
);
$stmt->execute([$slug]);
$article = $stmt->fetch();

if (!$article) redirect(SITE_URL . '/blog.php', 'Article introuvable.', 'error');

$metaTitle = $article['meta_title'] ?: $article['titre'] . ' — ' . SITE_NAME;
$metaDesc  = $article['meta_description'] ?: ($article['resume'] ?: mb_substr(strip_tags($article['contenu'] ?? ''), 0, 160));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($metaTitle) ?></title>
<meta name="description" content="<?= h($metaDesc) ?>">
<meta property="og:title" content="<?= h($article['titre']) ?>">
<meta property="og:description" content="<?= h($metaDesc) ?>">
<?php if ($article['image_cover']): ?>
<meta property="og:image" content="<?= SITE_URL ?>/assets/uploads/<?= h($article['image_cover']) ?>">
<?php endif; ?>
<meta property="og:type" content="article">
<meta property="og:url" content="<?= SITE_URL ?>/blog_article.php?slug=<?= h($slug) ?>">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css?v=2">
</head>
<body>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="container" style="max-width:800px;margin:0 auto;padding:32px 16px">

  <a href="<?= SITE_URL ?>/blog.php" style="font-size:13px;color:var(--text-muted);text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:16px">
    <i class="ti ti-arrow-left"></i> Retour au blog
  </a>

  <?php if ($article['image_cover']): ?>
    <img src="<?= SITE_URL ?>/assets/uploads/<?= h($article['image_cover']) ?>"
         alt="<?= h($article['titre']) ?>"
         style="width:100%;height:360px;object-fit:cover;border-radius:12px;margin-bottom:28px">
  <?php endif; ?>

  <div style="margin-bottom:24px">
    <h1 style="font-size:30px;font-weight:700;margin:0 0 12px;line-height:1.3"><?= h($article['titre']) ?></h1>
    <div style="font-size:13px;color:var(--text-muted);display:flex;gap:16px;flex-wrap:wrap">
      <?php if ($article['prenom']): ?>
        <span><i class="ti ti-user"></i> <?= h($article['prenom'] . ' ' . $article['nom']) ?></span>
      <?php endif; ?>
      <?php if ($article['published_at']): ?>
        <span><i class="ti ti-calendar"></i> <?= date('d/m/Y', strtotime($article['published_at'])) ?></span>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($article['resume']): ?>
    <div style="background:#EDE9FE;border-left:4px solid #6C47D4;padding:16px;border-radius:0 8px 8px 0;font-size:15px;font-style:italic;color:#4C1D95;margin-bottom:28px;line-height:1.6">
      <?= h($article['resume']) ?>
    </div>
  <?php endif; ?>

  <div style="font-size:16px;line-height:1.8;color:var(--text)">
    <?= nl2br(h($article['contenu'] ?? '')) ?>
  </div>

  <!-- Partage -->
  <div style="margin-top:40px;padding-top:24px;border-top:1px solid var(--border,#e5e7eb)">
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:12px">Partager cet article :</p>
    <div style="display:flex;gap:8px">
      <a href="https://twitter.com/intent/tweet?text=<?= urlencode($article['titre']) ?>&url=<?= urlencode(SITE_URL . '/blog_article.php?slug=' . $slug) ?>"
         target="_blank" style="background:#1DA1F2;color:#fff;padding:8px 16px;border-radius:8px;font-size:13px;text-decoration:none;display:inline-flex;align-items:center;gap:6px">
        <i class="ti ti-brand-twitter"></i> Twitter
      </a>
      <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode(SITE_URL . '/blog_article.php?slug=' . $slug) ?>"
         target="_blank" style="background:#0A66C2;color:#fff;padding:8px 16px;border-radius:8px;font-size:13px;text-decoration:none;display:inline-flex;align-items:center;gap:6px">
        <i class="ti ti-brand-linkedin"></i> LinkedIn
      </a>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
