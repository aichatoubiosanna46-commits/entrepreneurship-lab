<?php
// admin/coaches.php — Gestion des rôles coach / instructeur
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();
$pdo = getPDO();

// Changer le rôle d'un utilisateur (etudiant / coach / instructeur)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_role'])) {
    verifierCSRF();
    $userId  = (int)($_POST['user_id'] ?? 0);
    $newRole = $_POST['role'] ?? 'etudiant';
    if (!in_array($newRole, ['etudiant', 'coach', 'instructeur'], true)) {
        $newRole = 'etudiant';
    }
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
<title>Coachs &amp; instructeurs — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <div>
      <h1 class="admin-page-title">Coachs &amp; instructeurs</h1>
      <p class="admin-page-sub">Attribuer le rôle Coach ou Instructeur à un étudiant</p>
    </div>
  </div>
  <?= flash() ?>

  <div class="alert alert-info">
    <i class="ti ti-info-circle"></i>
    <span>
      <strong>Coach</strong> — accède au Review Center, corrige les livrables et envoie des messages aux étudiants.<br>
      <strong>Instructeur</strong> — peut ajouter/modifier le contenu des séquences (vidéos, textes, quiz...) uniquement sur les cours qui lui sont assignés (voir colonne "Formateur" dans Gestion des cours). Il ne peut pas créer de cours ni de module : cela reste réservé à l'administrateur.
    </span>
  </div>

  <div class="admin-card">
    <table class="admin-table">
      <thead>
        <tr><th>Nom</th><th>Email</th><th>Rôle actuel</th><th>Depuis</th><th>Changer le rôle</th></tr>
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
            <form method="POST" style="display:flex;gap:6px" onsubmit="return confirm('Confirmer le changement de rôle ?')">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <select name="role" class="form-control" style="width:auto">
                <option value="etudiant" <?= ($u['role']??'etudiant')==='etudiant' ? 'selected' : '' ?>>Étudiant</option>
                <option value="coach" <?= ($u['role']??'etudiant')==='coach' ? 'selected' : '' ?>>Coach</option>
                <option value="instructeur" <?= ($u['role']??'etudiant')==='instructeur' ? 'selected' : '' ?>>Instructeur</option>
              </select>
              <button type="submit" name="set_role" value="1" class="btn-outline btn-sm">
                <i class="ti ti-check"></i> Valider
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
