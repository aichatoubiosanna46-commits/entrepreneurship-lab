<?php
// ============================================================
//  admin/badges.php — Gestion des badges
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

sendSecurityHeaders();
reqAdmin();

$pdo = getPDO();

// Attribution manuelle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['award_badge'])) {
    verifierCSRF();
    $userId  = (int)$_POST['user_id'];
    $badgeId = (int)$_POST['badge_id'];
    if ($userId && $badgeId) {
        awardBadge($userId, $badgeId);
        redirect(SITE_URL . '/admin/badges.php', 'Badge attribué.', 'success');
    }
}

// Suppression badge
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_badge'])) {
    verifierCSRF();
    $badgeId = (int)$_POST['badge_id'];
    $pdo->prepare('DELETE FROM badges WHERE id = ?')->execute([$badgeId]);
    redirect(SITE_URL . '/admin/badges.php', 'Badge supprimé.', 'success');
}

$badges  = $pdo->query('SELECT b.*, (SELECT COUNT(*) FROM user_badges ub WHERE ub.badge_id = b.id) AS nb_obtenus FROM badges b ORDER BY b.created_at DESC')->fetchAll();
$users   = $pdo->query('SELECT id, nom, prenom, email FROM users WHERE actif = 1 ORDER BY nom')->fetchAll();

$pageTitle = 'Gestion des badges';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $pageTitle ?> — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
</head>
<body class="admin-layout">
<?php require_once __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <h1 class="admin-page-title"><i class="ti ti-award"></i> Badges & Gamification</h1>
    <a href="badge_add.php" class="btn-primary"><i class="ti ti-plus"></i> Nouveau badge</a>
  </div>
  <div class="admin-content">
    <?= flash() ?>

    <!-- Grille badges -->
    <?php if (empty($badges)): ?>
      <div style="text-align:center;padding:60px;color:var(--text-muted)">
        <i class="ti ti-award" style="font-size:48px;display:block;margin-bottom:12px;opacity:.4"></i>
        Aucun badge créé. <a href="badge_add.php">Créer le premier badge</a>
      </div>
    <?php else: ?>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px;margin-bottom:32px">
        <?php foreach ($badges as $b): ?>
          <div style="background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:20px">
            <div style="display:flex;gap:12px;align-items:flex-start">
              <?php if ($b['image']): ?>
                <img src="<?= SITE_URL ?>/assets/uploads/<?= h($b['image']) ?>" alt="" style="width:48px;height:48px;object-fit:contain;border-radius:8px">
              <?php else: ?>
                <div style="width:48px;height:48px;border-radius:8px;background:var(--primary-light);display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0">
                  <i class="ti ti-award" style="color:var(--primary-mid)"></i>
                </div>
              <?php endif; ?>
              <div style="flex:1">
                <div style="font-weight:600;font-size:14px"><?= h($b['nom']) ?></div>
                <div style="font-size:12px;color:var(--text-muted);margin-top:2px"><?= h($b['description'] ?? '') ?></div>
                <div style="font-size:11px;margin-top:6px;display:flex;gap:8px;flex-wrap:wrap">
                  <span style="background:#EEF2FF;color:#4338CA;padding:2px 8px;border-radius:99px"><?= h($b['condition_type']) ?></span>
                  <span style="color:var(--text-muted)"><?= $b['nb_obtenus'] ?> obtenu(s)</span>
                  <?php if (!$b['actif']): ?>
                    <span style="background:#FEE2E2;color:#991B1B;padding:2px 8px;border-radius:99px">Inactif</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <div style="display:flex;gap:8px;margin-top:16px">
              <a href="badge_add.php?edit=<?= $b['id'] ?>" class="btn-outline" style="font-size:12px;flex:1;text-align:center">
                <i class="ti ti-pencil"></i> Modifier
              </a>
              <form method="POST" style="flex:1" onsubmit="return confirm('Supprimer ce badge ?')">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="badge_id" value="<?= $b['id'] ?>">
                <button type="submit" name="delete_badge" class="btn-outline-danger btn-sm btn-full">
                  <i class="ti ti-trash"></i> Supprimer
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Attribution manuelle -->
      <div style="background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:24px">
        <h3 style="font-size:16px;font-weight:600;margin:0 0 16px"><i class="ti ti-user-star"></i> Attribution manuelle</h3>
        <form method="POST" style="display:flex;gap:12px;flex-wrap:wrap">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <select name="user_id" required style="padding:8px 12px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:13px;min-width:200px">
            <option value="">Sélectionner un étudiant</option>
            <?php foreach ($users as $u): ?>
              <option value="<?= $u['id'] ?>"><?= h($u['prenom'] . ' ' . $u['nom']) ?> (<?= h($u['email']) ?>)</option>
            <?php endforeach; ?>
          </select>
          <select name="badge_id" required style="padding:8px 12px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:13px;min-width:200px">
            <option value="">Sélectionner un badge</option>
            <?php foreach ($badges as $b): ?>
              <option value="<?= $b['id'] ?>"><?= h($b['nom']) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" name="award_badge" class="btn-primary" style="font-size:13px">
            <i class="ti ti-award"></i> Attribuer
          </button>
        </form>
      </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
