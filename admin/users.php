<?php
// admin/users.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();

$pdo = getPDO();

// Recherche
$search = trim($_GET['q'] ?? '');
$where  = "WHERE 1=1";
$params = [];
if ($search) {
    $where  .= " AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ?)";
    $params  = array_fill(0, 3, '%' . $search . '%');
}

$total = $pdo->prepare("SELECT COUNT(*) FROM users $where");
$total->execute($params);
[$offset, $pages, $page] = paginer((int)$total->fetchColumn(), 15);

$stmt = $pdo->prepare("SELECT u.*, (SELECT COUNT(*) FROM inscriptions i WHERE i.user_id = u.id) as nb_cours
    FROM users u $where ORDER BY u.created_at DESC LIMIT 15 OFFSET $offset");
$stmt->execute($params);
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Utilisateurs — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/dashboard.css">
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<div class="admin-content">
  <div class="admin-topbar">
    <h1 class="admin-page-title">Utilisateurs</h1>
    <form method="GET" style="display:flex;gap:8px">
      <div class="input-icon-wrap" style="width:260px">
        <i class="ti ti-search input-icon" aria-hidden="true"></i>
        <input type="text" name="q" value="<?= h($search) ?>" placeholder="Rechercher...">
      </div>
      <button type="submit" class="btn-outline btn-sm">Chercher</button>
    </form>
  </div>

  <?= flash() ?>

  <div class="admin-card" style="padding:0;overflow:hidden">
    <table class="admin-table" style="margin:0">
      <thead>
        <tr><th>Utilisateur</th><th>Email</th><th>Téléphone</th><th>Cours</th><th>Statut</th><th>Inscrit le</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td>
            <div class="user-cell">
              <div class="user-avatar"><?= strtoupper(mb_substr($u['prenom'],0,1).mb_substr($u['nom'],0,1)) ?></div>
              <div>
                <div style="font-weight:500;font-size:13px"><?= h($u['prenom'].' '.$u['nom']) ?></div>
                <div style="font-size:11px;color:var(--text-muted)"><?= h($u['ville'] ?: '—') ?></div>
              </div>
            </div>
          </td>
          <td style="font-size:13px"><?= h($u['email']) ?></td>
          <td style="font-size:13px"><?= h($u['telephone'] ?: '—') ?></td>
          <td><strong><?= $u['nb_cours'] ?></strong></td>
          <td>
            <?= $u['actif']
              ? '<span class="badge badge-success">Actif</span>'
              : '<span class="badge badge-danger">Bloqué</span>' ?>
          </td>
          <td style="font-size:12px;color:var(--text-muted)"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
          <td>
            <div style="display:flex;gap:6px">
              <a href="<?= SITE_URL ?>/admin/user_edit.php?id=<?= $u['id'] ?>" class="btn-icon" title="Modifier">
                <i class="ti ti-edit" aria-hidden="true"></i>
              </a>
              <?php if ($u['actif']): ?>
                <a href="<?= SITE_URL ?>/admin/user_delete.php?id=<?= $u['id'] ?>&action=block&csrf=<?= csrfToken() ?>"
                   class="btn-icon btn-icon-danger" title="Bloquer"
                   onclick="return confirm('Bloquer cet utilisateur ?')">
                  <i class="ti ti-ban" aria-hidden="true"></i>
                </a>
              <?php else: ?>
                <a href="<?= SITE_URL ?>/admin/user_delete.php?id=<?= $u['id'] ?>&action=unblock&csrf=<?= csrfToken() ?>"
                   class="btn-icon" title="Débloquer">
                  <i class="ti ti-circle-check" aria-hidden="true"></i>
                </a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($users)): ?>
        <tr><td colspan="7" style="text-align:center;padding:32px;color:var(--text-muted)">Aucun utilisateur trouvé.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($pages > 1): ?>
  <div style="display:flex;justify-content:center;gap:6px;margin-top:16px">
    <?php for ($p = 1; $p <= $pages; $p++): ?>
      <a href="?q=<?= h($search) ?>&page=<?= $p ?>"
         class="btn-outline btn-sm <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<script src="<?= SITE_URL ?>/assets/js/dashboard.js"></script>
</body>
</html>
