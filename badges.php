<?php
// ============================================================
//  badges.php — Grille des badges & XP (vue étudiant)
// ============================================================
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

sendSecurityHeaders();
reqConnecte();

$pdo    = getPDO();
$userId = $_SESSION['user_id'];

// Tous les badges avec info si obtenus
$stmt = $pdo->prepare(
    'SELECT b.*,
            ub.obtenu_le,
            (ub.id IS NOT NULL) AS obtenu
     FROM badges b
     LEFT JOIN user_badges ub ON ub.badge_id = b.id AND ub.user_id = ?
     WHERE b.actif = 1
     ORDER BY obtenu DESC, b.created_at ASC'
);
$stmt->execute([$userId]);
$badges = $stmt->fetchAll();

$xpTotal = getUserXPTotal($userId);
$rank    = getUserRank($userId);

// Historique XP récent
$xpStmt = $pdo->prepare(
    'SELECT * FROM user_xp WHERE user_id = ? ORDER BY created_at DESC LIMIT 10'
);
$xpStmt->execute([$userId]);
$xpHistory = $xpStmt->fetchAll();

$pageTitle = 'Mes badges & XP';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container" style="max-width:1100px;margin:0 auto;padding:32px 16px">

  <!-- En-tête XP -->
  <div style="background:linear-gradient(135deg,#6C47D4,#4C1D95);border-radius:16px;padding:32px;color:#fff;margin-bottom:32px;display:flex;gap:32px;flex-wrap:wrap;align-items:center">
    <div>
      <div style="font-size:13px;opacity:.8;margin-bottom:4px">Mes points d'expérience</div>
      <div style="font-size:42px;font-weight:700"><?= number_format($xpTotal) ?> XP</div>
    </div>
    <div style="width:1px;background:rgba(255,255,255,.2);align-self:stretch"></div>
    <div>
      <div style="font-size:13px;opacity:.8;margin-bottom:4px">Classement global</div>
      <div style="font-size:32px;font-weight:700">#<?= $rank ?></div>
    </div>
    <div style="width:1px;background:rgba(255,255,255,.2);align-self:stretch"></div>
    <div>
      <div style="font-size:13px;opacity:.8;margin-bottom:4px">Badges obtenus</div>
      <div style="font-size:32px;font-weight:700"><?= count(array_filter($badges, fn($b) => $b['obtenu'])) ?></div>
    </div>
    <div style="margin-left:auto">
      <a href="<?= SITE_URL ?>/leaderboard.php" style="background:rgba(255,255,255,.2);color:#fff;text-decoration:none;padding:10px 20px;border-radius:8px;font-size:14px;font-weight:500;display:inline-flex;align-items:center;gap:8px">
        <i class="ti ti-trophy"></i> Classement
      </a>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;align-items:start" class="badges-grid-layout">

    <!-- Grille badges -->
    <div>
      <h2 style="font-size:18px;font-weight:600;margin-bottom:16px">Tous les badges</h2>
      <?php if (empty($badges)): ?>
        <p style="color:var(--text-muted)">Aucun badge disponible pour le moment.</p>
      <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:16px">
          <?php foreach ($badges as $b): ?>
            <div style="background:#fff;border:2px solid <?= $b['obtenu'] ? '#6C47D4' : '#e5e7eb' ?>;border-radius:12px;padding:20px;text-align:center;position:relative;transition:.2s"
                 title="<?= h($b['description'] ?? '') ?>">
              <?php if ($b['obtenu']): ?>
                <span style="position:absolute;top:-8px;right:-8px;background:#6C47D4;color:#fff;border-radius:50%;width:24px;height:24px;display:flex;align-items:center;justify-content:center;font-size:12px">
                  <i class="ti ti-check"></i>
                </span>
              <?php endif; ?>

              <?php if ($b['image']): ?>
                <img src="<?= SITE_URL ?>/assets/uploads/<?= h($b['image']) ?>"
                     alt="<?= h($b['nom']) ?>"
                     style="width:64px;height:64px;object-fit:contain;<?= $b['obtenu'] ? '' : 'filter:grayscale(1);opacity:.4' ?>">
              <?php else: ?>
                <div style="width:64px;height:64px;border-radius:50%;background:<?= $b['obtenu'] ? '#EDE9FE' : '#f3f4f6' ?>;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;font-size:32px">
                  <i class="ti ti-award" style="color:<?= $b['obtenu'] ? '#6C47D4' : '#9ca3af' ?>"></i>
                </div>
              <?php endif; ?>

              <div style="font-weight:500;font-size:13px;margin-top:8px;color:<?= $b['obtenu'] ? 'var(--text)' : '#9ca3af' ?>">
                <?= h($b['nom']) ?>
              </div>

              <?php if ($b['obtenu'] && $b['obtenu_le']): ?>
                <div style="font-size:11px;color:var(--text-muted);margin-top:4px">
                  <?= date('d/m/Y', strtotime($b['obtenu_le'])) ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Historique XP -->
    <div>
      <h2 style="font-size:18px;font-weight:600;margin-bottom:16px">Historique XP récent</h2>
      <div style="background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:12px;overflow:hidden">
        <?php if (empty($xpHistory)): ?>
          <div style="padding:24px;text-align:center;color:var(--text-muted);font-size:13px">
            Aucun XP gagné pour le moment
          </div>
        <?php else: ?>
          <?php foreach ($xpHistory as $i => $xp): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;border-bottom:<?= $i < count($xpHistory)-1 ? '1px solid var(--border,#e5e7eb)' : 'none' ?>">
              <div>
                <div style="font-size:13px;font-weight:500"><?= h($xp['description'] ?: $xp['source']) ?></div>
                <div style="font-size:11px;color:var(--text-muted)"><?= date('d/m/Y', strtotime($xp['created_at'])) ?></div>
              </div>
              <div style="font-weight:600;color:<?= $xp['points'] > 0 ? '#27500A' : '#993C1D' ?>">
                +<?= $xp['points'] ?> XP
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<style>
@media(max-width:768px) {
  .badges-grid-layout { grid-template-columns: 1fr !important; }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
