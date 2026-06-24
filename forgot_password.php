<?php
// ============================================================
//  forgot_password.php — Demande de réinitialisation mot de passe
// ============================================================
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

sendSecurityHeaders();

if (estConnecte()) redirect(SITE_URL . '/dashboard.php');

$message   = '';
$resetLink = '';
$sent      = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCSRF();
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Adresse e-mail invalide.';
    } else {
        $pdo  = getPDO();
        $stmt = $pdo->prepare('SELECT id, prenom FROM users WHERE email = ? AND actif = 1 LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $pdo->prepare('UPDATE password_resets SET used = 1 WHERE user_id = ?')->execute([$user['id']]);
            $token     = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + 3600);
            $pdo->prepare(
                'INSERT INTO password_resets (user_id, email, token, expires_at) VALUES (?, ?, ?, ?)'
            )->execute([$user['id'], $email, $token, $expiresAt]);
            $resetLink = SITE_URL . '/reset_password.php?token=' . $token;
            emailResetPassword($email, $user['prenom'], $resetLink);
            logAction('password_reset_request', 'Demande de réinitialisation pour ' . $email, $user['id']);
        }
        $sent = true;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Mot de passe oublié — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Plus Jakarta Sans', sans-serif; min-height: 100vh; background: #FFFBEB; }

.auth-container { display: flex; min-height: 100vh; }

/* ── Panneau gauche ── */
.auth-brand {
  width: 48%; flex-shrink: 0;
  position: relative; overflow: hidden;
}
.auth-brand-img {
  position: absolute; inset: 0;
  width: 100%; height: 100%;
  object-fit: cover; object-position: center top;
}
.auth-brand-overlay {
  position: absolute; inset: 0;
  background: linear-gradient(155deg, rgba(28,25,23,.88) 0%, rgba(28,25,23,.55) 50%, rgba(245,158,11,.25) 100%);
}
.auth-brand-content {
  position: relative; z-index: 2;
  padding: 44px 40px; height: 100%;
  display: flex; flex-direction: column; justify-content: space-between;
}
.auth-logo { display: flex; align-items: center; gap: 12px; text-decoration: none; }
.auth-logo-mark {
  width: 44px; height: 44px; border-radius: 12px;
  background: linear-gradient(135deg, #F59E0B, #EF4444);
  display: flex; align-items: center; justify-content: center;
  font-size: 19px; font-weight: 800; color: #fff; flex-shrink: 0;
  box-shadow: 0 4px 14px rgba(245,158,11,.4);
}
.auth-logo-name { font-size: 17px; font-weight: 700; color: #FEF3C7; }

.auth-hero { flex: 1; display: flex; flex-direction: column; justify-content: center; padding: 28px 0; }
.auth-hero h1 {
  font-size: 32px; font-weight: 800; color: #fff;
  line-height: 1.2; margin-bottom: 14px;
}
.auth-hero h1 em { font-style: normal; color: #F59E0B; }
.auth-hero p {
  font-size: 14px; color: rgba(255,255,255,.7);
  line-height: 1.75; max-width: 340px; margin-bottom: 32px;
}
.auth-steps { display: flex; flex-direction: column; gap: 16px; }
.auth-step { display: flex; align-items: flex-start; gap: 14px; }
.auth-step-num {
  width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0;
  background: rgba(245,158,11,.2); border: 1px solid rgba(245,158,11,.4);
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; font-weight: 800; color: #F59E0B;
}
.auth-step-text strong { display: block; font-size: 13px; color: #fff; margin-bottom: 2px; }
.auth-step-text span   { font-size: 12px; color: rgba(255,255,255,.55); }

.auth-back-link {
  display: inline-flex; align-items: center; gap: 6px;
  font-size: 12px; color: rgba(255,255,255,.5);
  text-decoration: none; transition: color .15s;
}
.auth-back-link:hover { color: #F59E0B; }

/* ── Panneau droit ── */
.auth-form-panel {
  flex: 1; display: flex; align-items: center; justify-content: center;
  padding: 48px 32px; background: #fff;
}
.auth-form-box { width: 100%; max-width: 380px; }

.auth-icon-wrap {
  width: 64px; height: 64px; border-radius: 16px;
  background: linear-gradient(135deg, #FEF3C7, #FDE68A);
  border: 2px solid #F59E0B;
  display: flex; align-items: center; justify-content: center;
  font-size: 28px; margin-bottom: 20px;
}
.auth-form-title { font-size: 24px; font-weight: 800; color: #1C1917; margin-bottom: 6px; }
.auth-form-sub   { font-size: 13px; color: #6b7280; margin-bottom: 24px; line-height: 1.6; }

/* Form */
.form-group { display: flex; flex-direction: column; gap: 7px; margin-bottom: 18px; }
.form-group label { font-size: 13px; font-weight: 600; color: #1C1917; }
.input-icon-wrap { position: relative; }
.input-icon {
  position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
  color: #9ca3af; font-size: 16px; pointer-events: none;
}
.input-icon-wrap input {
  width: 100%; padding: 11px 14px 11px 40px;
  border: 1.5px solid #e5e7eb; border-radius: 10px;
  font-size: 14px; font-family: inherit; color: #1C1917;
  background: #fff; transition: border-color .2s, box-shadow .2s;
  box-sizing: border-box;
}
.input-icon-wrap input:focus {
  outline: none; border-color: #F59E0B;
  box-shadow: 0 0 0 3px rgba(245,158,11,.12);
}

/* Alerts */
.alert {
  display: flex; align-items: flex-start; gap: 10px;
  padding: 13px 16px; border-radius: 10px;
  font-size: 13px; margin-bottom: 16px;
}
.alert i { font-size: 18px; flex-shrink: 0; }
.alert-error   { background: #fef2f2; color: #dc2626; border: 1px solid #fca5a5; }
.alert-success { background: #ECFDF5; color: #15803d; border: 1px solid #86efac; }
.alert-demo    { background: #FFFBEB; color: #D97706; border: 1px solid #fde68a; font-size: 12px; }

/* Button */
.btn-submit {
  display: flex; align-items: center; justify-content: center; gap: 8px;
  width: 100%; padding: 13px;
  background: linear-gradient(135deg, #F59E0B, #D97706);
  color: #1C1917; border: none; border-radius: 10px;
  font-size: 14px; font-weight: 800; cursor: pointer; font-family: inherit;
  box-shadow: 0 3px 10px rgba(245,158,11,.3);
  transition: opacity .15s, transform .1s;
}
.btn-submit:hover { opacity: .92; transform: translateY(-1px); }

.back-to-login {
  display: flex; align-items: center; justify-content: center; gap: 6px;
  margin-top: 20px; font-size: 13px; color: #6b7280; text-decoration: none;
}
.back-to-login:hover { color: #F59E0B; }

/* Success state */
.success-wrap { text-align: center; padding: 12px 0; }
.success-icon {
  width: 72px; height: 72px; border-radius: 50%;
  background: linear-gradient(135deg, #ECFDF5, #D1FAE5);
  border: 2px solid #86efac;
  display: flex; align-items: center; justify-content: center;
  font-size: 32px; margin: 0 auto 20px;
  color: #16a34a;
}
.success-wrap h3 { font-size: 20px; font-weight: 800; color: #1C1917; margin-bottom: 8px; }
.success-wrap p  { font-size: 13px; color: #6b7280; line-height: 1.65; margin-bottom: 24px; }

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

  <!-- ── Panneau gauche ── -->
  <div class="auth-brand">
    <img
      class="auth-brand-img"
      src="https://images.unsplash.com/photo-1531482615713-2afd69097998?w=900&q=85&auto=format&fit=crop"
      alt="Formation entrepreneuriat"
      loading="eager"
    >
    <div class="auth-brand-overlay"></div>
    <div class="auth-brand-content">
      <a href="<?= SITE_URL ?>" class="auth-logo">
        <div class="auth-logo-mark">E</div>
        <span class="auth-logo-name"><?= SITE_NAME ?></span>
      </a>

      <div class="auth-hero">
        <h1>Récupérer<br><em>votre compte.</em></h1>
        <p>Un lien sécurisé sera envoyé à votre adresse e-mail pour réinitialiser votre mot de passe.</p>
        <div class="auth-steps">
          <div class="auth-step">
            <div class="auth-step-num">1</div>
            <div class="auth-step-text">
              <strong>Saisissez votre email</strong>
              <span>L'adresse utilisée lors de votre inscription</span>
            </div>
          </div>
          <div class="auth-step">
            <div class="auth-step-num">2</div>
            <div class="auth-step-text">
              <strong>Consultez votre boîte mail</strong>
              <span>Un lien sécurisé valable 1 heure vous sera envoyé</span>
            </div>
          </div>
          <div class="auth-step">
            <div class="auth-step-num">3</div>
            <div class="auth-step-text">
              <strong>Choisissez un nouveau mot de passe</strong>
              <span>Et reconnectez-vous immédiatement</span>
            </div>
          </div>
        </div>
      </div>

      <a href="<?= SITE_URL ?>/login.php" class="auth-back-link">
        <i class="ti ti-arrow-left" aria-hidden="true"></i> Retour à la connexion
      </a>
    </div>
  </div>

  <!-- ── Panneau droit ── -->
  <div class="auth-form-panel">
    <div class="auth-form-box">

      <?php if ($sent): ?>

        <!-- État succès -->
        <div class="success-wrap">
          <div class="success-icon">
            <i class="ti ti-mail-check" aria-hidden="true"></i>
          </div>
          <h3>Email envoyé !</h3>
          <p>
            Si cette adresse est associée à un compte, tu recevras un lien de réinitialisation dans quelques minutes.
            Vérifie aussi ton dossier spam.
          </p>

          <?php if ($resetLink): ?>
          <div class="alert alert-demo" style="text-align:left">
            <i class="ti ti-info-circle" style="flex-shrink:0"></i>
            <div>
              <strong>Mode démo</strong> (SMTP non configuré) :<br>
              <a href="<?= h($resetLink) ?>" style="color:#D97706;word-break:break-all;font-size:11px">
                <?= h($resetLink) ?>
              </a>
            </div>
          </div>
          <?php endif; ?>

          <a href="<?= SITE_URL ?>/login.php" class="btn-submit" style="text-decoration:none;margin-top:8px">
            <i class="ti ti-login" aria-hidden="true"></i> Retour à la connexion
          </a>
        </div>

      <?php else: ?>

        <div class="auth-icon-wrap">
          <i class="ti ti-lock-question" style="font-size:28px;color:#D97706" aria-hidden="true"></i>
        </div>
        <h2 class="auth-form-title">Mot de passe oublié ?</h2>
        <p class="auth-form-sub">
          Saisis ton adresse e-mail et nous t'enverrons un lien pour réinitialiser ton mot de passe.
        </p>

        <?php if ($message): ?>
          <div class="alert alert-error">
            <i class="ti ti-alert-circle"></i> <?= h($message) ?>
          </div>
        <?php endif; ?>

        <form method="POST" novalidate>
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
          <button type="submit" class="btn-submit">
            <i class="ti ti-send" aria-hidden="true"></i> Envoyer le lien
          </button>
        </form>

        <a href="<?= SITE_URL ?>/login.php" class="back-to-login">
          <i class="ti ti-arrow-left" aria-hidden="true"></i> Retour à la connexion
        </a>

      <?php endif; ?>

    </div>
  </div>

</div>
</body>
</html>