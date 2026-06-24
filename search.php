<?php
// search.php — Recherche de cours
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getPDO();
$q   = trim($_GET['q'] ?? '');
$catId = (int)($_GET['cat'] ?? 0);
$results = [];

if ($q || $catId) {
    $sql = 'SELECT c.*, cat.nom as categorie, cat.icone as cat_icone, cat.couleur as cat_couleur,
                   (SELECT COUNT(*) FROM sequences s JOIN modules m ON m.id = s.module_id WHERE m.course_id = c.id AND s.actif = 1) as nb_lecons
            FROM courses c
            JOIN categories cat ON cat.id = c.category_id
            WHERE c.actif = 1 AND c.statut = "publie"';
    $params = [];
    if ($q) {
        $sql .= ' AND (c.titre LIKE ? OR c.description LIKE ?)';
        $like = '%' . $q . '%';
        $params[] = $like; $params[] = $like;
    }
    if ($catId) { $sql .= ' AND c.category_id = ?'; $params[] = $catId; }
    $sql .= ' ORDER BY c.ordre ASC, c.created_at DESC LIMIT 50';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
}

$categories = $pdo->query('SELECT * FROM categories WHERE actif = 1 ORDER BY nom ASC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Recherche<?= $q ? ' : '.h($q) : '' ?> — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/home.css">
<style>
.search-wrap { max-width: 1100px; margin: 40px auto; padding: 0 24px 60px; }
.search-form { display: flex; gap: 10px; margin-bottom: 24px; }
.search-input {
  flex: 1; padding: 12px 16px; border: 1px solid var(--border,#e5e7eb);
  border-radius: 10px; font-size: 15px; font-family: inherit;
}
.search-input:focus { outline: none; border-color: #534AB7; }
.search-btn { padding: 12px 24px; background: #534AB7; color: #fff; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 6px; }
.cat-filters { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 32px; }
.cat-btn { padding: 6px 14px; border-radius: 20px; border: 1px solid var(--border,#e5e7eb); font-size: 13px; font-weight: 500; text-decoration: none; color: var(--text,#111); background: #fff; transition: .15s; }
.cat-btn:hover, .cat-btn.active { background: #534AB7; color: #fff; border-color: #534AB7; }
.results-title { font-size: 20px; font-weight: 700; margin-bottom: 20px; }
.course-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
.course-card {
  background: #fff; border: 1px solid var(--border,#e5e7eb); border-radius: 14px;
  overflow: hidden; text-decoration: none; color: inherit; transition: box-shadow .2s, transform .2s;
}
.course-card:hover { box-shadow: 0 8px 32px rgba(0,0,0,.1); transform: translateY(-2px); }
.course-thumb { aspect-ratio: 16/9; background: #f3f4f6; overflow: hidden; }
.course-thumb img { width: 100%; height: 100%; object-fit: cover; }
.course-thumb-ph { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 36px; }
.course-body { padding: 16px; }
.course-cat { font-size: 11px; font-weight: 600; text-transform: uppercase; margin-bottom: 6px; }
.course-title { font-size: 15px; font-weight: 700; margin-bottom: 8px; line-height: 1.3; }
.course-meta { display: flex; gap: 12px; font-size: 12px; color: var(--text-muted,#6b7280); }
.no-results { text-align: center; padding: 60px 0; color: var(--text-muted,#6b7280); }
.no-results i { font-size: 48px; margin-bottom: 12px; display: block; }
</style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<div class="search-wrap">
  <form class="search-form" method="GET">
    <input class="search-input" type="search" name="q" placeholder="Rechercher une formation..." value="<?= h($q) ?>">
    <?php if ($catId): ?><input type="hidden" name="cat" value="<?= $catId ?>"><?php endif; ?>
    <button type="submit" class="search-btn"><i class="ti ti-search"></i> Rechercher</button>
  </form>

  <div class="cat-filters">
    <a href="<?= SITE_URL ?>/search.php<?= $q ? '?q='.urlencode($q) : '' ?>" class="cat-btn <?= !$catId ? 'active' : '' ?>">
      Toutes catégories
    </a>
    <?php foreach ($categories as $cat): ?>
    <a href="<?= SITE_URL ?>/search.php?<?= $q ? 'q='.urlencode($q).'&' : '' ?>cat=<?= $cat['id'] ?>"
       class="cat-btn <?= $catId == $cat['id'] ? 'active' : '' ?>">
      <i class="ti <?= h($cat['icone']) ?>"></i> <?= h($cat['nom']) ?>
    </a>
    <?php endforeach; ?>
  </div>

  <?php if ($q || $catId): ?>
    <div class="results-title">
      <?= count($results) ?> résultat<?= count($results) != 1 ? 's' : '' ?>
      <?= $q ? 'pour « '.h($q).' »' : '' ?>
    </div>
    <?php if (empty($results)): ?>
      <div class="no-results">
        <i class="ti ti-search-off"></i>
        <p>Aucune formation trouvée. Essayez d'autres mots-clés.</p>
      </div>
    <?php else: ?>
    <div class="course-grid">
      <?php foreach ($results as $c): ?>
      <a href="<?= SITE_URL ?>/module.php?slug=<?= h($c['slug']) ?>" class="course-card">
        <div class="course-thumb">
          <?php if ($c['miniature']): ?>
            <img src="<?= SITE_URL ?>/assets/uploads/<?= h($c['miniature']) ?>" alt="">
          <?php else: ?>
            <div class="course-thumb-ph" style="background:<?= h($c['cat_couleur']) ?>22">
              <i class="ti <?= h($c['cat_icone']) ?>" style="color:<?= h($c['cat_couleur']) ?>"></i>
            </div>
          <?php endif; ?>
        </div>
        <div class="course-body">
          <div class="course-cat" style="color:<?= h($c['cat_couleur']) ?>"><?= h($c['categorie']) ?></div>
          <div class="course-title"><?= h($c['titre']) ?></div>
          <div class="course-meta">
            <span><i class="ti ti-list"></i> <?= $c['nb_lecons'] ?> séquences</span>
            <span><i class="ti ti-signal"></i> <?= ['debutant'=>'Débutant','intermediaire'=>'Inter.','avance'=>'Avancé'][$c['niveau']] ?? '' ?></span>
            <?php if ($c['type'] === 'gratuit'): ?>
              <span style="color:#16a34a;font-weight:600"><i class="ti ti-gift"></i> Gratuit</span>
            <?php else: ?>
              <span style="color:#6C47D4;font-weight:600"><?= fcfa((float)$c['prix']) ?></span>
            <?php endif; ?>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  <?php else: ?>
    <div class="no-results">
      <i class="ti ti-search"></i>
      <p>Entrez un mot-clé pour rechercher une formation.</p>
    </div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
