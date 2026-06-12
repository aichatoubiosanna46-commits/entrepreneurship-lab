<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
reqConnecte();

$pdo  = getPDO();
$user = utilisateurCourant();
if (!$user) { redirect(SITE_URL . '/login.php'); }

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCSRF();
    $nom       = trim($_POST['nom']       ?? '');
    $prenom    = trim($_POST['prenom']    ?? '');
    $email     = trim($_POST['email']     ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $ville     = trim($_POST['ville']     ?? '');
    $bio       = trim($_POST['bio']       ?? '');

    if (!$nom)    $errors[] = 'Le nom est obligatoire.';
    if (!$prenom) $errors[] = 'Le prénom est obligatoire.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Adresse email invalide.';

    if (empty($errors)) {
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $check->execute([$email, $user['id']]);
        if ($check->fetch()) { $errors[] = 'Cet email est déjà utilisé par un autre compte.'; }
    }

    if (empty($errors)) {
        $pdo->prepare(
            'UPDATE users SET nom=?, prenom=?, email=?, telephone=?, ville=?, bio=? WHERE id=?'
        )->execute([$nom, $prenom, $email, $telephone ?: null, $ville ?: null, $bio ?: null, $user['id']]);

        $newPw = $_POST['new_password'] ?? '';
        if ($newPw) {
            if (strlen($newPw) < 8) {
                $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
            } else {
                $hash = password_hash($newPw, PASSWORD_BCRYPT, ['cost' => 12]);
                $pdo->prepare('UPDATE users SET password=? WHERE id=?')->execute([$hash, $user['id']]);
            }
        }

        if (empty($errors)) {
            $_SESSION['user_nom'] = $nom . ' ' . $prenom;
            redirect(SITE_URL . '/profil.php', 'Profil mis à jour avec succès.', 'success');
        }
    }

    $user = array_merge($user, [
        'nom'=>$nom,'prenom'=>$prenom,'email'=>$email,
        'telephone'=>$telephone,'ville'=>$ville,'bio'=>$bio
    ]);
}

$pageTitle = 'Mon profil';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Mon profil — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/home.css">
<style>
.profil-page { max-width:720px; margin:40px auto; padding:0 20px 60px; }
.profil-header { display:flex; align-items:center; gap:20px; background:#1E1040; border-radius:16px; padding:28px; margin-bottom:28px; }
.profil-avatar { width:72px; height:72px; border-radius:50%; background:#6C47D4; color:#EDE9FE; display:flex; align-items:center; justify-content:center; font-size:28px; font-weight:700; flex-shrink:0; }
.profil-info h2 { font-size:20px; font-weight:700; color:#EDE9FE; }
.profil-info p  { font-size:13px; color:rgba(255,255,255,.5); margin-top:4px; }
.profil-card { background:#fff; border:0.5px solid var(--border); border-radius:14px; padding:28px; margin-bottom:20px; }
.profil-card h3 { font-size:15px; font-weight:700; margin-bottom:20px; color:var(--text); display:flex; align-items:center; gap:8px; }
.profil-card h3 i { color:#6C47D4; }
.form-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.form-field label { font-size:12px; font-weight:600; color:var(--text-muted); display:block; margin-bottom:6px; }
.form-field input, .form-field textarea { width:100%; padding:10px 12px; border:0.5px solid var(--border); border-radius:8px; font-size:13px; font-family:inherit; }
.form-field input:focus, .form-field textarea:focus { outline:none; border-color:#6C47D4; box-shadow:0 0 0 3px rgba(108,71,212,.12); }
.form-field textarea { resize:vertical; min-height:80px; }
.btn-purple { display:inline-flex; align-items:center; gap:8px; padding:11px 24px; background:#6C47D4; color:#fff; border:none; border-radius:9px; font-size:14px; font-weight:600; cursor:pointer; transition:.15s; }
.btn-purple:hover { background:#5B21B6; }
.error-box { background:#FAECE7; border:1px solid #F0997B; color:#993C1D; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:13px; }
@media (max-width:600px) { .form-grid-2 { grid-template-columns:1fr; } }
</style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="profil-page">
  <?= flash() ?>

  <!-- En-tête profil -->
  <div class="profil-header">
    <div class="profil-avatar">
      <?= strtoupper(mb_substr($user['prenom'],0,1).mb_substr($user['nom'],0,1)) ?>
    </div>
    <div class="profil-info">
      <h2><?= h($user['prenom'].' '.$user['nom']) ?></h2>
      <p><?= h($user['email']) ?></p>
      <?php if ($user['ville']): ?>
        <p><i class="ti ti-map-pin" style="font-size:12px"></i> <?= h($user['ville']) ?></p>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($errors): ?>
  <div class="error-box">
    <?php foreach ($errors as $e): ?><div>• <?= h($e) ?></div><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

    <!-- Informations personnelles -->
    <div class="profil-card">
      <h3><i class="ti ti-user"></i> Informations personnelles</h3>
      <div class="form-grid-2" style="margin-bottom:16px">
        <div class="form-field">
          <label>Prénom *</label>
          <input type="text" name="prenom" value="<?= h($user['prenom']) ?>" required>
        </div>
        <div class="form-field">
          <label>Nom *</label>
          <input type="text" name="nom" value="<?= h($user['nom']) ?>" required>
        </div>
      </div>
      <div class="form-field" style="margin-bottom:16px">
        <label>Adresse email *</label>
        <input type="email" name="email" value="<?= h($user['email']) ?>" required>
      </div>
      <div class="form-grid-2" style="margin-bottom:16px">
        <div class="form-field">
          <label>Téléphone</label>
          <input type="tel" name="telephone" value="<?= h($user['telephone'] ?? '') ?>" placeholder="+229 ...">
        </div>
        <div class="form-field">
          <label>Ville</label>
          <input type="text" name="ville" value="<?= h($user['ville'] ?? '') ?>" placeholder="Ex: Cotonou">
        </div>
      </div>
      <div class="form-field">
        <label>Bio (optionnel)</label>
        <textarea name="bio" placeholder="Parlez de vous en quelques mots..."><?= h($user['bio'] ?? '') ?></textarea>
      </div>
    </div>

    <!-- Changer mot de passe -->
    <div class="profil-card">
      <h3><i class="ti ti-lock"></i> Changer le mot de passe</h3>
      <div class="form-field">
        <label>Nouveau mot de passe (laisser vide pour ne pas changer)</label>
        <input type="password" name="new_password" placeholder="Minimum 8 caractères">
      </div>
    </div>

    <div style="display:flex;gap:12px;align-items:center">
      <button type="submit" class="btn-purple">
        <i class="ti ti-check"></i> Enregistrer les modifications
      </button>
      <a href="<?= SITE_URL ?>/dashboard.php" style="font-size:13px;color:var(--text-muted)">← Retour au tableau de bord</a>
    </div>
  </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
