<?php
// ============================================================
//  reset_password.php — Réinitialisation du mot de passe
// ============================================================
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

sendSecurityHeaders();

if (estConnecte()) redirect(SITE_URL . '/dashboard.php');

$token   = trim($_GET['token'] ?? '');
$erreur  = '';
$success = false;
$reset   = null;

if (!$token) {
    redirect(SITE_URL . '/forgot_password.php', 'Lien invalide ou expiré.', 'error');
}

$pdo  = getPDO();
$stmt = $pdo->prepare(
    'SELECT pr.*, u.email as user_email FROM password_resets pr
     JOIN users u ON u.id = pr.user_id
     WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()
     LIMIT 1'
);
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    redirect(SITE_URL . '/forgot_password.php', 'Lien invalide ou expiré. Faites une nouvelle demande.', 'error');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCSRF();
    $mdp1 = $_POST['password'] ?? '';
    $mdp2 = $_POST['password_confirm'] ?? '';

    if (strlen($mdp1) < 8)          $erreur = 'Le mot de passe doit comporter au moins 8 caractères.';
    elseif (!preg_match('/[A-Z]/', $mdp1))        $erreur = 'Le mot de passe doit contenir au moins une majuscule.';
    elseif (!preg_match('/[0-9]/', $mdp1))        $erreur = 'Le mot de passe doit contenir au moins un chiffre.';
    elseif (!preg_match('/[^A-Za-z0-9]/', $mdp1)) $erreur = 'Le mot de passe doit contenir au moins un caractère spécial.';
    elseif ($mdp1 !== $mdp2)        $erreur = 'Les mots de passe ne correspondent pas.';
    else {
        $hash = password_hash($mdp1, PASSWORD_BCRYPT, ['cost' => 12]);
        $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$hash, $reset['user_id']]);
        $pdo->prepare('UPDATE password_resets SET used = 1 WHERE token = ?')->execute([$token]);
        logAction('password_reset_done', 'Mot de passe réinitialisé pour ' . $reset['email'], $reset['user_id']);
        redirect(SITE_URL . '/login.php', 'Mot de passe mis à jour. Vous pouvez vous connecter.', 'success');
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Nouveau mot de passe — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Plus Jakarta Sans',sans-serif;min-height:100vh;background:#FFFBEB}

.auth-container{display:flex;min-height:100vh}

/* Panneau gauche */
.auth-brand{width:48%;flex-shrink:0;position:relative;overflow:hidden}
.auth-brand-img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center top}
.auth-brand-overlay{
  position:absolute;inset:0;
  background:linear-gradient(155deg,rgba(28,25,23,.88) 0%,rgba(28,25,23,.55) 50%,rgba(245,158,11,.25) 100%);
}
.auth-brand-content{
  position:relative;z-index:2;padding:44px 40px;height:100%;
  display:flex;flex-direction:column;justify-content:space-between;
}
.auth-logo{display:flex;align-items:center;gap:12px;text-decoration:none}
.auth-logo-mark{
  width:44px;height:44px;border-radius:12px;
  background:linear-gradient(135deg,#F59E0B,#EF4444);
  display:flex;align-items:center;justify-content:center;
  font-size:19px;font-weight:800;color:#fff;flex-shrink:0;
  box-shadow:0 4px 14px rgba(245,158,11,.4);
}
.auth-logo-name{font-size:17px;font-weight:700;color:#FEF3C7}

.auth-hero{flex:1;display:flex;flex-direction:column;justify-content:center;padding:28px 0}
.auth-hero h1{font-size:32px;font-weight:800;color:#fff;line-height:1.2;margin-bottom:14px}
.auth-hero h1 em{font-style:normal;color:#F59E0B}
.auth-hero p{font-size:14px;color:rgba(255,255,255,.7);line-height:1.75;max-width:340px;margin-bottom:28px}

.auth-steps{display:flex;flex-direction:column;gap:14px}
.auth-step{display:flex;align-items:flex-start;gap:12px}
.auth-step-num{
  width:28px;height:28px;border-radius:50%;flex-shrink:0;
  background:rgba(245,158,11,.2);border:1px solid rgba(245,158,11,.4);
  display:flex;align-items:center;justify-content:center;
  font-size:12px;font-weight:800;color:#F59E0B;
}
.auth-step-text strong{display:block;font-size:13px;color:#fff;margin-bottom:2px}
.auth-step-text span{font-size:12px;color:rgba(255,255,255,.5)}

.auth-back-link{
  display:inline-flex;align-items:center;gap:6px;
  font-size:12px;color:rgba(255,255,255,.4);text-decoration:none;
}
.auth-back-link:hover{color:#F59E0B}

/* Panneau droit */
.auth-form-panel{
  flex:1;display:flex;align-items:center;justify-content:center;
  padding:48px 32px;background:#fff;
}
.auth-form-box{width:100%;max-width:400px}

.auth-icon-wrap{
  width:64px;height:64px;border-radius:16px;
  background:linear-gradient(135deg,#FEF3C7,#FDE68A);
  border:2px solid #F59E0B;
  display:flex;align-items:center;justify-content:center;
  margin-bottom:20px;
}
.auth-form-title{font-size:24px;font-weight:800;color:#1C1917;margin-bottom:6px}
.auth-form-sub{font-size:13px;color:#6b7280;margin-bottom:24px;line-height:1.6}

.form-group{display:flex;flex-direction:column;gap:7px;margin-bottom:16px}
.form-group label{font-size:13px;font-weight:600;color:#1C1917}
.input-icon-wrap{position:relative}
.input-icon{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:16px;pointer-events:none}
.input-icon-wrap input{
  width:100%;padding:11px 42px 11px 40px;
  border:1.5px solid #e5e7eb;border-radius:10px;
  font-size:14px;font-family:inherit;color:#1C1917;
  background:#fff;transition:border-color .2s,box-shadow .2s;box-sizing:border-box;
}
.input-icon-wrap input:focus{outline:none;border-color:#F59E0B;box-shadow:0 0 0 3px rgba(245,158,11,.12)}
.input-toggle-pw{
  position:absolute;right:10px;top:50%;transform:translateY(-50%);
  background:none;border:none;cursor:pointer;color:#9ca3af;padding:4px;display:flex;align-items:center;
}
.input-toggle-pw:hover{color:#F59E0B}

/* Strength */
.strength-bars{display:flex;gap:4px;margin-top:6px;margin-bottom:3px}
.strength-bar{flex:1;height:4px;border-radius:2px;background:#e5e7eb;transition:background .3s}
.pw-requirements{list-style:none;padding:0;margin:6px 0 0;display:flex;flex-direction:column;gap:3px}
.pw-requirements li{font-size:11px;color:#9ca3af;display:flex;align-items:center;gap:5px;transition:color .2s}
.pw-requirements li.ok{color:#16a34a}
.pw-requirements li::before{content:'○';font-size:10px}
.pw-requirements li.ok::before{content:'✓'}

.alert{display:flex;align-items:flex-start;gap:10px;padding:13px 16px;border-radius:10px;font-size:13px;margin-bottom:16px}
.alert i{font-size:18px;flex-shrink:0}
.alert-error{background:#fef2f2;color:#dc2626;border:1px solid #fca5a5}

.btn-submit{
  display:flex;align-items:center;justify-content:center;gap:8px;
  width:100%;padding:13px;
  background:linear-gradient(135deg,#F59E0B,#D97706);
  color:#1C1917;border:none;border-radius:10px;
  font-size:14px;font-weight:800;cursor:pointer;font-family:inherit;
  box-shadow:0 3px 10px rgba(245,158,11,.3);
  transition:opacity .15s,transform .1s;
}
.btn-submit:hover{opacity:.92;transform:translateY(-1px)}

.back-to-login{
  display:flex;align-items:center;justify-content:center;gap:6px;
  margin-top:20px;font-size:13px;color:#6b7280;text-decoration:none;
}
.back-to-login:hover{color:#F59E0B}

/* Mobile */
@media(max-width:860px){
  .auth-brand{display:none}
  .auth-form-panel{
    padding:32px 20px;
    background-image:url('https://images.unsplash.com/photo-1531482615713-2afd69097998?w=900&q=85&auto=format&fit=crop');
    background-size:cover;background-position:center;position:relative;
  }
  .auth-form-panel::before{
    content:'';position:absolute;inset:0;
    background:rgba(28,25,23,.75);z-index:0;
  }
  .auth-form-box{
    position:relative;z-index:1;
    background:rgba(255,255,255,.92);
    backdrop-filter:blur(12px);
    -webkit-backdrop-filter:blur(12px);
    border-radius:20px;padding:32px 28px;
    box-shadow:0 8px 40px rgba(0,0,0,.25);
    border:1px solid rgba(255,255,255,.3);
  }
}
</style>
</head>
<body>

<div class="auth-container">

  <!-- Panneau gauche -->
  <div class="auth-brand">
    <img class="auth-brand-img"
         src="https://images.unsplash.com/photo-1531482615713-2afd69097998?w=900&q=85&auto=format&fit=crop"
         alt="Formation entrepreneuriat" loading="eager">
    <div class="auth-brand-overlay"></div>
    <div class="auth-brand-content">
      <a href="<?= SITE_URL ?>" class="auth-logo">
        <div class="auth-logo-mark">E</div>
        <span class="auth-logo-name"><?= SITE_NAME ?></span>
      </a>

      <div class="auth-hero">
        <h1>Nouveau<br><em>mot de passe.</em></h1>
        <p>Choisissez un mot de passe sécurisé pour protéger votre compte.</p>
        <div class="auth-steps">
          <div class="auth-step">
            <div class="auth-step-num">1</div>
            <div class="auth-step-text">
              <strong>Au moins 8 caractères</strong>
              <span>Plus c'est long, plus c'est sécurisé</span>
            </div>
          </div>
          <div class="auth-step">
            <div class="auth-step-num">2</div>
            <div class="auth-step-text">
              <strong>Une majuscule + un chiffre</strong>
              <span>Mélangez les types de caractères</span>
            </div>
          </div>
          <div class="auth-step">
            <div class="auth-step-num">3</div>
            <div class="auth-step-text">
              <strong>Un caractère spécial</strong>
              <span>Comme ! @ # $ % & *</span>
            </div>
          </div>
        </div>
      </div>

      <a href="<?= SITE_URL ?>/login.php" class="auth-back-link">
        <i class="ti ti-arrow-left" aria-hidden="true"></i> Retour à la connexion
      </a>
    </div>
  </div>

  <!-- Panneau droit -->
  <div class="auth-form-panel">
    <div class="auth-form-box">

      <div class="auth-icon-wrap">
        <i class="ti ti-lock-check" style="font-size:28px;color:#D97706" aria-hidden="true"></i>
      </div>
      <h2 class="auth-form-title">Nouveau mot de passe</h2>
      <p class="auth-form-sub">
        Compte : <strong><?= h($reset['user_email']) ?></strong>
      </p>

      <?php if ($erreur): ?>
        <div class="alert alert-error">
          <i class="ti ti-alert-circle"></i> <?= h($erreur) ?>
        </div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="token" value="<?= h($token) ?>">

        <div class="form-group">
          <label for="password">Nouveau mot de passe</label>
          <div class="input-icon-wrap">
            <i class="ti ti-lock input-icon" aria-hidden="true"></i>
            <input type="password" id="password" name="password"
                   placeholder="Min. 8 caractères" required
                   oninput="checkStrength(this.value)">
            <button type="button" class="input-toggle-pw" onclick="togglePw('password',this)">
              <i class="ti ti-eye"></i>
            </button>
          </div>
          <div class="strength-bars">
            <div class="strength-bar" id="bar1"></div>
            <div class="strength-bar" id="bar2"></div>
            <div class="strength-bar" id="bar3"></div>
            <div class="strength-bar" id="bar4"></div>
          </div>
          <span id="strength-text" style="font-size:11px;color:#9ca3af"></span>
          <ul class="pw-requirements">
            <li id="req-length">Au moins 8 caractères</li>
            <li id="req-upper">Une lettre majuscule</li>
            <li id="req-number">Un chiffre</li>
            <li id="req-special">Un caractère spécial (!@#$...)</li>
          </ul>
        </div>

        <div class="form-group">
          <label for="password_confirm">Confirmer le mot de passe</label>
          <div class="input-icon-wrap">
            <i class="ti ti-lock input-icon" aria-hidden="true"></i>
            <input type="password" id="password_confirm" name="password_confirm"
                   placeholder="Répéter le mot de passe" required
                   oninput="checkMatch()">
            <button type="button" class="input-toggle-pw" onclick="togglePw('password_confirm',this)">
              <i class="ti ti-eye"></i>
            </button>
          </div>
          <span id="match-text" style="font-size:11px;display:block;margin-top:3px"></span>
        </div>

        <button type="submit" class="btn-submit">
          <i class="ti ti-check" aria-hidden="true"></i> Enregistrer le nouveau mot de passe
        </button>
      </form>

      <a href="<?= SITE_URL ?>/login.php" class="back-to-login">
        <i class="ti ti-arrow-left" aria-hidden="true"></i> Retour à la connexion
      </a>

    </div>
  </div>

</div>

<script>
function togglePw(id, btn) {
  const input = document.getElementById(id);
  input.type  = input.type === 'password' ? 'text' : 'password';
  btn.querySelector('i').className = input.type === 'password' ? 'ti ti-eye' : 'ti ti-eye-off';
}

function checkStrength(val) {
  const checks = {
    length:  val.length >= 8,
    upper:   /[A-Z]/.test(val),
    number:  /[0-9]/.test(val),
    special: /[^A-Za-z0-9]/.test(val),
  };
  document.getElementById('req-length').classList.toggle('ok',  checks.length);
  document.getElementById('req-upper').classList.toggle('ok',   checks.upper);
  document.getElementById('req-number').classList.toggle('ok',  checks.number);
  document.getElementById('req-special').classList.toggle('ok', checks.special);

  const score = Object.values(checks).filter(Boolean).length;
  const colors  = ['#e5e7eb','#dc2626','#F59E0B','#eab308','#16a34a'];
  const labels  = ['','Trop faible','Moyen','Fort','Très fort 🔒'];
  const txtClrs = ['#9ca3af','#dc2626','#D97706','#ca8a04','#16a34a'];

  for (let i = 1; i <= 4; i++) {
    document.getElementById('bar'+i).style.background = i <= score ? colors[score] : '#e5e7eb';
  }
  const txt = document.getElementById('strength-text');
  txt.textContent = val.length > 0 ? labels[score] : '';
  txt.style.color = txtClrs[score];
}

function checkMatch() {
  const pw  = document.getElementById('password').value;
  const cfm = document.getElementById('password_confirm').value;
  const txt = document.getElementById('match-text');
  if (!cfm) { txt.textContent = ''; return; }
  txt.textContent = pw === cfm ? '✓ Les mots de passe correspondent' : '✗ Ne correspondent pas';
  txt.style.color = pw === cfm ? '#16a34a' : '#dc2626';
}
</script>
</body>
</html>