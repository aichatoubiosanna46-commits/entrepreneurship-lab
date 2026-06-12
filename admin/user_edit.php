<?php
// admin/user_edit.php — Modifier un utilisateur
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();
$pdo = getPDO();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { redirect(SITE_URL . '/admin/users.php', 'Utilisateur introuvable.', 'error'); }

$user = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$user->execute([$id]);
$user = $user->fetch();
if (!$user) { redirect(SITE_URL . '/admin/users.php', 'Utilisateur introuvable.', 'error'); }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCSRF();
    $nom   = trim($_POST['nom'] ?? '');
    $prenom= trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $tel   = trim($_POST['telephone'] ?? '');
    $ville = trim($_POST['ville'] ?? '');
    $actif = isset($_POST['actif']) ? 1 : 0;

    if (!$nom) $errors[] = 'Nom obligatoire.';
    if (!$prenom) $errors[] = 'Prénom obligatoire.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide.';

    if (empty($errors)) {
        // Vérifier doublon email
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $check->execute([$email, $id]);
        if ($check->fetch()) { $errors[] = 'Cet email est déjà utilisé.'; }
    }

    if (empty($errors)) {
        $pdo->prepare(
            'UPDATE users SET nom=?, prenom=?, email=?, telephone=?, ville=?, actif=? WHERE id=?'
        )->execute([$nom, $prenom, $email, $tel, $ville, $actif, $id]);

        // Nouveau mot de passe si renseigné
        $newPw = $_POST['new_password'] ?? '';
        if ($newPw && strlen($newPw) >= 8) {
            $hash = password_hash($newPw, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$hash, $id]);
        }
        redirect(SITE_URL . '/admin/users.php', 'Utilisateur modifié avec succès.', 'success');
    }
    // Recharger user après erreur
    $user = array_merge($user, ['nom'=>$nom,'prenom'=>$prenom,'email'=>$email,'telephone'=>$tel,'ville'=>$ville,'actif'=>$actif]);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Modifier utilisateur — Admin <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/dashboard.css">
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <div>
      <h1 class="admin-page-title">Modifier l'utilisateur</h1>
      <p class="admin-page-sub"><?= h($user['prenom'].' '.$user['nom']) ?> — #<?= $user['id'] ?></p>
    </div>
    <a href="<?= SITE_URL ?>/admin/users.php" class="btn-outline">← Retour</a>
  </div>
  <?= flash() ?>
  <?php if ($errors): ?>
  <div style="background:#FAECE7;border:1px solid #F0997B;border-radius:8px;padding:12px 16px;margin-bottom:16px;color:#993C1D">
    <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <form method="POST" class="admin-card" style="max-width:600px">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
      <div class="form-field">
        <label>Prénom *</label>
        <input type="text" name="prenom" required value="<?= h($user['prenom']) ?>">
      </div>
      <div class="form-field">
        <label>Nom *</label>
        <input type="text" name="nom" required value="<?= h($user['nom']) ?>">
      </div>
      <div class="form-field" style="grid-column:1/-1">
        <label>Email *</label>
        <input type="email" name="email" required value="<?= h($user['email']) ?>">
      </div>
      <div class="form-field">
        <label>Téléphone</label>
        <input type="tel" name="telephone" value="<?= h($user['telephone']??'') ?>">
      </div>
      <div class="form-field">
        <label>Ville</label>
        <input type="text" name="ville" value="<?= h($user['ville']??'') ?>">
      </div>
      <div class="form-field" style="grid-column:1/-1">
        <label>Nouveau mot de passe (laisser vide pour ne pas changer)</label>
        <input type="password" name="new_password" placeholder="Min. 8 caractères">
      </div>
      <div class="form-field" style="grid-column:1/-1">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
          <input type="checkbox" name="actif" value="1" <?= $user['actif'] ? 'checked' : '' ?>>
          Compte actif
        </label>
      </div>
    </div>
    <div style="display:flex;gap:10px;margin-top:8px">
      <button type="submit" class="btn-primary"><i class="ti ti-check"></i> Enregistrer</button>
      <a href="<?= SITE_URL ?>/admin/users.php" class="btn-outline">Annuler</a>
    </div>
  </form>
</div>
</body>
</html>
