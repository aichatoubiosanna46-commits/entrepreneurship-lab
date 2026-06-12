<?php
// module.php — Page publique d'un cours
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pdo  = getPDO();
$slug = trim($_GET['slug'] ?? '');
if (!$slug) { header('Location: ' . SITE_URL . '/index.php'); exit; }

// Récupérer le cours
$stmt = $pdo->prepare(
    'SELECT m.*, c.nom as categorie, c.icone as cat_icone, c.couleur as cat_couleur
     FROM courses m
     JOIN categories c ON c.id = m.category_id
     WHERE m.slug = ? AND m.actif = 1'
);
$stmt->execute([$slug]);
$course = $stmt->fetch();
if (!$course) { http_response_code(404); include __DIR__ . '/404.php'; exit; }

// Vérifier inscription
$inscrit   = false;
$userId    = $_SESSION['user_id'] ?? null;
if ($userId) {
    $inscrit = estInscrit($userId, $course['id']);
}

// Action : s'inscrire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'inscrire') {
    verifierCSRF();
    if (!$userId) {
        redirect(SITE_URL . '/login.php', 'Connecte-toi pour t\'inscrire.', 'info');
    }
    if (!$inscrit) {
       // APRÈS
$pdo->prepare(
    'INSERT IGNORE INTO enrollments (user_id, course_id, statut) VALUES (?, ?, "actif")'
)->execute([$userId, $course['id']]);
redirect(SITE_URL . '/module.php?slug=' . urlencode($slug), 'Inscription réussie ! Bonne formation 🎉', 'success');

    }
}

// Modules du cours avec séquences
$modulesStmt = $pdo->prepare(
    'SELECT * FROM modules WHERE course_id = ? AND actif = 1 ORDER BY ordre ASC, id ASC'
);
$modulesStmt->execute([$course['id']]);
$modules = $modulesStmt->fetchAll();

// Séquences par module
$modulesAvecSeq = [];
foreach ($modules as $mod) {
    $seqStmt = $pdo->prepare(
        'SELECT * FROM sequences WHERE module_id = ? AND actif = 1 ORDER BY ordre ASC, id ASC'
    );
    $seqStmt->execute([$mod['id']]);
    $sequences = $seqStmt->fetchAll();

    // Progression par séquence si connecté
    $completees = [];
    if ($userId) {
        foreach ($sequences as $s) {
           $p = $pdo->prepare('SELECT id FROM progress WHERE user_id = ? AND sequence_id = ? AND terminee = 1');
            $p->execute([$userId, $s['id']]);
            if ($p->fetch()) $completees[] = $s['id'];
        }
    }
    $modulesAvecSeq[] = [
        'module'    => $mod,
        'sequences' => $sequences,
        'completees'=> $completees,
    ];
}

// Progression globale du cours
// APRÈS
$pct = $userId ? progressionCours($userId, $course['id']) : 0;

// Nombre total de séquences
$nbTotal = array_sum(array_map(fn($m) => count($m['sequences']), $modulesAvecSeq));

$pageTitle = $course['titre'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($course['titre']) ?> — <?= SITE_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Outfit:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/home.css">
<style>
/* ── Hero ── */
.course-hero {
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 60%, #0f3460 100%);
  padding: 56px 0 40px;
  color: #fff;
}
.course-hero-inner {
  max-width: 1100px; margin: 0 auto; padding: 0 24px;
  display: grid; grid-template-columns: 1fr 340px; gap: 40px; align-items: start;
}
.course-cat-badge {
  display: inline-flex; align-items: center; gap: 6px;
  font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .06em;
  padding: 4px 12px; border-radius: 20px; margin-bottom: 16px;
  background: rgba(255,255,255,.12);
}
.course-hero h1 { font-size: clamp(22px, 4vw, 36px); font-weight: 700; margin: 0 0 12px; line-height: 1.25; }
.course-hero-desc { font-size: 15px; color: rgba(255,255,255,.75); margin-bottom: 20px; line-height: 1.6; }
.course-hero-meta { display: flex; flex-wrap: wrap; gap: 16px; font-size: 13px; color: rgba(255,255,255,.7); }
.course-hero-meta span { display: flex; align-items: center; gap: 5px; }

/* ── Card flottante ── */
.course-card-sticky {
  background: #fff; border-radius: 16px; overflow: hidden;
  box-shadow: 0 8px 40px rgba(0,0,0,.25);
  position: sticky; top: 24px;
}
.course-card-thumb { width: 100%; aspect-ratio: 16/9; object-fit: cover; display: block; }
.course-card-thumb-ph {
  width: 100%; aspect-ratio: 16/9;
  display: flex; align-items: center; justify-content: center;
  font-size: 52px;
}
.course-card-body { padding: 20px; }
.course-price {
  font-size: 26px; font-weight: 700; color: var(--text, #111); margin-bottom: 16px;
}
.course-price small { font-size: 13px; color: var(--text-muted, #6b7280); font-weight: 400; }

/* ── Boutons ── */
.btn-enroll {
  width: 100%; padding: 14px; font-size: 15px; font-weight: 700;
  border: none; border-radius: 10px; cursor: pointer;
  background: var(--amber, #BA7517); color: #fff;
  display: flex; align-items: center; justify-content: center; gap: 8px;
  text-decoration: none; transition: .2s;
}
.btn-enroll:hover { background: #9a6010; }
.btn-enroll.enrolled { background: #534AB7; }
.btn-enroll.enrolled:hover { background: #3d369a; }

/* ── Progression ── */
.course-progress-wrap { margin-bottom: 16px; }
.course-progress-label {
  display: flex; justify-content: space-between;
  font-size: 12px; color: var(--text-muted); margin-bottom: 6px;
}
.course-progress-bar {
  height: 8px; background: #e5e7eb; border-radius: 99px; overflow: hidden;
}
.course-progress-fill {
  height: 100%; background: linear-gradient(90deg, #534AB7, #BA7517);
  border-radius: 99px; transition: width .4s;
}

/* ── Contenu principal ── */
.course-body { max-width: 1100px; margin: 0 auto; padding: 40px 24px; display: grid; grid-template-columns: 1fr 340px; gap: 40px; align-items: start; }
.course-main { min-width: 0; }

/* ── Accordéon modules ── */
.modules-list { display: flex; flex-direction: column; gap: 12px; }
.module-item { border: 1px solid var(--border, #e5e7eb); border-radius: 12px; overflow: hidden; }
.module-header {
  display: flex; align-items: center; gap: 12px;
  padding: 16px 20px; cursor: pointer;
  background: var(--surface, #fff);
  transition: background .15s;
  user-select: none;
}
.module-header:hover { background: var(--surface-alt, #f9fafb); }
.module-num {
  width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; font-weight: 700;
  background: var(--primary-light, #f0effc); color: var(--primary, #534AB7);
}
.module-header-info { flex: 1; min-width: 0; }
.module-header-title { font-size: 14px; font-weight: 600; color: var(--text, #111); }
.module-header-meta { font-size: 12px; color: var(--text-muted, #6b7280); margin-top: 2px; }
.module-chevron { color: var(--text-muted); transition: transform .2s; font-size: 18px; }
.module-item.open .module-chevron { transform: rotate(180deg); }

.module-sequences { display: none; border-top: 1px solid var(--border, #e5e7eb); }
.module-item.open .module-sequences { display: block; }

.seq-item {
  display: flex; align-items: center; gap: 12px;
  padding: 12px 20px 12px 32px;
  border-bottom: 1px solid var(--border, #e5e7eb);
  text-decoration: none; color: var(--text, #111);
  transition: background .15s;
}
.seq-item:last-child { border-bottom: none; }
.seq-item:hover { background: var(--surface-alt, #f9fafb); }
.seq-check {
  width: 22px; height: 22px; border-radius: 50%; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  border: 2px solid var(--border, #e5e7eb);
  font-size: 11px;
}
.seq-check.done { background: #16a34a; border-color: #16a34a; color: #fff; }
.seq-check.locked { background: var(--surface-alt, #f9fafb); color: var(--text-muted); }
.seq-title { font-size: 13px; flex: 1; }
.seq-duration { font-size: 11px; color: var(--text-muted); }

/* ── Section objectifs ── */
.course-section-title {
  font-size: 18px; font-weight: 700; color: var(--text, #111);
  margin: 0 0 16px; display: flex; align-items: center; gap: 8px;
}
.objectifs-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 32px; }
.objectif-item {
  display: flex; align-items: flex-start; gap: 8px;
  font-size: 13px; padding: 10px 12px;
  background: var(--surface-alt, #f9fafb); border-radius: 8px;
}
.objectif-item i { color: #16a34a; flex-shrink: 0; margin-top: 1px; }

@media (max-width: 860px) {
  .course-hero-inner, .course-body { grid-template-columns: 1fr; }
  .course-card-sticky { position: static; }
  .objectifs-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<?= flash() ?>

<!-- ══ HERO ══ -->
<section class="course-hero">
  <div class="course-hero-inner">
    <div>
      <div class="course-cat-badge" style="color:<?= h($course['cat_couleur']) ?>">
        <i class="ti <?= h($course['cat_icone']) ?>"></i>
        <?= h($course['categorie']) ?>
      </div>
      <h1><?= h($course['titre']) ?></h1>
      <?php if ($course['sous_titre'] ?? ''): ?>
        <p class="course-hero-desc"><?= h($course['sous_titre']) ?></p>
      <?php elseif ($course['description']): ?>
        <p class="course-hero-desc"><?= h(mb_substr(strip_tags($course['description']), 0, 180)) ?>…</p>
      <?php endif; ?>
      <div class="course-hero-meta">
        <span><i class="ti ti-layers"></i> <?= count($modules) ?> module<?= count($modules) != 1 ? 's' : '' ?></span>
        <span><i class="ti ti-list"></i> <?= $nbTotal ?> séquence<?= $nbTotal != 1 ? 's' : '' ?></span>
        <?php if ($course['duree_heures']): ?>
          <span><i class="ti ti-clock"></i> <?= $course['duree_heures'] ?>h</span>
        <?php endif; ?>
        <span><i class="ti ti-signal"></i>
          <?= ['debutant'=>'Débutant','intermediaire'=>'Intermédiaire','avance'=>'Avancé'][$course['niveau']] ?? 'Tous niveaux' ?>
        </span>
        <?php if ($course['certificat']): ?>
          <span><i class="ti ti-certificate" style="color:#BA7517"></i> Certificat inclus</span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Card sticky -->
    <div class="course-card-sticky">
      <?php if ($course['miniature']): ?>
        <img class="course-card-thumb" src="<?= SITE_URL ?>/assets/uploads/<?= h($course['miniature']) ?>" alt="<?= h($course['titre']) ?>">
      <?php else: ?>
        <div class="course-card-thumb-ph" style="background:<?= h($course['cat_couleur']) ?>22">
          <i class="ti <?= h($course['cat_icone']) ?>" style="color:<?= h($course['cat_couleur']) ?>"></i>
        </div>
      <?php endif; ?>
      <div class="course-card-body">

        <?php if ($inscrit && $pct > 0): ?>
        <div class="course-progress-wrap">
          <div class="course-progress-label">
            <span>Progression</span><span><?= $pct ?>%</span>
          </div>
          <div class="course-progress-bar">
            <div class="course-progress-fill" style="width:<?= $pct ?>%"></div>
          </div>
        </div>
        <?php endif; ?>

        <div class="course-price">
          <?php if ($course['type'] === 'gratuit'): ?>
            Gratuit <small>— accès illimité</small>
          <?php else: ?>
            <?= fcfa((float)$course['prix']) ?>
          <?php endif; ?>
        </div>

        <?php if ($inscrit): ?>
          <?php
          // Trouver la première séquence non complétée
          $premiereSeq = null;
          foreach ($modulesAvecSeq as $ma) {
            foreach ($ma['sequences'] as $s) {
              if (!in_array($s['id'], $ma['completees'])) {
                $premiereSeq = $s;
                break 2;
              }
            }
          }
          $lienContinuer = $premiereSeq
            ? SITE_URL . '/sequence.php?id=' . $premiereSeq['id']
            : SITE_URL . '/dashboard.php';
          ?>
          <a href="<?= $lienContinuer ?>" class="btn-enroll enrolled">
            <i class="ti ti-player-play"></i>
            <?= $pct === 100 ? 'Revoir le cours' : 'Continuer' ?>
          </a>
          <p style="font-size:12px;color:var(--text-muted);text-align:center;margin-top:10px">
            <i class="ti ti-check" style="color:#16a34a"></i> Inscrit à ce cours
          </p>
        <?php elseif ($userId): ?>
          <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="inscrire">
            <button type="submit" class="btn-enroll">
              <i class="ti ti-user-plus"></i>
              <?= $course['type'] === 'gratuit' ? 'S\'inscrire gratuitement' : 'S\'inscrire — ' . fcfa((float)$course['prix']) ?>
            </button>
          </form>
        <?php else: ?>
          <a href="<?= SITE_URL ?>/register.php" class="btn-enroll">
            <i class="ti ti-user-plus"></i> Créer un compte gratuit
          </a>
          <p style="font-size:12px;color:var(--text-muted);text-align:center;margin-top:8px">
            Déjà inscrit ? <a href="<?= SITE_URL ?>/login.php" style="color:var(--primary)">Se connecter</a>
          </p>
        <?php endif; ?>

        <!-- Infos rapides -->
        <ul style="margin-top:16px;padding:0;list-style:none;display:flex;flex-direction:column;gap:8px">
          <li style="font-size:12px;color:var(--text-muted);display:flex;align-items:center;gap:6px">
            <i class="ti ti-infinity" style="color:var(--primary)"></i> Accès illimité après inscription
          </li>
          <?php if ($course['certificat']): ?>
          <li style="font-size:12px;color:var(--text-muted);display:flex;align-items:center;gap:6px">
            <i class="ti ti-certificate" style="color:#BA7517"></i> Certificat de complétion
          </li>
          <?php endif; ?>
          <li style="font-size:12px;color:var(--text-muted);display:flex;align-items:center;gap:6px">
            <i class="ti ti-device-mobile" style="color:#16a34a"></i> Accessible sur mobile
          </li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- ══ BODY ══ -->
<div class="course-body">
  <div class="course-main">

    <!-- Description complète -->
    <?php if ($course['description']): ?>
    <div style="margin-bottom:32px">
      <h2 class="course-section-title"><i class="ti ti-info-circle" style="color:var(--primary)"></i> À propos de ce cours</h2>
      <div style="font-size:14px;line-height:1.7;color:var(--text)">
        <?= $course['description'] /* HTML depuis éditeur */ ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Contenu du cours — Accordéon -->
    <div style="margin-bottom:32px">
      <h2 class="course-section-title">
        <i class="ti ti-layout-list" style="color:var(--primary)"></i>
        Contenu du cours
        <span style="font-size:13px;font-weight:400;color:var(--text-muted)">
          — <?= count($modules) ?> module<?= count($modules)!=1?'s':'' ?>,
          <?= $nbTotal ?> séquence<?= $nbTotal!=1?'s':'' ?>
        </span>
      </h2>

      <?php if (empty($modules)): ?>
        <p style="color:var(--text-muted);font-size:14px">Le contenu sera disponible prochainement.</p>
      <?php else: ?>
      <div class="modules-list">
        <?php foreach ($modulesAvecSeq as $idx => $ma): ?>
        <?php $mod = $ma['module']; $seqs = $ma['sequences']; $completees = $ma['completees']; ?>
        <div class="module-item <?= $idx === 0 ? 'open' : '' ?>" id="mod-<?= $mod['id'] ?>">
          <div class="module-header" onclick="toggleModule(<?= $mod['id'] ?>)">
            <div class="module-num"><?= $idx + 1 ?></div>
            <div class="module-header-info">
              <div class="module-header-title"><?= h($mod['titre']) ?></div>
              <div class="module-header-meta">
                <?= count($seqs) ?> séquence<?= count($seqs)!=1?'s':'' ?>
                <?php if ($mod['duree_min']): ?>
                  · <?= $mod['duree_min'] ?> min
                <?php endif; ?>
                <?php if ($userId && count($seqs) > 0): ?>
                  · <span style="color:#16a34a"><?= count($completees) ?>/<?= count($seqs) ?> complétée<?= count($completees)!=1?'s':'' ?></span>
                <?php endif; ?>
              </div>
            </div>
            <i class="ti ti-chevron-down module-chevron"></i>
          </div>
          <div class="module-sequences">
            <?php if (empty($seqs)): ?>
              <div style="padding:16px 20px;font-size:13px;color:var(--text-muted)">
                Aucune séquence pour ce module.
              </div>
            <?php else: ?>
              <?php foreach ($seqs as $s): ?>
              <?php $done = in_array($s['id'], $completees); ?>
              <?php $href = $inscrit
                ? SITE_URL . '/sequence.php?id=' . $s['id']
                : SITE_URL . '/register.php'; ?>
              <a href="<?= $href ?>" class="seq-item">
                <div class="seq-check <?= $done ? 'done' : ($inscrit ? '' : 'locked') ?>">
                  <?php if ($done): ?>
                    <i class="ti ti-check"></i>
                  <?php elseif (!$inscrit): ?>
                    <i class="ti ti-lock" style="font-size:10px"></i>
                  <?php else: ?>
                    <i class="ti ti-player-play" style="font-size:10px;color:var(--primary)"></i>
                  <?php endif; ?>
                </div>
                <span class="seq-title"><?= h($s['titre']) ?></span>
                <?php if ($s['duree_min'] ?? 0): ?>
                  <span class="seq-duration"><i class="ti ti-clock"></i> <?= $s['duree_min'] ?> min</span>
                <?php endif; ?>
              </a>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

  </div><!-- /course-main -->

  <!-- Colonne droite desktop (vide sur mobile, card sticky dans le hero) -->
  <div class="course-side-desktop" style="display:block">
  </div>

</div><!-- /course-body -->

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
function toggleModule(id) {
  const item = document.getElementById('mod-' + id);
  item.classList.toggle('open');
}
</script>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>