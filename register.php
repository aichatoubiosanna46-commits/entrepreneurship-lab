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
        'nom'      => trim($_POST['nom']      ?? ''),
        'prenom'   => trim($_POST['prenom']   ?? ''),
        'email'    => trim($_POST['email']    ?? ''),
        'password' => $_POST['password']      ?? '',
        'confirm'  => $_POST['confirm']       ?? '',
        'telephone'=> trim($_POST['telephone']?? ''),
        'ville'    => trim($_POST['ville']    ?? ''),
    ];

    if (!$vals['nom'])     $erreurs[] = 'Le nom est requis.';
    if (!$vals['prenom'])  $erreurs[] = 'Le prénom est requis.';
    if (!filter_var($vals['email'], FILTER_VALIDATE_EMAIL)) $erreurs[] = 'Email invalide.';
    if (strlen($vals['password']) < 8)  $erreurs[] = 'Le mot de passe doit faire au moins 8 caractères.';
    if ($vals['password'] !== $vals['confirm']) $erreurs[] = 'Les mots de passe ne correspondent pas.';

    if (empty($erreurs)) {
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$vals['email']]);
        if ($stmt->fetch()) {
            $erreurs[] = 'Cette adresse e-mail est déjà utilisée.';
        }
    }

    if (empty($erreurs)) {
        $pdo  = getPDO();
        $hash = password_hash($vals['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $pdo->prepare(
            'INSERT INTO users (nom, prenom, email, password, telephone, ville)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $vals['nom'], $vals['prenom'], $vals['email'],
            $hash, $vals['telephone'], $vals['ville']
        ]);
        $userId = $pdo->lastInsertId();

        // Connexion automatique après inscription
        $user = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $user->execute([$userId]);
        connecterUtilisateur($user->fetch());
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
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body class="auth-page">

<div class="auth-container">

  <div class="auth-brand">
    <a href="<?= SITE_URL ?>" class="auth-logo">
      <div class="logo-mark" style="width:48px;height:48px;font-size:20px">E</div>
      <span class="logo-name" style="font-size:20px;color:#EDE9FE"><?= SITE_NAME ?></span>
    </a>
    <h1 class="auth-brand-title">Lance ton<br>aventure.</h1>
    <p class="auth-brand-sub">Inscription gratuite. Accès immédiat à tous les cours gratuits. Payez seulement ce que vous voulez approfondir.</p>
    <ul class="auth-perks">
      <li><i class="ti ti-check" aria-hidden="true"></i> Cours gratuits illimités</li>
      <li><i class="ti ti-check" aria-hidden="true"></i> Assistant IA intégré</li>
      <li><i class="ti ti-check" aria-hidden="true"></i> Certificat à l'obtention</li>
      <li><i class="ti ti-check" aria-hidden="true"></i> Communauté d'entrepreneurs</li>
    </ul>
  </div>

  <div class="auth-form-panel">
    <div class="auth-form-box">
      <h2 class="auth-form-title">Créer mon compte</h2>
      <p class="auth-form-sub">Déjà inscrit ? <a href="<?= SITE_URL ?>/login.php">Se connecter</a></p>

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
            <label for="nom">Nom</label>
            <input type="text" id="nom" name="nom" value="<?= h($vals['nom'] ?? '') ?>" placeholder="DIALLO" required>
          </div>
          <div class="form-group">
            <label for="prenom">Prénom</label>
            <input type="text" id="prenom" name="prenom" value="<?= h($vals['prenom'] ?? '') ?>" placeholder="Fatou" required>
          </div>
        </div>

        <div class="form-group">
          <label for="email">Adresse e-mail</label>
          <div class="input-icon-wrap">
            <i class="ti ti-mail input-icon" aria-hidden="true"></i>
            <input type="email" id="email" name="email" value="<?= h($vals['email'] ?? '') ?>" placeholder="votre@email.com" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="telephone">Téléphone</label>
            <input type="tel" id="telephone" name="telephone" value="<?= h($vals['telephone'] ?? '') ?>" placeholder="+229 97 00 00 00">
          </div>
          <div class="form-group">
            <label for="ville">Ville</label>
            <input type="text" id="ville" name="ville" value="<?= h($vals['ville'] ?? '') ?>" placeholder="Cotonou">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="password">Mot de passe</label>
            <div class="input-icon-wrap">
              <i class="ti ti-lock input-icon" aria-hidden="true"></i>
              <input type="password" id="password" name="password" placeholder="Min. 8 caractères" required>
              <button type="button" class="input-toggle-pw" onclick="togglePw(this)" aria-label="Afficher">
                <i class="ti ti-eye"></i>
              </button>
            </div>
          </div>
          <div class="form-group">
            <label for="confirm">Confirmation</label>
            <div class="input-icon-wrap">
              <i class="ti ti-lock input-icon" aria-hidden="true"></i>
              <input type="password" id="confirm" name="confirm" placeholder="Répéter" required>
            </div>
          </div>
        </div>

        <button type="submit" class="btn-primary btn-full" style="margin-top:8px">
          <i class="ti ti-user-plus" aria-hidden="true"></i> Créer mon compte gratuitement
        </button>

        <p style="font-size:12px;color:var(--text-muted);margin-top:12px;text-align:center">
          En m'inscrivant j'accepte les conditions d'utilisation.
        </p>
      </form>
    </div>
  </div>

</div>

<script>
function togglePw(btn) {
  const input = btn.closest('.input-icon-wrap').querySelector('input');
  input.type = input.type === 'password' ? 'text' : 'password';
  btn.querySelector('i').className = input.type === 'password' ? 'ti ti-eye' : 'ti ti-eye-off';
}
</script>
</body>
</html>
