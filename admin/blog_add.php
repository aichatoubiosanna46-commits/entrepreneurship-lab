<?php
// ============================================================
//  admin/blog_add.php — Créer un article de blog
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

sendSecurityHeaders();
reqAdmin();

$pdo    = getPDO();
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCSRF();

    $titre       = trim($_POST['titre'] ?? '');
    $resume      = trim($_POST['resume'] ?? '');
    $contenu     = trim($_POST['contenu'] ?? '');
    $statut      = $_POST['statut'] === 'publie' ? 'publie' : 'brouillon';
    $metaTitle   = trim($_POST['meta_title'] ?? '');
    $metaDesc    = trim($_POST['meta_description'] ?? '');
    $imageCover  = null;

    if (strlen($titre) < 3) {
        $erreur = 'Le titre est trop court.';
    } else {
        if (!empty($_FILES['image_cover']['name'])) {
            $uploaded = uploadImage($_FILES['image_cover'], 'blog');
            if (!$uploaded) {
                $erreur = 'Image invalide ou trop volumineuse.';
            } else {
                $imageCover = $uploaded;
            }
        }

        if (!$erreur) {
            $slugBase  = slug($titre);
            $slugFinal = slugUnique($pdo, 'blog_articles', 'slug', $slugBase);
            $published = $statut === 'publie' ? date('Y-m-d H:i:s') : null;
            $adminData = adminCourant();
            $auteurId  = null;

            $pdo->prepare(
                'INSERT INTO blog_articles (titre, slug, resume, contenu, image_cover, auteur_id, statut, meta_title, meta_description, published_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([$titre, $slugFinal, $resume, $contenu, $imageCover, $auteurId, $statut, $metaTitle, $metaDesc, $published]);

            redirect(SITE_URL . '/admin/blog.php', 'Article créé.', 'success');
        }
    }
}

$pageTitle = 'Nouvel article';
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
    <h1 class="admin-page-title"><i class="ti ti-news"></i> Nouvel article</h1>
    <a href="blog.php" class="btn-outline" style="font-size:13px"><i class="ti ti-arrow-left"></i> Retour</a>
  </div>

  <?php if ($erreur): ?>
    <div class="alert alert-error"><i class="ti ti-alert-circle"></i> <?= h($erreur) ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" style="max-width:900px">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

    <div style="display:grid;grid-template-columns:1fr 280px;gap:20px;align-items:start">
      <!-- Colonne principale -->
      <div>
        <div class="admin-card" style="margin-bottom:16px">
          <div class="form-group">
            <label>Titre <span style="color:#dc2626">*</span></label>
            <input type="text" name="titre" value="<?= h($_POST['titre'] ?? '') ?>" required
                   style="font-size:16px;font-weight:500">
          </div>
          <div class="form-group">
            <label>Résumé (extrait)</label>
            <textarea name="resume" rows="3"><?= h($_POST['resume'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label>Contenu</label>
            <textarea name="contenu" rows="16" id="contenu"><?= h($_POST['contenu'] ?? '') ?></textarea>
          </div>
        </div>

        <!-- SEO -->
        <div class="admin-card">
          <h3 style="font-size:14px;font-weight:600;margin:0 0 14px;color:var(--text-muted)">
            <i class="ti ti-search"></i> SEO
          </h3>
          <div class="form-group">
            <label>Meta title</label>
            <input type="text" name="meta_title" value="<?= h($_POST['meta_title'] ?? '') ?>" maxlength="200">
          </div>
          <div class="form-group">
            <label>Meta description</label>
            <textarea name="meta_description" rows="2"><?= h($_POST['meta_description'] ?? '') ?></textarea>
          </div>
        </div>
      </div>

      <!-- Colonne latérale -->
      <div>
        <div class="admin-card" style="margin-bottom:16px">
          <div class="form-group">
            <label>Statut</label>
            <select name="statut">
              <option value="brouillon">Brouillon</option>
              <option value="publie">Publié</option>
            </select>
          </div>
          <button type="submit" class="btn-primary btn-full">
            <i class="ti ti-check"></i> Créer l'article
          </button>
        </div>

        <div class="admin-card">
          <label style="font-size:13px;font-weight:600;display:block;margin-bottom:8px">Image de couverture</label>
          <input type="file" name="image_cover" accept="image/*" style="font-size:13px;width:100%">
          <small style="color:var(--text-muted);font-size:11px;display:block;margin-top:6px">
            JPG, PNG, WebP. Max 2 Mo.
          </small>
        </div>
      </div>
    </div>
  </form>
</div>
</body>
</html>