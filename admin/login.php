<?php
// ============================================================
//  admin/login.php — Page de connexion exclusive à l'admin
//  Totalement séparée de login.php (front public)
//  Interroge uniquement la table `admins`
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Déjà connecté en tant qu'admin → dashboard
if (estAdmin()) {
    redirect(SITE_URL . '/admin/index.php');
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
        $stmt = $pdo->prepare('SELECT * FROM admins WHERE email = ? AND actif = 1 LIMIT 1');
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($mdp, $admin['password'])) {
            connecterAdmin($admin);
            redirect(SITE_URL . '/admin/index.php', 'Bienvenue dans l\'espace administration.', 'success');
        } else {
            // Délai anti-bruteforce
            sleep(1);
            $erreur = 'Email ou mot de passe incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Administration — Connexion · <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<style>
  /* Surcharge légère pour distinguer visuellement la page admin */
  .auth-brand { background: #1a1a2e !important; }
  .auth-brand-title { color: #fff !important; }
  .auth-brand-sub { color: #aab !important; }
  .logo-mark { background: #534AB7 !important; color: #fff !important; }
  .logo-name { color: #d0d0f0 !important; }
  .auth-stats span { color: #fff !important; }
  .auth-stats small { color: #889 !important; }
  .admin-login-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: #EEEDFE; color: #534AB7;
    border-radius: 20px; padding: 4px 12px;
    font-size: 12px; font-weight: 600;
    margin-bottom: 16px;
  }
</style>
</head>
<body class="auth-page">

<div class="auth-container">

  <!-- Panneau gauche — branding admin -->
  <div class="auth-brand">
    <a href="<?= SITE_URL ?>" class="auth-logo">
      <div class="logo-mark" style="width:48px;height:48px;font-size:20px">E</div>
      <span class="logo-name" style="font-size:20px"><?= SITE_NAME ?></span>
    </a>
    <h1 class="auth-brand-title">Espace<br>Administration</h1>
    <p class="auth-brand-sub">Accès réservé aux administrateurs de la plateforme.</p>
    <div class="auth-stats">
      <div><span><i class="ti ti-shield-check"></i></span><small>Accès sécurisé</small></div>
      <div><span><i class="ti ti-lock"></i></span><small>Session isolée</small></div>
    </div>
  </div>

  <!-- Panneau droit — formulaire -->
  <div class="auth-form-panel">
    <div class="auth-form-box">
      <div class="admin-login-badge">
        <i class="ti ti-shield"></i> Administrateur
      </div>
      <h2 class="auth-form-title">Connexion Admin</h2>
      <p class="auth-form-sub">Réservé aux comptes administrateurs.</p>

      <?php if ($erreur): ?>
        <div class="alert alert-error">
          <i class="ti ti-alert-circle"></i> <?= h($erreur) ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($_GET['error']) && $_GET['error'] === 'acces_refuse'): ?>
        <div class="alert alert-error">
          <i class="ti ti-lock"></i> Accès refusé. Connectez-vous d'abord.
        </div>
      <?php endif; ?>

      <?= flash() ?>

      <form method="POST" action="login.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <div class="form-group">
          <label for="email">Adresse e-mail admin</label>
          <div class="input-icon-wrap">
            <i class="ti ti-mail input-icon" aria-hidden="true"></i>
            <input type="email" id="email" name="email"
                   value="<?= h($_POST['email'] ?? '') ?>"
                   placeholder="admin@example.com" required autocomplete="email">
          </div>
        </div>

        <div class="form-group">
          <label for="password">Mot de passe</label>
          <div class="input-icon-wrap">
            <i class="ti ti-lock input-icon" aria-hidden="true"></i>
            <input type="password" id="password" name="password"
                   placeholder="••••••••" required autocomplete="current-password">
            <button type="button" class="input-toggle-pw" onclick="togglePw(this)" aria-label="Afficher le mot de passe">
              <i class="ti ti-eye"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-primary btn-full" style="background:#534AB7">
          <i class="ti ti-login" aria-hidden="true"></i> Accéder au panneau admin
        </button>
      </form>

      <p style="margin-top:20px;font-size:12px;color:var(--text-muted);text-align:center">
        <a href="<?= SITE_URL ?>" style="color:var(--text-muted)">← Retour au site public</a>
      </p>
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
