<?php
// ============================================================
//  admin/certificates.php — Liste des certificats émis
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

sendSecurityHeaders();
reqAdmin();

$pdo = getPDO();

// Révoquer un certificat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['revoke'])) {
    verifierCSRF();
    $certId = (int)$_POST['cert_id'];
    $pdo->prepare('DELETE FROM certificates WHERE id = ?')->execute([$certId]);
    logAction('certificate_revoked', 'Certificat #' . $certId . ' révoqué');
    redirect(SITE_URL . '/admin/certificates.php', 'Certificat révoqué.', 'success');
}

// Filtres
$filterCourse = (int)($_GET['course_id'] ?? 0);
$filterUser   = trim($_GET['search'] ?? '');

$where  = ['1=1'];
$params = [];

if ($filterCourse) {
    $where[]  = 'c.course_id = ?';
    $params[] = $filterCourse;
}
if ($filterUser) {
    $where[]  = '(u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)';
    $like     = '%' . $filterUser . '%';
    $params   = array_merge($params, [$like, $like, $like]);
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM certificates c JOIN users u ON u.id = c.user_id $whereSQL");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

[$offset, $pages, $pageCourante] = paginer($total, 20);

$stmt = $pdo->prepare(
    "SELECT c.*, u.nom, u.prenom, u.email, co.titre AS cours_titre
     FROM certificates c
     JOIN users u ON u.id = c.user_id
     JOIN courses co ON co.id = c.course_id
     $whereSQL
     ORDER BY c.delivre_le DESC
     LIMIT 20 OFFSET $offset"
);
$stmt->execute($params);
$certs = $stmt->fetchAll();

$courses = $pdo->query('SELECT id, titre FROM courses ORDER BY titre')->fetchAll();

$pageTitle = 'Certificats';
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
    <h1 class="admin-page-title"><i class="ti ti-certificate"></i> Certificats émis</h1>
    <div style="font-size:13px;color:var(--text-muted)"><?= $total ?> au total</div>
  </div>
  <div class="admin-content">
    <?= flash() ?>

    <!-- Filtres -->
    <form method="GET" style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap">
      <input type="text" name="search" value="<?= h($filterUser) ?>" placeholder="Nom, prénom ou email..."
             style="padding:8px 12px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:13px;min-width:200px">
      <select name="course_id" style="padding:8px 12px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:13px">
        <option value="">Tous les cours</option>
        <?php foreach ($courses as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $filterCourse == $c['id'] ? 'selected' : '' ?>><?= h($c['titre']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn-primary" style="font-size:13px">Filtrer</button>
      <?php if ($filterCourse || $filterUser): ?><a href="certificates.php" style="font-size:13px;color:var(--text-muted);align-self:center">Réinitialiser</a><?php endif; ?>
    </form>

    <div style="background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:12px;overflow:hidden">
      <table style="width:100%;border-collapse:collapse">
        <thead style="background:#f9fafb">
          <tr>
            <th style="padding:12px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--text-muted)">Étudiant</th>
            <th style="padding:12px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--text-muted)">Cours</th>
            <th style="padding:12px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--text-muted)">Code unique</th>
            <th style="padding:12px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--text-muted)">Délivré le</th>
            <th style="padding:12px 16px;text-align:center;font-size:12px;font-weight:600;color:var(--text-muted)">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($certs)): ?>
            <tr><td colspan="5" style="padding:40px;text-align:center;color:var(--text-muted)">Aucun certificat.</td></tr>
          <?php else: ?>
            <?php foreach ($certs as $cert): ?>
              <tr style="border-top:1px solid var(--border,#e5e7eb)">
                <td style="padding:12px 16px">
                  <div style="font-weight:500;font-size:13px"><?= h($cert['prenom'] . ' ' . $cert['nom']) ?></div>
                  <div style="font-size:11px;color:var(--text-muted)"><?= h($cert['email']) ?></div>
                </td>
                <td style="padding:12px 16px;font-size:13px"><?= h($cert['cours_titre']) ?></td>
                <td style="padding:12px 16px;font-family:monospace;font-size:11px;color:var(--text-muted)"><?= h($cert['code_unique']) ?></td>
                <td style="padding:12px 16px;font-size:12px;color:var(--text-muted)"><?= date('d/m/Y', strtotime($cert['delivre_le'])) ?></td>
                <td style="padding:12px 16px;text-align:center">
                  <div style="display:flex;gap:6px;justify-content:center">
                    <a href="<?= SITE_URL ?>/verify_certificate.php?code=<?= h($cert['code_unique']) ?>" target="_blank"
                       class="btn-outline" style="font-size:12px;padding:5px 10px">
                      <i class="ti ti-external-link"></i> Vérifier
                    </a>
                    <form method="POST" onsubmit="return confirm('Révoquer ce certificat ? Cette action est irréversible.')">
                      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                      <input type="hidden" name="cert_id" value="<?= $cert['id'] ?>">
                      <button type="submit" name="revoke" style="background:#FEE2E2;border:1px solid #FECACA;border-radius:6px;padding:5px 10px;cursor:pointer;font-size:12px;color:#991B1B">
                        <i class="ti ti-ban"></i> Révoquer
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($pages > 1): ?>
      <div style="display:flex;gap:6px;justify-content:center;margin-top:16px">
        <?php for ($p = 1; $p <= $pages; $p++): ?>
          <a href="?page=<?= $p ?><?= $filterCourse ? '&course_id='.$filterCourse : '' ?><?= $filterUser ? '&search='.urlencode($filterUser) : '' ?>"
             style="padding:6px 12px;border-radius:6px;font-size:13px;text-decoration:none;<?= $p === $pageCourante ? 'background:var(--primary,#6C47D4);color:#fff' : 'background:#f3f4f6;color:var(--text)' ?>"><?= $p ?></a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
