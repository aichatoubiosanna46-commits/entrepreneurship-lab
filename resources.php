<?php
// resources.php — Bibliothèque de ressources entrepreneuriales
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getPDO();
$userId = $_SESSION['user_id'] ?? null;
$type = trim($_GET['type'] ?? '');

$sql = 'SELECT * FROM library_resources WHERE actif = 1';
$params = [];
if ($type) { $sql .= ' AND type = ?'; $params[] = $type; }
$sql .= ' ORDER BY created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$resources = $stmt->fetchAll();

$types = [
    'business_plan' => ['label'=>'Business Plan','icon'=>'ti-file-analytics','color'=>'#534AB7'],
    'social_media'  => ['label'=>'Réseaux sociaux','icon'=>'ti-brand-instagram','color'=>'#E1306C'],
    'sales_script'  => ['label'=>'Scripts de vente','icon'=>'ti-speakerphone','color'=>'#BA7517'],
    'autre'         => ['label'=>'Autres ressources','icon'=>'ti-files','color'=>'#3B6D11'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bibliothèque — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/home.css">
<style>
.lib-wrap { max-width:1100px;margin:40px auto;padding:0 24px 80px; }
.lib-header { text-align:center;margin-bottom:40px; }
.lib-header h1 { font-size:32px;font-weight:800;margin:0 0 8px; }
.lib-header p { color:var(--text-muted,#6b7280);font-size:15px; }
.type-filters { display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin-bottom:32px; }
.type-btn { display:flex;align-items:center;gap:8px;padding:10px 20px;border-radius:12px;border:1px solid var(--border,#e5e7eb);font-size:13px;font-weight:600;text-decoration:none;color:var(--text,#111);background:#fff;transition:.15s; }
.type-btn:hover,.type-btn.active { border-color:transparent;color:#fff; }
.resources-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px; }
.res-card { background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:14px;padding:24px;display:flex;flex-direction:column;gap:14px; }
.res-icon { width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:24px; }
.res-title { font-size:15px;font-weight:700;line-height:1.3; }
.res-desc { font-size:13px;color:var(--text-muted,#6b7280);line-height:1.5;flex:1; }
.res-meta { display:flex;align-items:center;justify-content:space-between;font-size:12px;color:var(--text-muted,#6b7280); }
.res-download { display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;background:#534AB7;color:#fff;font-size:13px;font-weight:600;text-decoration:none; }
.res-download:hover { background:#3d369a; }
.lock-badge { display:inline-flex;align-items:center;gap:4px;padding:6px 12px;border-radius:8px;background:#f9fafb;color:var(--text-muted,#6b7280);font-size:12px;border:1px solid #e5e7eb; }
</style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<div class="lib-wrap">
  <div class="lib-header">
    <h1><i class="ti ti-library" style="color:#BA7517"></i> Bibliothèque de ressources</h1>
    <p>Business plans, templates réseaux sociaux, scripts de vente — tout pour booster votre entrepreneuriat</p>
  </div>

  <div class="type-filters">
    <a href="<?= SITE_URL ?>/resources.php" class="type-btn <?= !$type ? 'active' : '' ?>" style="<?= !$type ? 'background:#534AB7;border-color:#534AB7;color:#fff' : '' ?>">
      <i class="ti ti-grid-4x4"></i> Tout afficher
    </a>
    <?php foreach ($types as $key => $t): ?>
    <a href="<?= SITE_URL ?>/resources.php?type=<?= $key ?>" class="type-btn <?= $type===$key?'active':'' ?>"
       style="<?= $type===$key?"background:{$t['color']};border-color:{$t['color']};color:#fff":'' ?>">
      <i class="ti <?= $t['icon'] ?>"></i> <?= $t['label'] ?>
    </a>
    <?php endforeach; ?>
  </div>

  <?php if (empty($resources)): ?>
    <div style="text-align:center;padding:60px;color:var(--text-muted,#6b7280)">
      <i class="ti ti-files" style="font-size:48px;display:block;margin-bottom:12px"></i>
      <p>Aucune ressource disponible pour l'instant.</p>
    </div>
  <?php else: ?>
  <div class="resources-grid">
    <?php foreach ($resources as $r):
      $t = $types[$r['type']] ?? $types['autre'];
      $canDownload = $userId && true; // Logique d'accès selon abonnement
    ?>
    <div class="res-card">
      <div class="res-icon" style="background:<?= $t['color'] ?>22;color:<?= $t['color'] ?>">
        <i class="ti <?= $t['icon'] ?>"></i>
      </div>
      <div>
        <div class="res-title"><?= h($r['titre']) ?></div>
        <div style="font-size:11px;color:var(--text-muted,#6b7280);margin-top:3px"><?= $t['label'] ?></div>
      </div>
      <?php if ($r['description']): ?>
        <div class="res-desc"><?= h($r['description']) ?></div>
      <?php endif; ?>
      <div class="res-meta">
        <span><i class="ti ti-download"></i> <?= $r['downloads'] ?> téléchargements</span>
      </div>
      <?php if ($canDownload): ?>
        <a href="<?= SITE_URL ?>/assets/uploads/library/<?= h($r['fichier']) ?>" download class="res-download">
          <i class="ti ti-download"></i> Télécharger
        </a>
      <?php else: ?>
        <a href="<?= SITE_URL ?>/payment.php" class="lock-badge">
          <i class="ti ti-lock"></i> Abonnez-vous pour accéder
        </a>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
