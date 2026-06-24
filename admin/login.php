<?php
// ============================================================
//  admin/login.php — Page de connexion exclusive a l'admin
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

sendSecurityHeaders();

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
        $rateLimitKey = 'admin_login_' . $email;
        if (checkRateLimit($rateLimitKey, MAX_LOGIN_ATTEMPTS, LOGIN_LOCKOUT_MINUTES * 60)) {
            $erreur = 'Acces temporairement bloque. Reessayez dans ' . LOGIN_LOCKOUT_MINUTES . ' minutes.';
        } else {
            $pdo  = getPDO();
            $stmt = $pdo->prepare('SELECT * FROM admins WHERE email = ? AND actif = 1 LIMIT 1');
            $stmt->execute([$email]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($mdp, $admin['password'])) {
                clearAttempts($rateLimitKey);
                logAction('admin_login_success', 'Connexion admin reussie pour ' . $email);
                connecterAdmin($admin);
                redirect(SITE_URL . '/admin/index.php', 'Bienvenue dans l\'espace administration.', 'success');
            } else {
                recordFailedAttempt($rateLimitKey);
                logAction('admin_login_failed', 'Echec connexion admin pour ' . $email);
                sleep(1);
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
<title>Administration - Connexion - <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
<style>
/* ---- Auth layout ---- */
body { margin:0; padding:0; min-height:100vh; display:flex; align-items:stretch; background:#f9fafb; font-family:'Plus Jakarta Sans',sans-serif; }
.auth-container { display:flex; width:100%; min-height:100vh; }

/* Panneau gauche */
.auth-brand {
  width: 420px; flex-shrink: 0;
  background: linear-gradient(160deg, #1E1040 0%, #150C30 100%);
  display: flex; flex-direction: column; justify-content: center;
  padding: 48px 40px;
}
.auth-logo { display:flex; align-items:center; gap:12px; margin-bottom:40px; text-decoration:none; }
.auth-logo .logo-mark {
  width:48px; height:48px; font-size:20px; font-weight:700;
  background:linear-gradient(135deg,#10B981,#34D399);
  color:#fff; border-radius:12px;
  display:flex; align-items:center; justify-content:center;
  box-shadow:0 4px 12px rgba(108,71,212,.4);
}
.auth-logo-name { font-size:18px; font-weight:700; color:#EDE9FE; }
.auth-brand h1 { font-size:32px; font-weight:700; color:#fff; line-height:1.2; margin-bottom:12px; }
.auth-brand p  { font-size:14px; color:rgba(255,255,255,.5); line-height:1.8; margin-bottom:32px; }
.auth-perks    { list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:14px; }
.auth-perks li { display:flex; align-items:center; gap:12px; font-size:13px; color:rgba(255,255,255,.65); }
.auth-perks li i { font-size:18px; color:#34D399; flex-shrink:0; }

/* Panneau droit */
.auth-form-panel {
  flex:1; display:flex; align-items:center; justify-content:center;
  padding:48px 28px; background:#fff;
}
.auth-form-box { width:100%; max-width:400px; }
.auth-badge {
  display:inline-flex; align-items:center; gap:6px;
  background:#D1FAE5; color:#059669;
  border-radius:20px; padding:5px 14px;
  font-size:12px; font-weight:700;
  margin-bottom:20px;
  border: 1px solid rgba(83,74,183,.2);
}
.auth-form-box h2 { font-size:24px; font-weight:700; color:#111827; margin-bottom:6px; }
.auth-form-box p  { font-size:14px; color:#6b7280; margin-bottom:28px; }

/* Alert */
.alert { display:flex; align-items:flex-start; gap:10px; padding:13px 16px; border-radius:10px; font-size:13px; margin-bottom:16px; }
.alert i { font-size:18px; flex-shrink:0; }
.alert-error { background:#fef2f2; color:#dc2626; border:1px solid #fca5a5; }
.alert-success { background:#dcfce7; color:#15803d; border:1px solid #86efac; }

/* Form */
.form-group { display:flex; flex-direction:column; gap:7px; margin-bottom:16px; }
.form-group label { font-size:13px; font-weight:600; color:#111827; }
.input-wrap { position:relative; }
.input-wrap i.icon { position:absolute; left:13px; top:50%; transform:translateY(-50%); color:#9ca3af; font-size:16px; pointer-events:none; }
.input-wrap input {
  width:100%; padding:11px 14px 11px 40px;
  border:1.5px solid #e5e7eb; border-radius:10px;
  font-size:14px; font-family:inherit; color:#111827;
  background:#fff; box-sizing:border-box;
  transition:border-color .2s, box-shadow .2s;
}
.input-wrap input:focus { outline:none; border-color:#10B981; box-shadow:0 0 0 4px rgba(108,71,212,.12); }
.input-wrap .toggle-pw {
  position:absolute; right:10px; top:50%; transform:translateY(-50%);
  background:none; border:none; cursor:pointer; color:#9ca3af; padding:4px;
  display:flex; align-items:center;
}

/* Submit */
.btn-submit {
  width:100%; padding:12px; margin-top:8px;
  background:linear-gradient(135deg,#059669,#10B981);
  color:#fff; border:none; border-radius:10px;
  font-size:14px; font-weight:700; font-family:inherit;
  cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px;
  box-shadow:0 2px 8px rgba(83,74,183,.3);
  transition:opacity .15s, transform .1s;
}
.btn-submit:hover { opacity:.9; transform:translateY(-1px); }

.back-link { text-align:center; margin-top:24px; font-size:12px; color:#9ca3af; }
.back-link a { color:#9ca3af; text-decoration:none; }
.back-link a:hover { color:#10B981; }

@media (max-width: 768px) {
  .auth-brand { display:none; }
  .auth-form-panel { padding:28px 20px; }
}
</style>
</head>
<body>
<div class="auth-container">

  <!-- Panneau gauche -->
  <div class="auth-brand">
    <a href="<?= SITE_URL ?>" class="auth-logo">
      <div class="logo-mark">E</div>
      <span class="auth-logo-name"><?= SITE_NAME ?></span>
    </a>
    <h1>Espace<br>Administration</h1>
    <p>Acces reserve aux administrateurs de la plateforme.</p>
    <ul class="auth-perks">
      <li><i class="ti ti-shield-check"></i> Acces securise et chiffre</li>
      <li><i class="ti ti-lock"></i> Session isolee des apprenants</li>
      <li><i class="ti ti-eye-off"></i> Protection anti-bruteforce</li>
    </ul>
  </div>

  <!-- Panneau droit -->
  <div class="auth-form-panel">
    <div class="auth-form-box">

      <div class="auth-badge">
        <i class="ti ti-shield"></i> Administrateur
      </div>

      <h2>Connexion Admin</h2>
      <p>Reserve aux comptes administrateurs.</p>

      <?php if ($erreur): ?>
        <div class="alert alert-error">
          <i class="ti ti-alert-circle"></i> <?= h($erreur) ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($_GET['error']) && $_GET['error'] === 'acces_refuse'): ?>
        <div class="alert alert-error">
          <i class="ti ti-lock"></i> Acces refuse. Connectez-vous d'abord.
        </div>
      <?php endif; ?>

      <?= flash() ?>

      <form method="POST" action="login.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <div class="form-group">
          <label for="email">Adresse e-mail</label>
          <div class="input-wrap">
            <i class="ti ti-mail icon"></i>
            <input type="email" id="email" name="email"
                   value="<?= h($_POST['email'] ?? '') ?>"
                   placeholder="admin@example.com" required autocomplete="email">
          </div>
        </div>

        <div class="form-group">
          <label for="password">Mot de passe</label>
          <div class="input-wrap">
            <i class="ti ti-lock icon"></i>
            <input type="password" id="password" name="password"
                   placeholder="••••••••" required autocomplete="current-password"
                   style="padding-right:44px">
            <button type="button" class="toggle-pw" onclick="togglePw(this)">
              <i class="ti ti-eye"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-submit">
          <i class="ti ti-login"></i> Acceder au panneau admin
        </button>
      </form>

      <div class="back-link">
        <a href="<?= SITE_URL ?>">← Retour au site public</a>
      </div>

    </div>
  </div>

</div>
<script>
function togglePw(btn) {
  const input = btn.closest('.input-wrap').querySelector('input');
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