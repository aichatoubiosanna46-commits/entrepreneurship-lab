<?php
// catalogue.php — Catalogue public des cours avec filtres
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getPDO();

// Filtres
$cat      = trim($_GET['cat']   ?? '');
$prix     = trim($_GET['prix']  ?? '');
$q        = trim($_GET['q']     ?? '');
$sort     = trim($_GET['sort']  ?? 'recent');

// Catégories
try {
    $categories = $pdo->query('SELECT * FROM categories ORDER BY nom ASC')->fetchAll();
} catch (Exception $e) { $categories = []; }

// Construction requête
$where = ['c.actif = 1', 'c.statut = "publie"'];
$params = [];

if ($cat) {
    $where[] = 'cat.id = ?';
    $params[] = $cat;
}
if ($prix === 'gratuit') {
    $where[] = 'c.type = "gratuit"';
} elseif ($prix === 'payant') {
    $where[] = 'c.type = "payant"';
}
if ($q) {
    $where[] = '(c.titre LIKE ? OR c.description LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
}

$orderBy = match($sort) {
    'prix_asc'  => 'c.prix ASC',
    'prix_desc' => 'c.prix DESC',
    'titre'     => 'c.titre ASC',
    default     => 'c.created_at DESC',
};

$sql = "SELECT c.*, cat.nom as cat_nom, cat.couleur as cat_couleur, cat.icone as cat_icone,
        (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id AND e.statut = 'actif') as nb_inscrits,
        (SELECT COUNT(*) FROM sequences s JOIN modules m ON m.id = s.module_id WHERE m.course_id = c.id AND s.actif = 1) as nb_lecons
        FROM courses c
        LEFT JOIN categories cat ON cat.id = c.category_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY $orderBy";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $cours = $stmt->fetchAll();
} catch (Exception $e) { $cours = []; }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Catalogue des formations — <?= SITE_NAME ?></title>
<meta name="description" content="Découvrez toutes les formations entrepreneuriales d'Ariziki EntrepreneurshipLab. Gratuit, Business Plan, Lancement — certifiés Université de Parakou.">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<style>
/* Variables Sunrise Africa (alignées sur style.css / dashboard / coaching) */
:root{--amber:#F59E0B;--amber-light:#FEF3C7;--amber-dark:#D97706;--text-muted:#6b7280}
body.cat-page{background:#FFFBEB}

.cat-hero{background:linear-gradient(135deg,#1C1917,#292524);padding:52px 24px 40px;text-align:center}
.cat-hero h1{font-size:32px;font-weight:800;color:#fff;margin-bottom:8px}
.cat-hero h1 em{font-style:normal;color:var(--amber)}
.cat-hero p{font-size:14px;color:rgba(255,255,255,.6);margin-bottom:24px}

.cat-search-bar{max-width:520px;margin:0 auto;display:flex;gap:8px}
.cat-search-bar input{flex:1;padding:12px 16px;border-radius:10px;border:none;font-size:14px;font-family:inherit}
.cat-search-bar button{padding:12px 20px;background:var(--amber);color:#1C1917;border:none;border-radius:10px;font-weight:700;cursor:pointer}

.cat-wrap{max-width:1100px;margin:0 auto;padding:32px 20px 60px}
.cat-filters{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:28px;align-items:center}
.cat-filter-label{font-size:12px;font-weight:600;color:var(--text-muted)}
.filter-chip{padding:6px 14px;border-radius:20px;border:1.5px solid #e5e7eb;background:#fff;font-size:12px;font-weight:600;cursor:pointer;text-decoration:none;color:var(--text-muted);transition:.15s}
.filter-chip:hover,.filter-chip.active{background:var(--amber);border-color:var(--amber);color:#1C1917}
.cat-sort{margin-left:auto}
.cat-sort select{padding:7px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:12px;font-family:inherit;background:#fff;cursor:pointer}

.cat-stats{display:flex;gap:8px;align-items:center;margin-bottom:20px;font-size:13px;color:var(--text-muted)}
.cat-stats strong{color:#1C1917}

.cours-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px}

.cours-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;text-decoration:none;color:inherit;display:flex;flex-direction:column;box-shadow:0 2px 8px rgba(0,0,0,.04);transition:transform .2s,box-shadow .2s}
.cours-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.09)}
.cours-thumb{height:160px;position:relative;overflow:hidden}
.cours-thumb img{width:100%;height:100%;object-fit:cover}
.cours-thumb-ph{width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:40px}
.cours-badge{position:absolute;top:10px;left:10px;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700}
.cours-badge.gratuit{background:#ECFDF5;color:#16a34a}
.cours-badge.payant{background:var(--amber-light);color:var(--amber-dark)}
.cours-body{padding:18px;flex:1;display:flex;flex-direction:column}
.cours-cat{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px}
.cours-title{font-size:15px;font-weight:700;color:#1C1917;margin-bottom:8px;line-height:1.35}
.cours-desc{font-size:13px;color:var(--text-muted);line-height:1.6;margin-bottom:14px;flex:1;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.cours-meta{display:flex;align-items:center;justify-content:space-between;padding-top:12px;border-top:1px solid #e5e7eb}
.cours-price{font-size:16px;font-weight:800;color:#1C1917}
.cours-price.free{color:#16a34a}
.cours-info{font-size:11px;color:var(--text-muted);display:flex;gap:10px}
.cours-info span{display:flex;align-items:center;gap:3px}
.btn-cours{display:block;width:100%;padding:11px;text-align:center;background:linear-gradient(135deg,var(--amber),var(--amber-dark));color:#1C1917;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;margin-top:14px;transition:opacity .15s}
.btn-cours:hover{opacity:.9}
.btn-cours.outline{background:transparent;border:1.5px solid #1C1917;color:#1C1917}
.btn-cours.outline:hover{background:#1C1917;color:#fff}

.empty-state{text-align:center;padding:60px 24px;background:#fff;border:1px dashed #fde68a;border-radius:16px;color:var(--text-muted)}
.empty-state i{font-size:48px;display:block;margin-bottom:12px;color:#fde68a}

@media(max-width:900px){.cours-grid{grid-template-columns:1fr 1fr}}
@media(max-width:600px){.cours-grid{grid-template-columns:1fr}.cat-filters{flex-direction:column;align-items:flex-start}.cat-sort{margin-left:0}}
</style>
</head>
<body class="cat-page">
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="cat-hero">
  <h1>Toutes nos <em>formations</em></h1>
  <p>Des parcours pratiques, certifiés Université de Parakou · Paiement Mobile Money</p>
  <form class="cat-search-bar" method="GET" action="catalogue.php">
    <?php if ($cat):?><input type="hidden" name="cat" value="<?= h($cat) ?>"><?php endif;?>
    <?php if ($prix):?><input type="hidden" name="prix" value="<?= h($prix) ?>"><?php endif;?>
    <input type="text" name="q" value="<?= h($q) ?>" placeholder="Rechercher une formation...">
    <button type="submit"><i class="ti ti-search"></i></button>
  </form>
</div>

<div class="cat-wrap">
  <!-- Filtres -->
  <div class="cat-filters">
    <span class="cat-filter-label">Catégorie :</span>
    <a href="catalogue.php?prix=<?= h($prix) ?>&q=<?= h($q) ?>&sort=<?= h($sort) ?>" class="filter-chip <?= !$cat ? 'active' : '' ?>">Toutes</a>
    <?php foreach ($categories as $c): ?>
    <a href="catalogue.php?cat=<?= $c['id'] ?>&prix=<?= h($prix) ?>&q=<?= h($q) ?>&sort=<?= h($sort) ?>"
       class="filter-chip <?= $cat == $c['id'] ? 'active' : '' ?>"
       style="<?= $cat == $c['id'] ? 'background:'.$c['couleur'].';border-color:'.$c['couleur'].';color:#fff' : '' ?>">
      <?= h($c['nom']) ?>
    </a>
    <?php endforeach; ?>

    <span class="cat-filter-label" style="margin-left:8px">Prix :</span>
    <a href="catalogue.php?cat=<?= h($cat) ?>&q=<?= h($q) ?>&sort=<?= h($sort) ?>" class="filter-chip <?= !$prix ? 'active' : '' ?>">Tous</a>
    <a href="catalogue.php?cat=<?= h($cat) ?>&prix=gratuit&q=<?= h($q) ?>&sort=<?= h($sort) ?>" class="filter-chip <?= $prix==='gratuit' ? 'active' : '' ?>">Gratuit</a>
    <a href="catalogue.php?cat=<?= h($cat) ?>&prix=payant&q=<?= h($q) ?>&sort=<?= h($sort) ?>" class="filter-chip <?= $prix==='payant' ? 'active' : '' ?>">Payant</a>

    <div class="cat-sort">
      <select onchange="window.location='catalogue.php?cat=<?= h($cat) ?>&prix=<?= h($prix) ?>&q=<?= h($q) ?>&sort='+this.value">
        <option value="recent" <?= $sort==='recent' ? 'selected' : '' ?>>Plus récents</option>
        <option value="titre" <?= $sort==='titre' ? 'selected' : '' ?>>A → Z</option>
        <option value="prix_asc" <?= $sort==='prix_asc' ? 'selected' : '' ?>>Prix croissant</option>
        <option value="prix_desc" <?= $sort==='prix_desc' ? 'selected' : '' ?>>Prix décroissant</option>
      </select>
    </div>
  </div>

  <div class="cat-stats">
    <strong><?= count($cours) ?></strong> formation<?= count($cours) > 1 ? 's' : '' ?> trouvée<?= count($cours) > 1 ? 's' : '' ?>
    <?php if ($q): ?> pour "<strong><?= h($q) ?></strong>"<?php endif; ?>
  </div>

  <?php if (empty($cours)): ?>
  <div class="empty-state">
    <i class="ti ti-search-off"></i>
    <p>Aucune formation ne correspond à votre recherche.</p>
    <a href="catalogue.php" style="color:var(--amber-dark);font-weight:600">Voir toutes les formations →</a>
  </div>
  <?php else: ?>
  <div class="cours-grid">
    <?php foreach ($cours as $c): ?>
    <div class="cours-card">
      <div class="cours-thumb" style="background:<?= h($c['cat_couleur'] ?? '#F59E0B') ?>22">
        <?php if ($c['miniature']): ?>
          <img src="<?= SITE_URL ?>/assets/uploads/<?= h($c['miniature']) ?>" alt="<?= h($c['titre']) ?>">
        <?php else: ?>
          <div class="cours-thumb-ph">
            <i class="ti <?= h($c['cat_icone'] ?? 'ti-book') ?>" style="color:<?= h($c['cat_couleur'] ?? '#F59E0B') ?>"></i>
          </div>
        <?php endif; ?>
        <span class="cours-badge <?= $c['type']==='gratuit' ? 'gratuit' : 'payant' ?>">
          <?= $c['type']==='gratuit' ? 'Gratuit' : number_format((float)$c['prix'],0,',',' ').' FCFA' ?>
        </span>
      </div>
      <div class="cours-body">
        <?php if ($c['cat_nom']): ?>
        <div class="cours-cat" style="color:<?= h($c['cat_couleur'] ?? '#F59E0B') ?>"><?= h($c['cat_nom']) ?></div>
        <?php endif; ?>
        <div class="cours-title"><?= h($c['titre']) ?></div>
        <?php if ($c['description']): ?>
        <div class="cours-desc"><?= h(strip_tags($c['description'])) ?></div>
        <?php endif; ?>
        <div class="cours-meta">
          <div>
            <?php if ($c['type']==='gratuit'): ?>
              <div class="cours-price free">Gratuit</div>
            <?php else: ?>
              <div class="cours-price"><?= number_format((float)$c['prix'],0,',',' ') ?> <span style="font-size:12px;font-weight:400;color:var(--text-muted)">FCFA</span></div>
            <?php endif; ?>
          </div>
          <div class="cours-info">
            <span><i class="ti ti-list"></i> <?= $c['nb_lecons'] ?> séq.</span>
            <span><i class="ti ti-users"></i> <?= $c['nb_inscrits'] ?></span>
          </div>
        </div>
        <a href="<?= SITE_URL ?>/course.php?slug=<?= h($c['slug']) ?>" class="btn-cours">
          <?= $c['type']==='gratuit' ? 'Commencer gratuitement' : 'Voir la formation' ?> →
        </a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
