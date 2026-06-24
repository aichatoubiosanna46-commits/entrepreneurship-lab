<?php
// ============================================================
//  admin/blog_edit.php — Modifier un article de blog
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

sendSecurityHeaders();
reqAdmin();

$pdo    = getPDO();
$id     = (int)($_GET['id'] ?? 0);
$erreur = '';

if (!$id) redirect(SITE_URL . '/admin/blog.php');

$stmt = $pdo->prepare('SELECT * FROM blog_articles WHERE id = ?');
$stmt->execute([$id]);
$article = $stmt->fetch();
if (!$article) redirect(SITE_URL . '/admin/blog.php', 'Article introuvable.', 'error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCSRF();

    $titre      = trim($_POST['titre'] ?? '');
    $resume     = trim($_POST['resume'] ?? '');
    $contenu    = trim($_POST['contenu'] ?? '');
    $statut     = $_POST['statut'] === 'publie' ? 'publie' : 'brouillon';
    $metaTitle  = trim($_POST['meta_title'] ?? '');
    $metaDesc   = trim($_POST['meta_description'] ?? '');
    $imageCover = $article['image_cover'];

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
            $published = ($statut === 'publie' && !$article['published_at']) ? date('Y-m-d H:i:s') : $article['published_at'];
            $pdo->prepare(
                'UPDATE blog_articles SET titre = ?, resume = ?, contenu = ?, image_cover = ?, statut = ?,
                 meta_title = ?, meta_description = ?, published_at = ?, updated_at = NOW()
                 WHERE id = ?'
            )->execute([$titre, $resume, $contenu, $imageCover, $statut, $metaTitle, $metaDesc, $published, $id]);
            redirect(SITE_URL . '/admin/blog.php', 'Article mis à jour.', 'success');
        }
    }
}

$pageTitle = 'Modifier : ' . $article['titre'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Modifier article — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
</head>
<body class="admin-layout">
<?php require_once __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <h1 class="admin-page-title"><i class="ti ti-pencil"></i> Modifier l'article</h1>
    <a href="blog.php" class="btn-outline" style="font-size:13px"><i class="ti ti-arrow-left"></i> Retour</a>
  </div>
  <div class="admin-content" style="max-width:900px">
    <?php if ($erreur): ?><div class="alert alert-error"><i class="ti ti-alert-circle"></i> <?= h($erreur) ?></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

      <div style="display:grid;grid-template-columns:1fr 280px;gap:20px;align-items:start">
        <div>
          <div style="background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:24px;margin-bottom:16px">
            <div class="form-group">
              <label>Titre <span style="color:#dc2626">*</span></label>
              <input type="text" name="titre" value="<?= h($_POST['titre'] ?? $article['titre']) ?>" required
                     style="width:100%;padding:10px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:16px;font-weight:500;box-sizing:border-box">
            </div>
            <div class="form-group">
              <label>Résumé</label>
              <textarea name="resume" rows="3"
                        style="width:100%;padding:10px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:14px;font-family:inherit;resize:vertical;box-sizing:border-box"><?= h($_POST['resume'] ?? $article['resume'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
              <label>Contenu</label>
              <textarea name="contenu" rows="16"
                        style="width:100%;padding:10px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:14px;font-family:inherit;resize:vertical;box-sizing:border-box"><?= h($_POST['contenu'] ?? $article['contenu'] ?? '') ?></textarea>
            </div>
          </div>

          <div style="background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:24px">
            <h3 style="font-size:14px;font-weight:600;margin:0 0 14px;color:var(--text-muted)"><i class="ti ti-search"></i> SEO</h3>
            <div class="form-group">
              <label>Meta title</label>
              <input type="text" name="meta_title" value="<?= h($_POST['meta_title'] ?? $article['meta_title'] ?? '') ?>" maxlength="200"
                     style="width:100%;padding:8px 12px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:13px;box-sizing:border-box">
            </div>
            <div class="form-group">
              <label>Meta description</label>
              <textarea name="meta_description" rows="2"
                        style="width:100%;padding:8px 12px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:13px;font-family:inherit;resize:vertical;box-sizing:border-box"><?= h($_POST['meta_description'] ?? $article['meta_description'] ?? '') ?></textarea>
            </div>
          </div>
        </div>

        <div>
          <div style="background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:20px;margin-bottom:16px">
            <div class="form-group">
              <label>Statut</label>
              <select name="statut" style="width:100%;padding:8px 12px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:13px">
                <option value="brouillon" <?= ($article['statut'] ?? '') === 'brouillon' ? 'selected' : '' ?>>Brouillon</option>
                <option value="publie" <?= ($article['statut'] ?? '') === 'publie' ? 'selected' : '' ?>>Publié</option>
              </select>
            </div>
            <button type="submit" class="btn-primary" style="width:100%">
              <i class="ti ti-check"></i> Enregistrer
            </button>
          </div>

          <div style="background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:20px">
            <label style="font-size:13px;font-weight:600;display:block;margin-bottom:8px">Image de couverture</label>
            <?php if ($article['image_cover']): ?>
              <img src="<?= SITE_URL ?>/assets/uploads/<?= h($article['image_cover']) ?>" alt="" style="width:100%;border-radius:8px;margin-bottom:8px;max-height:150px;object-fit:cover">
            <?php endif; ?>
            <input type="file" name="image_cover" accept="image/*" style="font-size:13px;width:100%;box-sizing:border-box">
            <small style="color:var(--text-muted);font-size:11px;display:block;margin-top:6px">Remplace l'image actuelle si fourni.</small>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>
</body>
</html>
