<?php
// ============================================================
//  register.php — Inscription utilisateur
// ============================================================
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (estConnecte()) {
    redirect(SITE_URL . '/dashboard.php');
}

$erreurs = [];
$vals    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCSRF();

    $vals = [
        'nom'      => trim($_POST['nom']       ?? ''),
        'prenom'   => trim($_POST['prenom']    ?? ''),
        'email'    => trim($_POST['email']     ?? ''),
        'password' => $_POST['password']       ?? '',
        'confirm'  => $_POST['confirm']        ?? '',
        'telephone'=> trim($_POST['telephone'] ?? ''),
        'ville'    => trim($_POST['ville']     ?? ''),
    ];

    if (!$vals['nom'])    $erreurs[] = 'Le nom est requis.';
    if (!$vals['prenom']) $erreurs[] = 'Le prénom est requis.';
    if (!filter_var($vals['email'], FILTER_VALIDATE_EMAIL)) $erreurs[] = 'Email invalide.';
    if (strlen($vals['password']) < 8)            $erreurs[] = 'Le mot de passe doit faire au moins 8 caractères.';
    elseif (!preg_match('/[A-Z]/', $vals['password']))        $erreurs[] = 'Le mot de passe doit contenir au moins une majuscule.';
    elseif (!preg_match('/[0-9]/', $vals['password']))        $erreurs[] = 'Le mot de passe doit contenir au moins un chiffre.';
    elseif (!preg_match('/[^A-Za-z0-9]/', $vals['password'])) $erreurs[] = 'Le mot de passe doit contenir au moins un caractère spécial.';
    if ($vals['password'] !== $vals['confirm']) $erreurs[] = 'Les mots de passe ne correspondent pas.';

    if (empty($erreurs)) {
        $pdo  = getPDO();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$vals['email']]);
        if ($stmt->fetch()) $erreurs[] = 'Cette adresse e-mail est déjà utilisée.';
    }

    if (empty($erreurs)) {
        $pdo  = getPDO();
        $hash = password_hash($vals['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $pdo->prepare(
            'INSERT INTO users (nom, prenom, email, password, telephone, ville) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$vals['nom'], $vals['prenom'], $vals['email'], $hash, $vals['telephone'], $vals['ville']]);
        $userId = $pdo->lastInsertId();

        $user = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $user->execute([$userId]);
        connecterUtilisateur($user->fetch());
    triggerAutomation('user_registered', $userId, 0);
        emailBienvenue($vals['email'], $vals['prenom']);
        redirect(SITE_URL . '/payment.php', 'Bienvenue, ' . $vals['prenom'] . ' ! Ton compte est créé. Choisis maintenant ton parcours.', 'success');
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Créer un compte — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Plus Jakarta Sans', sans-serif; min-height: 100vh; background: #F5F0E8; }

.auth-container { display: flex; min-height: 100vh; }

/* ── Panneau gauche : image ── */
.auth-brand {
  width: 48%;
  flex-shrink: 0;
  position: relative;
  overflow: hidden;
}
.auth-brand-img {
  position: absolute; inset: 0;
  width: 100%; height: 100%;
  object-fit: cover; object-position: center top;
}
.auth-brand-overlay {
  position: absolute; inset: 0;
  background: linear-gradient(
    155deg,
    rgba(28,25,23,.85) 0%,
    rgba(28,25,23,.5) 45%,
    rgba(239,68,68,.3) 100%
  );
}
.auth-brand-content {
  position: relative; z-index: 2;
  padding: 44px 40px;
  height: 100%;
  display: flex; flex-direction: column;
  justify-content: space-between;
}
.auth-logo { display: flex; align-items: center; gap: 12px; text-decoration: none; }
.auth-logo-mark {
  width: 44px; height: 44px; border-radius: 12px;
  background: linear-gradient(135deg, #D85A30, #085041);
  display: flex; align-items: center; justify-content: center;
  font-size: 19px; font-weight: 800; color: #fff; flex-shrink: 0;
  box-shadow: 0 4px 14px rgba(245,158,11,.4);
}
.auth-logo-name { font-size: 17px; font-weight: 700; color: #FBE3DA; }

.auth-hero { flex: 1; display: flex; flex-direction: column; justify-content: center; padding: 28px 0; }
.auth-hero h1 {
  font-size: 34px; font-weight: 800; color: #fff;
  line-height: 1.2; margin-bottom: 14px;
}
.auth-hero h1 em { font-style: normal; color: #D85A30; }
.auth-hero p {
  font-size: 14px; color: rgba(255,255,255,.7);
  line-height: 1.75; max-width: 360px; margin-bottom: 28px;
}
.auth-checklist { display: flex; flex-direction: column; gap: 11px; }
.auth-check {
  display: flex; align-items: center; gap: 11px;
  font-size: 13px; color: rgba(255,255,255,.85);
}
.auth-check-ico {
  width: 28px; height: 28px; border-radius: 8px; flex-shrink: 0;
  background: rgba(245,158,11,.2); border: 1px solid rgba(245,158,11,.3);
  display: flex; align-items: center; justify-content: center;
}
.auth-check-ico i { font-size: 15px; color: #D85A30; }

.auth-stats-bar {
  display: flex; gap: 20px; flex-wrap: wrap;
  padding-top: 24px;
  border-top: 1px solid rgba(255,255,255,.1);
}
.auth-stat-item { text-align: center; }
.auth-stat-item strong { display: block; font-size: 20px; font-weight: 800; color: #D85A30; }
.auth-stat-item span  { font-size: 11px; color: rgba(255,255,255,.5); }

/* ── Panneau droit : formulaire ── */
.auth-form-panel {
  flex: 1;
  display: flex; align-items: center; justify-content: center;
  padding: 40px 32px;
  background: #fff;
  overflow-y: auto;
}
.auth-form-box { width: 100%; max-width: 420px; }
.auth-form-title { font-size: 24px; font-weight: 800; color: #1A1A18; margin-bottom: 5px; }
.auth-form-sub { font-size: 13px; color: #6b7280; margin-bottom: 24px; }
.auth-form-sub a { color: #D85A30; font-weight: 600; text-decoration: none; }
.auth-form-sub a:hover { text-decoration: underline; }

/* Form */
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 14px; }
.form-group label { font-size: 13px; font-weight: 600; color: #1A1A18; }
.form-group input {
  padding: 10px 14px; border: 1.5px solid #e5e7eb;
  border-radius: 9px; font-size: 13px;
  font-family: inherit; color: #1A1A18;
  background: #fff; width: 100%;
  transition: border-color .2s, box-shadow .2s;
}
.form-group input:focus {
  outline: none; border-color: #D85A30;
  box-shadow: 0 0 0 3px rgba(245,158,11,.12);
}
.input-icon-wrap { position: relative; }
.input-icon {
  position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
  color: #9ca3af; font-size: 15px; pointer-events: none;
}
.input-icon-wrap input { padding-left: 36px; padding-right: 38px; }
.input-toggle-pw {
  position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
  background: none; border: none; cursor: pointer;
  color: #9ca3af; padding: 4px; display: flex; align-items: center;
}
.input-toggle-pw:hover { color: #D85A30; }

/* Strength */
.strength-bars { display: flex; gap: 4px; margin-top: 6px; margin-bottom: 3px; }
.strength-bar { flex: 1; height: 4px; border-radius: 2px; background: #e5e7eb; transition: background .3s; }
.pw-requirements { list-style: none; padding: 0; margin: 6px 0 0; display: flex; flex-direction: column; gap: 3px; }
.pw-requirements li { font-size: 11px; color: #9ca3af; display: flex; align-items: center; gap: 5px; transition: color .2s; }
.pw-requirements li.ok { color: #16a34a; }
.pw-requirements li::before { content: '○'; font-size: 10px; }
.pw-requirements li.ok::before { content: '✓'; }

/* Alert */
.alert {
  display: flex; align-items: flex-start; gap: 10px;
  padding: 13px 16px; border-radius: 10px;
  font-size: 13px; margin-bottom: 16px;
}
.alert i { font-size: 18px; flex-shrink: 0; }
.alert-error { background: #F0F9F5; color: #dc2626; border: 1px solid #fca5a5; }

/* Button */
.btn-submit {
  display: flex; align-items: center; justify-content: center; gap: 8px;
  width: 100%; padding: 13px; margin-top: 6px;
  background: linear-gradient(135deg, #D85A30, #085041);
  color: #fff; border: none; border-radius: 10px;
  font-size: 14px; font-weight: 800; cursor: pointer;
  font-family: inherit;
  box-shadow: 0 3px 10px rgba(245,158,11,.3);
  transition: opacity .15s, transform .1s;
}
.btn-submit:hover { opacity: .92; transform: translateY(-1px); }

.auth-cgu {
  font-size: 11px; color: #9ca3af;
  text-align: center; margin-top: 12px; line-height: 1.6;
}
.auth-cgu a { color: #D85A30; text-decoration: none; }

/* Bouton Google */
.auth-divider {
  display: flex; align-items: center; gap: 12px;
  margin: 16px 0; font-size: 12px; color: #9ca3af;
}
.auth-divider::before, .auth-divider::after {
  content: ''; flex: 1; height: 1px; background: #e5e7eb;
}
.btn-google {
  display: flex; align-items: center; justify-content: center; gap: 10px;
  width: 100%; padding: 12px;
  background: #fff; color: #1A1A18;
  border: 1.5px solid #e5e7eb; border-radius: 10px;
  font-size: 14px; font-weight: 600; text-decoration: none;
  font-family: inherit;
  transition: background .15s, border-color .15s;
}
.btn-google:hover { background: #f9fafb; border-color: #d1d5db; }

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
@media (max-width: 480px) {
  .form-row { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<div class="auth-container">

  <!-- ── Panneau gauche : image ── -->
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
        <h1>Lance ton<br><em>aventure.</em></h1>
        <p>
          Inscription gratuite. Accès immédiat aux cours gratuits.
          Paie uniquement ce que tu veux approfondir.
        </p>
        <div class="auth-checklist">
          <div class="auth-check">
            <div class="auth-check-ico"><i class="ti ti-school" aria-hidden="true"></i></div>
            <span>Cours gratuits illimités dès l'inscription</span>
          </div>
          <div class="auth-check">
            <div class="auth-check-ico"><i class="ti ti-certificate" aria-hidden="true"></i></div>
            <span>Certificat Université de Parakou à l'obtention</span>
          </div>
          <div class="auth-check">
            <div class="auth-check-ico"><i class="ti ti-headset" aria-hidden="true"></i></div>
            <span>Coaching 1:1 avec un mentor dédié</span>
          </div>
          <div class="auth-check">
            <div class="auth-check-ico"><i class="ti ti-device-mobile" aria-hidden="true"></i></div>
            <span>Paiement Mobile Money (MTN, Moov)</span>
          </div>
        </div>
      </div>

     
    </div>
  </div>

  <!-- ── Panneau droit : formulaire ── -->
  <div class="auth-form-panel">
    <div class="auth-form-box">

      <h2 class="auth-form-title">Créer mon compte </h2>
      <p class="auth-form-sub">
        Déjà inscrit ? <a href="<?= SITE_URL ?>/login.php">Se connecter</a>
      </p>

      <?php if (!empty($erreurs)): ?>
        <div class="alert alert-error">
          <i class="ti ti-alert-circle"></i>
          <ul style="margin:0;padding-left:16px">
            <?php foreach ($erreurs as $e): ?>
              <li><?= h($e) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="POST" action="register.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <div class="form-row">
          <div class="form-group">
            <label for="prenom">Prénom</label>
            <input type="text" id="prenom" name="prenom"
                   value="<?= h($vals['prenom'] ?? '') ?>"
                   placeholder="Fatou" required>
          </div>
          <div class="form-group">
            <label for="nom">Nom</label>
            <input type="text" id="nom" name="nom"
                   value="<?= h($vals['nom'] ?? '') ?>"
                   placeholder="DIALLO" required>
          </div>
        </div>

        <div class="form-group">
          <label for="email">Adresse e-mail</label>
          <div class="input-icon-wrap">
            <i class="ti ti-mail input-icon" aria-hidden="true"></i>
            <input type="email" id="email" name="email"
                   value="<?= h($vals['email'] ?? '') ?>"
                   placeholder="votre@email.com" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="telephone">Téléphone</label>
            <input type="tel" id="telephone" name="telephone"
                   value="<?= h($vals['telephone'] ?? '') ?>"
                   placeholder="+229 97 00 00 00">
          </div>
          <div class="form-group">
            <label for="ville">Ville</label>
            <input type="text" id="ville" name="ville"
                   value="<?= h($vals['ville'] ?? '') ?>"
                   placeholder="Cotonou">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="password">Mot de passe</label>
            <div class="input-icon-wrap">
              <i class="ti ti-lock input-icon" aria-hidden="true"></i>
              <input type="password" id="password" name="password"
                     placeholder="Min. 8 caractères" required
                     oninput="checkStrength(this.value)">
              <button type="button" class="input-toggle-pw" onclick="togglePw(this)">
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
            <label for="confirm">Confirmation</label>
            <div class="input-icon-wrap">
              <i class="ti ti-lock input-icon" aria-hidden="true"></i>
              <input type="password" id="confirm" name="confirm"
                     placeholder="Répéter" required
                     oninput="checkMatch()">
            </div>
            <span id="match-text" style="font-size:11px;display:block;margin-top:3px"></span>
          </div>
        </div>

        <button type="submit" class="btn-submit">
          <i class="ti ti-user-plus" aria-hidden="true"></i>
          Créer mon compte gratuitement
        </button>

        <p class="auth-cgu">
          En m'inscrivant j'accepte les
          <a href="<?= SITE_URL ?>/rgpd.php">conditions d'utilisation</a>
          et la politique de confidentialité.
        </p>
      </form>

      <div class="auth-divider">ou</div>

      <a href="<?= SITE_URL ?>/google_login.php" class="btn-google">
        <svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true">
          <path fill="#4285F4" d="M17.64 9.2c0-.64-.06-1.25-.16-1.84H9v3.48h4.84a4.14 4.14 0 0 1-1.8 2.72v2.26h2.9c1.7-1.57 2.7-3.88 2.7-6.62z"/>
          <path fill="#34A853" d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.9-2.26c-.8.55-1.84.87-3.06.87-2.36 0-4.36-1.6-5.08-3.74H.92v2.34A9 9 0 0 0 9 18z"/>
          <path fill="#FBBC05" d="M3.92 10.69A5.4 5.4 0 0 1 3.64 9c0-.59.1-1.16.28-1.69V4.97H.92A9 9 0 0 0 0 9c0 1.45.35 2.83.92 4.03l3-2.34z"/>
          <path fill="#EA4335" d="M9 3.58c1.32 0 2.5.45 3.44 1.35l2.58-2.58A9 9 0 0 0 9 0a9 9 0 0 0-8.08 4.97l3 2.34C4.64 5.18 6.64 3.58 9 3.58z"/>
        </svg>
        S'inscrire avec Google
      </a>

    </div>
  </div>

</div>

<script>
function togglePw(btn) {
  const input = btn.closest('.input-icon-wrap').querySelector('input');
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
  const colors  = ['#e5e7eb','#dc2626','#D85A30','#eab308','#16a34a'];
  const labels  = ['','Trop faible','Moyen','Fort','Très fort 🔒'];
  const txtClrs = ['#9ca3af','#dc2626','#C04A22','#ca8a04','#16a34a'];

  for (let i = 1; i <= 4; i++) {
    document.getElementById('bar'+i).style.background = i <= score ? colors[score] : '#e5e7eb';
  }
  const txt = document.getElementById('strength-text');
  txt.textContent = val.length > 0 ? labels[score] : '';
  txt.style.color = txtClrs[score];
}

function checkMatch() {
  const pw  = document.getElementById('password').value;
  const cfm = document.getElementById('confirm').value;
  const txt = document.getElementById('match-text');
  if (!cfm) { txt.textContent = ''; return; }
  txt.textContent = pw === cfm ? '✓ Les mots de passe correspondent' : '✗ Ne correspondent pas';
  txt.style.color = pw === cfm ? '#16a34a' : '#dc2626';
}
</script>
</body>
</html>