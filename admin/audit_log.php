<?php
// ============================================================
//  admin/audit_log.php — Journal d'audit des actions
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

sendSecurityHeaders();
reqAdmin();

$pdo = getPDO();

// Filtres
$filterAction = trim($_GET['action'] ?? '');
$filterUser   = trim($_GET['user'] ?? '');
$filterDate   = trim($_GET['date'] ?? '');

$where  = ['1=1'];
$params = [];

if ($filterAction) {
    $where[]  = 'al.action LIKE ?';
    $params[] = '%' . $filterAction . '%';
}
if ($filterUser) {
    $where[]  = '(u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)';
    $like     = '%' . $filterUser . '%';
    $params   = array_merge($params, [$like, $like, $like]);
}
if ($filterDate) {
    $where[]  = 'DATE(al.created_at) = ?';
    $params[] = $filterDate;
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs al LEFT JOIN users u ON u.id = al.user_id $whereSQL");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

[$offset, $pages, $pageCourante] = paginer($total, 30);

$stmt = $pdo->prepare(
    "SELECT al.*, u.nom, u.prenom, u.email
     FROM audit_logs al
     LEFT JOIN users u ON u.id = al.user_id
     $whereSQL
     ORDER BY al.created_at DESC
     LIMIT 30 OFFSET $offset"
);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Actions distinctes pour le filtre
$actions = $pdo->query('SELECT DISTINCT action FROM audit_logs ORDER BY action')->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Journal d\'audit';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $pageTitle ?> — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
</head>
<body class="admin-layout">
<?php require_once __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <h1 class="admin-page-title"><i class="ti ti-shield-check"></i> Journal d'audit</h1>
    <div style="font-size:13px;color:var(--text-muted)"><?= $total ?> entrées</div>
  </div>
  <div class="admin-content">
    <?= flash() ?>

    <!-- Filtres -->
    <form method="GET" style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap">
      <select name="action" style="padding:8px 12px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:13px;min-width:180px">
        <option value="">Toutes les actions</option>
        <?php foreach ($actions as $a): ?>
          <option value="<?= h($a) ?>" <?= $filterAction === $a ? 'selected' : '' ?>><?= h($a) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="text" name="user" value="<?= h($filterUser) ?>" placeholder="Filtrer par utilisateur..."
             style="padding:8px 12px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:13px;min-width:180px">
      <input type="date" name="date" value="<?= h($filterDate) ?>"
             style="padding:8px 12px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:13px">
      <button type="submit" class="btn-primary" style="font-size:13px">Filtrer</button>
      <?php if ($filterAction || $filterUser || $filterDate): ?>
        <a href="audit_log.php" style="font-size:13px;color:var(--text-muted);align-self:center">Réinitialiser</a>
      <?php endif; ?>
    </form>

    <div style="background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:12px;overflow:hidden">
      <table style="width:100%;border-collapse:collapse;font-size:13px">
        <thead style="background:#f9fafb">
          <tr>
            <th style="padding:10px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--text-muted)">Date</th>
            <th style="padding:10px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--text-muted)">Action</th>
            <th style="padding:10px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--text-muted)">Utilisateur</th>
            <th style="padding:10px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--text-muted)">Détails</th>
            <th style="padding:10px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--text-muted)">IP</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($logs)): ?>
            <tr><td colspan="5" style="padding:40px;text-align:center;color:var(--text-muted)">Aucune entrée de log.</td></tr>
          <?php else: ?>
            <?php foreach ($logs as $log): ?>
              <?php
                $actionColor = match(true) {
                    str_contains($log['action'], 'login_success')  => '#EAF3DE',
                    str_contains($log['action'], 'failed')         => '#FAECE7',
                    str_contains($log['action'], 'delete')         => '#FEE2E2',
                    str_contains($log['action'], 'revoke')         => '#FEE2E2',
                    default                                         => '#EEF2FF'
                };
                $textColor = match(true) {
                    str_contains($log['action'], 'login_success')  => '#27500A',
                    str_contains($log['action'], 'failed')         => '#993C1D',
                    str_contains($log['action'], 'delete')         => '#991B1B',
                    str_contains($log['action'], 'revoke')         => '#991B1B',
                    default                                         => '#4338CA'
                };
              ?>
              <tr style="border-top:1px solid var(--border,#e5e7eb)">
                <td style="padding:10px 16px;font-size:11px;color:var(--text-muted);white-space:nowrap"><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                <td style="padding:10px 16px">
                  <span style="background:<?= $actionColor ?>;color:<?= $textColor ?>;font-size:11px;font-weight:600;padding:3px 10px;border-radius:99px;white-space:nowrap">
                    <?= h($log['action']) ?>
                  </span>
                </td>
                <td style="padding:10px 16px">
                  <?php if ($log['nom']): ?>
                    <div style="font-size:12px;font-weight:500"><?= h($log['prenom'] . ' ' . $log['nom']) ?></div>
                    <div style="font-size:10px;color:var(--text-muted)"><?= h($log['email'] ?? '') ?></div>
                  <?php else: ?>
                    <span style="color:#9ca3af;font-size:12px">Système</span>
                  <?php endif; ?>
                </td>
                <td style="padding:10px 16px;font-size:12px;color:var(--text-muted);max-width:300px">
                  <?= h(mb_substr($log['details'] ?? '', 0, 100)) ?><?= strlen($log['details'] ?? '') > 100 ? '...' : '' ?>
                </td>
                <td style="padding:10px 16px;font-family:monospace;font-size:11px;color:var(--text-muted)"><?= h($log['ip'] ?? '') ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($pages > 1): ?>
      <div style="display:flex;gap:6px;justify-content:center;margin-top:16px">
        <?php for ($p = 1; $p <= $pages; $p++): ?>
          <a href="?page=<?= $p ?><?= $filterAction ? '&action='.urlencode($filterAction) : '' ?><?= $filterUser ? '&user='.urlencode($filterUser) : '' ?><?= $filterDate ? '&date='.$filterDate : '' ?>"
             style="padding:6px 12px;border-radius:6px;font-size:13px;text-decoration:none;<?= $p === $pageCourante ? 'background:var(--primary);color:#fff' : 'background:#f3f4f6;color:var(--text)' ?>"><?= $p ?></a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
