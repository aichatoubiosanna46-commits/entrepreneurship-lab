<?php
// admin/coaches.php — Gestion des coachs
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();
$pdo = getPDO();

// Promouvoir/rétrograder coach
if (isset($_GET['toggle_coach'])) {
    $userId = (int)$_GET['toggle_coach'];
    $user = $pdo->prepare('SELECT role FROM users WHERE id=?');
    $user->execute([$userId]);
    $user = $user->fetch();
    $newRole = ($user['role'] ?? 'etudiant') === 'coach' ? 'etudiant' : 'coach';
    $pdo->prepare('UPDATE users SET role=? WHERE id=?')->execute([$newRole, $userId]);
    redirect(SITE_URL . '/admin/coaches.php', 'Rôle mis à jour.', 'success');
}

try {
    $users = $pdo->query(
        'SELECT id, nom, prenom, email, role, created_at FROM users WHERE actif=1 ORDER BY role DESC, nom ASC'
    )->fetchAll();
} catch(Exception $e) { $users = []; }

$currentPage = 'coaches.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Coachs — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <div>
      <h1 class="admin-page-title">Gestion des coachs</h1>
      <p class="admin-page-sub">Promouvoir un étudiant au rôle de coach</p>
    </div>
  </div>
  <?= flash() ?>

  <div class="alert alert-info">
    <i class="ti ti-info-circle"></i>
    <span><strong>Rôle Coach</strong> — accède au Review Center, note les assignments et envoie des messages aux étudiants.</span>
  </div>

  <div class="admin-card">
    <table class="admin-table">
      <thead>
        <tr><th>Nom</th><th>Email</th><th>Rôle actuel</th><th>Depuis</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td><strong><?= h($u['nom'].' '.$u['prenom']) ?></strong></td>
          <td><?= h($u['email']) ?></td>
          <td>
            <?php if (($u['role'] ?? 'etudiant') === 'coach'): ?>
            <span class="badge badge-success"><i class="ti ti-star"></i> Coach</span>
            <?php elseif (($u['role'] ?? 'etudiant') === 'instructeur'): ?>
            <span class="badge badge-amber"><i class="ti ti-school"></i> Instructeur</span>
            <?php else: ?>
            <span class="badge badge-neutral">Étudiant</span>
            <?php endif; ?>
          </td>
          <td style="font-size:12px;color:#9ca3af"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
          <td>
            <div style="display:flex;gap:6px">
              <a href="?toggle_coach=<?= $u['id'] ?>"
                 class="btn-outline btn-sm<?= ($u['role']??'etudiant')==='coach' ? ' btn-outline-danger' : '' ?>"
                 onclick="return confirm('<?= ($u['role']??'etudiant')==='coach' ? 'Rétrograder en étudiant ?' : 'Promouvoir comme coach ?' ?>')">
                <?= ($u['role']??'etudiant')==='coach' ? '<i class="ti ti-arrow-down"></i> Rétrograder' : '<i class="ti ti-star"></i> Coach' ?>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
