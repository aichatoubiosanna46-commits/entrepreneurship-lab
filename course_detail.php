<?php
// course_detail.php — Page de présentation publique d'un cours
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pdo  = getPDO();
$slug = trim($_GET['slug'] ?? '');

if (!$slug) redirect(SITE_URL . '/catalogue.php');

try {
    $stmt = $pdo->prepare(
        'SELECT c.*, cat.nom as cat_nom, cat.couleur as cat_couleur, cat.icone as cat_icone
         FROM courses c LEFT JOIN categories cat ON cat.id = c.category_id
         WHERE c.slug = ? AND c.actif = 1 LIMIT 1'
    );
    $stmt->execute([$slug]);
    $course = $stmt->fetch();
} catch (Exception $e) { $course = null; }

if (!$course) redirect(SITE_URL . '/catalogue.php');

// Modules du cours
try {
    $mods = $pdo->prepare('SELECT * FROM modules WHERE course_id = ? AND actif = 1 ORDER BY ordre ASC');
    $mods->execute([$course['id']]);
    $modules = $mods->fetchAll();
} catch (Exception $e) { $modules = []; }

// Nombre total de séquences
try {
    $nbSeq = (int)$pdo->prepare('SELECT COUNT(*) FROM sequences s JOIN modules m ON m.id = s.module_id WHERE m.course_id = ? AND s.actif = 1')->execute([$course['id']]) ? 
             $pdo->query('SELECT COUNT(*) FROM sequences s JOIN modules m ON m.id = s.module_id WHERE m.course_id = '.(int)$course['id'].' AND s.actif = 1')->fetchColumn() : 0;
} catch (Exception $e) { $nbSeq = 0; }

// Inscrits
try {
    $nbInscrits = (int)$pdo->prepare('SELECT COUNT(*) FROM enrollments WHERE course_id = ? AND statut = "actif"')->execute([$course['id']]) ?
                  $pdo->query('SELECT COUNT(*) FROM enrollments WHERE course_id = '.(int)$course['id'].' AND statut = "actif"')->fetchColumn() : 0;
} catch (Exception $e) { $nbInscrits = 0; }

// Est-ce que l'utilisateur est déjà inscrit ?
$estInscrit = false;
if (estConnecte()) {
    try {
        $chk = $pdo->prepare('SELECT id FROM enrollments WHERE user_id = ? AND course_id = ? AND statut = "actif"');
        $chk->execute([$_SESSION['user_id'], $course['id']]);
        $estInscrit = (bool)$chk->fetch();
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($course['seo_title'] ?: $course['titre']) ?> — <?= SITE_NAME ?></title>
<meta name="description" content="<?= h($course['seo_description'] ?: substr(strip_tags($course['description'] ?? ''), 0, 155)) ?>">
<meta name="keywords" content="<?= h($course['seo_keywords'] ?? 'entrepreneuriat, formation, bénin, ariziki') ?>">
<meta property="og:title" content="<?= h($course['seo_title'] ?: $course['titre']) ?> — <?= SITE_NAME ?>">
<meta property="og:description" content="<?= h($course['seo_description'] ?: substr(strip_tags($course['description'] ?? ''), 0, 155)) ?>">
<meta property="og:type" content="website">
<?php if ($course['miniature']): ?>
<meta property="og:image" content="<?= SITE_URL ?>/assets/uploads/<?= h($course['miniature']) ?>">
<?php endif; ?>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<style>
:root{--gold:#D85A30;--gold-dark:#C04A22;--navy:#1A1A18;--bg:#F5F0E8;--muted:#6b7280;--border:#e5e7eb}
body{background:#fff;font-family:'Plus Jakarta Sans',sans-serif}

.cd-hero{background:linear-gradient(135deg,#1A1A18 0%,#292524 100%);padding:52px 24px 48px;color:#fff}
.cd-hero-inner{max-width:1000px;margin:0 auto;display:grid;grid-template-columns:1fr 340px;gap:40px;align-items:start}
.cd-breadcrumb{font-size:12px;color:rgba(255,255,255,.5);margin-bottom:16px}
.cd-breadcrumb a{color:rgba(255,255,255,.6);text-decoration:none}
.cd-breadcrumb a:hover{color:var(--gold)}
.cd-cat-tag{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:700;margin-bottom:14px}
.cd-title{font-size:30px;font-weight:800;color:#fff;line-height:1.2;margin-bottom:12px}
.cd-subtitle{font-size:15px;color:rgba(255,255,255,.7);margin-bottom:20px;line-height:1.6}
.cd-stats{display:flex;gap:20px;flex-wrap:wrap;margin-bottom:20px}
.cd-stat{display:flex;align-items:center;gap:6px;font-size:13px;color:rgba(255,255,255,.7)}
.cd-stat i{color:var(--gold);font-size:16px}

.cd-card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 8px 32px rgba(0,0,0,.15);position:sticky;top:80px}
.cd-card-thumb{height:180px;border-radius:10px;overflow:hidden;margin-bottom:20px;background:#f3f4f6}
.cd-card-thumb img{width:100%;height:100%;object-fit:cover}
.cd-card-price{font-size:32px;font-weight:800;color:#1A1A18;margin-bottom:16px}
.cd-card-price.free{color:#16a34a;font-size:24px}
.btn-enroll{display:block;width:100%;padding:14px;text-align:center;background:linear-gradient(135deg,var(--gold),#C04A22);color:#1A1A18;border:none;border-radius:10px;font-size:15px;font-weight:800;cursor:pointer;text-decoration:none;transition:opacity .15s;margin-bottom:10px}
.btn-enroll:hover{opacity:.9}
.btn-enroll.enrolled{background:#ECFDF5;color:#16a34a;border:1.5px solid #86efac}
.cd-card-perks{list-style:none;padding:0;margin:16px 0 0;display:flex;flex-direction:column;gap:9px}
.cd-card-perks li{font-size:13px;color:#374151;display:flex;align-items:center;gap:8px}
.cd-card-perks li i{color:var(--gold);font-size:15px;flex-shrink:0}

.cd-body{max-width:1000px;margin:0 auto;padding:40px 24px 60px;display:grid;grid-template-columns:1fr 340px;gap:40px}
.cd-main{}
.cd-section{margin-bottom:36px}
.cd-section-title{font-size:18px;font-weight:700;color:#1A1A18;margin-bottom:16px;padding-bottom:10px;border-bottom:2px solid #D85A30;display:inline-block}
.cd-desc{font-size:14px;color:#374151;line-height:1.8}

.cd-module{border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:10px}
.cd-module-header{padding:14px 18px;background:#f9fafb;display:flex;align-items:center;gap:10px;cursor:pointer;user-select:none}
.cd-module-header i.arrow{margin-left:auto;transition:transform .2s;color:var(--muted)}
.cd-module-header.open i.arrow{transform:rotate(180deg)}
.cd-module-title{font-size:14px;font-weight:600;color:#1A1A18;flex:1}
.cd-module-count{font-size:12px;color:var(--muted)}
.cd-module-body{display:none;padding:4px 0}
.cd-module-body.open{display:block}
.cd-seq-item{padding:10px 18px 10px 48px;font-size:13px;color:#374151;border-top:1px solid #f3f4f6;display:flex;align-items:center;gap:8px}
.cd-seq-item i{color:var(--muted);font-size:15px}

.cd-perks-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.cd-perk-item{display:flex;align-items:flex-start;gap:10px;padding:14px;background:#F5F0E8;border-radius:10px;border:1px solid #fde68a}
.cd-perk-item i{font-size:20px;color:var(--gold);flex-shrink:0}
.cd-perk-item strong{display:block;font-size:13px;font-weight:700;color:#1A1A18;margin-bottom:2px}
.cd-perk-item span{font-size:12px;color:var(--muted)}

@media(max-width:900px){
  .cd-hero-inner,.cd-body{grid-template-columns:1fr}
  .cd-card{position:static}
  .cd-card{order:-1}
  .cd-perks-grid{grid-template-columns:1fr}
}
</style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>

<!-- Hero -->
<div class="cd-hero">
  <div class="cd-hero-inner">
    <div>
      <div class="cd-breadcrumb">
        <a href="<?= SITE_URL ?>">Accueil</a> › 
        <a href="<?= SITE_URL ?>/catalogue.php">Formations</a> › 
        <?= h($course['titre']) ?>
      </div>
      <?php if ($course['cat_nom']): ?>
      <div class="cd-cat-tag" style="background:<?= h($course['cat_couleur'] ?? '#D85A30') ?>33;color:<?= h($course['cat_couleur'] ?? '#D85A30') ?>">
        <i class="ti <?= h($course['cat_icone'] ?? 'ti-book') ?>"></i>
        <?= h($course['cat_nom']) ?>
      </div>
      <?php endif; ?>
      <h1 class="cd-title"><?= h($course['titre']) ?></h1>
      <?php if ($course['sous_titre'] ?? ''): ?>
      <p class="cd-subtitle"><?= h($course['sous_titre']) ?></p>
      <?php endif; ?>
      <div class="cd-stats">
        <div class="cd-stat"><i class="ti ti-list"></i> <?= $nbSeq ?> séquences</div>
        <div class="cd-stat"><i class="ti ti-users"></i> <?= $nbInscrits ?> inscrits</div>
        <div class="cd-stat"><i class="ti ti-certificate"></i> Certificat inclus</div>
        <div class="cd-stat"><i class="ti ti-infinity"></i> Accès à vie</div>
      </div>
    </div>

    <!-- Card sticky -->
    <div class="cd-card">
      <?php if ($course['miniature']): ?>
      <div class="cd-card-thumb">
        <img src="<?= SITE_URL ?>/assets/uploads/<?= h($course['miniature']) ?>" alt="">
      </div>
      <?php endif; ?>

      <?php if ($course['type'] === 'gratuit'): ?>
        <div class="cd-card-price free">Gratuit</div>
      <?php else: ?>
        <div class="cd-card-price"><?= number_format((float)$course['prix'],0,',',' ') ?> <span style="font-size:16px;font-weight:400;color:var(--muted)">FCFA</span></div>
      <?php endif; ?>

      <?php if ($estInscrit): ?>
        <a href="<?= SITE_URL ?>/module.php?slug=<?= h($course['slug']) ?>" class="btn-enroll enrolled">
          <i class="ti ti-player-play"></i> Continuer la formation
        </a>
      <?php elseif ($course['type'] === 'gratuit'): ?>
        <a href="<?= SITE_URL ?>/register.php" class="btn-enroll">
          <i class="ti ti-user-plus"></i> Commencer gratuitement →
        </a>
      <?php else: ?>
        <a href="<?= SITE_URL ?>/payment.php" class="btn-enroll">
          <i class="ti ti-credit-card"></i> S'inscrire — <?= number_format((float)$course['prix'],0,',',' ') ?> FCFA →
        </a>
      <?php endif; ?>

      <ul class="cd-card-perks">
        <li><i class="ti ti-device-mobile"></i> Paiement Mobile Money accepté</li>
        <li><i class="ti ti-certificate"></i> Certificat Université de Parakou</li>
        <li><i class="ti ti-infinity"></i> Accès à vie au contenu</li>
        <li><i class="ti ti-headset"></i> Coaching 1:1 inclus</li>
        <li><i class="ti ti-shield-check"></i> Paiement sécurisé FedaPay</li>
      </ul>
    </div>
  </div>
</div>

<!-- Corps -->
<div class="cd-body">
  <div class="cd-main">

    <!-- Description -->
    <?php if ($course['description']): ?>
    <div class="cd-section">
      <div class="cd-section-title">À propos de cette formation</div>
      <div class="cd-desc"><?= nl2br(h($course['description'])) ?></div>
    </div>
    <?php endif; ?>

    <!-- Ce que tu vas apprendre -->
    <div class="cd-section">
      <div class="cd-section-title">Ce que tu vas apprendre</div>
      <div class="cd-perks-grid">
        <div class="cd-perk-item"><i class="ti ti-bulb"></i><div><strong>Valider ton idée</strong><span>Méthode pratique adaptée au contexte béninois</span></div></div>
        <div class="cd-perk-item"><i class="ti ti-chart-bar"></i><div><strong>Construire ton Business Plan</strong><span>Modèle téléchargeable et exercices concrets</span></div></div>
        <div class="cd-perk-item"><i class="ti ti-users"></i><div><strong>Trouver tes premiers clients</strong><span>Stratégies terrain testées au Bénin</span></div></div>
        <div class="cd-perk-item"><i class="ti ti-certificate"></i><div><strong>Être certifié</strong><span>Reconnaissance officielle Université de Parakou</span></div></div>
      </div>
    </div>

    <!-- Programme des modules -->
    <?php if (!empty($modules)): ?>
    <div class="cd-section">
      <div class="cd-section-title">Programme de la formation</div>
      <?php foreach ($modules as $i => $mod): ?>
      <?php
        try {
          $seqs = $pdo->prepare('SELECT titre, type FROM sequences WHERE module_id = ? AND actif = 1 ORDER BY ordre ASC');
          $seqs->execute([$mod['id']]);
          $seqList = $seqs->fetchAll();
        } catch (Exception $e) { $seqList = []; }
      ?>
      <div class="cd-module">
        <div class="cd-module-header" onclick="toggleModule(this)">
          <span style="width:26px;height:26px;border-radius:50%;background:var(--gold);color:#1A1A18;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;flex-shrink:0"><?= $i+1 ?></span>
          <div class="cd-module-title"><?= h($mod['titre']) ?></div>
          <div class="cd-module-count"><?= count($seqList) ?> leçon<?= count($seqList)>1?'s':'' ?></div>
          <i class="ti ti-chevron-down arrow"></i>
        </div>
        <div class="cd-module-body <?= $i===0?'open':'' ?>">
          <?php foreach ($seqList as $seq): ?>
          <div class="cd-seq-item">
            <?php $icon = match($seq['type'] ?? 'video') {
              'video' => 'ti-video', 'quiz' => 'ti-help-circle',
              'text' => 'ti-file-text', 'assignment' => 'ti-clipboard',
              default => 'ti-file'
            }; ?>
            <i class="ti <?= $icon ?>"></i>
            <?= h($seq['titre']) ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div>

  <!-- Sidebar vide sur desktop (sticky card déjà dans hero) -->
  <div></div>
</div>

<script>
function toggleModule(header) {
  header.classList.toggle('open');
  header.nextElementSibling.classList.toggle('open');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
