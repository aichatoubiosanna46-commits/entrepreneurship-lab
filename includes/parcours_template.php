<?php
// ============================================================
//  parcours_template.php — Template commun à tous les parcours
//  Requires: $config, $cours, $user
// ============================================================
$accentCss  = $config['accent'];
$gradHero   = $config['grad_hero'];
$nbCours    = count($cours);
$totalMods  = array_sum(array_column($cours, 'nb_modules'));
$totalSeqs  = array_sum(array_column($cours, 'nb_sequences'));
$progMoyen  = $nbCours > 0 ? (int)round(array_sum(array_column($cours,'progression')) / $nbCours) : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Parcours <?= h($config['nom']) ?> — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/home.css">
<style>
:root {
  --accent: <?= $accentCss ?>;
  --navy:   #0F1D35;
  --gold-dk:#6C47D4;
  --bg-page:#F4F2ED;
}

/* ── Layout ── */
.parc-page { min-height:100vh; background:var(--bg-page); font-family:'Plus Jakarta Sans',sans-serif; }

/* ── Hero ── */
.parc-hero { background:<?= $gradHero ?>; padding:0; position:relative; overflow:hidden; }
.parc-hero::after { content:''; position:absolute; bottom:-1px; left:0; right:0; height:52px; background:var(--bg-page); clip-path:ellipse(55% 100% at 50% 100%); }
.parc-hero-inner { max-width:980px; margin:0 auto; padding:28px 24px 72px; }
.parc-back a { display:inline-flex; align-items:center; gap:6px; color:rgba(255,255,255,.55); font-size:12px; text-decoration:none; transition:color .15s; margin-bottom:20px; }
.parc-back a:hover { color:#fff; }
.parc-pill { display:inline-flex; align-items:center; gap:6px; background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.2); color:#fff; font-size:11px; font-weight:700; padding:4px 14px; border-radius:100px; letter-spacing:.06em; text-transform:uppercase; margin-bottom:14px; }
.parc-hero h1 { font-size:clamp(22px,4vw,32px); font-weight:700; color:#fff; margin-bottom:10px; line-height:1.2; }
.parc-hero p { font-size:13.5px; color:rgba(255,255,255,.62); max-width:500px; line-height:1.7; margin-bottom:20px; }
.parc-meta { display:flex; flex-wrap:wrap; gap:16px; }
.parc-meta span { display:flex; align-items:center; gap:6px; font-size:12px; color:rgba(255,255,255,.7); }
.parc-meta span i { font-size:15px; color:var(--accent); }

/* ── Barre de progression globale ── */
.parc-prog-bar { background:#fff; border-bottom:1px solid #e8e8e8; }
.parc-prog-inner { max-width:980px; margin:0 auto; padding:12px 24px; display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
.prog-label { font-size:12px; font-weight:600; color:#333; white-space:nowrap; }
.prog-track { flex:1; min-width:120px; height:7px; background:#eee; border-radius:100px; overflow:hidden; }
.prog-fill { height:100%; background:var(--accent); border-radius:100px; transition:width .4s; }
.prog-pct { font-size:12px; font-weight:700; color:var(--accent); min-width:36px; text-align:right; }

/* ── Contenu principal ── */
.parc-main { max-width:980px; margin:0 auto; padding:28px 24px 60px; display:grid; grid-template-columns:1fr 300px; gap:28px; align-items:start; }
@media(max-width:860px){ .parc-main{ grid-template-columns:1fr; } }

/* ── Header section cours ── */
.sec-header { margin-bottom:20px; }
.sec-header h2 { font-size:17px; font-weight:700; margin-bottom:4px; }
.sec-header p  { font-size:13px; color:var(--text-muted,#6b6b6b); }

/* ── Course card ── */
.course-card { background:#fff; border-radius:14px; border:1.5px solid #e8e8e8; margin-bottom:20px; overflow:hidden; transition:box-shadow .2s; }
.course-card:hover { box-shadow:0 8px 28px rgba(15,29,53,.1); }
.course-head { display:flex; align-items:center; gap:14px; padding:16px 20px; border-bottom:1px solid #f0f0f0; cursor:pointer; user-select:none; }
.course-thumb { width:48px; height:48px; border-radius:10px; object-fit:cover; flex-shrink:0; background:var(--accent); display:flex; align-items:center; justify-content:center; font-size:22px; }
.course-thumb img { width:100%; height:100%; object-fit:cover; border-radius:10px; }
.course-meta { flex:1; min-width:0; }
.course-title { font-size:14px; font-weight:700; color:#111; margin-bottom:3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.course-sub { font-size:11.5px; color:var(--text-muted,#6b6b6b); display:flex; gap:12px; flex-wrap:wrap; }
.course-sub span { display:flex; align-items:center; gap:4px; }
.course-prog-mini { min-width:80px; text-align:right; }
.course-prog-pct { font-size:13px; font-weight:700; color:var(--accent); display:block; }
.course-prog-bar { width:80px; height:5px; background:#eee; border-radius:100px; margin-top:4px; overflow:hidden; }
.course-prog-bar-fill { height:100%; background:var(--accent); border-radius:100px; }
.course-chevron { font-size:18px; color:#ccc; transition:transform .25s; flex-shrink:0; }
.course-card.open .course-chevron { transform:rotate(180deg); }

/* ── Badge statut inscription ── */
.badge-enroll { display:inline-flex; align-items:center; gap:4px; font-size:10px; font-weight:700; padding:2px 8px; border-radius:100px; }
.badge-enroll.inscrit  { background:#EAF3DE; color:#27500A; }
.badge-enroll.libre    { background:#F4F2ED; color:#666; }

/* ── Modules list ── */
.modules-list { display:none; }
.course-card.open .modules-list { display:block; }

.module-item { border-top:1px solid #f0f0f0; }
.module-head { display:flex; align-items:center; gap:12px; padding:12px 20px 12px 32px; cursor:pointer; transition:background .15s; }
.module-head:hover { background:#fafafa; }
.module-num-badge { width:24px; height:24px; border-radius:6px; background:rgba(0,0,0,.06); display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; color:#555; flex-shrink:0; }
.module-info { flex:1; min-width:0; }
.module-name { font-size:13px; font-weight:600; color:#222; }
.module-stats { font-size:11px; color:#999; margin-top:2px; }
.mod-chevron { font-size:15px; color:#ccc; transition:transform .2s; }
.module-item.open .mod-chevron { transform:rotate(180deg); }

/* ── Séquences list ── */
.sequences-list { display:none; background:#fafafa; }
.module-item.open .sequences-list { display:block; }

.seq-row { display:flex; align-items:center; gap:12px; padding:10px 20px 10px 56px; border-top:1px solid #f0f0f0; transition:background .15s; text-decoration:none; color:inherit; }
.seq-row:hover { background:#f3f3f3; }
.seq-status { width:20px; height:20px; border-radius:50%; flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:10px; }
.seq-status.done  { background:var(--accent); color:#fff; }
.seq-status.todo  { border:2px solid #ddd; }
.seq-title { flex:1; font-size:12.5px; color:#333; }
.seq-title.done { color:#999; text-decoration:line-through; }
.seq-badges { display:flex; gap:5px; }
.seq-badge { display:inline-flex; align-items:center; gap:3px; font-size:10px; padding:2px 6px; border-radius:100px; font-weight:600; }
.seq-badge.vid { background:#FEF3C7; color:#92400E; }
.seq-badge.pdf { background:#DBEAFE; color:#1E40AF; }
.seq-badge.quiz { background:#EDE9FE; color:#5B21B6; }
.seq-badge.act  { background:#FCE7F3; color:#9D174D; }
.seq-dur { font-size:11px; color:#bbb; white-space:nowrap; }

/* ── Sidebar ── */
.parc-sidebar { display:flex; flex-direction:column; gap:16px; }

.sidebar-box { background:#fff; border-radius:14px; border:1.5px solid #e8e8e8; overflow:hidden; }
.sb-head { padding:14px 18px; border-bottom:1px solid #f0f0f0; }
.sb-head h3 { font-size:13px; font-weight:700; color:#111; }
.sb-body { padding:16px 18px; }

/* Stats sidebar */
.stat-row { display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid #f5f5f5; font-size:12.5px; }
.stat-row:last-child { border:none; }
.stat-row .val { font-weight:700; color:var(--accent); }

/* Upgrade box */
.upgrade-box { background:linear-gradient(135deg,var(--navy,#0F1D35),#1a2d4a); border-radius:14px; padding:20px; color:#fff; }
.upgrade-box h3 { font-size:14px; font-weight:700; margin-bottom:6px; }
.upgrade-box p  { font-size:12px; color:rgba(255,255,255,.65); margin-bottom:16px; line-height:1.6; }
.upgrade-btn { display:block; text-align:center; background:var(--accent); color:var(--navy,#0F1D35); font-size:12px; font-weight:700; padding:10px 16px; border-radius:9px; text-decoration:none; transition:opacity .2s; }
.upgrade-btn:hover { opacity:.85; }

/* ── Vide ── */
.empty-state { text-align:center; padding:48px 24px; }
.empty-state i { font-size:52px; color:#ddd; display:block; margin-bottom:14px; }
.empty-state h3 { font-size:16px; font-weight:600; margin-bottom:8px; }
.empty-state p  { font-size:13px; color:#999; max-width:340px; margin:0 auto 20px; }
</style>
</head>
<body class="parc-page">

<?php include __DIR__ . '/header.php'; ?>

<!-- Hero -->
<div class="parc-hero">
  <div class="parc-hero-inner">
    <div class="parc-back"><a href="<?= SITE_URL ?>/pricing.php"><i class="ti ti-arrow-left"></i> Changer de parcours</a></div>
    <div class="parc-pill"><?= $config['emoji'] ?> Parcours <?= h($config['nom']) ?> <?= $config['prix'] > 0 ? '— '.number_format($config['prix'],0,',',' ').' FCFA' : '— Gratuit' ?></div>
    <h1><?= h($config['accroche']) ?></h1>
    <p><?= h($config['desc']) ?></p>
    <div class="parc-meta">
      <span><i class="ti ti-books"></i> <?= $nbCours ?> cours</span>
      <span><i class="ti ti-layout-list"></i> <?= $totalMods ?> modules</span>
      <span><i class="ti ti-list-numbers"></i> <?= $totalSeqs ?> séquences</span>
      <?php if ($config['prix'] === 0): ?>
      <span><i class="ti ti-circle-check"></i> Accès immédiat gratuit</span>
      <?php else: ?>
      <span><i class="ti ti-certificate"></i> Certificat inclus</span>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Barre progression globale -->
<div class="parc-prog-bar">
  <div class="parc-prog-inner">
    <span class="prog-label"><i class="ti ti-chart-line" style="font-size:15px;margin-right:4px"></i>Ma progression</span>
    <div class="prog-track"><div class="prog-fill" style="width:<?= $progMoyen ?>%"></div></div>
    <span class="prog-pct"><?= $progMoyen ?>%</span>
  </div>
</div>

<!-- Contenu -->
<div class="parc-main">

  <!-- Colonne principale -->
  <div>
    <?= flash() ?>

    <div class="sec-header">
      <h2>Cours de ce parcours</h2>
      <p><?= $nbCours ?> cours · <?= $totalMods ?> modules · <?= $totalSeqs ?> séquences disponibles</p>
    </div>

    <?php if (empty($cours)): ?>
    <div class="empty-state">
      <i class="ti ti-books"></i>
      <h3>Aucun cours disponible pour l'instant</h3>
      <p>Les cours de ce parcours seront bientôt disponibles. Reviens dans quelques jours !</p>
      <a href="<?= SITE_URL ?>/pricing.php" class="btn-primary" style="display:inline-flex">
        <i class="ti ti-arrow-left"></i> Voir les autres parcours
      </a>
    </div>
    <?php else: ?>

    <?php foreach ($cours as $ci => $c): ?>
    <div class="course-card" id="cc-<?= $c['id'] ?>">

      <!-- En-tête du cours -->
      <div class="course-head" onclick="toggleCourse(<?= $c['id'] ?>)">
        <div class="course-thumb">
          <?php if ($c['miniature']): ?>
            <img src="<?= SITE_URL ?>/assets/uploads/<?= h($c['miniature']) ?>" alt="">
          <?php else: ?>
            <?= $c['emoji'] ?? '📚' ?>
          <?php endif; ?>
        </div>
        <div class="course-meta">
          <div class="course-title"><?= h($c['titre']) ?></div>
          <div class="course-sub">
            <span><i class="ti ti-layout-list"></i> <?= $c['nb_modules'] ?> modules</span>
            <span><i class="ti ti-list-numbers"></i> <?= $c['nb_sequences'] ?> séquences</span>
            <?php if ($c['duree_heures']): ?>
            <span><i class="ti ti-clock"></i> <?= $c['duree_heures'] ?>h</span>
            <?php endif; ?>
            <?php if ($c['enrolled_id']): ?>
            <span class="badge-enroll inscrit"><i class="ti ti-check"></i> Inscrit</span>
            <?php else: ?>
            <span class="badge-enroll libre">Libre d'accès</span>
            <?php endif; ?>
          </div>
        </div>
        <div class="course-prog-mini">
          <span class="course-prog-pct"><?= $c['progression'] ?>%</span>
          <div class="course-prog-bar"><div class="course-prog-bar-fill" style="width:<?= $c['progression'] ?>%"></div></div>
        </div>
        <i class="ti ti-chevron-down course-chevron"></i>
      </div>

      <!-- Modules -->
      <div class="modules-list">
        <?php if (empty($c['modules'])): ?>
          <div style="padding:20px 24px;font-size:13px;color:#999;text-align:center">Aucun module dans ce cours pour l'instant.</div>
        <?php else: ?>
        <?php foreach ($c['modules'] as $mi => $mod): ?>
        <div class="module-item" id="mi-<?= $mod['id'] ?>">

          <!-- Titre module -->
          <div class="module-head" onclick="toggleModule(<?= $mod['id'] ?>)">
            <div class="module-num-badge"><?= $mi + 1 ?></div>
            <div class="module-info">
              <div class="module-name"><?= h($mod['titre']) ?></div>
              <div class="module-stats">
                <?= $mod['nb_seq'] ?> séquence<?= $mod['nb_seq']>1?'s':'' ?>
                <?php if ($mod['duree_min'] ?? null): ?> · <?= $mod['duree_min'] ?> min<?php endif; ?>
              </div>
            </div>
            <i class="ti ti-chevron-down mod-chevron"></i>
          </div>

          <!-- Séquences -->
          <div class="sequences-list">
            <?php if (empty($mod['sequences'])): ?>
              <div style="padding:12px 56px;font-size:12px;color:#bbb">Aucune séquence.</div>
            <?php else: ?>
            <?php foreach ($mod['sequences'] as $si => $seq): ?>
            <a href="<?= SITE_URL ?>/module.php?id=<?= $seq['id'] ?>" class="seq-row">
              <div class="seq-status <?= $seq['terminee'] ? 'done' : 'todo' ?>">
                <?php if ($seq['terminee']): ?><i class="ti ti-check" style="font-size:10px"></i><?php endif; ?>
              </div>
              <div class="seq-title <?= $seq['terminee'] ? 'done' : '' ?>"><?= h($seq['titre']) ?></div>
              <div class="seq-badges">
                <?php if ($seq['video_url']): ?><span class="seq-badge vid"><i class="ti ti-player-play"></i>Vidéo</span><?php endif; ?>
                <?php if ($seq['fichier_pdf']): ?><span class="seq-badge pdf"><i class="ti ti-file-text"></i>PDF</span><?php endif; ?>
                <?php if ($seq['nb_quizzes']): ?><span class="seq-badge quiz"><i class="ti ti-help"></i>Quiz</span><?php endif; ?>
                <?php if ($seq['nb_activites']): ?><span class="seq-badge act"><i class="ti ti-pencil"></i>Activité</span><?php endif; ?>
              </div>
              <?php if ($seq['duree_min']): ?>
              <div class="seq-dur"><?= $seq['duree_min'] ?> min</div>
              <?php endif; ?>
            </a>
            <?php endforeach; ?>
            <?php endif; ?>
          </div>

        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div><!-- /modules-list -->
    </div><!-- /course-card -->
    <?php endforeach; ?>

    <?php endif; // fin if empty($cours) ?>
  </div><!-- /col gauche -->

  <!-- Sidebar droite -->
  <aside class="parc-sidebar">

    <!-- Récap -->
    <div class="sidebar-box">
      <div class="sb-head"><h3>📊 Mon avancement</h3></div>
      <div class="sb-body">
        <div class="stat-row"><span>Cours</span><span class="val"><?= $nbCours ?></span></div>
        <div class="stat-row"><span>Modules</span><span class="val"><?= $totalMods ?></span></div>
        <div class="stat-row"><span>Séquences</span><span class="val"><?= $totalSeqs ?></span></div>
        <div class="stat-row"><span>Progression</span><span class="val"><?= $progMoyen ?>%</span></div>
      </div>
    </div>

    <!-- Navigation parcours -->
    <div class="sidebar-box">
      <div class="sb-head"><h3>🗺 Mes parcours</h3></div>
      <div class="sb-body" style="padding:8px 0">
        <?php
        $allTarifs = [
          'decouverte'    => ['💡','Découverte','parcours-decouverte.php'],
          'business_plan' => ['📊','Business Plan','parcours-business-plan.php'],
          'lancement'     => ['🚀','Lancement','parcours-lancement.php'],
        ];
        foreach ($allTarifs as $k => [$em,$nm,$pg]): ?>
        <a href="<?= SITE_URL ?>/<?= $pg ?>" style="display:flex;align-items:center;gap:10px;padding:10px 18px;text-decoration:none;color:inherit;transition:background .15s;<?= $k === $config['tarif'] ? 'background:#f9f9f9;font-weight:700;border-left:3px solid var(--accent)' : '' ?>">
          <span><?= $em ?></span>
          <span style="font-size:13px"><?= $nm ?></span>
          <?php if ($k === $config['tarif']): ?><i class="ti ti-arrow-right" style="margin-left:auto;font-size:14px;color:var(--accent)"></i><?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Upgrade si pas dernier tarif -->
    <?php if (!empty($config['upgrade_tarif'])): ?>
    <div class="upgrade-box">
      <h3>🚀 Passer au niveau supérieur</h3>
      <p>Le parcours <strong><?= h($config['upgrade_nom']) ?></strong> t'offre plus de contenu, du coaching et un certificat reconnu.</p>
      <a href="<?= SITE_URL ?>/parcours-<?= $config['upgrade_tarif'] ?>.php" class="upgrade-btn">
        Voir le parcours <?= h($config['upgrade_nom']) ?> — <?= h($config['upgrade_prix']) ?> →
      </a>
    </div>
    <?php endif; ?>

  </aside>

</div><!-- /parc-main -->

<script>
function toggleCourse(id) {
  const card = document.getElementById('cc-' + id);
  card.classList.toggle('open');
  // Ouvrir le premier module auto si on vient d'ouvrir
  if (card.classList.contains('open')) {
    const first = card.querySelector('.module-item');
    if (first && !first.classList.contains('open')) first.classList.add('open');
  }
}
function toggleModule(id) {
  document.getElementById('mi-' + id).classList.toggle('open');
}
// Ouvrir le premier cours au chargement
document.addEventListener('DOMContentLoaded', () => {
  const first = document.querySelector('.course-card');
  if (first) {
    first.classList.add('open');
    const mod = first.querySelector('.module-item');
    if (mod) mod.classList.add('open');
  }
});
</script>
</body>
</html>
