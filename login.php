<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

sendSecurityHeaders();

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
        $rateLimitKey = 'login_' . $email;
        if (checkRateLimit($rateLimitKey, MAX_LOGIN_ATTEMPTS, LOGIN_LOCKOUT_MINUTES * 60)) {
            $erreur = 'Compte temporairement bloqué. Veuillez réessayer dans ' . LOGIN_LOCKOUT_MINUTES . ' minutes.';
        } else {
            $pdo  = getPDO();
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND actif = 1 LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && !empty($user['locked_until']) && new DateTime() < new DateTime($user['locked_until'])) {
                $erreur = 'Compte temporairement bloqué (15 min). Veuillez réessayer plus tard.';
            } elseif ($user && password_verify($mdp, $user['password'])) {
                clearAttempts($rateLimitKey);
                logAction('login_success', 'Connexion réussie pour ' . $email, $user['id']);
                connecterUtilisateur($user);
                redirect(SITE_URL . '/dashboard.php', 'Bienvenue, ' . $user['prenom'] . ' !', 'success');
            } else {
                recordFailedAttempt($rateLimitKey);
                logAction('login_failed', 'Échec de connexion pour ' . $email);
                $erreur = 'Email ou mot de passe incorrect.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Connexion — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<style>
/* ── Auth layout ── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Plus Jakarta Sans', sans-serif; min-height: 100vh; background: #FFFBEB; }

.auth-container {
  display: flex;
  min-height: 100vh;
}

/* ── Panneau gauche : image + overlay ── */
.auth-brand {
  width: 52%;
  flex-shrink: 0;
  position: relative;
  overflow: hidden;
}
.auth-brand-img {
  position: absolute; inset: 0;
  width: 100%; height: 100%;
  object-fit: cover; object-position: center;
}
.auth-brand-overlay {
  position: absolute; inset: 0;
  background: linear-gradient(
    160deg,
    rgba(28,25,23,.82) 0%,
    rgba(28,25,23,.55) 50%,
    rgba(245,158,11,.35) 100%
  );
}
.auth-brand-content {
  position: relative; z-index: 2;
  padding: 48px 44px;
  height: 100%;
  display: flex; flex-direction: column;
  justify-content: space-between;
}
.auth-logo {
  display: flex; align-items: center; gap: 12px; text-decoration: none;
}
.auth-logo-mark {
  width: 46px; height: 46px; border-radius: 12px;
  background: linear-gradient(135deg, #F59E0B, #EF4444);
  display: flex; align-items: center; justify-content: center;
  font-size: 20px; font-weight: 800; color: #fff;
  flex-shrink: 0;
  box-shadow: 0 4px 14px rgba(245,158,11,.4);
}
.auth-logo-name {
  font-size: 18px; font-weight: 700; color: #FEF3C7;
}
.auth-hero-text { flex: 1; display: flex; flex-direction: column; justify-content: center; padding: 32px 0; }
.auth-hero-text h1 {
  font-size: 36px; font-weight: 800; color: #fff;
  line-height: 1.2; margin-bottom: 16px;
}
.auth-hero-text h1 em { font-style: normal; color: #F59E0B; }
.auth-hero-text p {
  font-size: 14px; color: rgba(255,255,255,.7);
  line-height: 1.75; max-width: 380px; margin-bottom: 32px;
}
.auth-perks { display: flex; flex-direction: column; gap: 12px; }
.auth-perk {
  display: flex; align-items: center; gap: 12px;
  font-size: 13px; color: rgba(255,255,255,.8);
}
.auth-perk-icon {
  width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;
  background: rgba(245,158,11,.2); border: 1px solid rgba(245,158,11,.3);
  display: flex; align-items: center; justify-content: center;
}
.auth-perk-icon i { font-size: 17px; color: #F59E0B; }
.auth-stats-row {
  display: flex; gap: 24px; flex-wrap: wrap;
  padding-top: 28px;
  border-top: 1px solid rgba(255,255,255,.12);
}
.auth-stat { text-align: center; }
.auth-stat-val { font-size: 22px; font-weight: 800; color: #F59E0B; display: block; }
.auth-stat-lbl { font-size: 11px; color: rgba(255,255,255,.5); }

/* ── Panneau droit : formulaire ── */
.auth-form-panel {
  flex: 1;
  display: flex; align-items: center; justify-content: center;
  padding: 48px 32px;
  background: #fff;
}
.auth-form-box { width: 100%; max-width: 400px; }
.auth-form-title {
  font-size: 26px; font-weight: 800; color: #1C1917; margin-bottom: 6px;
}
.auth-form-sub {
  font-size: 14px; color: #6b7280; margin-bottom: 28px;
}
.auth-form-sub a { color: #F59E0B; font-weight: 600; text-decoration: none; }
.auth-form-sub a:hover { text-decoration: underline; }

/* Form elements */
.form-group { display: flex; flex-direction: column; gap: 7px; margin-bottom: 18px; }
.form-group label {
  font-size: 13px; font-weight: 600; color: #1C1917;
  display: flex; justify-content: space-between; align-items: center;
}
.label-link { font-size: 12px; color: #F59E0B; text-decoration: none; font-weight: 500; }
.label-link:hover { text-decoration: underline; }

.input-icon-wrap { position: relative; }
.input-icon {
  position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
  color: #9ca3af; font-size: 16px; pointer-events: none;
}
.input-icon-wrap input {
  width: 100%; padding: 11px 42px 11px 40px;
  border: 1.5px solid #e5e7eb; border-radius: 10px;
  font-size: 14px; font-family: inherit; color: #1C1917;
  background: #fff; transition: border-color .2s, box-shadow .2s;
  box-sizing: border-box;
}
.input-icon-wrap input:focus {
  outline: none; border-color: #F59E0B;
  box-shadow: 0 0 0 4px rgba(245,158,11,.12);
}
.input-toggle-pw {
  position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
  background: none; border: none; cursor: pointer;
  color: #9ca3af; padding: 4px; display: flex; align-items: center;
}
.input-toggle-pw:hover { color: #F59E0B; }

/* Alert */
.alert {
  display: flex; align-items: flex-start; gap: 10px;
  padding: 13px 16px; border-radius: 10px;
  font-size: 13px; margin-bottom: 16px;
}
.alert i { font-size: 18px; flex-shrink: 0; }
.alert-error { background: #fef2f2; color: #dc2626; border: 1px solid #fca5a5; }

/* Submit button */
.btn-primary {
  display: flex; align-items: center; justify-content: center; gap: 8px;
  width: 100%; padding: 13px;
  background: linear-gradient(135deg, #F59E0B, #D97706);
  color: #1C1917; border: none; border-radius: 10px;
  font-size: 15px; font-weight: 800; cursor: pointer;
  font-family: inherit;
  box-shadow: 0 3px 10px rgba(245,158,11,.35);
  transition: opacity .15s, transform .1s;
}
.btn-primary:hover { opacity: .92; transform: translateY(-1px); }

/* Divider */
.auth-divider {
  display: flex; align-items: center; gap: 12px;
  margin: 20px 0; font-size: 12px; color: #9ca3af;
}
.auth-divider::before, .auth-divider::after {
  content: ''; flex: 1; height: 1px; background: #e5e7eb;
}

/* Bottom link */
.auth-bottom {
  text-align: center; margin-top: 20px; font-size: 13px; color: #6b7280;
}
.auth-bottom a { color: #F59E0B; font-weight: 600; text-decoration: none; }
.auth-bottom a:hover { text-decoration: underline; }

/* Bouton Google */
.btn-google {
  display: flex; align-items: center; justify-content: center; gap: 10px;
  width: 100%; padding: 12px;
  background: #fff; color: #1C1917;
  border: 1.5px solid #e5e7eb; border-radius: 10px;
  font-size: 14px; font-weight: 600; text-decoration: none;
  font-family: inherit;
  transition: background .15s, border-color .15s;
}
.btn-google:hover { background: #f9fafb; border-color: #d1d5db; }

/* Responsive */
@media (max-width: 860px) {
  .auth-brand { display: none; }
  .auth-form-panel {
    padding: 32px 20px;
    background-image: url('https://images.unsplash.com/photo-1531482615713-2afd69097998?w=900&q=85&auto=format&fit=crop');
    background-size: cover;
    background-position: center;
    position: relative;
  }
  .auth-form-panel::before {
    content: '';
    position: absolute; inset: 0;
    background: rgba(28,25,23,.75);
    z-index: 0;
  }
  .auth-form-box {
    position: relative;
    z-index: 1;
    background: rgba(255,255,255,.92);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-radius: 20px;
    padding: 32px 28px;
    box-shadow: 0 8px 40px rgba(0,0,0,.25);
    border: 1px solid rgba(255,255,255,.3);
  }
}
</style>
</head>
<body>

<div class="auth-container">

  <!-- ── Panneau gauche : image apprentissage africain ── -->
  <div class="auth-brand">
    <!-- Image Unsplash : jeune entrepreneur africain au travail -->
    <img
      class="auth-brand-img"
      src="https://images.unsplash.com/photo-1531482615713-2afd69097998?w=900&q=85&auto=format&fit=crop"
      alt="Entrepreneurs en formation"
      loading="eager"
    >
    <div class="auth-brand-overlay"></div>

    <div class="auth-brand-content">
      <!-- Logo -->
      <a href="<?= SITE_URL ?>" class="auth-logo">
        <div class="auth-logo-mark">E</div>
        <span class="auth-logo-name"><?= SITE_NAME ?></span>
      </a>

      <!-- Texte hero -->
      <div class="auth-hero-text">
        <h1>Apprends à<br><em>entreprendre.</em></h1>
        <p>
          Des formations pratiques, adaptées au contexte béninois.
          Coaching 1:1, certification Université de Parakou, paiement Mobile Money.
        </p>
        <div class="auth-perks">
          <div class="auth-perk">
            <div class="auth-perk-icon"><i class="ti ti-certificate" aria-hidden="true"></i></div>
            <span>Certifié par l'Université de Parakou</span>
          </div>
          <div class="auth-perk">
            <div class="auth-perk-icon"><i class="ti ti-device-mobile" aria-hidden="true"></i></div>
            <span>Paiement Mobile Money accepté</span>
          </div>
          <div class="auth-perk">
            <div class="auth-perk-icon"><i class="ti ti-headset" aria-hidden="true"></i></div>
            <span>Coaching 1:1 avec un mentor dédié</span>
          </div>
          <div class="auth-perk">
            <div class="auth-perk-icon"><i class="ti ti-infinity" aria-hidden="true"></i></div>
            <span>Accès à vie au contenu</span>
          </div>
        </div>
      </div>

      
    </div>
  </div>

  <!-- ── Panneau droit : formulaire ── -->
  <div class="auth-form-panel">
    <div class="auth-form-box">

      <h2 class="auth-form-title">Bon retour ! </h2>
      <p class="auth-form-sub">
        Pas encore de compte ?
        <a href="<?= SITE_URL ?>/register.php">S'inscrire gratuitement</a>
      </p>

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
            <button type="button" class="input-toggle-pw" onclick="togglePw(this)">
              <i class="ti ti-eye"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-primary">
          <i class="ti ti-login" aria-hidden="true"></i> Se connecter
        </button>
      </form>

      <div class="auth-divider">ou</div>

      <a href="<?= SITE_URL ?>/google_login.php" class="btn-google">
        <svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true">
          <path fill="#4285F4" d="M17.64 9.2c0-.64-.06-1.25-.16-1.84H9v3.48h4.84a4.14 4.14 0 0 1-1.8 2.72v2.26h2.9c1.7-1.57 2.7-3.88 2.7-6.62z"/>
          <path fill="#34A853" d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.9-2.26c-.8.55-1.84.87-3.06.87-2.36 0-4.36-1.6-5.08-3.74H.92v2.34A9 9 0 0 0 9 18z"/>
          <path fill="#FBBC05" d="M3.92 10.69A5.4 5.4 0 0 1 3.64 9c0-.59.1-1.16.28-1.69V4.97H.92A9 9 0 0 0 0 9c0 1.45.35 2.83.92 4.03l3-2.34z"/>
          <path fill="#EA4335" d="M9 3.58c1.32 0 2.5.45 3.44 1.35l2.58-2.58A9 9 0 0 0 9 0a9 9 0 0 0-8.08 4.97l3 2.34C4.64 5.18 6.64 3.58 9 3.58z"/>
        </svg>
        Continuer avec Google
      </a>

      <div class="auth-bottom">
        Pas encore inscrit ?
        <a href="<?= SITE_URL ?>/register.php">Créer un compte gratuit </a>
      </div>

    </div>
  </div>

</div>

<script>
function togglePw(btn) {
  const input = btn.closest('.input-icon-wrap').querySelector('input');
  const icon  = btn.querySelector('i');
  input.type  = input.type === 'password' ? 'text' : 'password';
  icon.className = input.type === 'password' ? 'ti ti-eye' : 'ti ti-eye-off';
}
</script>
</body>
</html>