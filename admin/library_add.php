<?php
// admin/library_add.php — Ajouter une ressource à la bibliothèque
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();
$pdo = getPDO();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCSRF();
    $titre    = trim($_POST['titre'] ?? '');
    $desc     = trim($_POST['description'] ?? '');
    $type     = $_POST['type'] ?? 'autre';
    $tarifMin = $_POST['tarif_min'] ?? 'decouverte';

    if (!$titre) $errors[] = 'Titre obligatoire.';
    if (empty($_FILES['fichier']['name'])) $errors[] = 'Fichier obligatoire.';

    if (empty($errors)) {
        $file = $_FILES['fichier'];
        $nomFichier = uploadFichier($file, 'library', 20);
        if (!$nomFichier) { $errors[] = 'Erreur lors de l\'upload (type ou taille invalide).'; }
        else {
            // Extraire juste le nom de fichier
            $nomFichier = basename($nomFichier);
            $taille = round($file['size'] / 1024);
            $pdo->prepare(
                'INSERT INTO library_resources (titre, description, fichier, type, tarif_min) VALUES (?, ?, ?, ?, ?)'
            )->execute([$titre, $desc, $nomFichier, $type, $tarifMin]);
            redirect(SITE_URL . '/admin/library.php', 'Ressource ajoutée !', 'success');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Ajouter ressource — Admin <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/dashboard.css">
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <div><h1 class="admin-page-title">Ajouter une ressource</h1></div>
    <a href="<?= SITE_URL ?>/admin/library.php" class="btn-outline">← Retour</a>
  </div>
  <?php if ($errors): ?>
  <div style="background:#FAECE7;border:1px solid #F0997B;border-radius:8px;padding:12px 16px;margin-bottom:16px;color:#993C1D">
    <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
  </div>
  <?php endif; ?>
  <form method="POST" enctype="multipart/form-data" class="admin-card" style="max-width:600px">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <div class="form-field"><label>Titre *</label><input type="text" name="titre" required value="<?= h($_POST['titre']??'') ?>"></div>
    <div class="form-field"><label>Description</label><textarea name="description" rows="3"><?= h($_POST['description']??'') ?></textarea></div>
    <div class="form-field">
      <label>Type</label>
      <select name="type">
        <option value="business_plan">Business Plan</option>
        <option value="social_media">Template Réseaux Sociaux</option>
        <option value="sales_script">Script de Vente</option>
        <option value="autre">Autre</option>
      </select>
    </div>
    <div class="form-field">
      <label>Accès minimum requis</label>
      <select name="tarif_min">
        <option value="decouverte">Découverte (gratuit)</option>
        <option value="business_plan">Business Plan</option>
        <option value="lancement">Lancement</option>
      </select>
    </div>
    <div class="form-field">
      <label>Fichier (PDF, Word, PPT, Excel — max 20 Mo) *</label>
      <input type="file" name="fichier" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx" required>
    </div>
    <button type="submit" class="btn-primary"><i class="ti ti-upload"></i> Ajouter la ressource</button>
  </form>
</div>
</body>
</html>
