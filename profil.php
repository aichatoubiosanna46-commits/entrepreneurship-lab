<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
reqConnecte();
$user = utilisateurCourant();
$pageTitle = 'Mon profil';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Mon profil — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/home.css">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<?= flash() ?>
<div class="container" style="padding:40px 20px">
  <h1 style="font-size:22px;font-weight:600;margin-bottom:24px">Mon profil</h1>
  <div style="background:var(--white);border:0.5px solid var(--border);border-radius:var(--radius-lg);padding:28px;max-width:500px">
    <p style="font-size:13px;color:var(--text-muted)">Nom : <strong><?= h($user['prenom'].' '.$user['nom']) ?></strong></p>
    <p style="font-size:13px;color:var(--text-muted);margin-top:10px">Email : <strong><?= h($user['email']) ?></strong></p>
    <?php if ($user['ville']): ?>
    <p style="font-size:13px;color:var(--text-muted);margin-top:10px">Ville : <strong><?= h($user['ville']) ?></strong></p>
    <?php endif; ?>
    <a href="<?= SITE_URL ?>/dashboard.php" class="btn-outline" style="margin-top:20px">← Retour au tableau de bord</a>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
