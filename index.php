<?php
// index.php — Page d'accueil publique + espace connecté
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getPDO();

// Formations inscrites (vue connectée uniquement)
$mesModules = [];
if (estConnecte()) {
    $mesMods = $pdo->prepare(
        'SELECT m.*, c.nom as categorie, c.icone as cat_icone, c.couleur as cat_couleur,
                (SELECT COUNT(*) FROM sequences s
                 JOIN modules mo ON mo.id = s.module_id
                 WHERE mo.course_id = m.id AND s.actif = 1) as nb_lecons
         FROM enrollments i
         JOIN courses m ON m.id = i.course_id
         JOIN categories c ON c.id = m.category_id
         WHERE i.user_id = ? AND i.statut = "actif"
         ORDER BY i.created_at DESC'
    );
    $mesMods->execute([$_SESSION['user_id']]);
    $mesModules = $mesMods->fetchAll();
}

// Slides homepage
$slides = $pdo->query('SELECT * FROM slides WHERE actif = 1 ORDER BY ordre ASC LIMIT 6')->fetchAll();

$pageTitle = 'Accueil';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= SITE_NAME ?> — Formations en entrepreneuriat</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Outfit:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/home.css">

<style>
/* ============================================================
   VARIABLES
   ============================================================ */
:root {
  --navy:        #0F1D35;
  --navy-mid:    #1E3050;
  --gold:        #F5C518;
  --gold-dark:   #D4A90E;
  --gold-pale:   rgba(245,197,24,.12);
  --gold-border: rgba(212,169,14,.3);
  --text-muted:  #6B7280;
  --bg-light:    #F4F7FD;
  --radius:      10px;
  --radius-lg:   14px;
}

/* ============================================================
   NAVBAR
   ============================================================ */
.elab-nav {
  background: var(--navy);
  padding: 12px 32px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: sticky;
  top: 0;
  z-index: 200;
  box-shadow: 0 2px 16px rgba(0,0,0,.3);
}
.elab-nav-logo {
  color: var(--gold);
  font-size: 14px;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 8px;
  text-decoration: none;
}
.elab-nav-logo .dot {
  width: 30px; height: 30px;
  background: var(--gold);
  border-radius: 8px;
  display: grid; place-items: center;
}
.elab-nav-logo .dot i { font-size: 15px; color: var(--navy); }
.elab-nav-links { display: flex; gap: 24px; }
.elab-nav-links a {
  font-size: 12px; color: rgba(255,255,255,.65);
  text-decoration: none; transition: color .2s;
}
.elab-nav-links a:hover { color: var(--gold); }
.elab-nav-btn {
  background: var(--gold); color: var(--navy);
  border: none; border-radius: 7px;
  padding: 8px 18px; font-size: 12px; font-weight: 700;
  cursor: pointer; text-decoration: none;
  transition: background .2s;
}
.elab-nav-btn:hover { background: var(--gold-dark); }

/* ============================================================
   CAROUSEL — pleine largeur, visible par tous
   ============================================================ */
.elab-carousel {
  position: relative;
  overflow: hidden;
  background: var(--navy);
  height: 500px;
  width: 100%;
}
.carousel-track {
  display: flex;
  height: 100%;
  will-change: transform;
  transition: transform .65s cubic-bezier(.4,0,.2,1);
}
.carousel-slide {
  min-width: 100%;
  height: 100%;
  position: relative;
  flex-shrink: 0;
  overflow: hidden;
}
.carousel-slide img {
  width: 100%; height: 100%;
  object-fit: cover;
  object-position: center;
  display: block;
}
/* Fallback dégradé si pas d'image */
.carousel-slide.no-img { background: linear-gradient(135deg, var(--navy) 0%, #1a3060 50%, #0d3b2e 100%); }
.carousel-slide.no-img-2 { background: linear-gradient(135deg, #1a2340 0%, var(--gold-dark) 100%); }
.carousel-slide.no-img-3 { background: linear-gradient(135deg, #0d2b1a 0%, #1a5a3e 100%); }

.carousel-overlay {
  position: absolute; inset: 0;
  background: linear-gradient(100deg, rgba(15,29,53,.85) 35%, rgba(15,29,53,.3) 100%);
}
.carousel-content {
  position: absolute; inset: 0;
  display: flex; align-items: center;
  padding: 0 72px;
}
.carousel-inner { max-width: 580px; }

.carousel-tag {
  display: inline-flex; align-items: center; gap: 6px;
  background: rgba(245,197,24,.15);
  border: 1px solid rgba(245,197,24,.35);
  border-radius: 100px; padding: 5px 14px;
  font-size: 10px; color: var(--gold);
  margin-bottom: 18px;
}
.carousel-tag::before {
  content: ''; width: 6px; height: 6px;
  border-radius: 50%; background: var(--gold); display: block;
}
.carousel-inner h1 {
  font-size: clamp(24px, 4vw, 38px);
  font-weight: 700; color: #fff;
  line-height: 1.18; margin-bottom: 14px;
}
.carousel-inner h1 em {
  font-style: normal; color: var(--gold);
  text-decoration: underline;
  text-decoration-color: rgba(245,197,24,.45);
}
.carousel-inner p {
  font-size: 13px; color: rgba(255,255,255,.72);
  line-height: 1.75; margin-bottom: 24px; max-width: 460px;
}
.carousel-btns { display: flex; gap: 12px; flex-wrap: wrap; }

.cbtn-primary {
  background: var(--gold); color: var(--navy);
  border: none; border-radius: 8px;
  padding: 12px 24px; font-size: 12px; font-weight: 700;
  cursor: pointer; text-decoration: none;
  transition: background .2s, transform .15s; display: inline-block;
}
.cbtn-primary:hover { background: var(--gold-dark); transform: translateY(-1px); }
.cbtn-outline {
  background: transparent; color: #fff;
  border: 1.5px solid rgba(255,255,255,.4);
  border-radius: 8px; padding: 11px 24px;
  font-size: 12px; font-weight: 600;
  cursor: pointer; text-decoration: none;
  transition: border-color .2s, color .2s; display: inline-block;
}
.cbtn-outline:hover { border-color: var(--gold); color: var(--gold); }

/* Flèches */
.carousel-arrow {
  position: absolute; top: 50%; transform: translateY(-50%);
  width: 46px; height: 46px;
  background: rgba(255,255,255,.12);
  border: 1.5px solid rgba(255,255,255,.2);
  border-radius: 50%; color: #fff; font-size: 20px;
  display: grid; place-items: center;
  cursor: pointer; z-index: 20;
  transition: background .2s, border-color .2s;
  backdrop-filter: blur(6px);
}
.carousel-arrow:hover { background: rgba(245,197,24,.55); border-color: var(--gold); }
.carousel-arrow.prev { left: 20px; }
.carousel-arrow.next { right: 20px; }

/* Dots */
.carousel-dots {
  position: absolute; bottom: 20px; left: 50%;
  transform: translateX(-50%);
  display: flex; gap: 8px; z-index: 20;
}
.cdot {
  width: 8px; height: 8px; border-radius: 50%;
  background: rgba(255,255,255,.35); cursor: pointer;
  transition: background .25s, width .3s, border-radius .3s;
  border: none;
}
.cdot.active { background: var(--gold); width: 28px; border-radius: 4px; }

/* Indicateur de progression */
.carousel-progress {
  position: absolute; bottom: 0; left: 0;
  height: 3px; background: var(--gold);
  transition: width .1s linear;
  z-index: 20;
}

/* ============================================================
   TRUST BAR
   ============================================================ */
.trust-bar {
  background: var(--navy);
  padding: 12px 32px;
  display: flex; justify-content: center;
  flex-wrap: wrap; gap: 24px;
  border-top: 1px solid rgba(255,255,255,.08);
}
.trust-item {
  display: flex; align-items: center; gap: 6px;
  font-size: 11px; color: rgba(255,255,255,.6);
}
.trust-item i { font-size: 14px; color: var(--gold); }

/* ============================================================
   STATS BAR
   ============================================================ */
.stats-bar {
  background: var(--gold);
  padding: 18px 32px;
  display: flex; justify-content: space-around; flex-wrap: wrap; gap: 10px;
}
.stat-item .big { font-size: 20px; font-weight: 700; color: var(--navy); text-align: center; }
.stat-item .sm  { font-size: 9px; color: rgba(15,29,53,.6); text-align: center; margin-top: 2px; }

/* ============================================================
   SECTIONS
   ============================================================ */
.elab-section { padding: 48px 32px; }
.elab-section.alt { background: var(--bg-light); }
.elab-section.dark { background: var(--navy); }

.sec-tag {
  display: inline-flex; align-items: center; gap: 5px;
  background: var(--gold-pale); border: 1px solid var(--gold-border);
  border-radius: 100px; padding: 4px 12px;
  font-size: 9px; color: #8A6700; margin-bottom: 8px;
}
.sec-tag.light {
  background: rgba(245,197,24,.12); color: var(--gold);
  border-color: rgba(245,197,24,.25);
}
.sec-title { font-size: 17px; font-weight: 700; color: var(--navy); margin-bottom: 4px; }
.sec-title.light { color: #fff; }
.sec-sub { font-size: 11px; color: var(--text-muted); margin-bottom: 20px; }
.sec-sub.light { color: rgba(255,255,255,.5); }

/* ============================================================
   WHY GRID
   ============================================================ */
.why-grid {
  display: grid; grid-template-columns: repeat(3,1fr);
  gap: 14px; margin-top: 18px;
}
.why-card {
  background: var(--bg-light); border: 1px solid rgba(0,0,0,.07);
  border-radius: var(--radius-lg); padding: 18px;
}
.why-ico {
  width: 38px; height: 38px;
  background: rgba(245,197,24,.15); border-radius: 9px;
  display: grid; place-items: center; margin-bottom: 10px;
}
.why-ico i { font-size: 20px; color: var(--gold-dark); }
.why-card h4 { font-size: 11px; font-weight: 600; color: var(--navy); margin-bottom: 5px; }
.why-card p  { font-size: 9.5px; color: var(--text-muted); line-height: 1.65; }

/* ============================================================
   TARIFS
   ============================================================ */
.pricing-grid {
  display: grid; grid-template-columns: repeat(3,1fr);
  gap: 16px; margin-top: 20px;
}
.pricing-card {
  background: #fff; border-radius: var(--radius-lg);
  border: 1.5px solid rgba(0,0,0,.08);
  overflow: hidden;
  transition: transform .2s, box-shadow .2s;
  position: relative;
}
.pricing-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(15,29,53,.12); }
.pricing-card.featured {
  border-color: var(--gold);
  box-shadow: 0 6px 24px rgba(245,197,24,.25);
}
.pricing-thumb {
  height: 70px; display: flex; align-items: center;
  justify-content: center; font-size: 26px; position: relative;
}
.pricing-thumb.t1 { background: linear-gradient(135deg, var(--navy), var(--navy-mid)); }
.pricing-thumb.t2 { background: linear-gradient(135deg, #1a2340, var(--gold-dark)); }
.pricing-thumb.t3 { background: linear-gradient(135deg, #0d3b2e, #1a6b52); }

.pricing-badge {
  position: absolute; top: 8px; left: 8px;
  background: var(--gold); color: var(--navy);
  font-size: 8px; font-weight: 700;
  padding: 3px 8px; border-radius: 100px;
}
.pricing-badge.pop { background: #fff; color: var(--navy); }

.pricing-body { padding: 16px; }
.pricing-body h4 { font-size: 12px; font-weight: 700; color: var(--navy); margin-bottom: 4px; line-height: 1.3; }
.pricing-body .pricing-desc { font-size: 9.5px; color: var(--text-muted); line-height: 1.6; margin-bottom: 12px; }

.pricing-price {
  display: flex; align-items: baseline; gap: 4px; margin-bottom: 14px;
}
.pricing-price .amount { font-size: 22px; font-weight: 700; color: var(--navy); }
.pricing-price .amount.free { color: #16a34a; font-size: 16px; }
.pricing-price .currency { font-size: 11px; color: var(--text-muted); }

.pricing-features { list-style: none; padding: 0; margin: 0 0 16px; }
.pricing-features li {
  display: flex; align-items: center; gap: 7px;
  font-size: 9.5px; color: var(--text-muted);
  padding: 4px 0; border-bottom: 1px solid rgba(0,0,0,.05);
}
.pricing-features li:last-child { border-bottom: none; }
.pricing-features li i { font-size: 12px; color: var(--gold-dark); flex-shrink: 0; }

.btn-pricing {
  display: block; text-align: center; text-decoration: none;
  background: var(--navy); color: #fff;
  border-radius: 8px; padding: 10px;
  font-size: 10px; font-weight: 700;
  transition: background .2s;
}
.btn-pricing:hover { background: var(--navy-mid); }
.btn-pricing.gold { background: var(--gold); color: var(--navy); }
.btn-pricing.gold:hover { background: var(--gold-dark); }
.btn-pricing.outline {
  background: transparent; color: var(--navy);
  border: 1.5px solid var(--navy);
}
.btn-pricing.outline:hover { background: var(--navy); color: #fff; }

/* ============================================================
   HOW IT WORKS
   ============================================================ */
.how-steps {
  display: grid; grid-template-columns: repeat(4,1fr);
  gap: 14px; margin-top: 20px;
}
.hstep { text-align: center; }
.step-num {
  width: 42px; height: 42px; border-radius: 50%;
  background: var(--gold); color: var(--navy);
  font-size: 16px; font-weight: 700;
  display: grid; place-items: center;
  margin: 0 auto 10px;
}
.hstep h4 { font-size: 11px; font-weight: 600; color: #fff; margin-bottom: 4px; }
.hstep p  { font-size: 9px; color: rgba(255,255,255,.5); line-height: 1.6; }

/* ============================================================
   CTA
   ============================================================ */
.cta-section {
  background: linear-gradient(135deg, var(--gold), var(--gold-dark));
  padding: 48px 32px; text-align: center;
}
.cta-section h2 { font-size: 18px; font-weight: 700; color: var(--navy); margin-bottom: 10px; }
.cta-section p  { font-size: 11px; color: rgba(15,29,53,.65); margin-bottom: 22px; line-height: 1.7; }
.cta-btns { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }

/* ============================================================
   VUE CONNECTÉE
   ============================================================ */
.hero-connected {
  background: linear-gradient(120deg, #EEF3FB 0%, #FFF9D6 100%);
  padding: 20px 32px;
  border-bottom: 1px solid rgba(0,0,0,.07);
}
.hero-connected-inner {
  display: flex; align-items: center;
  justify-content: space-between; flex-wrap: wrap; gap: 12px;
}
.hero-greeting h1 { font-size: 18px; font-weight: 700; color: var(--navy); }
.name-highlight { color: var(--gold-dark); }
.hero-greeting p { font-size: 11px; color: var(--text-muted); margin-top: 3px; }
.btn-primary {
  background: var(--navy); color: #fff;
  border: none; border-radius: 8px;
  padding: 10px 18px; font-size: 12px; font-weight: 700;
  text-decoration: none;
  display: inline-flex; align-items: center; gap: 7px;
  cursor: pointer; transition: background .2s;
}
.btn-primary:hover { background: var(--navy-mid); }

/* Modules (vue connectée) */
.modules-grid {
  display: grid; grid-template-columns: repeat(3,1fr);
  gap: 14px; margin-top: 18px;
}
.module-card {
  background: #fff; border-radius: var(--radius-lg); overflow: hidden;
  border: 1px solid rgba(0,0,0,.07); text-decoration: none;
  display: block; transition: transform .2s, box-shadow .2s;
}
.module-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(15,29,53,.1); }
.module-thumb {
  height: 70px; position: relative; background: var(--navy);
  display: flex; align-items: center; justify-content: center;
}
.module-thumb img { width: 100%; height: 100%; object-fit: cover; }
.module-thumb-placeholder { width: 100%; height: 100%; display: grid; place-items: center; }
.module-progress-bar { position: absolute; bottom: 0; left: 0; right: 0; height: 3px; background: rgba(255,255,255,.2); }
.module-progress-bar div { height: 100%; background: var(--gold); }
.module-body { padding: 10px 12px 14px; }
.module-cat { font-size: 8px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
.module-body h3 { font-size: 11px; font-weight: 600; color: var(--navy); margin: 4px 0 6px; line-height: 1.35; }
.module-meta {
  display: flex; align-items: center; justify-content: space-between;
  font-size: 9px; color: var(--text-muted); gap: 6px;
}
.module-meta span { display: flex; align-items: center; gap: 3px; }
.module-pct { font-size: 9px; font-weight: 700; color: var(--gold-dark); }

/* ============================================================
   FOOTER
   ============================================================ */
.elab-footer { background: var(--navy); padding: 32px 32px 18px; }
.foot-grid {
  display: grid; grid-template-columns: 2fr 1fr 1fr;
  gap: 28px; margin-bottom: 22px;
}
.foot-logo { color: var(--gold); font-size: 14px; font-weight: 700; margin-bottom: 8px; }
.foot-desc { font-size: 9.5px; color: rgba(255,255,255,.5); line-height: 1.75; }
.foot-col h5 { font-size: 11px; font-weight: 600; color: #fff; margin-bottom: 10px; }
.foot-col ul { list-style: none; padding: 0; margin: 0; }
.foot-col ul li { font-size: 9.5px; color: rgba(255,255,255,.5); margin-bottom: 6px; }
.foot-col ul li a { color: inherit; text-decoration: none; }
.foot-col ul li a:hover { color: var(--gold); }
.foot-bottom {
  border-top: 1px solid rgba(255,255,255,.1); padding-top: 14px;
  display: flex; justify-content: space-between; flex-wrap: wrap; gap: 6px;
}
.foot-bottom p { font-size: 9px; color: rgba(255,255,255,.35); }

/* ============================================================
   RESPONSIVE
   ============================================================ */
@media (max-width: 900px) {
  .pricing-grid, .why-grid, .modules-grid { grid-template-columns: 1fr 1fr; }
  .how-steps { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 768px) {
  .elab-carousel { height: 380px; }
  .carousel-content { padding: 0 24px; }
  .elab-nav-links { display: none; }
  .foot-grid { grid-template-columns: 1fr; }
}
@media (max-width: 500px) {
  .pricing-grid, .why-grid, .modules-grid, .how-steps { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<!-- ======================================================
     NAVBAR
     ====================================================== -->
<nav class="elab-nav">
  <a href="<?= SITE_URL ?>" class="elab-nav-logo">
    <div class="dot"><i class="ti ti-star"></i></div>
    <?= SITE_NAME ?>
  </a>
  <div class="elab-nav-links">
    <a href="<?= SITE_URL ?>">Accueil</a>
    <a href="#tarifs">Formations</a>
    <a href="<?= SITE_URL ?>/coaching.php">Coaching</a>
    <a href="<?= SITE_URL ?>/blog.php">Blog</a>
  </div>
  <?php if (estConnecte()): ?>
    <a href="<?= SITE_URL ?>/dashboard.php" class="elab-nav-btn">
      <i class="ti ti-layout-dashboard"></i> Mon espace
    </a>
  <?php else: ?>
    <a href="<?= SITE_URL ?>/register.php" class="elab-nav-btn">S'inscrire →</a>
  <?php endif; ?>
</nav>

<?= flash() ?>

<!-- ============================================================
     CAROUSEL D'IMAGES — visible pour TOUS (connecté ou non)
     ============================================================ -->
<div class="elab-carousel" id="mainCarousel">
  <div class="carousel-track" id="carouselTrack">

    <?php if (!empty($slides)): ?>
      <?php foreach ($slides as $i => $s): ?>
      <div class="carousel-slide <?= !$s['image'] ? 'no-img' : '' ?>">
        <?php if ($s['image']): ?>
          <img src="<?= SITE_URL ?>/assets/uploads/<?= h($s['image']) ?>"
               alt="<?= h($s['titre']) ?>" loading="<?= $i === 0 ? 'eager' : 'lazy' ?>">
        <?php endif; ?>
        <div class="carousel-overlay"></div>
        <div class="carousel-content">
          <div class="carousel-inner">
            <div class="carousel-tag">🇧🇯 Université de Parakou · Certifié</div>
            <h1><?= h($s['titre']) ?></h1>
            <?php if ($s['sous_titre']): ?>
              <p><?= h($s['sous_titre']) ?></p>
            <?php endif; ?>
            <div class="carousel-btns">
              <?php if ($s['lien']): ?>
                <a href="<?= h($s['lien']) ?>" class="cbtn-primary"><?= h($s['texte_btn'] ?: 'Commencer') ?> →</a>
              <?php else: ?>
                <a href="<?= SITE_URL ?>/register.php" class="cbtn-primary">Commencer gratuitement →</a>
              <?php endif; ?>
              <a href="#tarifs" class="cbtn-outline">Voir les formations</a>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>

    <?php else: ?>
      <!-- 3 slides de démonstration si aucune slide en BDD -->
      <div class="carousel-slide no-img">
        <div class="carousel-overlay"></div>
        <div class="carousel-content">
          <div class="carousel-inner">
            <div class="carousel-tag">🇧🇯 Fait pour les entrepreneurs béninois</div>
            <h1>Lance ton business<br><em>avant ton diplôme</em></h1>
            <p>Des formations pratiques, accessibles et adaptées au contexte africain. Coaching 1:1, paiement MoMo, certification Université de Parakou.</p>
            <div class="carousel-btns">
              <a href="<?= SITE_URL ?>/register.php" class="cbtn-primary">Commencer gratuitement →</a>
              <a href="#tarifs" class="cbtn-outline">Voir les formations</a>
            </div>
          </div>
        </div>
      </div>
      <div class="carousel-slide no-img-2">
        <div class="carousel-overlay"></div>
        <div class="carousel-content">
          <div class="carousel-inner">
            <div class="carousel-tag">💡 Trouve ton idée, construis ton plan</div>
            <h1>De l'idée au<br><em>Business Plan</em></h1>
            <p>Valide ton projet en 4h avec notre méthode éprouvée. Un mentor t'accompagne à chaque étape, sans jargon ni théorie inutile.</p>
            <div class="carousel-btns">
              <a href="#tarifs" class="cbtn-primary">Voir les tarifs →</a>
              <a href="<?= SITE_URL ?>/register.php" class="cbtn-outline">Créer un compte</a>
            </div>
          </div>
        </div>
      </div>
      <div class="carousel-slide no-img-3">
        <div class="carousel-overlay"></div>
        <div class="carousel-content">
          <div class="carousel-inner">
            <div class="carousel-tag">🚀 Coaching 1:1 · Certification · MoMo</div>
            <h1>Certifié par<br><em>l'Université de Parakou</em></h1>
            <p>Rejoins 1 200+ apprenants qui ont transformé leur idée en activité réelle. Paiement simple par Mobile Money, accès à vie.</p>
            <div class="carousel-btns">
              <a href="<?= SITE_URL ?>/register.php" class="cbtn-primary">Rejoindre la communauté →</a>
              <a href="#comment" class="cbtn-outline">Comment ça marche</a>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

  </div><!-- /carousel-track -->

  <!-- Flèches (toujours visibles s'il y a plus d'1 slide) -->
  <?php $nbSlides = !empty($slides) ? count($slides) : 3; ?>
  <?php if ($nbSlides > 1): ?>
  <button class="carousel-arrow prev" onclick="carouselMove(-1)" aria-label="Précédent">
    <i class="ti ti-chevron-left"></i>
  </button>
  <button class="carousel-arrow next" onclick="carouselMove(1)" aria-label="Suivant">
    <i class="ti ti-chevron-right"></i>
  </button>
  <div class="carousel-dots" id="carouselDots">
    <?php for ($i = 0; $i < $nbSlides; $i++): ?>
      <button class="cdot <?= $i === 0 ? 'active' : '' ?>" onclick="carouselTo(<?= $i ?>)" aria-label="Slide <?= $i+1 ?>"></button>
    <?php endfor; ?>
  </div>
  <?php endif; ?>

  <!-- Barre de progression autoplay -->
  <div class="carousel-progress" id="carouselProgress"></div>
</div>

<!-- ── TRUST BAR ── -->
<div class="trust-bar">
  <div class="trust-item"><i class="ti ti-certificate"></i> Certification Université de Parakou</div>
  <div class="trust-item"><i class="ti ti-device-mobile"></i> Paiement MoMo</div>
  <div class="trust-item"><i class="ti ti-headset"></i> Coaching 1:1</div>
  <div class="trust-item"><i class="ti ti-infinity"></i> Accès à vie</div>
</div>

<!-- ── STATS BAR ── -->
<div class="stats-bar">
  <div class="stat-item"><div class="big">1 200+</div><div class="sm">Apprenants</div></div>
  <div class="stat-item"><div class="big">4.8 ★</div><div class="sm">Satisfaction</div></div>
  <div class="stat-item"><div class="big">100%</div><div class="sm">En ligne</div></div>
  <div class="stat-item"><div class="big">MoMo</div><div class="sm">Paiement sécurisé</div></div>
</div>

<!-- ============================================================
     VUE CONNECTÉE — modules en cours (après le carousel)
     ============================================================ -->
<?php if (estConnecte()): ?>

<?php
  $user = utilisateurCourant();
?>
<div class="hero-connected">
  <div class="hero-connected-inner">
    <div class="hero-greeting">
      <h1>Bonjour, <span class="name-highlight"><?= h($user['prenom']) ?></span> 👋</h1>
      <p>Continue ta progression ou explore de nouveaux parcours ci-dessous.</p>
    </div>
    <a href="<?= SITE_URL ?>/dashboard.php" class="btn-primary">
      <i class="ti ti-layout-dashboard"></i> Mon tableau de bord
    </a>
  </div>
</div>

<?php if (!empty($mesModules)): ?>
<div class="elab-section">
  <div class="sec-tag">📚 Ma progression</div>
  <div class="sec-title">Modules en cours</div>
  <div class="modules-grid">
    <?php foreach ($mesModules as $m): ?>
    <?php $pct = progressionCours($_SESSION['user_id'], $m['id']); ?>
    <a href="<?= SITE_URL ?>/module.php?slug=<?= h($m['slug']) ?>" class="module-card">
      <div class="module-thumb">
        <?php if ($m['miniature']): ?>
          <img src="<?= SITE_URL ?>/assets/uploads/<?= h($m['miniature']) ?>" alt="<?= h($m['titre']) ?>">
        <?php else: ?>
          <div class="module-thumb-placeholder" style="background:<?= h($m['cat_couleur'] ?? '#BA7517') ?>22">
            <i class="ti <?= h($m['cat_icone'] ?? 'ti-book') ?>" style="color:<?= h($m['cat_couleur'] ?? '#BA7517') ?>;font-size:28px"></i>
          </div>
        <?php endif; ?>
        <div class="module-progress-bar"><div style="width:<?= $pct ?>%"></div></div>
      </div>
      <div class="module-body">
        <span class="module-cat" style="color:<?= h($m['cat_couleur'] ?? '#BA7517') ?>"><?= h($m['categorie']) ?></span>
        <h3><?= h($m['titre']) ?></h3>
        <div class="module-meta">
          <span><i class="ti ti-list"></i> <?= $m['nb_lecons'] ?> séquence<?= $m['nb_lecons'] != 1 ? 's' : '' ?></span>
          <span class="module-pct"><?= $pct ?>% complété</span>
        </div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php endif; /* fin estConnecte */ ?>

<!-- ============================================================
     POURQUOI NOUS — visible pour tous
     ============================================================ -->
<div class="elab-section">
  <div class="sec-tag">✨ Pourquoi nous ?</div>
  <div class="sec-title">Conçu pour les étudiants entrepreneurs</div>
  <div class="sec-sub">Compatible avec ton emploi du temps universitaire, 100% en ligne</div>
  <div class="why-grid">
    <div class="why-card">
      <div class="why-ico"><i class="ti ti-certificate"></i></div>
      <h4>Certification co-signée</h4>
      <p>L'Université de Parakou valide officiellement ton parcours entrepreneurial</p>
    </div>
    <div class="why-card">
      <div class="why-ico"><i class="ti ti-clock"></i></div>
      <h4>À ton rythme</h4>
      <p>100% en ligne, accès à vie au contenu, compatible avec tes cours universitaires</p>
    </div>
    <div class="why-card">
      <div class="why-ico"><i class="ti ti-headset"></i></div>
      <h4>Coaching 1:1</h4>
      <p>Un mentor dédié t'accompagne à chaque étape de la construction de ton projet</p>
    </div>
  </div>
</div>

<!-- ============================================================
     TARIFS — "Choisis ton parcours"
     ============================================================ -->
<div class="elab-section alt" id="tarifs">
  <div class="sec-tag">🎓 Formations</div>
  <div class="sec-title">Choisis ton parcours</div>
  <div class="sec-sub">Commence gratuitement, investis quand tu es prêt(e)</div>
  <div class="pricing-grid">

    <!-- Parcours 1 : Gratuit -->
    <div class="pricing-card">
      <div class="pricing-thumb t1">
        💡
        <span class="pricing-badge">Gratuit</span>
      </div>
      <div class="pricing-body">
        <h4>Trouver son idée de business</h4>
        <p class="pricing-desc">Valide ton projet en 4h avec notre méthode simple et adaptée au contexte béninois.</p>
        <div class="pricing-price">
          <span class="amount free">Gratuit</span>
        </div>
        <ul class="pricing-features">
          <li><i class="ti ti-check"></i> Accès immédiat sans carte</li>
          <li><i class="ti ti-check"></i> 3 vidéos + guide PDF</li>
          <li><i class="ti ti-check"></i> Exercice de validation d'idée</li>
          <li><i class="ti ti-check"></i> Accès à la communauté</li>
        </ul>
        <a href="<?= SITE_URL ?>/register.php" class="btn-pricing outline">
          Commencer gratuitement →
        </a>
      </div>
    </div>

    <!-- Parcours 2 : Business Plan (Populaire) -->
    <div class="pricing-card featured">
      <div class="pricing-thumb t2">
        📊
        <span class="pricing-badge pop">⭐ Populaire</span>
      </div>
      <div class="pricing-body">
        <h4>Business Plan simplifié</h4>
        <p class="pricing-desc">De l'idée au plan d'action concret. Tout ce qu'il faut pour convaincre un investisseur ou lancer seul.</p>
        <div class="pricing-price">
          <span class="amount">5 000</span>
          <span class="currency">FCFA</span>
        </div>
        <ul class="pricing-features">
          <li><i class="ti ti-check"></i> 8 modules vidéo</li>
          <li><i class="ti ti-check"></i> Modèle de Business Plan téléchargeable</li>
          <li><i class="ti ti-check"></i> 1 session coaching 1:1 (30 min)</li>
          <li><i class="ti ti-check"></i> Certificat de complétion</li>
          <li><i class="ti ti-check"></i> Paiement MoMo accepté</li>
        </ul>
        <a href="<?= SITE_URL ?>/register.php" class="btn-pricing gold">
          S'inscrire — 5 000 FCFA →
        </a>
      </div>
    </div>

    <!-- Parcours 3 : Lancer son activité -->
    <div class="pricing-card">
      <div class="pricing-thumb t3">
        🚀
      </div>
      <div class="pricing-body">
        <h4>Lancer son activité</h4>
        <p class="pricing-desc">Le parcours complet : coaching intensif, accompagnement terrain et certification universitaire.</p>
        <div class="pricing-price">
          <span class="amount">8 000</span>
          <span class="currency">FCFA</span>
        </div>
        <ul class="pricing-features">
          <li><i class="ti ti-check"></i> Tous les modules Business Plan inclus</li>
          <li><i class="ti ti-check"></i> 3 sessions coaching 1:1</li>
          <li><i class="ti ti-check"></i> Suivi personnalisé 30 jours</li>
          <li><i class="ti ti-check"></i> Certification Université de Parakou</li>
          <li><i class="ti ti-check"></i> Accès au réseau d'alumni</li>
        </ul>
        <a href="<?= SITE_URL ?>/register.php" class="btn-pricing">
          S'inscrire — 8 000 FCFA →
        </a>
      </div>
    </div>

  </div>
</div>

<!-- ============================================================
     COMMENT ÇA MARCHE
     ============================================================ -->
<div class="elab-section dark" id="comment">
  <div class="sec-tag light">🗺 Parcours</div>
  <div class="sec-title light">Comment ça marche ?</div>
  <div class="sec-sub light">4 étapes pour lancer ton entreprise</div>
  <div class="how-steps">
    <div class="hstep">
      <div class="step-num">1</div>
      <h4>Crée ton compte</h4>
      <p>Inscription gratuite en 2 min, aucune carte bancaire requise</p>
    </div>
    <div class="hstep">
      <div class="step-num">2</div>
      <h4>Choisis ta formation</h4>
      <p>Gratuit, Business Plan ou parcours complet avec coaching</p>
    </div>
    <div class="hstep">
      <div class="step-num">3</div>
      <h4>Apprends & pratique</h4>
      <p>Vidéos courtes + exercices concrets + coaching 1:1</p>
    </div>
    <div class="hstep">
      <div class="step-num">4</div>
      <h4>Lance ton business</h4>
      <p>Certifié par l'Université de Parakou, prêt à te lancer</p>
    </div>
  </div>
</div>

<!-- ── CTA ── -->
<div class="cta-section">
  <h2>Prêt(e) à lancer ton entreprise ?</h2>
  <p>Rejoins 1 200+ apprenants · Paiement MoMo · Certification Université de Parakou</p>
  <div class="cta-btns">
    <a href="<?= SITE_URL ?>/register.php" class="cbtn-primary" style="font-size:13px;padding:13px 28px">
      Créer mon compte gratuitement
    </a>
    <a href="#tarifs" class="cbtn-outline" style="color:var(--navy);border-color:var(--navy);font-size:13px;padding:12px 28px">
      Voir les formations
    </a>
  </div>
</div>

<!-- ── FOOTER ── -->
<footer class="elab-footer">
  <div class="foot-grid">
    <div>
      <div class="foot-logo">⭐ <?= SITE_NAME ?></div>
      <p class="foot-desc">Lancez votre entreprise avant votre diplôme.<br>Certifié Université de Parakou.</p>
    </div>
    <div class="foot-col">
      <h5>Formations</h5>
      <ul>
        <li><a href="#">Entrepreneuriat</a></li>
        <li><a href="#">Business Plan</a></li>
        <li><a href="#">Marketing</a></li>
        <li><a href="#">Finance</a></li>
      </ul>
    </div>
    <div class="foot-col">
      <h5>Support</h5>
      <ul>
        <li><a href="#">FAQ</a></li>
        <li><a href="#">Contact</a></li>
        <li><a href="#">Communauté</a></li>
        <li><a href="#">Blog</a></li>
      </ul>
    </div>
  </div>
  <div class="foot-bottom">
    <p>© <?= date('Y') ?> <?= SITE_NAME ?></p>
    <p>Fait avec ❤ pour les étudiants</p>
  </div>
</footer>

<script>
/* ============================================================
   CAROUSEL — défilement fluide avec autoplay + barre de progression
   ============================================================ */
(function () {
  const track    = document.getElementById('carouselTrack');
  const dots     = document.querySelectorAll('.cdot');
  const progress = document.getElementById('carouselProgress');
  if (!track) return;

  const total   = track.children.length;
  if (total <= 1) return;

  let current   = 0;
  let timer, progTimer, progWidth = 0;
  const DELAY   = 5000; // ms entre slides
  const TICK    = 50;   // ms de mise à jour barre

  function goTo(n) {
    current = (n + total) % total;
    track.style.transform = 'translateX(-' + (current * 100) + '%)';
    dots.forEach(function(d, i) { d.classList.toggle('active', i === current); });
    resetProgress();
  }

  function resetProgress() {
    progWidth = 0;
    if (progress) progress.style.width = '0%';
    clearInterval(progTimer);
    progTimer = setInterval(function () {
      progWidth += (TICK / DELAY) * 100;
      if (progress) progress.style.width = Math.min(progWidth, 100) + '%';
    }, TICK);
  }

  function startAutoplay() {
    timer = setInterval(function () { goTo(current + 1); }, DELAY);
  }

  function stopAutoplay() {
    clearInterval(timer);
    clearInterval(progTimer);
  }

  // Exposer pour les boutons HTML inline
  window.carouselTo = function (n) { goTo(n); stopAutoplay(); startAutoplay(); };
  window.carouselMove = function (d) { goTo(current + d); stopAutoplay(); startAutoplay(); };

  // Swipe tactile
  var startX = 0;
  track.addEventListener('touchstart', function(e) { startX = e.touches[0].clientX; }, { passive: true });
  track.addEventListener('touchend', function(e) {
    var diff = startX - e.changedTouches[0].clientX;
    if (Math.abs(diff) > 50) { goTo(current + (diff > 0 ? 1 : -1)); stopAutoplay(); startAutoplay(); }
  });

  // Pause au survol
  var wrap = document.getElementById('mainCarousel');
  if (wrap) {
    wrap.addEventListener('mouseenter', stopAutoplay);
    wrap.addEventListener('mouseleave', function () { startAutoplay(); resetProgress(); });
  }

  startAutoplay();
  resetProgress();
})();
</script>
</body>
</html>