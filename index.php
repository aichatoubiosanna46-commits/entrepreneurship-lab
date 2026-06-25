<?php
// index.php — Page d'accueil publique + espace connecté
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getPDO();

$mesModules = [];
if (estConnecte()) {
    try {
        $mesMods = $pdo->prepare('SELECT m.*, c.nom as categorie, c.icone as cat_icone, c.couleur as cat_couleur,
                    (SELECT COUNT(*) FROM sequences s
                     JOIN modules mo ON mo.id = s.module_id
                     WHERE mo.course_id = m.id AND s.actif = 1) as nb_lecons
             FROM enrollments i
             JOIN courses m ON m.id = i.course_id
             LEFT JOIN categories c ON c.id = m.category_id
             WHERE i.user_id = ? AND i.statut = "actif"
             ORDER BY i.created_at DESC');
        $mesMods->execute([$_SESSION['user_id']]);
        $mesModules = $mesMods->fetchAll();
    } catch (PDOException $e) { $mesModules = []; }
}

try {
    $slides = $pdo->query('SELECT * FROM slides WHERE actif = 1 ORDER BY ordre ASC LIMIT 6')->fetchAll();
} catch (PDOException $e) { $slides = []; }

try {
    $nbMembres = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE actif = 1')->fetchColumn();
    $nbCours   = (int)$pdo->query('SELECT COUNT(*) FROM courses WHERE actif = 1 AND statut = "publie"')->fetchColumn();
    $nbCerts   = (int)$pdo->query('SELECT COUNT(*) FROM certificates')->fetchColumn();
} catch (PDOException $e) {
    $nbMembres = 1200; $nbCours = 24; $nbCerts = 340;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Entrepreneurship-lab-Ariziki</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/home.css">
<style>
:root {
  --navy:        #085041;
  --navy-mid:    #0e7a5c;
  --gold:        #D85A30;
  --gold-dark:   #C04A22;
  --gold-pale:   rgba(216,90,48,.12);
  --gold-border: rgba(216,90,48,.35);
  --text-muted:  #6b7069;
  --bg-light:    #F5F0E8;
  --radius:      10px;
  --radius-lg:   14px;
}

/* NAVBAR */
.elab-nav {
  background: var(--navy); padding: 12px 32px;
  display: flex; align-items: center; gap: 32px;
  position: sticky; top: 0; z-index: 200;
  box-shadow: 0 2px 16px rgba(0,0,0,.3);
}
.elab-nav-logo {
  color: var(--gold); font-size: 14px; font-weight: 700;
  display: flex; align-items: center; gap: 8px; text-decoration: none;
}
.elab-nav-links { display: flex; gap: 24px; }
.elab-nav-links a { font-size: 12px; color: rgba(255,255,255,.65); text-decoration: none; transition: color .2s; }
.elab-nav-links a:hover { color: var(--gold); }
.elab-nav-btn {
  background: var(--gold); color: var(--navy);
  border: none; border-radius: 7px;
  padding: 8px 18px; font-size: 12px; font-weight: 700;
  cursor: pointer; text-decoration: none; transition: background .2s;
}
.elab-nav-btn:hover { background: var(--gold-dark); }
.elab-nav-burger { display: none; background: none; border: none; color: #fff; font-size: 22px; cursor: pointer; padding: 4px; }
.elab-nav-mobile { display: none; flex-direction: column; background: var(--navy); border-top: 1px solid rgba(255,255,255,.1); position: sticky; top: 0; z-index: 199; }
.elab-nav-mobile.open { display: flex; }
.elab-nav-mobile a { color: rgba(255,255,255,.8); font-size: 13px; text-decoration: none; padding: 14px 24px; border-bottom: 1px solid rgba(255,255,255,.06); }
.elab-nav-mobile a:hover { color: var(--gold); background: rgba(255,255,255,.04); }

/* HERO (image unique) */
.elab-hero { position: relative; overflow: hidden; background: linear-gradient(135deg, var(--navy), var(--navy-mid)); min-height: 480px; width: 100%; display: flex; align-items: center; justify-content: center; padding: 64px 24px; }
.elab-hero-img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; object-position: center; display: block; }
.elab-hero-overlay { position: absolute; inset: 0; background: linear-gradient(160deg, rgba(8,80,65,.88) 20%, rgba(8,80,65,.55) 100%); }
.elab-hero-content { position: relative; z-index: 1; max-width: 760px; margin: 0 auto; text-align: center; }
.cbtn-primary {
  background: var(--gold); color: #fff; border: none; border-radius: 8px;
  padding: 12px 24px; font-size: 12px; font-weight: 700;
  cursor: pointer; text-decoration: none; transition: background .2s, transform .15s; display: inline-block;
}
.cbtn-primary:hover { background: var(--gold-dark); transform: translateY(-1px); }
.cbtn-outline {
  background: transparent; color: #fff; border: 1.5px solid rgba(255,255,255,.4);
  border-radius: 8px; padding: 11px 24px; font-size: 12px; font-weight: 600;
  cursor: pointer; text-decoration: none; transition: border-color .2s, color .2s; display: inline-block;
}
.cbtn-outline:hover { border-color: var(--gold); color: var(--gold); }

/* TRUST / STATS */
.trust-bar { background: var(--navy); padding: 12px 32px; display: flex; justify-content: center; flex-wrap: wrap; gap: 24px; border-top: 1px solid rgba(255,255,255,.08); }
.trust-item { display: flex; align-items: center; gap: 6px; font-size: 11px; color: rgba(255,255,255,.6); }
.trust-item i { font-size: 14px; color: var(--gold); }
.stats-bar { background: var(--gold); padding: 18px 32px; display: flex; justify-content: space-around; flex-wrap: wrap; gap: 10px; }
.stat-item .big { font-size: 20px; font-weight: 700; color: var(--navy); text-align: center; }
.stat-item .sm  { font-size: 9px; color: rgba(15,29,53,.6); text-align: center; margin-top: 2px; }

/* SECTIONS */
.elab-section { padding: 48px 32px; }
.elab-section.alt { background: var(--bg-light); }
.elab-section.dark { background: var(--navy); }
.sec-tag { display: inline-flex; align-items: center; gap: 5px; background: var(--gold-pale); border: 1px solid var(--gold-border); border-radius: 100px; padding: 4px 12px; font-size: 9px; color: #8A6700; margin-bottom: 8px; }
.sec-tag.light { background: rgba(245,197,24,.12); color: var(--gold); border-color: rgba(245,197,24,.25); }
.sec-title { font-size: 17px; font-weight: 700; color: var(--navy); margin-bottom: 4px; }
.sec-title.light { color: #fff; }
.sec-sub { font-size: 11px; color: var(--text-muted); margin-bottom: 20px; }
.sec-sub.light { color: rgba(255,255,255,.5); }

/* WHY */
.why-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 14px; margin-top: 18px; }
.why-card { background: var(--bg-light); border: 1px solid rgba(0,0,0,.07); border-radius: var(--radius-lg); padding: 18px; }
.why-ico { width: 38px; height: 38px; background: rgba(245,197,24,.15); border-radius: 9px; display: grid; place-items: center; margin-bottom: 10px; }
.why-ico i { font-size: 20px; color: var(--gold-dark); }
.why-card h4 { font-size: 11px; font-weight: 600; color: var(--navy); margin-bottom: 5px; }
.why-card p  { font-size: 9.5px; color: var(--text-muted); line-height: 1.65; }

/* TARIFS */
.pricing-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; margin-top: 20px; }
.pricing-card { background: #fff; border-radius: var(--radius-lg); border: 1.5px solid rgba(0,0,0,.08); overflow: hidden; transition: transform .2s, box-shadow .2s; position: relative; }
.pricing-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(15,29,53,.12); }
.pricing-card.featured { border-color: var(--gold); box-shadow: 0 6px 24px rgba(245,197,24,.25); }
.pricing-thumb { height: 70px; display: flex; align-items: center; justify-content: center; font-size: 26px; position: relative; }
.pricing-thumb.t1 { background: linear-gradient(135deg, var(--navy), var(--navy-mid)); }
.pricing-thumb.t2 { background: linear-gradient(135deg, #1a2340, var(--gold-dark)); }
.pricing-thumb.t3 { background: linear-gradient(135deg, #0d3b2e, #1a6b52); }
.pricing-thumb.t4 { background: linear-gradient(135deg, #1a0533, #6C47D4); }
.pricing-badge { position: absolute; top: 8px; left: 8px; background: var(--gold); color: var(--navy); font-size: 8px; font-weight: 700; padding: 3px 8px; border-radius: 100px; }
.pricing-badge.pop { background: #fff; color: var(--navy); }
.pricing-body { padding: 16px; }
.pricing-body h4 { font-size: 12px; font-weight: 700; color: var(--navy); margin-bottom: 4px; line-height: 1.3; }
.pricing-body .pricing-desc { font-size: 9.5px; color: var(--text-muted); line-height: 1.6; margin-bottom: 12px; }
.pricing-price { display: flex; align-items: baseline; gap: 4px; margin-bottom: 14px; }
.pricing-price .amount { font-size: 22px; font-weight: 700; color: var(--navy); }
.pricing-price .amount.free { color: #16a34a; font-size: 16px; }
.pricing-price .currency { font-size: 11px; color: var(--text-muted); }
.pricing-features { list-style: none; padding: 0; margin: 0 0 16px; }
.pricing-features li { display: flex; align-items: center; gap: 7px; font-size: 9.5px; color: var(--text-muted); padding: 4px 0; border-bottom: 1px solid rgba(0,0,0,.05); }
.pricing-features li:last-child { border-bottom: none; }
.pricing-features li i { font-size: 12px; color: var(--gold-dark); flex-shrink: 0; }
.btn-pricing { display: block; text-align: center; text-decoration: none; background: var(--navy); color: #fff; border-radius: 8px; padding: 10px; font-size: 10px; font-weight: 700; transition: background .2s; }
.btn-pricing:hover { background: var(--navy-mid); }
.btn-pricing.gold { background: var(--gold); color: var(--navy); }
.btn-pricing.gold:hover { background: var(--gold-dark); }
.btn-pricing.outline { background: transparent; color: var(--navy); border: 1.5px solid var(--navy); }
.btn-pricing.outline:hover { background: var(--navy); color: #fff; }

/* HOW */
.how-steps { display: grid; grid-template-columns: repeat(4,1fr); gap: 14px; margin-top: 20px; }
.hstep { text-align: center; }
.step-num { width: 42px; height: 42px; border-radius: 50%; background: var(--gold); color: var(--navy); font-size: 16px; font-weight: 700; display: grid; place-items: center; margin: 0 auto 10px; }
.hstep h4 { font-size: 11px; font-weight: 600; color: #fff; margin-bottom: 4px; }
.hstep p  { font-size: 9px; color: rgba(255,255,255,.5); line-height: 1.6; }

/* CTA */
.cta-section { position:relative; background: #1a1a18; padding: 56px 32px; text-align: center; overflow:hidden; }
.cta-section::before { content:''; position:absolute; inset:0; background: radial-gradient(circle at 30% 30%, rgba(10,92,70,0.45), transparent 60%), radial-gradient(circle at 70% 70%, rgba(216,90,48,0.30), transparent 60%); pointer-events:none; }
.cta-section > * { position:relative; z-index:1; }
.cta-section .cta-eyebrow { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: #7fffc4; margin-bottom: 10px; }
.cta-section h2 { font-size: 22px; font-weight: 800; color: #fff; margin-bottom: 10px; font-family: 'Syne', sans-serif; }
.cta-section p  { font-size: 13px; color: rgba(255,255,255,.7); margin-bottom: 22px; line-height: 1.7; max-width:480px; margin-left:auto; margin-right:auto; }
.cta-btns { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }

/* VUE CONNECTEE */
.hero-connected { background: linear-gradient(120deg, #EEF3FB 0%, #FFF9D6 100%); padding: 20px 32px; border-bottom: 1px solid rgba(0,0,0,.07); }
.hero-connected-inner { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
.hero-greeting h1 { font-size: 18px; font-weight: 700; color: var(--navy); }
.name-highlight { color: var(--gold-dark); }
.hero-greeting p { font-size: 11px; color: var(--text-muted); margin-top: 3px; }
.btn-primary { background: var(--navy); color: #fff; border: none; border-radius: 8px; padding: 10px 18px; font-size: 12px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 7px; cursor: pointer; transition: background .2s; }
.btn-primary:hover { background: var(--navy-mid); }
.modules-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 14px; margin-top: 18px; }
.module-card { background: #fff; border-radius: var(--radius-lg); overflow: hidden; border: 1px solid rgba(0,0,0,.07); text-decoration: none; display: block; transition: transform .2s, box-shadow .2s; }
.module-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(15,29,53,.1); }
.module-thumb { height: 70px; position: relative; background: var(--navy); display: flex; align-items: center; justify-content: center; }
.module-thumb img { width: 100%; height: 100%; object-fit: cover; }
.module-thumb-placeholder { width: 100%; height: 100%; display: grid; place-items: center; }
.module-progress-bar { position: absolute; bottom: 0; left: 0; right: 0; height: 3px; background: rgba(255,255,255,.2); }
.module-progress-bar div { height: 100%; background: var(--gold); }
.module-body { padding: 10px 12px 14px; }
.module-cat { font-size: 8px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
.module-body h3 { font-size: 11px; font-weight: 600; color: var(--navy); margin: 4px 0 6px; line-height: 1.35; }
.module-meta { display: flex; align-items: center; justify-content: space-between; font-size: 9px; color: var(--text-muted); gap: 6px; }
.module-meta span { display: flex; align-items: center; gap: 3px; }
.module-pct { font-size: 9px; font-weight: 700; color: var(--gold-dark); }

/* PARTENAIRES MARQUEE */
.partners-marquee { overflow: hidden; width: 100%; mask-image: linear-gradient(90deg, transparent, #000 8%, #000 92%, transparent); -webkit-mask-image: linear-gradient(90deg, transparent, #000 8%, #000 92%, transparent); }
.partners-track { display: flex; align-items: center; gap: 22px; width: max-content; animation: scroll-partners 28s linear infinite; }
.partners-marquee:hover .partners-track { animation-play-state: paused; }
.partner-pill { display: flex; align-items: center; justify-content: center; background: #fff; border: 1px solid rgba(0,0,0,.08); border-radius: 14px; padding: 22px 34px; font-size: 14px; color: var(--navy); font-weight: 700; min-width: 220px; min-height: 70px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,.04); white-space: nowrap; }
@keyframes scroll-partners { from { transform: translateX(0); } to { transform: translateX(-50%); } }

/* FOOTER */
.elab-footer { background: #1A1A18; padding: 32px 32px 18px; }
.foot-bottom { border-top: 1px solid rgba(255,255,255,.1); padding-top: 14px; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 6px; }
.foot-bottom p { font-size: 9px; color: rgba(255,255,255,.35); }

/* RESPONSIVE */
@media (max-width: 900px) {
  .pricing-grid, .tarifs-grid { grid-template-columns: 1fr 1fr; }
  .why-grid, .modules-grid { grid-template-columns: 1fr 1fr; }
  .how-steps { grid-template-columns: 1fr 1fr; }
  .equipe-grid { grid-template-columns: 1fr 1fr !important; max-width: 700px !important; }
  .parcours-grid, .methode-grid { grid-template-columns: 1fr 1fr; }
  .constat-grid, .temoignages-grid { grid-template-columns: 1fr; }
  .footer-grid { grid-template-columns: 1fr 1fr !important; }
}
@media (max-width: 768px) {
  .elab-hero { min-height: 400px; padding: 48px 20px; }
  .elab-nav-links { display: none; }
  .elab-nav-burger { display: block; }
  .equipe-grid { grid-template-columns: 1fr !important; max-width: 420px !important; margin-left: auto !important; margin-right: auto !important; }
  .elab-section { padding: 40px 20px; }
}
@media (max-width: 500px) {
  .pricing-grid, .why-grid, .modules-grid, .how-steps, .tarifs-grid { grid-template-columns: 1fr; }
  .parcours-grid, .methode-grid { grid-template-columns: 1fr; }
  .equipe-grid { max-width: 100% !important; padding: 0 16px; }
  .footer-grid { grid-template-columns: 1fr !important; }
  .methode-band { flex-direction: column; text-align: center; gap: 18px; padding: 24px 20px; }
  .methode-band > div[style*="width:1px"] { display: none; }
  .trust-bar { gap: 12px; padding: 10px 16px; }
  .stats-bar { padding: 14px 16px; gap: 16px; }
  .partner-pill { padding: 16px 22px; font-size: 12px; min-width: 160px; min-height: 56px; }
}
</style>
</head>
<body>

<nav class="elab-nav">
  <a href="<?= SITE_URL ?>" class="elab-nav-logo" style="margin-right:auto">
    <img src="<?= SITE_URL ?>/assets/images/logo.png"
         alt="<?= SITE_NAME ?>"
         style="height:48px;width:auto"
         onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
    <div style="display:none;align-items:center;gap:8px">
      <div style="width:32px;height:32px;background:#D85A30;border-radius:8px;display:flex;align-items:center;justify-content:center">
        <i class="ti ti-star" style="color:#1A1A18;font-size:16px"></i>
      </div>
      <span style="color:#D85A30;font-size:14px;font-weight:700"><?= SITE_NAME ?></span>
    </div>
  </a>
  <div class="elab-nav-links">
    <a href="#parcours">Le Parcours</a>
    <a href="#methode">La Méthode</a>
    <a href="#temoignages">Témoignages</a>
    <a href="#tarifs">Tarifs</a>
    <a href="#equipe">À propos</a>
  </div>
  <?php if (estConnecte()): ?>
    <a href="<?= SITE_URL ?>/dashboard.php" class="elab-nav-btn">
      <i class="ti ti-layout-dashboard"></i> Mon espace
    </a>
  <?php else: ?>
    <a href="<?= SITE_URL ?>/register.php" class="elab-nav-btn">Commencer gratuitement →</a>
  <?php endif; ?>
  <button class="elab-nav-burger" onclick="document.querySelector('.elab-nav-mobile').classList.toggle('open')" aria-label="Menu">
    <i class="ti ti-menu-2"></i>
  </button>
</nav>
<div class="elab-nav-mobile">
  <a href="#parcours">Le Parcours</a>
  <a href="#methode">La Méthode</a>
  <a href="#temoignages">Témoignages</a>
  <a href="#tarifs">Tarifs</a>
  <a href="#equipe">À propos</a>
</div>

<?= flash() ?>

<?php $heroSlide = $slides[0] ?? null; ?>
<!-- HERO (image unique) -->
<div class="elab-hero">
  <?php if ($heroSlide && $heroSlide['image']): ?>
    <img src="<?= SITE_URL ?>/assets/uploads/<?= h($heroSlide['image']) ?>" alt="<?= h($heroSlide['titre'] ?? SITE_NAME) ?>"
         loading="eager" class="elab-hero-img" onerror="this.src='https://images.unsplash.com/photo-1600880292203-757bb62b4baf?w=1600&q=85&auto=format&fit=crop'">
  <?php else: ?>
    <img src="https://images.unsplash.com/photo-1600880292203-757bb62b4baf?w=1600&q=85&auto=format&fit=crop"
         alt="Étudiant entrepreneur béninois" loading="eager" class="elab-hero-img">
  <?php endif; ?>
  <div class="elab-hero-overlay"></div>
  <div class="elab-hero-content">
    <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.2);border-radius:100px;padding:6px 16px;color:rgba(255,255,255,0.9);font-size:13px;">
      <span style="width:7px;height:7px;background:#4dff9e;border-radius:50%;display:inline-block;"></span> Ouvert aux inscriptions — Rentrée 2024
    </div>
    <h1 style="font-family:'Syne',sans-serif;font-size:clamp(26px,5vw,46px);font-weight:800;color:#fff;line-height:1.15;margin:22px 0 18px">
      <?= $heroSlide ? h($heroSlide['titre']) : 'Entreprends <span style="color:#7fffc4">pendant</span> tes études.<br>Pas après.' ?>
    </h1>
    <p style="font-size:15px;color:rgba(255,255,255,.75);line-height:1.75;max-width:560px;margin:0 auto 28px">
      <?= $heroSlide && $heroSlide['sous_titre'] ? h($heroSlide['sous_titre']) : 'Le premier parcours en ligne conçu pour les étudiants béninois qui veulent créer leur activité avant même d\'avoir leur diplôme.' ?>
    </p>
    <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;margin-bottom:44px">
      <?php if ($heroSlide && $heroSlide['lien']): ?>
        <a href="<?= h($heroSlide['lien']) ?>" class="cbtn-primary" style="font-size:13px;padding:13px 28px"><?= h($heroSlide['texte_btn'] ?: 'Commencer') ?> →</a>
      <?php else: ?>
        <a href="<?= SITE_URL ?>/register.php" class="cbtn-primary" style="font-size:13px;padding:13px 28px">Démarrer gratuitement</a>
      <?php endif; ?>
      <a href="#methode" class="cbtn-outline" style="font-size:13px;padding:12px 28px">▶ Voir la vidéo (2 min)</a>
    </div>
    <div style="display:flex;justify-content:center;gap:40px;flex-wrap:wrap">
      <div style="text-align:center">
        <div style="font-family:'Syne',sans-serif;font-size:26px;font-weight:800;color:#7fffc4">30+</div>
        <div style="font-size:11px;color:rgba(255,255,255,.6);margin-top:4px">Modules pratiques</div>
      </div>
      <div style="text-align:center">
        <div style="font-family:'Syne',sans-serif;font-size:26px;font-weight:800;color:#7fffc4">1 000</div>
        <div style="font-size:11px;color:rgba(255,255,255,.6);margin-top:4px">objectif Étudiants visés</div>
      </div>
      <div style="text-align:center">
        <div style="font-family:'Syne',sans-serif;font-size:26px;font-weight:800;color:#7fffc4">3×</div>
        <div style="font-size:11px;color:rgba(255,255,255,.6);margin-top:4px">Universités partenaires</div>
      </div>
    </div>
  </div>
</div>

<!-- TRUST BAR -->
<div class="trust-bar">
  <div class="trust-item"><i class="ti ti-certificate"></i> Certification Université de Parakou</div>
  <div class="trust-item"><i class="ti ti-device-mobile"></i> Paiement MoMo</div>
  <div class="trust-item"><i class="ti ti-headset"></i> Coaching 1:1</div>
  <div class="trust-item"><i class="ti ti-infinity"></i> Accès à vie</div>
</div>

<!-- STATS BAR -->
<div class="stats-bar">
  <div class="stat-item"><div class="big"><?= number_format($nbMembres) ?>+</div><div class="sm">Membres</div></div>
  <div class="stat-item"><div class="big"><?= $nbCours ?>+</div><div class="sm">Formations</div></div>
  <div class="stat-item"><div class="big"><?= $nbCerts ?>+</div><div class="sm">Certificats</div></div>
  <div class="stat-item"><div class="big">MoMo</div><div class="sm">Paiement sécurisé</div></div>
</div>

<!-- VUE CONNECTEE -->
<?php if (estConnecte()): ?>
<?php $user = utilisateurCourant(); ?>
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
  <div class="sec-tag">Ma progression</div>
  <div class="sec-title">Modules en cours</div>
  <div class="modules-grid">
    <?php foreach ($mesModules as $m): ?>
    <?php $pct = progressionCours($_SESSION['user_id'], $m['id']); ?>
    <a href="<?= SITE_URL ?>/module.php?slug=<?= h($m['slug']) ?>" class="module-card">
      <div class="module-thumb">
        <?php if ($m['miniature']): ?>
          <img src="<?= SITE_URL ?>/assets/uploads/<?= h($m['miniature']) ?>" alt="<?= h($m['titre']) ?>">
        <?php else: ?>
          <div class="module-thumb-placeholder" style="background:<?= h($m['cat_couleur'] ?? '#6C47D4') ?>22">
            <i class="ti <?= h($m['cat_icone'] ?? 'ti-book') ?>" style="color:<?= h($m['cat_couleur'] ?? '#6C47D4') ?>;font-size:28px"></i>
          </div>
        <?php endif; ?>
        <div class="module-progress-bar"><div style="width:<?= $pct ?>%"></div></div>
      </div>
      <div class="module-body">
        <span class="module-cat" style="color:<?= h($m['cat_couleur'] ?? '#6C47D4') ?>"><?= h($m['categorie']) ?></span>
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
<?php endif; ?>

<!-- LE CONSTAT -->
<div class="elab-section" id="constat" style="background:var(--bg-light)">
  <div class="sec-tag">Le constat</div>
  <div class="sec-title">Le diplôme ne suffit plus.</div>
  <div class="sec-sub">Au Bénin, décrocher un emploi après les études prend en moyenne 3 à 7 ans. Ce n'est pas une fatalité.</div>
  <div class="constat-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-top:24px;align-items:stretch">
    <div style="background:#fff;border-radius:var(--radius-lg);padding:28px;border:1px solid rgba(0,0,0,.07)">
      <div style="font-family:'Syne',sans-serif;font-size:48px;font-weight:800;color:var(--gold-dark);line-height:1">30%</div>
      <p style="font-size:12px;color:var(--text-muted);margin:6px 0 20px">des 15–35 ans sont au chômage au Bénin (INSAE 2024)</p>
      <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:12px">
        <li style="display:flex;gap:8px;font-size:12px;color:var(--text-muted);line-height:1.6"><span style="color:var(--gold-dark);flex-shrink:0">●</span> 3 à 7 ans d'attente après le diplôme pour trouver un premier emploi</li>
        <li style="display:flex;gap:8px;font-size:12px;color:var(--text-muted);line-height:1.6"><span style="color:var(--gold-dark);flex-shrink:0">●</span> Les universités forment des salariés, pas des entrepreneurs</li>
        <li style="display:flex;gap:8px;font-size:12px;color:var(--text-muted);line-height:1.6"><span style="color:var(--gold-dark);flex-shrink:0">●</span> Les outils et ressources ne sont pas adaptés au contexte béninois</li>
        <li style="display:flex;gap:8px;font-size:12px;color:var(--text-muted);line-height:1.6"><span style="color:var(--gold-dark);flex-shrink:0">●</span> Pas de soutien structuré pendant les années d'études</li>
      </ul>
    </div>
    <div style="background:#fff;border-radius:var(--radius-lg);padding:28px;border:1px solid rgba(0,0,0,.07);display:flex;flex-direction:column">
      <h3 style="font-family:'Syne',sans-serif;font-size:17px;font-weight:700;color:var(--navy);margin-bottom:12px">Et si on inversait l'équation ?</h3>
      <p style="font-size:12px;color:var(--text-muted);line-height:1.7;margin-bottom:16px">
        Kossi, 22 ans, L2 à Parakou, a lancé un service de livraison de repas. 3 partenariats avec des restauratrices via WhatsApp. En 6 mois, ses frais de scolarité étaient couverts.
      </p>
      <div style="background:var(--navy);border-radius:12px;padding:18px;margin-top:auto">
        <p style="font-size:12.5px;color:rgba(255,255,255,.9);line-height:1.75;font-style:italic;margin-bottom:8px">
          « Le plus dur n'était pas de trouver l'idée. C'était d'oser appeler la première restauratrice pour lui proposer un partenariat. »
        </p>
        <p style="font-size:11px;color:rgba(255,255,255,.55)">— Kossi, 24 ans, entrepreneur étudiant, Parakou</p>
      </div>
    </div>
  </div>
</div>

<!-- LE PARCOURS -->
<div class="elab-section" id="parcours">
  <div class="sec-tag">Le parcours</div>
  <div class="sec-title">30 modules. Une seule direction.</div>
  <div class="sec-sub">Un programme progressif en 3 séries, conçu pour t'amener de l'idée à la première vente — pendant tes études.</div>
  <div class="parcours-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-top:24px">
    <div style="background:var(--navy);color:#fff;border-radius:var(--radius-lg);padding:22px">
      <span style="display:inline-block;background:rgba(125,255,196,.18);color:#7fffc4;font-size:9px;font-weight:700;padding:3px 10px;border-radius:100px;margin-bottom:12px">GRATUIT</span>
      <h4 style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700;margin-bottom:8px">Phase 0 — Le Déclic</h4>
      <p style="font-size:11.5px;color:rgba(255,255,255,.7);line-height:1.6;margin-bottom:14px">Découvre ton potentiel entrepreneurial et identifie tes premières pistes d'opportunités, sans aucun risque.</p>
      <p style="font-size:10.5px;color:rgba(255,255,255,.5);margin-bottom:10px">3 modules d'onboarding</p>
      <p style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700;color:#7fffc4">0 FCFA</p>
    </div>
    <div style="background:#fff;border:1px solid rgba(0,0,0,.08);border-radius:var(--radius-lg);padding:22px">
      <span style="display:inline-block;background:rgba(14,122,92,.12);color:var(--navy-mid);font-size:9px;font-weight:700;padding:3px 10px;border-radius:100px;margin-bottom:12px">ESSENTIEL</span>
      <h4 style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700;color:var(--navy);margin-bottom:8px">Série 1 — Valider ton idée</h4>
      <p style="font-size:11.5px;color:var(--text-muted);line-height:1.6;margin-bottom:14px">Teste ton idée sur le terrain avant d'investir temps et argent, avec des méthodes simples et adaptées au Bénin.</p>
      <p style="font-size:10.5px;color:var(--text-muted);margin-bottom:10px">9 modules de validation</p>
      <p style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700;color:var(--navy)">1 000 – 5 000 FCFA</p>
    </div>
    <div style="background:var(--bg-light);border:1px solid rgba(0,0,0,.06);border-radius:var(--radius-lg);padding:22px">
      <span style="display:inline-block;background:rgba(216,90,48,.14);color:var(--gold-dark);font-size:9px;font-weight:700;padding:3px 10px;border-radius:100px;margin-bottom:12px">AVANCÉ</span>
      <h4 style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700;color:var(--navy);margin-bottom:8px">Série 2 — Lancer et vendre</h4>
      <p style="font-size:11.5px;color:var(--text-muted);line-height:1.6;margin-bottom:14px">Passe à l'action : premiers clients, premières ventes, premiers retours du marché, avec un accompagnement concret.</p>
      <p style="font-size:10.5px;color:var(--text-muted);margin-bottom:10px">9 modules de lancement</p>
      <p style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700;color:var(--navy)">5 000 – 10 000 FCFA</p>
    </div>
    <div style="background:#1a1a18;color:#fff;border-radius:var(--radius-lg);padding:22px;grid-column:1 / -1">
      <span style="display:inline-block;background:rgba(255,255,255,.14);color:#fff;font-size:9px;font-weight:700;padding:3px 10px;border-radius:100px;margin-bottom:12px">EXPERT</span>
      <h4 style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700;margin-bottom:8px">Série 3 — Structurer et scaler</h4>
      <p style="font-size:11.5px;color:rgba(255,255,255,.7);line-height:1.6;margin-bottom:14px">Structure ton activité pour durer : gestion, équipe, croissance — et prépare le passage à l'échelle de ton entreprise.</p>
      <div style="display:flex;flex-wrap:wrap;gap:14px;align-items:center;justify-content:space-between">
        <div>
          <p style="font-size:10.5px;color:rgba(255,255,255,.5);margin-bottom:6px">9 modules de croissance</p>
          <p style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700;color:#fff">10 000 – 15 000 FCFA</p>
        </div>
        <p style="font-size:11px;color:rgba(255,255,255,.65)">🎓 Certification UP &nbsp;|&nbsp; 👤 Coaching 1:1 &nbsp;|&nbsp; 🏆 Badge numérique &nbsp;|&nbsp; 🤝 Réseau Alumni</p>
      </div>
    </div>
  </div>
</div>

<!-- LA METHODE -->
<div class="elab-section" id="methode" style="background:#f0f9f5">
  <div class="sec-tag">La méthode</div>
  <div class="sec-title">80% pratique. 20% théorie.</div>
  <div class="sec-sub">On ne te donne pas des cours magistraux. On te met en situation réelle, avec des outils béninois, pour des problèmes béninois.</div>
  <div class="methode-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-top:24px">
    <div style="background:#fff;border-radius:var(--radius-lg);padding:20px;border:1px solid rgba(0,0,0,.06)">
      <div style="font-size:24px;margin-bottom:10px">🎬</div>
      <h4 style="font-size:12px;font-weight:700;color:var(--navy);margin-bottom:6px">Vidéos interactives</h4>
      <p style="font-size:11px;color:var(--text-muted);line-height:1.6">Des capsules courtes et concrètes, conçues pour être vues entre deux cours, avec des quiz pour ancrer chaque notion.</p>
    </div>
    <div style="background:#fff;border-radius:var(--radius-lg);padding:20px;border:1px solid rgba(0,0,0,.06)">
      <div style="font-size:24px;margin-bottom:10px">🛠️</div>
      <h4 style="font-size:12px;font-weight:700;color:var(--navy);margin-bottom:6px">Outils prêts à l'emploi</h4>
      <p style="font-size:11px;color:var(--text-muted);line-height:1.6">Modèles, fiches et calculateurs téléchargeables que tu utilises immédiatement sur ton propre projet.</p>
    </div>
    <div style="background:#fff;border-radius:var(--radius-lg);padding:20px;border:1px solid rgba(0,0,0,.06)">
      <div style="font-size:24px;margin-bottom:10px">🌍</div>
      <h4 style="font-size:12px;font-weight:700;color:var(--navy);margin-bottom:6px">Cas 100% béninois</h4>
      <p style="font-size:11px;color:var(--text-muted);line-height:1.6">Des études de cas tirées d'entrepreneurs étudiants locaux, pas de théories importées hors contexte.</p>
    </div>
    <div style="background:#fff;border-radius:var(--radius-lg);padding:20px;border:1px solid rgba(0,0,0,.06)">
      <div style="font-size:24px;margin-bottom:10px">🏅</div>
      <h4 style="font-size:12px;font-weight:700;color:var(--navy);margin-bottom:6px">Badges & certification</h4>
      <p style="font-size:11px;color:var(--text-muted);line-height:1.6">Chaque étape franchie débloque un badge vérifiable, jusqu'à la certification finale co-signée par l'université.</p>
    </div>
  </div>
  <div class="methode-band" style="background:var(--navy);border-radius:20px;padding:28px 32px;margin-top:24px;display:flex;align-items:center;gap:32px;flex-wrap:wrap">
    <div style="text-align:center">
      <div style="font-size:11px;color:rgba(255,255,255,.6);margin-bottom:4px">Pratique</div>
      <div style="font-family:'Syne',sans-serif;font-size:64px;font-weight:800;color:#fff;line-height:1">80<span style="color:#7fffc4">%</span></div>
    </div>
    <div style="width:1px;height:60px;background:rgba(255,255,255,.15)"></div>
    <div style="text-align:center">
      <div style="font-size:11px;color:rgba(255,255,255,.6);margin-bottom:4px">Théorie</div>
      <div style="font-family:'Syne',sans-serif;font-size:64px;font-weight:800;color:#fff;line-height:1">20<span style="color:#7fffc4">%</span></div>
    </div>
    <div style="width:1px;height:60px;background:rgba(255,255,255,.15)"></div>
    <p style="font-size:12.5px;color:rgba(255,255,255,.75);line-height:1.7;flex:1;min-width:220px">
      Chaque module se termine par un livrable concret que tu peux montrer à un client, un partenaire ou un jury.
    </p>
  </div>
</div>

<!-- TEMOIGNAGES -->
<div class="elab-section" id="temoignages" style="background:#fff">
  <div class="sec-tag">Ils ont osé</div>
  <div class="sec-title">Des étudiants qui ont franchi le pas.</div>
  <div class="sec-sub">Pas des exceptions. Des exemples reproductibles, avec les bons outils.</div>
  <div class="temoignages-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:24px">
    <div style="background:var(--bg-light);border-radius:var(--radius-lg);padding:26px;border:1px solid rgba(0,0,0,.06)">
      <p style="font-size:13px;color:var(--dark,#1A1A18);line-height:1.75;font-style:italic;margin-bottom:18px">
        « J'ai lancé mon service de livraison de repas en L2. En 6 mois, mes frais de scolarité étaient couverts. Aujourd'hui diplômé, je gère une équipe de 6 personnes. Ce qui a tout changé ? Avoir un cadre pour passer de l'idée à l'action. »
      </p>
      <div style="display:flex;align-items:center;gap:12px">
        <div style="width:44px;height:44px;border-radius:50%;background:rgba(8,80,65,.14);color:var(--navy);font-weight:700;font-size:16px;display:flex;align-items:center;justify-content:center;flex-shrink:0">K</div>
        <div>
          <div style="font-size:12.5px;font-weight:700;color:var(--navy)">Kossi</div>
          <div style="font-size:10.5px;color:var(--text-muted)">24 ans — Livraison de repas, Parakou</div>
        </div>
      </div>
    </div>
    <div style="background:var(--bg-light);border-radius:var(--radius-lg);padding:26px;border:1px solid rgba(0,0,0,.06)">
      <p style="font-size:13px;color:var(--dark,#1A1A18);line-height:1.75;font-style:italic;margin-bottom:18px">
        « Mes compétences en informatique valaient de l'argent, mais je ne le savais pas. En 8 mois, 22 clients actifs. J'ai soutenu ma licence avec mon entreprise comme cas pratique de mon mémoire. »
      </p>
      <div style="display:flex;align-items:center;gap:12px">
        <div style="width:44px;height:44px;border-radius:50%;background:rgba(216,90,48,.14);color:var(--gold-dark);font-weight:700;font-size:16px;display:flex;align-items:center;justify-content:center;flex-shrink:0">F</div>
        <div>
          <div style="font-size:12.5px;font-weight:700;color:var(--navy)">Fatima</div>
          <div style="font-size:10.5px;color:var(--text-muted)">26 ans — Développeuse web pour PME, Cotonou</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- PARTENAIRES -->
<div class="elab-section partners-section" style="background:var(--bg-light);padding:36px 0;text-align:center">
  <p style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:20px">Soutenu et reconnu par</p>
  <div class="partners-marquee">
    <div class="partners-track">
      <span class="partner-pill">Université de Parakou</span>
      <span class="partner-pill">CPID ONG</span>
      <span class="partner-pill">ANPE Bénin</span>
      <span class="partner-pill">RJEB (Réseau des Jeunes Entrepreneurs Béninois)</span>
      <span class="partner-pill">CePEPE Bénin</span>
      <span class="partner-pill">Université de Parakou</span>
      <span class="partner-pill">CPID ONG</span>
      <span class="partner-pill">ANPE Bénin</span>
      <span class="partner-pill">RJEB (Réseau des Jeunes Entrepreneurs Béninois)</span>
      <span class="partner-pill">CePEPE Bénin</span>
    </div>
  </div>
</div>

<!-- POURQUOI NOUS -->
<div class="elab-section">
  <div class="sec-tag">Pourquoi nous ?</div>
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

<!-- TARIFS -->
<div class="elab-section alt" id="tarifs">
  <div class="sec-tag">Tarification</div>
  <div class="sec-title">Conçu pour ta bourse d'étudiant.</div>
  <div class="sec-sub">Paiement en Mobile Money. Pas besoin de carte bancaire. Commence gratuitement.</div>
  <div class="tarifs-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-top:24px;align-items:stretch">
    <div style="background:#fff;border:1.5px solid rgba(0,0,0,.08);border-radius:var(--radius-lg);padding:26px;display:flex;flex-direction:column">
      <h4 style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700;color:var(--navy);margin-bottom:8px">Phase 0</h4>
      <p style="font-size:11.5px;color:var(--text-muted);line-height:1.6;margin-bottom:14px">Découvre ton potentiel entrepreneurial sans aucun engagement.</p>
      <div style="font-family:'Syne',sans-serif;font-size:24px;font-weight:800;color:var(--navy);margin-bottom:16px">0 FCFA</div>
      <ul style="list-style:none;padding:0;margin:0 0 20px;display:flex;flex-direction:column;gap:8px;flex:1">
        <li style="font-size:11.5px;color:var(--text-muted);display:flex;gap:7px"><i class="ti ti-check" style="color:var(--gold-dark);flex-shrink:0"></i> 3 modules complets</li>
        <li style="font-size:11.5px;color:var(--text-muted);display:flex;gap:7px"><i class="ti ti-check" style="color:var(--gold-dark);flex-shrink:0"></i> Outils « Radar à Opportunités »</li>
        <li style="font-size:11.5px;color:var(--text-muted);display:flex;gap:7px"><i class="ti ti-check" style="color:var(--gold-dark);flex-shrink:0"></i> Badge « Explorateur Entrepreneurial »</li>
        <li style="font-size:11.5px;color:var(--text-muted);display:flex;gap:7px"><i class="ti ti-check" style="color:var(--gold-dark);flex-shrink:0"></i> Accès au forum communautaire</li>
      </ul>
      <a href="<?= SITE_URL ?>/register.php" class="btn-pricing outline">Commencer maintenant →</a>
    </div>
    <div style="background:var(--navy);color:#fff;border-radius:var(--radius-lg);padding:26px;display:flex;flex-direction:column;position:relative;box-shadow:0 12px 32px rgba(8,80,65,.25)">
      <span style="position:absolute;top:-12px;left:26px;background:var(--gold);color:#fff;font-size:10px;font-weight:700;padding:5px 14px;border-radius:100px">🔥 Le plus choisi</span>
      <h4 style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700;margin-bottom:8px;margin-top:8px">Séries 1 + 2</h4>
      <p style="font-size:11.5px;color:rgba(255,255,255,.7);line-height:1.6;margin-bottom:14px">De la validation de ton idée jusqu'à tes premières ventes réelles.</p>
      <div style="font-family:'Syne',sans-serif;font-size:24px;font-weight:800;color:#fff;margin-bottom:16px">15 000 FCFA</div>
      <ul style="list-style:none;padding:0;margin:0 0 20px;display:flex;flex-direction:column;gap:8px;flex:1">
        <li style="font-size:11.5px;color:rgba(255,255,255,.8);display:flex;gap:7px"><i class="ti ti-check" style="color:#7fffc4;flex-shrink:0"></i> 18 modules (Séries 1 & 2)</li>
        <li style="font-size:11.5px;color:rgba(255,255,255,.8);display:flex;gap:7px"><i class="ti ti-check" style="color:#7fffc4;flex-shrink:0"></i> Tous les templates & outils</li>
        <li style="font-size:11.5px;color:rgba(255,255,255,.8);display:flex;gap:7px"><i class="ti ti-check" style="color:#7fffc4;flex-shrink:0"></i> Coaching mensuel en groupe</li>
        <li style="font-size:11.5px;color:rgba(255,255,255,.8);display:flex;gap:7px"><i class="ti ti-check" style="color:#7fffc4;flex-shrink:0"></i> Certification Université de Parakou</li>
        <li style="font-size:11.5px;color:rgba(255,255,255,.8);display:flex;gap:7px"><i class="ti ti-check" style="color:#7fffc4;flex-shrink:0"></i> Paiement par tranches possible</li>
      </ul>
      <a href="<?= SITE_URL ?>/register.php" class="btn-pricing gold">S'inscrire — Payer en Mobile Money →</a>
    </div>
    <div style="background:#fff;border:1.5px solid rgba(0,0,0,.08);border-radius:var(--radius-lg);padding:26px;display:flex;flex-direction:column">
      <h4 style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700;color:var(--navy);margin-bottom:8px">Parcours Complet</h4>
      <p style="font-size:11.5px;color:var(--text-muted);line-height:1.6;margin-bottom:14px">Toutes les séries, du déclic jusqu'à la structuration de ton entreprise.</p>
      <div style="font-family:'Syne',sans-serif;font-size:24px;font-weight:800;color:var(--navy);margin-bottom:16px">25 000 FCFA</div>
      <ul style="list-style:none;padding:0;margin:0 0 20px;display:flex;flex-direction:column;gap:8px;flex:1">
        <li style="font-size:11.5px;color:var(--text-muted);display:flex;gap:7px"><i class="ti ti-check" style="color:var(--gold-dark);flex-shrink:0"></i> 30 modules complets</li>
        <li style="font-size:11.5px;color:var(--text-muted);display:flex;gap:7px"><i class="ti ti-check" style="color:var(--gold-dark);flex-shrink:0"></i> Coaching individuel mensuel</li>
        <li style="font-size:11.5px;color:var(--text-muted);display:flex;gap:7px"><i class="ti ti-check" style="color:var(--gold-dark);flex-shrink:0"></i> Accès réseau alumni</li>
        <li style="font-size:11.5px;color:var(--text-muted);display:flex;gap:7px"><i class="ti ti-check" style="color:var(--gold-dark);flex-shrink:0"></i> Certificat mention Excellente</li>
        <li style="font-size:11.5px;color:var(--text-muted);display:flex;gap:7px"><i class="ti ti-check" style="color:var(--gold-dark);flex-shrink:0"></i> Mise en relation partenaires</li>
      </ul>
      <a href="<?= SITE_URL ?>/register.php" class="btn-pricing">Accès complet →</a>
    </div>
  </div>
  <p style="text-align:center;font-size:11px;color:var(--text-muted);margin-top:22px">Paiement accepté via MTN Mobile Money · Moov Money · Wave — Paiement en 3 fois disponible sur demande.</p>
</div>

<!-- COMMENT CA MARCHE -->
<div class="elab-section dark" id="comment">
  <div class="sec-tag light">Parcours</div>
  <div class="sec-title light">Comment ça marche ?</div>
  <div class="sec-sub light">4 étapes pour lancer ton entreprise</div>
  <div class="how-steps">
    <div class="hstep"><div class="step-num">1</div><h4>Crée ton compte</h4><p>Inscription gratuite en 2 min, aucune carte bancaire requise</p></div>
    <div class="hstep"><div class="step-num">2</div><h4>Choisis ta formation</h4><p>Gratuit, Business Plan ou parcours complet avec coaching</p></div>
    <div class="hstep"><div class="step-num">3</div><h4>Apprends & pratique</h4><p>Vidéos courtes + exercices concrets + coaching 1:1</p></div>
    <div class="hstep"><div class="step-num">4</div><h4>Lance ton business</h4><p>Certifié par l'Université de Parakou, prêt à te lancer</p></div>
  </div>
</div>

<!-- NOTRE EQUIPE -->
<div class="elab-section alt" id="equipe" style="text-align:center">
  <div class="sec-tag">L'équipe</div>
  <div class="sec-title" style="margin-bottom:6px">Portés par CPID ONG.</div>
  <p style="font-size:13px;color:var(--text-muted);max-width:520px;margin:0 auto 36px;line-height:1.75">
    Une organisation dédiée à l'insertion professionnelle des jeunes béninois depuis plus de 10 ans.
  </p>

  <div class="equipe-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:28px;max-width:1000px;margin:0 auto 48px">

    <!-- Membre 1 -->
    <div style="background:#fff;border:1px solid #fde68a;border-radius:20px;overflow:hidden;box-shadow:0 6px 28px rgba(0,0,0,.08);transition:transform .25s,box-shadow .25s" onmouseover="this.style.transform='translateY(-6px)';this.style.boxShadow='0 16px 40px rgba(0,0,0,.14)'" onmouseout="this.style.transform='none';this.style.boxShadow='0 6px 28px rgba(0,0,0,.08)'">
      <div style="height:240px;background:linear-gradient(160deg,#D85A30 0%,#085041 100%);display:flex;align-items:center;justify-content:center;position:relative">
        <img src="<?= SITE_URL ?>/assets/images/equipes/1.png" alt="Professeur Bertrand SOGBOSSI"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"
             style="width:100%;height:100%;object-fit:cover;object-position:top;display:block">
        <div style="display:none;width:120px;height:120px;border-radius:50%;background:rgba(255,255,255,.2);backdrop-filter:blur(4px);border:4px solid rgba(255,255,255,.5);align-items:center;justify-content:center;font-size:42px;font-weight:700;color:#fff;position:absolute">BS</div>
        <div style="position:absolute;bottom:14px;left:14px;background:rgba(0,0,0,.55);backdrop-filter:blur(6px);border-radius:8px;padding:5px 12px">
          <div style="font-size:11px;font-weight:700;color:#D85A30">Président du Conseil d'administration</div>
        </div>
      </div>
      <div style="padding:22px 20px 20px">
        <div style="font-size:17px;font-weight:700;color:#1A1A18;margin-bottom:8px">Professeur Bertrand SOGBOSSI</div>
        <p style="font-size:13px;color:#6b7280;line-height:1.7;margin-bottom:18px">
          En tant que recteur de l'université de Parakou, il occupe également la fonction de président du conseil d'administration du centre d'incubation Ariziki. Il met à profit sa rigueur scientifique et son expérience pour contribuer à l'amélioration des outils déployés au sein du centre.
        </p>
        <div style="display:flex;gap:8px">
          <a href="https://facebook.com/" target="_blank" style="flex:1;display:flex;align-items:center;justify-content:center;background:#EFF6FF;border-radius:8px;padding:9px;color:#1877F2;text-decoration:none" onmouseover="this.style.background='#dbeafe'" onmouseout="this.style.background='#EFF6FF'"><i class="ti ti-brand-facebook" style="font-size:18px"></i></a>
          <a href="https://wa.me/" target="_blank" style="flex:1;display:flex;align-items:center;justify-content:center;background:#F0FDF4;border-radius:8px;padding:9px;color:#16a34a;text-decoration:none" onmouseover="this.style.background='#dcfce7'" onmouseout="this.style.background='#F0FDF4'"><i class="ti ti-brand-whatsapp" style="font-size:18px"></i></a>
          <a href="https://linkedin.com/in/" target="_blank" style="flex:1;display:flex;align-items:center;justify-content:center;background:#EFF6FF;border-radius:8px;padding:9px;color:#0A66C2;text-decoration:none" onmouseover="this.style.background='#dbeafe'" onmouseout="this.style.background='#EFF6FF'"><i class="ti ti-brand-linkedin" style="font-size:18px"></i></a>
        </div>
      </div>
    </div>

    <!-- Membre 2 -->
    <div style="background:#fff;border:1px solid #fde68a;border-radius:20px;overflow:hidden;box-shadow:0 6px 28px rgba(0,0,0,.08);transition:transform .25s,box-shadow .25s" onmouseover="this.style.transform='translateY(-6px)';this.style.boxShadow='0 16px 40px rgba(0,0,0,.14)'" onmouseout="this.style.transform='none';this.style.boxShadow='0 6px 28px rgba(0,0,0,.08)'">
      <div style="height:240px;background:linear-gradient(160deg,#085041 0%,#D85A30 100%);display:flex;align-items:center;justify-content:center;position:relative">
        <img src="<?= SITE_URL ?>/assets/images/equipes/2.png" alt="Céphas HOUNZANDJI"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"
             style="width:100%;height:100%;object-fit:cover;object-position:top;display:block">
        <div style="display:none;width:120px;height:120px;border-radius:50%;background:rgba(255,255,255,.2);backdrop-filter:blur(4px);border:4px solid rgba(255,255,255,.5);align-items:center;justify-content:center;font-size:42px;font-weight:700;color:#fff;position:absolute">CH</div>
        <div style="position:absolute;bottom:14px;left:14px;background:rgba(0,0,0,.55);backdrop-filter:blur(6px);border-radius:8px;padding:5px 12px">
          <div style="font-size:11px;font-weight:700;color:#fbbf24">Gestionnaire de projet</div>
        </div>
      </div>
      <div style="padding:22px 20px 20px">
        <div style="font-size:17px;font-weight:700;color:#1A1A18;margin-bottom:8px">Céphas HOUNZANDJI</div>
        <p style="font-size:13px;color:#6b7280;line-height:1.7;margin-bottom:18px">
          Gestionnaire de projet et consultant formateur en entrepreneuriat, il possède une solide expérience de 20 ans dans l'accompagnement entrepreneurial au Bénin. Actuellement directeur du centre d'incubation Ariziki, il continue à soutenir des start-ups et favoriser l'innovation.
        </p>
        <div style="display:flex;gap:8px">
          <a href="https://facebook.com/" target="_blank" style="flex:1;display:flex;align-items:center;justify-content:center;background:#EFF6FF;border-radius:8px;padding:9px;color:#1877F2;text-decoration:none" onmouseover="this.style.background='#dbeafe'" onmouseout="this.style.background='#EFF6FF'"><i class="ti ti-brand-facebook" style="font-size:18px"></i></a>
          <a href="https://wa.me/" target="_blank" style="flex:1;display:flex;align-items:center;justify-content:center;background:#F0FDF4;border-radius:8px;padding:9px;color:#16a34a;text-decoration:none" onmouseover="this.style.background='#dcfce7'" onmouseout="this.style.background='#F0FDF4'"><i class="ti ti-brand-whatsapp" style="font-size:18px"></i></a>
          <a href="https://linkedin.com/in/" target="_blank" style="flex:1;display:flex;align-items:center;justify-content:center;background:#EFF6FF;border-radius:8px;padding:9px;color:#0A66C2;text-decoration:none" onmouseover="this.style.background='#dbeafe'" onmouseout="this.style.background='#EFF6FF'"><i class="ti ti-brand-linkedin" style="font-size:18px"></i></a>
        </div>
      </div>
    </div>

    <!-- Membre 3 -->
    <div style="background:#fff;border:1px solid #fde68a;border-radius:20px;overflow:hidden;box-shadow:0 6px 28px rgba(0,0,0,.08);transition:transform .25s,box-shadow .25s" onmouseover="this.style.transform='translateY(-6px)';this.style.boxShadow='0 16px 40px rgba(0,0,0,.14)'" onmouseout="this.style.transform='none';this.style.boxShadow='0 6px 28px rgba(0,0,0,.08)'">
      <div style="height:240px;background:linear-gradient(160deg,#1A1A18 0%,#C04A22 100%);display:flex;align-items:center;justify-content:center;position:relative">
        <img src="<?= SITE_URL ?>/assets/images/equipes/3.png" alt="Annick Chaffa"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"
             style="width:100%;height:100%;object-fit:cover;object-position:top;display:block">
        <div style="display:none;width:120px;height:120px;border-radius:50%;background:rgba(255,255,255,.2);backdrop-filter:blur(4px);border:4px solid rgba(255,255,255,.5);align-items:center;justify-content:center;font-size:42px;font-weight:700;color:#fff;position:absolute">AC</div>
        <div style="position:absolute;bottom:14px;left:14px;background:rgba(0,0,0,.55);backdrop-filter:blur(6px);border-radius:8px;padding:5px 12px">
          <div style="font-size:11px;font-weight:700;color:#fbbf24">Ingénieur agronome</div>
        </div>
      </div>
      <div style="padding:22px 20px 20px">
        <div style="font-size:17px;font-weight:700;color:#1A1A18;margin-bottom:8px">Annick Chaffa</div>
        <p style="font-size:13px;color:#6b7280;line-height:1.7;margin-bottom:18px">
          Ingénieur agronome et experte en entrepreneuriat vert, Annick est Chargée des programmes au centre d'incubation Ariziki. Elle supervise la qualité des services fournis par les coachs et consultants, et s'engage à promouvoir des projets qui allient viabilité économique et protection de l'environnement.
        </p>
        <div style="display:flex;gap:8px">
          <a href="https://facebook.com/" target="_blank" style="flex:1;display:flex;align-items:center;justify-content:center;background:#EFF6FF;border-radius:8px;padding:9px;color:#1877F2;text-decoration:none" onmouseover="this.style.background='#dbeafe'" onmouseout="this.style.background='#EFF6FF'"><i class="ti ti-brand-facebook" style="font-size:18px"></i></a>
          <a href="https://wa.me/" target="_blank" style="flex:1;display:flex;align-items:center;justify-content:center;background:#F0FDF4;border-radius:8px;padding:9px;color:#16a34a;text-decoration:none" onmouseover="this.style.background='#dcfce7'" onmouseout="this.style.background='#F0FDF4'"><i class="ti ti-brand-whatsapp" style="font-size:18px"></i></a>
          <a href="https://linkedin.com/in/" target="_blank" style="flex:1;display:flex;align-items:center;justify-content:center;background:#EFF6FF;border-radius:8px;padding:9px;color:#0A66C2;text-decoration:none" onmouseover="this.style.background='#dbeafe'" onmouseout="this.style.background='#EFF6FF'"><i class="ti ti-brand-linkedin" style="font-size:18px"></i></a>
        </div>
      </div>
    </div>

  </div>

  

<!-- CTA -->
<div class="cta-section">
  <div class="cta-eyebrow">C'est maintenant</div>
  <h2>Ton projet commence aujourd'hui.</h2>
  <p>3 modules gratuits. Aucun engagement. Juste toi, tes idées, et une méthode qui marche sur le terrain béninois.</p>
  <div class="cta-btns">
    <a href="<?= SITE_URL ?>/register.php" style="background:#fff;color:#1a1a18;font-size:13px;font-weight:700;padding:13px 28px;border-radius:10px;text-decoration:none">Démarrer gratuitement →</a>
    <a href="<?= SITE_URL ?>/contact.php" style="background:transparent;color:#fff;border:1.5px solid rgba(255,255,255,.4);font-size:13px;font-weight:700;padding:12px 28px;border-radius:10px;text-decoration:none">Contacter l'équipe</a>
  </div>
</div>

<!-- FOOTER -->
<footer class="elab-footer">
  <div style="max-width:1100px;margin:0 auto;padding:40px 32px 0">
    <div class="footer-grid" style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:40px;margin-bottom:40px">
      <div>
        <div style="margin-bottom:14px">
          <img src="<?= SITE_URL ?>/assets/images/logo.png" alt="<?= SITE_NAME ?>" style="height:36px;width:auto">
        </div>
        <p style="font-size:15px;color:#fff;font-weight:700;font-family:'Syne',sans-serif;margin-bottom:2px">Ariziki EntrepreneurshipLab</p>
        <p style="font-size:12px;color:rgba(255,255,255,.45);margin-bottom:16px">Une initiative CPID ONG — Bénin</p>
        <p style="font-size:13px;color:rgba(255,255,255,.5);line-height:1.75;margin-bottom:16px">
          Lancez votre entreprise avant votre diplôme.<br>Certifié Université de Parakou, Bénin.
        </p>
        <div style="display:flex;gap:10px">
          <a href="#" style="width:34px;height:34px;background:rgba(255,255,255,.08);border-radius:8px;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.6);text-decoration:none" onmouseover="this.style.background='#1877F2'" onmouseout="this.style.background='rgba(255,255,255,.08)'"><i class="ti ti-brand-facebook" style="font-size:17px"></i></a>
          <a href="#" style="width:34px;height:34px;background:rgba(255,255,255,.08);border-radius:8px;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.6);text-decoration:none" onmouseover="this.style.background='#25D366'" onmouseout="this.style.background='rgba(255,255,255,.08)'"><i class="ti ti-brand-whatsapp" style="font-size:17px"></i></a>
          <a href="#" style="width:34px;height:34px;background:rgba(255,255,255,.08);border-radius:8px;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.6);text-decoration:none" onmouseover="this.style.background='#0A66C2'" onmouseout="this.style.background='rgba(255,255,255,.08)'"><i class="ti ti-brand-linkedin" style="font-size:17px"></i></a>
        </div>
      </div>
      <div>
        <h5 style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:rgba(255,255,255,.35);margin-bottom:14px">Navigation</h5>
        <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:9px">
          <li><a href="<?= SITE_URL ?>/index.php#parcours" style="font-size:13px;color:rgba(255,255,255,.55);text-decoration:none" onmouseover="this.style.color='#D85A30'" onmouseout="this.style.color='rgba(255,255,255,.55)'">Le Parcours</a></li>
          <li><a href="<?= SITE_URL ?>/index.php#tarifs" style="font-size:13px;color:rgba(255,255,255,.55);text-decoration:none" onmouseover="this.style.color='#D85A30'" onmouseout="this.style.color='rgba(255,255,255,.55)'">Tarifs</a></li>
          <li><a href="<?= SITE_URL ?>/index.php#equipe" style="font-size:13px;color:rgba(255,255,255,.55);text-decoration:none" onmouseover="this.style.color='#D85A30'" onmouseout="this.style.color='rgba(255,255,255,.55)'">À propos</a></li>
          <li><a href="<?= SITE_URL ?>/contact.php" style="font-size:13px;color:rgba(255,255,255,.55);text-decoration:none" onmouseover="this.style.color='#D85A30'" onmouseout="this.style.color='rgba(255,255,255,.55)'">Contact</a></li>
          <li><a href="<?= SITE_URL ?>/rgpd.php" style="font-size:13px;color:rgba(255,255,255,.55);text-decoration:none" onmouseover="this.style.color='#D85A30'" onmouseout="this.style.color='rgba(255,255,255,.55)'">Mentions légales</a></li>
        </ul>
      </div>
      <div>
        <h5 style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:rgba(255,255,255,.35);margin-bottom:14px">Mon compte</h5>
        <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:9px">
          <li><a href="<?= SITE_URL ?>/auth.php" style="font-size:13px;color:rgba(255,255,255,.55);text-decoration:none" onmouseover="this.style.color='#D85A30'" onmouseout="this.style.color='rgba(255,255,255,.55)'">Se connecter</a></li>
          <li><a href="<?= SITE_URL ?>/auth.php?mode=signup" style="font-size:13px;color:rgba(255,255,255,.55);text-decoration:none" onmouseover="this.style.color='#D85A30'" onmouseout="this.style.color='rgba(255,255,255,.55)'">S'inscrire</a></li>
          <li><a href="<?= SITE_URL ?>/dashboard.php" style="font-size:13px;color:rgba(255,255,255,.55);text-decoration:none" onmouseover="this.style.color='#D85A30'" onmouseout="this.style.color='rgba(255,255,255,.55)'">Mon espace</a></li>
          <li><a href="<?= SITE_URL ?>/payment.php" style="font-size:13px;color:rgba(255,255,255,.55);text-decoration:none" onmouseover="this.style.color='#D85A30'" onmouseout="this.style.color='rgba(255,255,255,.55)'">Abonnement</a></li>
          <li><a href="<?= SITE_URL ?>/resources.php" style="font-size:13px;color:rgba(255,255,255,.55);text-decoration:none" onmouseover="this.style.color='#D85A30'" onmouseout="this.style.color='rgba(255,255,255,.55)'">Bibliothèque</a></li>
        </ul>
      </div>
      <div>
        <h5 style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:rgba(255,255,255,.35);margin-bottom:14px">Légal</h5>
        <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:9px">
          <li><a href="<?= SITE_URL ?>/rgpd.php" style="font-size:13px;color:rgba(255,255,255,.55);text-decoration:none" onmouseover="this.style.color='#D85A30'" onmouseout="this.style.color='rgba(255,255,255,.55)'">Confidentialité</a></li>
          <li><a href="<?= SITE_URL ?>/rgpd.php" style="font-size:13px;color:rgba(255,255,255,.55);text-decoration:none" onmouseover="this.style.color='#D85A30'" onmouseout="this.style.color='rgba(255,255,255,.55)'">Mentions légales</a></li>
          <li><a href="<?= SITE_URL ?>/rgpd.php" style="font-size:13px;color:rgba(255,255,255,.55);text-decoration:none" onmouseover="this.style.color='#D85A30'" onmouseout="this.style.color='rgba(255,255,255,.55)'">RGPD</a></li>
        </ul>
      </div>
    </div>
    <div style="border-top:1px solid rgba(255,255,255,.08);padding:20px 0;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
      <p style="font-size:12px;color:rgba(255,255,255,.3)">© <?= date('Y') ?> <?= SITE_NAME ?> — Parakou, Bénin</p>
      <p style="font-size:12px;color:rgba(255,255,255,.3)">
        Fait par
        <a href="https://aichaporfolio.vercel.app/" target="_blank" style="color:#D85A30;text-decoration:none;font-weight:600" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Aïchatou BIO SANNA</a>
      </p>
    </div>
  </div>
</footer>

</body>
</html>