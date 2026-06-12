<?php
// ============================================================
//  login.php — Page de connexion (front public, table `users`)
//  L'administration a sa propre page : admin/login.php
// ============================================================
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Déjà connecté → dashboard utilisateur
if (estConnecte()) {
    redirect(SITE_URL . '/dashboard.php');
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCSRF();

    $email = trim($_POST['email'] ?? '');
    $mdp   = $_POST['password'] ?? '';

    if (!$email || !$mdp) {
        $erreur = 'Veuillez remplir tous les champs.';
    } else {
        $pdo  = getPDO();
        // Interroge uniquement la table users (jamais admins)
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND actif = 1 LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($mdp, $user['password'])) {
            connecterUtilisateur($user);
            redirect(SITE_URL . '/dashboard.php', 'Bienvenue, ' . $user['prenom'] . ' !', 'success');
        } else {
            $erreur = 'Email ou mot de passe incorrect.';
        }
    }
}

$pageTitle = 'Connexion';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Connexion — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body class="auth-page">

<div class="auth-container">

  <!-- Panneau gauche — branding -->
  <div class="auth-brand">
    <a href="<?= SITE_URL ?>" class="auth-logo">
      <div class="logo-mark" style="width:48px;height:48px;font-size:20px">E</div>
      <span class="logo-name" style="font-size:20px;color:#FAEEDA"><?= SITE_NAME ?></span>
    </a>
    <h1 class="auth-brand-title">Apprends à<br>entreprendre.</h1>
    <p class="auth-brand-sub">Rejoins des centaines d'entrepreneurs béninois qui développent leurs compétences chaque semaine.</p>
    <div class="auth-stats">
      <div><span>1 200+</span><small>Apprenants</small></div>
      <div><span>48</span><small>Cours</small></div>
      <div><span>100%</span><small>Pratique</small></div>
    </div>
  </div>

  <!-- Panneau droit — formulaire -->
  <div class="auth-form-panel">
    <div class="auth-form-box">
      <h2 class="auth-form-title">Connexion</h2>
      <p class="auth-form-sub">Pas encore de compte ? <a href="<?= SITE_URL ?>/register.php">S'inscrire gratuitement</a></p>

      <?php if ($erreur): ?>
        <div class="alert alert-error">
          <i class="ti ti-alert-circle"></i> <?= h($erreur) ?>
        </div>
      <?php endif; ?>

      <?= flash() ?>

      <form method="POST" action="login.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <div class="form-group">
          <label for="email">Adresse e-mail</label>
          <div class="input-icon-wrap">
            <i class="ti ti-mail input-icon" aria-hidden="true"></i>
            <input type="email" id="email" name="email"
                   value="<?= h($_POST['email'] ?? '') ?>"
                   placeholder="votre@email.com" required autocomplete="email">
          </div>
        </div>

        <div class="form-group">
          <label for="password">
            Mot de passe
            <a href="<?= SITE_URL ?>/forgot_password.php" class="label-link">Oublié ?</a>
          </label>
          <div class="input-icon-wrap">
            <i class="ti ti-lock input-icon" aria-hidden="true"></i>
            <input type="password" id="password" name="password"
                   placeholder="••••••••" required autocomplete="current-password">
            <button type="button" class="input-toggle-pw" onclick="togglePw(this)" aria-label="Afficher le mot de passe">
              <i class="ti ti-eye"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-primary btn-full">
          <i class="ti ti-login" aria-hidden="true"></i> Se connecter
        </button>
      </form>
    </div>
  </div>

</div>

<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
<script>
function togglePw(btn) {
  const input = btn.closest('.input-icon-wrap').querySelector('input');
  const icon  = btn.querySelector('i');
  if (input.type === 'password') {
    input.type = 'text';
    icon.className = 'ti ti-eye-off';
  } else {
    input.type = 'password';
    icon.className = 'ti ti-eye';
  }
}
</script>
</body>
</html>
