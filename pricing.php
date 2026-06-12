<?php
// ============================================================
//  pricing.php — Choix du parcours après inscription
// ============================================================
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
reqConnecte();

$user = utilisateurCourant();

// Charger les cours disponibles groupés par tarif
$pdo = getPDO();
$coursParTarif = [];
$stmt = $pdo->query(
    "SELECT id, titre, tarif, type, prix, duree_heures,
            (SELECT COUNT(*) FROM modules m WHERE m.course_id = courses.id AND m.actif=1) as nb_modules
     FROM courses
     WHERE actif = 1 AND statut = 'publie'
     ORDER BY tarif, ordre ASC"
);
foreach ($stmt->fetchAll() as $c) {
    $coursParTarif[$c['tarif']][] = $c;
}

// Définition des 3 tarifs
$tarifs = [
  'decouverte' => [
    'slug'    => 'decouverte',
    'emoji'   => '💡',
    'nom'     => 'Découverte',
    'accroche'=> 'Trouver son idée de business',
    'desc'    => 'Valide ton projet en 4h avec notre méthode adaptée au contexte béninois. Sans carte bancaire.',
    'prix'    => 0,
    'badge'   => 'Gratuit',
    'badge_cls'=> 'free',
    'thumb'   => 't1',
    'btn_cls' => 'outline',
    'avantages'=> [
      'Accès immédiat sans paiement',
      '3 vidéos + guide PDF',
      'Exercice de validation d\'idée',
      'Accès à la communauté',
    ],
    'non'     => ['Coaching personnalisé','Certificat officiel'],
  ],
  'business_plan' => [
    'slug'    => 'business-plan',
    'emoji'   => '📊',
    'nom'     => 'Business Plan',
    'accroche'=> 'Business Plan simplifié',
    'desc'    => 'De l\'idée au plan d\'action concret. Tout ce qu\'il faut pour convaincre un investisseur.',
    'prix'    => 5000,
    'badge'   => '⭐ Populaire',
    'badge_cls'=> 'pop',
    'thumb'   => 't2',
    'btn_cls' => 'gold',
    'featured'=> true,
    'avantages'=> [
      '8 modules vidéo complets',
      'Template Business Plan Word',
      '1 session coaching 1:1 (30 min)',
      'Certificat de complétion',
      'Paiement MTN MoMo accepté',
    ],
    'non'     => ['Accompagnement terrain'],
  ],
  'lancement' => [
    'slug'    => 'lancement',
    'emoji'   => '🚀',
    'nom'     => 'Lancement',
    'accroche'=> 'Lancer son activité',
    'desc'    => 'Le parcours complet : coaching intensif, accompagnement terrain et certification universitaire.',
    'prix'    => 8000,
    'badge'   => '🏆 Complet',
    'badge_cls'=> 'pro',
    'thumb'   => 't3',
    'btn_cls' => 'solid',
    'avantages'=> [
      'Tous les modules Business Plan inclus',
      '3 sessions coaching 1:1',
      'Suivi personnalisé 30 jours',
      'Certification Université de Parakou',
      'Accès réseau d\'alumni',
      'Accompagnement terrain inclus',
    ],
    'non'     => [],
  ],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Choisis ton parcours — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<style>
:root {
  --navy:      #0F1D35;
  --navy-mid:  #1a2d4a;
  --gold:      #F5C518;
  --gold-dk:   #BA7517;
  --bg-page:   #F4F2ED;
}

/* ── Page ── */
.pricing-page { min-height:100vh; background:var(--bg-page); font-family:'Plus Jakarta Sans',sans-serif; }

/* ── Topbar ── */
.p-topbar { background:var(--navy); height:58px; display:flex; align-items:center; justify-content:space-between; padding:0 28px; }
.p-logo { display:flex; align-items:center; gap:10px; text-decoration:none; color:#FAEEDA; font-size:15px; font-weight:500; }
.p-logo .lm { width:34px; height:34px; background:var(--gold-dk); border-radius:8px; display:flex; align-items:center; justify-content:center; font-weight:700; color:#fff; font-size:16px; }
.p-user { font-size:12px; color:rgba(255,255,255,.55); }
.p-user strong { color:#FAEEDA; }

/* ── Hero ── */
.p-hero { background:var(--navy); padding:48px 24px 88px; text-align:center; position:relative; overflow:hidden; }
.p-hero::after { content:''; position:absolute; bottom:-1px; left:0; right:0; height:56px; background:var(--bg-page); clip-path:ellipse(55% 100% at 50% 100%); }
.p-hero-pill { display:inline-flex; align-items:center; gap:6px; background:rgba(245,197,24,.12); color:var(--gold); border:1px solid rgba(245,197,24,.25); font-size:11px; font-weight:700; letter-spacing:.07em; text-transform:uppercase; padding:4px 14px; border-radius:100px; margin-bottom:18px; }
.p-hero h1 { font-size:clamp(22px,4vw,34px); font-weight:700; color:#fff; margin-bottom:12px; }
.p-hero h1 span { color:var(--gold); }
.p-hero p { font-size:13.5px; color:rgba(255,255,255,.58); max-width:460px; margin:0 auto; line-height:1.7; }

/* ── Steps ── */
.p-steps { display:flex; justify-content:center; align-items:center; gap:0; padding:28px 24px 8px; max-width:500px; margin:0 auto; }
.ps-item { display:flex; align-items:center; gap:8px; }
.ps-dot { width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; flex-shrink:0; }
.ps-dot.done { background:#97C459; color:#fff; }
.ps-dot.cur  { background:var(--gold-dk); color:#fff; }
.ps-dot.next { background:#ddd; color:#999; }
.ps-label { font-size:11px; color:var(--text-muted); white-space:nowrap; }
.ps-item.cur .ps-label { color:var(--text); font-weight:600; }
.ps-sep { flex:1; height:1px; background:#ddd; min-width:28px; max-width:56px; }

/* ── Flash ── */
.p-flash { max-width:940px; margin:12px auto 0; padding:0 20px; }

/* ── Grid tarifs ── */
.p-grid-wrap { max-width:940px; margin:0 auto; padding:0 20px 16px; }
.p-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:20px; }
@media(max-width:860px){ .p-grid{ grid-template-columns:1fr 1fr; } }
@media(max-width:560px){ .p-grid{ grid-template-columns:1fr; } }

/* ── Card ── */
.p-card { background:#fff; border-radius:16px; border:1.5px solid rgba(0,0,0,.08); overflow:hidden; display:flex; flex-direction:column; transition:transform .2s,box-shadow .2s; }
.p-card:hover { transform:translateY(-6px); box-shadow:0 18px 44px rgba(15,29,53,.13); }
.p-card.featured { border-color:var(--gold-dk); box-shadow:0 6px 24px rgba(186,117,23,.18); }

.p-thumb { height:96px; display:flex; align-items:center; justify-content:center; font-size:34px; position:relative; }
.t1 { background:linear-gradient(135deg,#1a1710,#2e2312); }
.t2 { background:linear-gradient(135deg,var(--navy),var(--gold-dk)); }
.t3 { background:linear-gradient(135deg,#0d3b2e,#1a6b52); }

.p-badge { position:absolute; top:10px; left:10px; font-size:9px; font-weight:700; padding:3px 10px; border-radius:100px; letter-spacing:.04em; text-transform:uppercase; }
.p-badge.free { background:#22c55e; color:#fff; }
.p-badge.pop  { background:var(--gold); color:var(--navy); }
.p-badge.pro  { background:#10b981; color:#fff; }

.p-body { padding:20px; flex:1; display:flex; flex-direction:column; }
.p-name { font-size:15px; font-weight:700; color:var(--navy); margin-bottom:5px; }
.p-desc { font-size:11.5px; color:var(--text-muted); line-height:1.65; margin-bottom:14px; }
.p-price { display:flex; align-items:baseline; gap:5px; margin-bottom:16px; }
.p-price .amt { font-size:26px; font-weight:700; color:var(--navy); }
.p-price .amt.free { font-size:20px; color:#22c55e; }
.p-price .cur { font-size:12px; color:var(--text-muted); }
.p-price .per { font-size:11px; color:var(--text-muted); }

.p-feats { list-style:none; padding:0; margin:0 0 18px; flex:1; }
.p-feats li { display:flex; align-items:flex-start; gap:8px; font-size:12px; color:#333; padding:5px 0; border-bottom:1px solid rgba(0,0,0,.04); }
.p-feats li:last-child { border:none; }
.p-feats li i { font-size:14px; flex-shrink:0; margin-top:1px; }
.p-feats li i.yes { color:var(--gold-dk); }
.p-feats li i.no  { color:#ccc; }
.p-feats li.non   { color:#bbb; }

/* ── Courses count badge ── */
.p-courses-count { display:inline-flex; align-items:center; gap:5px; font-size:11px; font-weight:600; background:rgba(186,117,23,.1); color:var(--gold-dk); border-radius:100px; padding:3px 10px; margin-bottom:14px; }

/* ── Buttons ── */
.p-btn { display:block; text-align:center; padding:12px 16px; border-radius:10px; font-size:13px; font-weight:700; text-decoration:none; transition:all .2s; cursor:pointer; border:2px solid transparent; }
.p-btn.outline { border-color:var(--navy); color:var(--navy); }
.p-btn.outline:hover { background:var(--navy); color:#fff; }
.p-btn.gold { background:var(--gold); color:var(--navy); border-color:var(--gold); }
.p-btn.gold:hover { background:var(--gold-dk); border-color:var(--gold-dk); color:#fff; }
.p-btn.solid { background:var(--navy); color:#fff; border-color:var(--navy); }
.p-btn.solid:hover { background:var(--navy-mid); }

/* ── Reassurance ── */
.p-reassure { display:flex; flex-wrap:wrap; justify-content:center; gap:20px; padding:20px; max-width:940px; margin:0 auto; }
.p-ri { display:flex; align-items:center; gap:7px; font-size:11.5px; color:var(--text-muted); }
.p-ri i { color:var(--gold-dk); font-size:16px; }

/* ── Skip ── */
.p-skip { text-align:center; font-size:12px; color:var(--text-muted); padding:0 0 40px; }
.p-skip a { color:var(--amber,#BA7517); text-decoration:underline; }
</style>
</head>
<body class="pricing-page">

<!-- Topbar -->
<div class="p-topbar">
  <a href="<?= SITE_URL ?>" class="p-logo">
    <div class="lm">E</div><?= SITE_NAME ?>
  </a>
  <div class="p-user">Connecté : <strong><?= h($user['prenom'].' '.$user['nom']) ?></strong></div>
</div>

<!-- Hero -->
<div class="p-hero">
  <div class="p-hero-pill">🎓 Étape 2 sur 3</div>
  <h1>Choisis ton <span>parcours</span></h1>
  <p>Ton compte est prêt ! Sélectionne le parcours qui correspond à ton objectif entrepreneurial.</p>
</div>

<!-- Stepper -->
<div class="p-steps">
  <div class="ps-item">
    <div class="ps-dot done"><i class="ti ti-check" style="font-size:12px"></i></div>
    <span class="ps-label">Compte créé</span>
  </div>
  <div class="ps-sep"></div>
  <div class="ps-item cur">
    <div class="ps-dot cur">2</div>
    <span class="ps-label">Choisir un parcours</span>
  </div>
  <div class="ps-sep"></div>
  <div class="ps-item">
    <div class="ps-dot next">3</div>
    <span class="ps-label">Commencer</span>
  </div>
</div>

<!-- Flash -->
<div class="p-flash"><?= flash() ?></div>

<!-- Grille tarifs -->
<div class="p-grid-wrap" style="padding-top:20px">
  <div class="p-grid">
    <?php foreach ($tarifs as $key => $t): ?>
    <div class="p-card <?= !empty($t['featured']) ? 'featured' : '' ?>">
      <div class="p-thumb <?= $t['thumb'] ?>">
        <?= $t['emoji'] ?>
        <span class="p-badge <?= $t['badge_cls'] ?>"><?= $t['badge'] ?></span>
      </div>
      <div class="p-body">
        <div class="p-name"><?= h($t['accroche']) ?></div>
        <p class="p-desc"><?= h($t['desc']) ?></p>

        <?php
        $nb = count($coursParTarif[$key] ?? []);
        if ($nb > 0): ?>
        <div class="p-courses-count">
          <i class="ti ti-book"></i>
          <?= $nb ?> cours disponible<?= $nb>1?'s':'' ?>
        </div>
        <?php endif; ?>

        <div class="p-price">
          <?php if ($t['prix'] === 0): ?>
            <span class="amt free">Gratuit</span>
          <?php else: ?>
            <span class="amt"><?= number_format($t['prix'],0,',',' ') ?></span>
            <span class="cur">FCFA</span>
            <span class="per">/ accès à vie</span>
          <?php endif; ?>
        </div>

        <ul class="p-feats">
          <?php foreach ($t['avantages'] as $av): ?>
          <li><i class="ti ti-circle-check yes"></i><?= h($av) ?></li>
          <?php endforeach; ?>
          <?php foreach ($t['non'] as $n): ?>
          <li class="non"><i class="ti ti-x no"></i><?= h($n) ?></li>
          <?php endforeach; ?>
        </ul>

        <a href="<?= SITE_URL ?>/parcours-<?= $t['slug'] ?>.php" class="p-btn <?= $t['btn_cls'] ?>">
          <?php if ($t['prix'] === 0): ?>
            Commencer gratuitement →
          <?php else: ?>
            Choisir — <?= number_format($t['prix'],0,',',' ') ?> FCFA →
          <?php endif; ?>
        </a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Reassurance -->
<div class="p-reassure">
  <div class="p-ri"><i class="ti ti-lock"></i> Paiement sécurisé MTN MoMo & Moov</div>
  <div class="p-ri"><i class="ti ti-refresh"></i> Satisfait ou remboursé sous 7 jours</div>
  <div class="p-ri"><i class="ti ti-infinity"></i> Accès à vie à ton parcours</div>
  <div class="p-ri"><i class="ti ti-headset"></i> Support 7j/7</div>
</div>

<!-- Skip -->
<p class="p-skip">Décider plus tard ? <a href="<?= SITE_URL ?>/dashboard.php">Accéder à mon tableau de bord</a></p>

</body>
</html>
