<?php
// module.php — Page d'un module avec ses séquences (leçons)
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pdo  = getPDO();
$slug = trim($_GET['slug'] ?? '');
if (!$slug) { header('Location: '.SITE_URL.'/index.php'); exit; }

$stmt = $pdo->prepare('SELECT m.*, c.nom as categorie, c.icone as cat_icone, c.couleur as cat_couleur FROM modules m JOIN categories c ON c.id = m.category_id WHERE m.slug = ? AND m.actif = 1');
$stmt->execute([$slug]);
$module = $stmt->fetch();
if (!$module) { http_response_code(404); die('Module introuvable.'); }

// Séquences du module
$lecons = $pdo->prepare('SELECT * FROM sequences WHERE course_id = ? AND actif = 1 ORDER BY ordre ASC, id ASC');
$lecons->execute([$module['id']]);
$lecons = $lecons->fetchAll();

$inscrit = estConnecte() ? estInscrit($_SESSION['user_id'], $module['id']) : false;

// Inscription rapide
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inscrire']) && estConnecte()) {
    verifierCSRF();
    if (!$inscrit) {
        $st = $pdo->prepare('INSERT IGNORE INTO inscriptions (user_id, course_id, paye) VALUES (?, ?, ?)');
        $st->execute([$_SESSION['user_id'], $module['id'], $module['type'] === 'gratuit' ? 1 : 0]);
        redirect(SITE_URL.'/module.php?slug='.$slug, 'Tu es inscrit à ce module !', 'success');
    }
}

$pageTitle = $module['titre'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($module['titre']) ?> — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/home.css">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<?= flash() ?>

<!-- Hero module -->
<div class="module-hero" style="background:<?= h($module['cat_couleur']) ?>18;border-bottom:1px solid <?= h($module['cat_couleur']) ?>33">
  <div class="container">
    <div class="module-hero-inner">
      <div class="module-hero-text">
        <span class="module-cat" style="color:<?= h($module['cat_couleur']) ?>;font-size:13px">
          <i class="ti <?= h($module['cat_icone']) ?>"></i> <?= h($module['categorie']) ?>
        </span>
        <h1><?= h($module['titre']) ?></h1>
        <?php if ($module['description']): ?>
        <p><?= h($module['description']) ?></p>
        <?php endif; ?>
        <div class="module-hero-meta">
          <span><i class="ti ti-list"></i> <?= count($lecons) ?> séquence<?= count($lecons) != 1 ? 's' : '' ?></span>
          <?php if ($module['duree_heures']): ?>
          <span><i class="ti ti-clock"></i> <?= $module['duree_heures'] ?>h</span>
          <?php endif; ?>
          <span><i class="ti ti-signal"></i> <?= ucfirst($module['niveau']) ?></span>
        </div>
        <?php if (!estConnecte()): ?>
          <a href="<?= SITE_URL ?>/register.php" class="btn-primary btn-lg" style="margin-top:16px">
            <i class="ti ti-user-plus"></i> S'inscrire pour accéder
          </a>
        <?php elseif (!$inscrit): ?>
          <form method="POST" style="margin-top:16px">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="inscrire" value="1">
            <button type="submit" class="btn-primary btn-lg">
              <i class="ti ti-bookmark-plus"></i> S'inscrire à ce module
            </button>
          </form>
        <?php else: ?>
          <?php $pct = progression($_SESSION['user_id'], $module['id']); ?>
          <div class="module-enrolled-badge">
            <i class="ti ti-check-circle" style="color:#3B6D11"></i>
            Inscrit · <?= $pct ?>% complété
          </div>
        <?php endif; ?>
      </div>
      <?php if ($module['miniature']): ?>
      <div class="module-hero-thumb">
        <img src="<?= SITE_URL ?>/assets/uploads/<?= h($module['miniature']) ?>" alt="<?= h($module['titre']) ?>">
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Liste des séquences -->
<div class="container" style="padding-top:32px;padding-bottom:48px">
  <h2 style="font-size:18px;font-weight:600;margin-bottom:20px">
    Séquences du module
  </h2>
  <?php if (empty($lecons)): ?>
  <div style="text-align:center;padding:48px;color:var(--text-muted)">
    <i class="ti ti-list-search" style="font-size:40px;display:block;margin-bottom:12px;opacity:.4"></i>
    Aucune séquence ajoutée pour l'instant.
  </div>
  <?php else: ?>
  <div class="sequences-list">
    <?php foreach ($lecons as $i => $l): ?>
    <?php
      $done = false;
      if (estConnecte() && $inscrit) {
        $st = $pdo->prepare('SELECT terminee FROM progression WHERE user_id=? AND sequence_id=?');
        $st->execute([$_SESSION['user_id'], $l['id']]);
        $row = $st->fetch();
        $done = $row && $row['terminee'];
      }
      $canAccess = estConnecte() && $inscrit;
    ?>
    <div class="sequence-card <?= $done ? 'done' : '' ?> <?= !$canAccess ? 'locked' : '' ?>">
      <div class="seq-num"><?= $i + 1 ?></div>
      <?php if ($l['image_seq'] ?? null): ?>
      <div class="seq-thumb">
        <img src="<?= SITE_URL ?>/assets/uploads/sequences/<?= h($l['image_seq']) ?>" alt="">
      </div>
      <?php endif; ?>
      <div class="seq-info">
        <h3><?= h($l['titre']) ?></h3>
        <div class="seq-meta">
          <?php if ($l['duree_min']): ?><span><i class="ti ti-clock"></i> <?= $l['duree_min'] ?> min</span><?php endif; ?>
          <?php if ($l['video_url']): ?><span><i class="ti ti-video"></i> Vidéo</span><?php endif; ?>
        </div>
      </div>
      <div class="seq-action">
        <?php if (!estConnecte()): ?>
          <a href="<?= SITE_URL ?>/register.php" class="seq-btn locked-btn"><i class="ti ti-lock"></i></a>
        <?php elseif (!$inscrit): ?>
          <span class="seq-btn locked-btn"><i class="ti ti-lock"></i></span>
        <?php elseif ($done): ?>
          <a href="<?= SITE_URL ?>/lecon.php?id=<?= $l['id'] ?>" class="seq-btn done-btn"><i class="ti ti-check"></i></a>
        <?php else: ?>
          <a href="<?= SITE_URL ?>/lecon.php?id=<?= $l['id'] ?>" class="seq-btn start-btn"><i class="ti ti-player-play"></i></a>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
