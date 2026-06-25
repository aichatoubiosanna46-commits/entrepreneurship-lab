<?php
// admin/badge_add.php — Créer/modifier un badge
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();

$pdo = getPDO();
$id  = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$badge = ['titre'=>'','description'=>'','icone'=>'🏅','couleur'=>'#BA7517'];
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM badges WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if ($found) $badge = $found;
}

$erreurs = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCSRF();
    $titre   = trim($_POST['titre'] ?? '');
    $desc    = trim($_POST['description'] ?? '');
    $icone   = trim($_POST['icone'] ?? '🏅') ?: '🏅';
    $couleur = trim($_POST['couleur'] ?? '#BA7517');

    if (!$titre) $erreurs[] = 'Le titre est requis.';

    if (empty($erreurs)) {
        if ($id) {
            $pdo->prepare('UPDATE badges SET titre=?, description=?, icone=?, couleur=? WHERE id=?')
                ->execute([$titre, $desc ?: null, $icone, $couleur, $id]);
        } else {
            $slugBase = slugUnique($pdo, 'badges', 'slug', slug($titre));
            $pdo->prepare('INSERT INTO badges (titre, slug, description, icone, couleur) VALUES (?,?,?,?,?)')
                ->execute([$titre, $slugBase, $desc ?: null, $icone, $couleur]);
        }
        redirect(SITE_URL.'/admin/badges.php', 'Badge enregistré !', 'success');
    }
    $badge = array_merge($badge, $_POST);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $id ? 'Modifier' : 'Ajouter' ?> un badge</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/dashboard.css">
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<div class="admin-content">
  <div class="admin-topbar">
    <div><h1 class="admin-page-title"><?= $id ? 'Modifier' : 'Ajouter' ?> un badge</h1></div>
    <a href="<?= SITE_URL ?>/admin/badges.php" class="btn-outline"><i class="ti ti-arrow-left"></i> Retour</a>
  </div>

  <?php if (!empty($erreurs)): ?>
  <div class="alert alert-error"><i class="ti ti-alert-circle"></i>
    <div><?php foreach ($erreurs as $e) echo '<div>'.h($e).'</div>'; ?></div>
  </div>
  <?php endif; ?>

  <form method="POST" style="max-width:480px">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <?php if ($id): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>
    <div class="admin-card">
      <div class="form-group">
        <label for="titre">Titre *</label>
        <input type="text" id="titre" name="titre" value="<?= h($badge['titre']) ?>" placeholder="Explorateur Entrepreneurial" required>
      </div>
      <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="3"><?= h($badge['description'] ?? '') ?></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="icone">Emoji</label>
          <input type="text" id="icone" name="icone" value="<?= h($badge['icone']) ?>" maxlength="10">
        </div>
        <div class="form-group">
          <label for="couleur">Couleur</label>
          <input type="color" id="couleur" name="couleur" value="<?= h($badge['couleur']) ?>">
        </div>
      </div>
      <button type="submit" class="btn-primary btn-full"><i class="ti ti-device-floppy"></i> Enregistrer</button>
    </div>
  </form>
</div>
<script src="<?= SITE_URL ?>/assets/js/dashboard.js"></script>
</body>
</html>
