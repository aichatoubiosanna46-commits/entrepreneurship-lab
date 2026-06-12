<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
reqConnecte();

$pdo  = getPDO();
$user = utilisateurCourant();
if (!$user) { redirect(SITE_URL . '/login.php'); }

$errors = [];

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
        if ($newPw !== '') {
            if (strlen($newPw) < 8) {
                $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
            } else {
                $hash = password_hash($newPw, PASSWORD_BCRYPT, ['cost' => 12]);
                $pdo->prepare('UPDATE users SET password=? WHERE id=?')->execute([$hash, $user['id']]);
            }
        }

        if (empty($errors)) {
            $_SESSION['user_nom'] = $nom . ' ' . $prenom;
            redirect(SITE_URL . '/profil.php', 'Profil mis à jour avec succès !', 'success');
        }
    }

    // Recharger les valeurs saisies en cas d'erreur
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
.profil-wrap        { max-width: 680px; margin: 40px auto; padding: 0 20px 60px; }
.profil-hero        { background: #1E1040; border-radius: 14px; padding: 28px 28px 24px; margin-bottom: 24px; display: flex; align-items: center; gap: 20px; }
.profil-avatar      { width: 68px; height: 68px; border-radius: 50%; background: #6C47D4; color: #EDE9FE; display: flex; align-items: center; justify-content: center; font-size: 26px; font-weight: 700; flex-shrink: 0; border: 3px solid rgba(108,71,212,.5); }
.profil-hero-info h2 { font-size: 19px; font-weight: 700; color: #EDE9FE; }
.profil-hero-info p  { font-size: 13px; color: rgba(255,255,255,.45); margin-top: 3px; }

.profil-card        { background: #fff; border: 0.5px solid var(--border); border-radius: 12px; padding: 24px; margin-bottom: 18px; }
.profil-card-title  { font-size: 14px; font-weight: 700; color: var(--text); margin: 0 0 20px; display: flex; align-items: center; gap: 8px; }
.profil-card-title i { color: #6C47D4; font-size: 18px; }

.form-grid          { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.form-field         { display: flex; flex-direction: column; gap: 5px; }
.form-field label   { font-size: 12px; font-weight: 600; color: var(--text-muted); }
.form-field input,
.form-field textarea {
  padding: 9px 12px; border: 0.5px solid var(--border);
  border-radius: 8px; font-size: 13px; font-family: inherit;
  width: 100%; background: #fff; color: var(--text);
  transition: border-color .15s;
}
.form-field input:focus,
.form-field textarea:focus {
  outline: none; border-color: #6C47D4;
  box-shadow: 0 0 0 3px rgba(108,71,212,.1);
}
.form-field textarea { resize: vertical; min-height: 75px; }

.btn-save { display: inline-flex; align-items: center; gap: 8px; padding: 11px 26px; background: #6C47D4; color: #fff; border: none; border-radius: 9px; font-size: 14px; font-weight: 600; cursor: pointer; transition: background .15s; text-decoration: none; }
.btn-save:hover { background: #5B21B6; }

.error-box { background: #FAECE7; border: 1px solid #F0997B; color: #993C1D; padding: 12px 16px; border-radius: 8px; margin-bottom: 18px; font-size: 13px; }
.error-box div + div { margin-top: 4px; }

@media (max-width: 560px) { .form-grid { grid-template-columns: 1fr; } }
</style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="profil-wrap">
  <?= flash() ?>

  <!-- Avatar + nom -->
  <div class="profil-hero">
    <div class="profil-avatar">
      <?= strtoupper(mb_substr($user['prenom'],0,1) . mb_substr($user['nom'],0,1)) ?>
    </div>
    <div class="profil-hero-info">
      <h2><?= h($user['prenom'] . ' ' . $user['nom']) ?></h2>
      <p><?= h($user['email']) ?><?= $user['ville'] ? ' · ' . h($user['ville']) : '' ?></p>
    </div>
  </div>

  <!-- Erreurs -->
  <?php if ($errors): ?>
  <div class="error-box">
    <?php foreach ($errors as $e): ?><div>• <?= h($e) ?></div><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

    <!-- Infos personnelles -->
    <div class="profil-card">
      <p class="profil-card-title"><i class="ti ti-user"></i> Informations personnelles</p>

      <div class="form-grid" style="margin-bottom:14px">
        <div class="form-field">
          <label>Prénom *</label>
          <input type="text" name="prenom" value="<?= h($user['prenom']) ?>" required>
        </div>
        <div class="form-field">
          <label>Nom *</label>
          <input type="text" name="nom" value="<?= h($user['nom']) ?>" required>
        </div>
      </div>

      <div class="form-field" style="margin-bottom:14px">
        <label>Adresse email *</label>
        <input type="email" name="email" value="<?= h($user['email']) ?>" required>
      </div>

      <div class="form-grid" style="margin-bottom:14px">
        <div class="form-field">
          <label>Téléphone</label>
          <input type="tel" name="telephone" value="<?= h($user['telephone'] ?? '') ?>" placeholder="+229 …">
        </div>
        <div class="form-field">
          <label>Ville</label>
          <input type="text" name="ville" value="<?= h($user['ville'] ?? '') ?>" placeholder="Ex : Cotonou">
        </div>
      </div>

      <div class="form-field">
        <label>Bio <span style="font-weight:400;color:var(--text-muted)">(optionnel)</span></label>
        <textarea name="bio" placeholder="Parlez de vous en quelques mots…"><?= h($user['bio'] ?? '') ?></textarea>
      </div>
    </div>

    <!-- Mot de passe -->
    <div class="profil-card">
      <p class="profil-card-title"><i class="ti ti-lock"></i> Changer le mot de passe</p>
      <div class="form-field">
        <label>Nouveau mot de passe <span style="font-weight:400;color:var(--text-muted)">(laisser vide pour ne pas changer)</span></label>
        <input type="password" name="new_password" placeholder="Minimum 8 caractères" autocomplete="new-password">
      </div>
    </div>

    <!-- Actions -->
    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
      <button type="submit" class="btn-save">
        <i class="ti ti-check"></i> Enregistrer les modifications
      </button>
      <a href="<?= SITE_URL ?>/dashboard.php" style="font-size:13px;color:var(--text-muted);text-decoration:none">
        ← Retour au tableau de bord
      </a>
    </div>
  </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
