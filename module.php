<?php
// module.php — Page publique d'un cours
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pdo  = getPDO();

// Vérifier expiration d'accès
if (estConnecte()) {
    try {
        $slug = $_GET['slug'] ?? '';
        $expStmt = $pdo->prepare(
            'SELECT e.expires_at FROM enrollments e
             JOIN courses c ON c.id = e.course_id
             WHERE e.user_id = ? AND c.slug = ? AND e.statut = "actif" LIMIT 1'
        );
        $expStmt->execute([$_SESSION['user_id'], $slug]);
        $expRow = $expStmt->fetch();
        if ($expRow && !empty($expRow['expires_at']) && strtotime($expRow['expires_at']) < time()) {
            redirect(SITE_URL . '/payment.php', 'Votre accès à cette formation a expiré. Renouvelez votre abonnement.', 'error');
        }
    } catch (Exception $e) {}
}
$slug = trim($_GET['slug'] ?? '');
if (!$slug) { header('Location: ' . SITE_URL . '/index.php'); exit; }

// Récupérer le cours
$stmt = $pdo->prepare(
    'SELECT m.*, c.nom as categorie, c.icone as cat_icone, c.couleur as cat_couleur, m.prerequis_course_id, m.redirect_completion, m.completion_rule
     FROM courses m
     JOIN categories c ON c.id = m.category_id
     WHERE m.slug = ? AND m.actif = 1'
);
$stmt->execute([$slug]);
$course = $stmt->fetch();
if (!$course) { http_response_code(404); echo '<p style="font-family:sans-serif;text-align:center;padding:60px">Formation introuvable. <a href="'.SITE_URL.'/search.php">Retour aux formations</a></p>'; exit; }

// Vérifier restriction par tag
if ($userId && !empty($course['tag_requis_id'] ?? null)) {
    $tagCheck = $pdo->prepare(
        'SELECT id FROM user_tags WHERE user_id=? AND tag_id=?'
    );
    $tagCheck->execute([$userId, $course['tag_requis_id']]);
    if (!$tagCheck->fetch()) {
        http_response_code(403);
        echo '<div style="font-family:sans-serif;text-align:center;padding:80px">
            <h2>Accès restreint</h2>
            <p>Vous n\'avez pas les droits nécessaires pour accéder à cette formation.</p>
            <a href="' . SITE_URL . '/catalogue.php">Retour au catalogue</a>
        </div>';
        exit;
    }
}

// Vérifier prérequis entre cours
if ($userId && !empty($course['prerequis_course_id'])) {
    $prereqPct = progressionCours($userId, $course['prerequis_course_id']);
    if ($prereqPct < 100) {
        $prereqCourse = $pdo->prepare('SELECT titre, slug FROM courses WHERE id=?');
        $prereqCourse->execute([$course['prerequis_course_id']]);
        $prereqCourse = $prereqCourse->fetch();
        ?>
        <!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">
        <title>Prérequis requis — <?= SITE_NAME ?></title>
        <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
        <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
        </head><body>
        <?php include __DIR__ . '/includes/header.php'; ?>
        <div style="max-width:500px;margin:80px auto;padding:0 24px;text-align:center">
          <div style="background:#fff;border-radius:20px;padding:40px;border:1px solid #e5e7eb;box-shadow:0 4px 24px rgba(0,0,0,.06)">
            <div style="font-size:48px;margin-bottom:16px">🔒</div>
            <h2 style="font-size:20px;font-weight:800;color:#1A1A18;margin-bottom:10px">Cours prérequis non complété</h2>
            <p style="font-size:14px;color:#6b7280;margin-bottom:20px;line-height:1.7">
              Pour accéder à <strong><?= h($course['titre']) ?></strong>, tu dois d'abord compléter le cours prérequis :
            </p>
            <div style="background:#FBE3DA;border-radius:12px;padding:16px;margin-bottom:20px">
              <div style="font-size:15px;font-weight:700;color:#1A1A18"><?= h($prereqCourse['titre'] ?? '') ?></div>
              <div style="font-size:13px;color:#C04A22;margin-top:4px">Progression actuelle : <?= $prereqPct ?>%</div>
              <div style="height:6px;background:#e5e7eb;border-radius:3px;margin-top:8px;overflow:hidden">
                <div style="height:100%;width:<?= $prereqPct ?>%;background:#D85A30;border-radius:3px"></div>
              </div>
            </div>
            <a href="<?= SITE_URL ?>/module.php?slug=<?= h($prereqCourse['slug'] ?? '') ?>"
               style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:#D85A30;color:#1A1A18;border-radius:10px;font-size:14px;font-weight:700;text-decoration:none">
              <i class="ti ti-arrow-right"></i> Continuer le prérequis
            </a>
          </div>
        </div>
        <?php include __DIR__ . '/includes/footer.php'; ?>
        </body></html>
        <?php exit;
    }
}

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
$utm_source   = $_COOKIE['utm_source']   ?? ($_GET['utm_source']   ?? null);
$utm_medium   = $_COOKIE['utm_medium']   ?? ($_GET['utm_medium']   ?? null);
$utm_campaign = $_COOKIE['utm_campaign'] ?? ($_GET['utm_campaign'] ?? null);
$pdo->prepare(
    'INSERT IGNORE INTO enrollments (user_id, course_id, statut, utm_source, utm_medium, utm_campaign) VALUES (?, ?, "actif", ?, ?, ?)'
)->execute([$userId, $course['id'], $utm_source, $utm_medium, $utm_campaign]);
// Déclencher automation inscription cours
triggerAutomation('course_enrolled', $userId, $course['id']);
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
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
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
  background: var(--amber, #6C47D4); color: #fff;
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
  height: 100%; background: linear-gradient(90deg, #534AB7, #6C47D4);
  border-radius: 99px; transition: width .4s;
}

/* ── Contenu principal ── */
.course-body { max-width: 1100px; margin: 0 auto; padding: 40px 24px; display: grid; grid-template-columns: 1fr 340px; gap: 40px; align-items: start; }
.course-main { min-width: 0; }

/* ── Sections modules en cartes ── */
.modules-sections { display: flex; flex-direction: column; gap: 36px; }

.module-section-title {
  font-size: 20px; font-weight: 700; color: #1a1a2e;
  padding-bottom: 10px; border-bottom: 3px solid #1a1a2e;
  margin-bottom: 20px; display: flex; align-items: center; gap: 10px;
}
.module-section-title .mod-count {
  font-size: 12px; font-weight: 500; color: #6b7280;
  background: #f3f4f6; border-radius: 20px; padding: 2px 10px;
}

/* Grille de cartes */
.seq-cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 14px;
}
.seq-card {
  position: relative;
  background: #f8f9fa; border: 1px solid #e9ecef;
  border-radius: 12px; padding: 20px 16px 16px;
  text-decoration: none; color: #111;
  display: flex; flex-direction: column; align-items: center;
  text-align: center; gap: 12px;
  transition: box-shadow .2s, transform .15s;
  cursor: pointer;
}
.seq-card:hover { box-shadow: 0 4px 18px rgba(0,0,0,.1); transform: translateY(-2px); }
.seq-card.done   { background: #f0fdf4; border-color: #86efac; }
.seq-card.locked { opacity: .65; }

/* Badge statut coin haut droite */
.seq-badge {
  position: absolute; top: 10px; right: 10px;
  width: 26px; height: 26px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; font-weight: 700;
}
.seq-badge.done   { background: #16a34a; color: #fff; }
.seq-badge.todo   { background: #e5e7eb; color: #9ca3af; }
.seq-badge.locked { background: #e5e7eb; color: #9ca3af; }

/* Icone centrale */
.seq-icon-circle {
  width: 72px; height: 72px; border-radius: 50%;
  background: #e8f4f8; border: 2px dashed #c7e2ec;
  display: flex; align-items: center; justify-content: center;
  font-size: 30px; flex-shrink: 0;
}
.seq-icon-circle.done-circle { background: #dcfce7; border-color: #86efac; }

.seq-card-title {
  font-size: 12px; font-weight: 500; color: #374151;
  line-height: 1.4; word-break: break-word;
}
.seq-card-meta { font-size: 11px; color: #9ca3af; }

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
          <span><i class="ti ti-certificate" style="color:#6C47D4"></i> Certificat inclus</span>
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
            <i class="ti ti-certificate" style="color:#6C47D4"></i> Certificat de complétion
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

    <!-- Contenu du cours — Cartes par module -->
    <div style="margin-bottom:32px">


      <?php if (empty($modules)): ?>
        <p style="color:var(--text-muted);font-size:14px">Le contenu sera disponible prochainement.</p>
      <?php else: ?>

      
<?php
// Aperçu gratuit de la première séquence (invité)
$firstSeqId = null;
if (!$inscrit && !empty($modulesAvecSeq)) {
    foreach ($modulesAvecSeq as $ma) {
        if (!empty($ma['sequences'])) {
            $firstSeqId = $ma['sequences'][0]['id'];
            break;
        }
    }
}
?>
<?php if (!$inscrit && $firstSeqId && $course['type'] === 'gratuit'): ?>
<div style="background:#EDE9FE;border:1px solid #c4b5fd;border-radius:12px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;gap:12px">
  <i class="ti ti-eye" style="font-size:20px;color:#7c3aed"></i>
  <div style="flex:1">
    <div style="font-size:13px;font-weight:700;color:#4c1d95">Aperçu gratuit disponible</div>
    <div style="font-size:12px;color:#6b7280">Découvrez la première séquence sans créer de compte</div>
  </div>
  <a href="<?= SITE_URL ?>/sequence.php?id=<?= $firstSeqId ?>&preview=1"
     style="padding:8px 14px;background:#7c3aed;color:#fff;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none">
    Voir l'aperçu
  </a>
</div>
<?php endif; ?>

      <!-- Cards des modules -->
      <div class="seq-cards-grid" style="grid-template-columns:repeat(auto-fill,minmax(200px,1fr));margin-bottom:40px">
        <?php foreach ($modulesAvecSeq as $idx => $ma): ?>
        <?php
          $mod = $ma['module'];
          $seqs = $ma['sequences'];
          $completees = $ma['completees'];
          $nbSeqs = count($seqs);
          $nbDone = count($completees);
          $pctMod = $nbSeqs > 0 ? round($nbDone/$nbSeqs*100) : 0;
          $allDone = $nbSeqs > 0 && $nbDone === $nbSeqs;
          // Lien vers première séquence non complétée ou première séquence
          $firstSeq = null;
          foreach ($seqs as $s) {
            if (!in_array($s['id'], $completees)) { $firstSeq = $s; break; }
          }
          if (!$firstSeq && !empty($seqs)) $firstSeq = $seqs[0];
          $href = $inscrit && $firstSeq ? SITE_URL.'/sequence.php?id='.$firstSeq['id'] : ($inscrit ? '#' : SITE_URL.'/register.php');
        ?>
        <a href="<?= $href ?>" class="seq-card <?= $allDone ? 'done' : '' ?>" style="min-height:180px">
          <!-- Badge statut -->
          <div class="seq-badge <?= $allDone ? 'done' : 'todo' ?>">
            <i class="ti <?= $allDone ? 'ti-check' : 'ti-circle-check' ?>" style="font-size:14px"></i>
          </div>

          <!-- Icone module -->
          <div class="seq-icon-circle <?= $allDone ? 'done-circle' : '' ?>">
            <i class="ti ti-layout-list" style="color:#6C47D4;font-size:28px"></i>
          </div>

          <!-- Titre -->
          <div class="seq-card-title" style="font-weight:600">
            Module <?= $idx+1 ?><br>
            <span style="font-weight:400;font-size:11px;color:#6b7280"><?= h($mod['titre']) ?></span>
          </div>

          <!-- Progression -->
          <?php if ($nbSeqs > 0): ?>
          <div style="width:100%;padding:0 4px">
            <div style="height:4px;background:#e5e7eb;border-radius:99px;overflow:hidden">
              <div style="height:100%;width:<?= $pctMod ?>%;background:<?= $allDone ? '#16a34a' : '#6C47D4' ?>;border-radius:99px"></div>
            </div>
            <div style="font-size:10px;color:#9ca3af;text-align:center;margin-top:4px">
              <?= $nbDone ?>/<?= $nbSeqs ?> séquence<?= $nbSeqs>1?'s':'' ?>
            </div>
          </div>
          <?php endif; ?>
        </a>
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

<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>