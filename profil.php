<?php
// profil.php — Profil étudiant avec champs personnalisés
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
reqConnecte();

$pdo    = getPDO();
$userId = $_SESSION['user_id'];
$user   = utilisateurCourant();
$msg    = '';
$erreur = '';

// Sauvegarde profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profil') {
    verifierCSRF();
    $prenom     = trim($_POST['prenom']     ?? '');
    $nom        = trim($_POST['nom']        ?? '');
    $telephone  = trim($_POST['telephone']  ?? '');
    $ville      = trim($_POST['ville']      ?? '');
    $universite = trim($_POST['universite'] ?? '');
    $filiere    = trim($_POST['filiere']    ?? '');
    $promotion  = trim($_POST['promotion']  ?? '');
    $bio        = trim($_POST['bio']        ?? '');

    if (!$prenom || !$nom) {
        $erreur = 'Prénom et nom sont requis.';
    } else {
        // Upload avatar
        $avatar = $user['avatar'];
        if (!empty($_FILES['avatar']['tmp_name'])) {
            $ext     = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp'];
            if (!in_array($ext, $allowed)) {
                $erreur = 'Format image non supporté (jpg, png, webp).';
            } elseif ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
                $erreur = 'Image trop lourde (max 2 Mo).';
            } else {
                $newName = 'avatar_' . $userId . '_' . time() . '.' . $ext;
                $dest    = __DIR__ . '/assets/uploads/' . $newName;
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
                    $avatar = $newName;
                }
            }
        }

        if (!$erreur) {
            $pdo->prepare(
                'UPDATE users SET prenom=?, nom=?, telephone=?, ville=?,
                 universite=?, filiere=?, promotion=?, bio=?, avatar=?
                 WHERE id=?'
            )->execute([$prenom, $nom, $telephone, $ville,
                         $universite, $filiere, $promotion, $bio, $avatar, $userId]);
            $_SESSION['user_nom'] = $prenom . ' ' . $nom;
            $user = utilisateurCourant();
            $msg = 'Profil mis à jour avec succès.';
        }
    }
}

// Changement mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_pw') {
    verifierCSRF();
    $ancien = $_POST['ancien_mdp'] ?? '';
    $nouv   = $_POST['nouveau_mdp'] ?? '';
    $conf   = $_POST['confirm_mdp'] ?? '';

    if (!password_verify($ancien, $user['password'])) {
        $erreur = 'Ancien mot de passe incorrect.';
    } elseif (strlen($nouv) < 8) {
        $erreur = 'Le nouveau mot de passe doit faire au moins 8 caractères.';
    } elseif ($nouv !== $conf) {
        $erreur = 'Les mots de passe ne correspondent pas.';
    } else {
        $hash = password_hash($nouv, PASSWORD_BCRYPT, ['cost' => 12]);
        $pdo->prepare('UPDATE users SET password=? WHERE id=?')->execute([$hash, $userId]);
        $msg = 'Mot de passe modifié avec succès.';
    }
}

// Stats
try {
    $nbCours  = (int)$pdo->query("SELECT COUNT(*) FROM enrollments WHERE user_id=$userId AND statut='actif'")->fetchColumn();
    $nbCerts  = (int)$pdo->query("SELECT COUNT(*) FROM certificates WHERE user_id=$userId")->fetchColumn();
    $nbBadges = (int)$pdo->query("SELECT COUNT(*) FROM user_badges WHERE user_id=$userId")->fetchColumn();
    $xpTotal  = (int)($user['xp_total'] ?? 0);
} catch (Exception $e) { $nbCours=$nbCerts=$nbBadges=$xpTotal=0; }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Mon profil <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/user-dashboard.css">
<style>
:root{--amber:#F59E0B;--amber-light:#FEF3C7;--amber-dark:#D97706;--text-muted:#6b7280}
.user-dash-page{background:#FFFBEB}
.user-dash-layout{display:flex;max-width:1200px;margin:0 auto;padding:28px 20px;gap:24px;align-items:flex-start}
.user-sidebar{width:220px;flex-shrink:0;background:#fff;border:1px solid #e5e7eb;border-radius:16px;overflow:hidden;position:sticky;top:80px}
.user-sidebar-profile{padding:24px 16px 16px;text-align:center;background:linear-gradient(135deg,#1C1917,#292524);border-bottom:1px solid rgba(255,255,255,.08)}
.user-avatar-ring{width:64px;height:64px;border-radius:50%;margin:0 auto 10px;border:3px solid #F59E0B;overflow:hidden;display:flex;align-items:center;justify-content:center}
.user-avatar-ring img{width:100%;height:100%;object-fit:cover}
.user-avatar-placeholder{width:100%;height:100%;background:linear-gradient(135deg,#F59E0B,#EF4444);color:#fff;font-size:22px;font-weight:800;display:flex;align-items:center;justify-content:center}
.user-sidebar-name{font-size:14px;font-weight:700;color:#FEF3C7}
.user-sidebar-email{font-size:11px;color:rgba(255,255,255,.45);margin-top:2px}
.user-sidebar-nav{padding:8px 0}
.user-nav-item{display:flex;align-items:center;gap:10px;padding:10px 16px;font-size:13px;color:#6b7280;text-decoration:none;transition:background .15s,color .15s;border-left:3px solid transparent}
.user-nav-item i{font-size:17px;flex-shrink:0}
.user-nav-item:hover{background:#FFFBEB;color:#D97706}
.user-nav-item.active{background:#FEF3C7;color:#D97706;font-weight:600;border-left-color:#F59E0B}
.user-sidebar-footer{border-top:1px solid #f3f4f6;padding:6px 0}
.user-dash-main{flex:1;min-width:0}
.dash-topbar{margin-bottom:20px}
.dash-title{font-size:22px;font-weight:800;color:#1C1917;margin-bottom:4px}

.profile-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:28px;margin-bottom:20px}
.profile-card-title{font-size:16px;font-weight:700;color:#1C1917;margin-bottom:20px;display:flex;align-items:center;gap:8px;padding-bottom:12px;border-bottom:1px solid #f3f4f6}
.profile-card-title i{color:#F59E0B;font-size:20px}

.xp-bar-wrap{background:#e5e7eb;border-radius:99px;height:10px;overflow:hidden;margin-top:6px}
.xp-bar{height:100%;background:linear-gradient(90deg,#F59E0B,#EF4444);border-radius:99px;transition:width .4s}

.stats-mini{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px}
.stat-mini{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px;text-align:center;position:relative;overflow:hidden}
.stat-mini::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#F59E0B,#EF4444)}
.stat-mini-val{font-size:24px;font-weight:800;color:#1C1917}
.stat-mini-lbl{font-size:11px;color:#6b7280;margin-top:2px}

.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.form-group{display:flex;flex-direction:column;gap:6px;margin-bottom:14px}
.form-group label{font-size:13px;font-weight:600;color:#1C1917}
.form-group input,.form-group textarea,.form-group select{padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:13px;font-family:inherit;color:#1C1917;background:#fff;width:100%;transition:border-color .2s}
.form-group input:focus,.form-group textarea:focus{outline:none;border-color:#F59E0B;box-shadow:0 0 0 3px rgba(245,158,11,.12)}
.form-group textarea{resize:vertical;min-height:80px}
.btn-save{display:inline-flex;align-items:center;gap:8px;padding:11px 22px;background:linear-gradient(135deg,#F59E0B,#D97706);color:#1C1917;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit}
.alert{display:flex;align-items:flex-start;gap:10px;padding:13px 16px;border-radius:10px;font-size:13px;margin-bottom:16px}
.alert-success{background:#ECFDF5;color:#15803d;border:1px solid #86efac}
.alert-error{background:#fef2f2;color:#dc2626;border:1px solid #fca5a5}

.avatar-upload-wrap{display:flex;align-items:center;gap:16px;margin-bottom:20px}
.avatar-preview{width:72px;height:72px;border-radius:50%;border:3px solid #F59E0B;overflow:hidden;display:flex;align-items:center;justify-content:center;background:#FEF3C7;flex-shrink:0}
.avatar-preview img{width:100%;height:100%;object-fit:cover}
.avatar-preview-placeholder{font-size:26px;font-weight:800;color:#D97706}

@media(max-width:768px){.user-dash-layout{flex-direction:column}.user-sidebar{width:100%;position:static}.form-row{grid-template-columns:1fr}.stats-mini{grid-template-columns:1fr 1fr}}
</style>
</head>
<body class="user-dash-page">
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="user-dash-layout">
  <aside class="user-sidebar">
    <div class="user-sidebar-profile">
      <div class="user-avatar-ring">
        <?php if ($user['avatar']): ?>
          <img src="<?= SITE_URL ?>/assets/uploads/<?= h($user['avatar']) ?>" alt="">
        <?php else: ?>
          <div class="user-avatar-placeholder"><?= mb_strtoupper(mb_substr($user['prenom'],0,1)) ?></div>
        <?php endif; ?>
      </div>
      <div class="user-sidebar-name"><?= h($user['prenom'].' '.$user['nom']) ?></div>
      <div class="user-sidebar-email"><?= h($user['email']) ?></div>
    </div>
    <nav class="user-sidebar-nav">
      <a href="<?= SITE_URL ?>/dashboard.php" class="user-nav-item"><i class="ti ti-layout-dashboard"></i> Tableau de bord</a>
      <a href="<?= SITE_URL ?>/search.php" class="user-nav-item"><i class="ti ti-book"></i> Formations</a>
      <a href="<?= SITE_URL ?>/favorites.php" class="user-nav-item"><i class="ti ti-heart"></i> Favoris</a>
      <a href="<?= SITE_URL ?>/resources.php" class="user-nav-item"><i class="ti ti-library"></i> Bibliothèque</a>
      <a href="<?= SITE_URL ?>/payment.php" class="user-nav-item"><i class="ti ti-credit-card"></i> Abonnement</a>
      <a href="<?= SITE_URL ?>/notifications.php" class="user-nav-item"><i class="ti ti-bell"></i> Notifications</a>
      <a href="<?= SITE_URL ?>/profil.php" class="user-nav-item active"><i class="ti ti-user"></i> Mon profil</a>
    </nav>
    <div class="user-sidebar-footer">
      <a href="<?= SITE_URL ?>/logout.php" class="user-nav-item" style="color:#F0997B"><i class="ti ti-logout"></i> Déconnexion</a>
    </div>
  </aside>

  <main class="user-dash-main">
    <div class="dash-topbar">
      <h1 class="dash-title">Mon profil</h1>
    </div>

    <!-- Stats mini -->
    <div class="stats-mini">
      <div class="stat-mini"><div class="stat-mini-val"><?= $nbCours ?></div><div class="stat-mini-lbl">Formations</div></div>
      <div class="stat-mini"><div class="stat-mini-val"><?= $nbCerts ?></div><div class="stat-mini-lbl">Certificats</div></div>
      <div class="stat-mini"><div class="stat-mini-val"><?= $nbBadges ?></div><div class="stat-mini-lbl">Badges</div></div>
      <div class="stat-mini">
        <div class="stat-mini-val" style="color:#F59E0B"><?= number_format($xpTotal) ?></div>
        <div class="stat-mini-lbl">XP gagnés</div>
        <div class="xp-bar-wrap"><div class="xp-bar" style="width:<?= min(100, ($xpTotal % 500) / 5) ?>%"></div></div>
      </div>
    </div>

    <?php if ($msg): ?><div class="alert alert-success"><i class="ti ti-check-circle"></i> <?= h($msg) ?></div><?php endif; ?>
    <?php if ($erreur): ?><div class="alert alert-error"><i class="ti ti-alert-circle"></i> <?= h($erreur) ?></div><?php endif; ?>

    <!-- Informations personnelles -->
    <div class="profile-card">
      <div class="profile-card-title"><i class="ti ti-user-edit"></i> Informations personnelles</div>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="action" value="update_profil">

        <!-- Avatar -->
        <div class="avatar-upload-wrap">
          <div class="avatar-preview" id="avatarPreview">
            <?php if ($user['avatar']): ?>
              <img src="<?= SITE_URL ?>/assets/uploads/<?= h($user['avatar']) ?>" alt="" id="avatarImg">
            <?php else: ?>
              <div class="avatar-preview-placeholder"><?= mb_strtoupper(mb_substr($user['prenom'],0,1)) ?></div>
            <?php endif; ?>
          </div>
          <div>
            <label for="avatarInput" style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:#FEF3C7;border:1px solid #fde68a;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;color:#D97706">
              <i class="ti ti-camera"></i> Changer la photo
            </label>
            <input type="file" id="avatarInput" name="avatar" accept="image/*" style="display:none" onchange="previewAvatar(this)">
            <div style="font-size:11px;color:#9ca3af;margin-top:4px">JPG, PNG ou WebP · Max 2 Mo</div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group"><label>Prénom *</label><input type="text" name="prenom" value="<?= h($user['prenom']) ?>" required></div>
          <div class="form-group"><label>Nom *</label><input type="text" name="nom" value="<?= h($user['nom']) ?>" required></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Téléphone</label><input type="tel" name="telephone" value="<?= h($user['telephone'] ?? '') ?>" placeholder="+229 97 00 00 00"></div>
          <div class="form-group"><label>Ville</label><input type="text" name="ville" value="<?= h($user['ville'] ?? '') ?>" placeholder="Cotonou"></div>
        </div>

        <div style="border-top:1px solid #f3f4f6;padding-top:16px;margin-top:4px;margin-bottom:16px">
          <div style="font-size:13px;font-weight:600;color:#1C1917;margin-bottom:14px">🎓 Informations académiques</div>
          <div class="form-row">
            <div class="form-group"><label>Université</label><input type="text" name="universite" value="<?= h($user['universite'] ?? '') ?>" placeholder="Université de Parakou"></div>
            <div class="form-group"><label>Filière</label><input type="text" name="filiere" value="<?= h($user['filiere'] ?? '') ?>" placeholder="Licence Pro Entrepreneuriat"></div>
          </div>
          <div class="form-group" style="max-width:50%"><label>Promotion</label><input type="text" name="promotion" value="<?= h($user['promotion'] ?? '') ?>" placeholder="2024-2025"></div>
        </div>

        <div class="form-group">
          <label>Bio / Présentation</label>
          <textarea name="bio" rows="3" placeholder="Présentez-vous en quelques mots..."><?= h($user['bio'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn-save">
          <i class="ti ti-device-floppy"></i> Sauvegarder
        </button>
      </form>
    </div>

    <!-- Sécurité -->
    <div class="profile-card">
      <div class="profile-card-title"><i class="ti ti-lock"></i> Sécurité — Changer le mot de passe</div>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="action" value="change_pw">
        <div class="form-group"><label>Ancien mot de passe</label><input type="password" name="ancien_mdp" required></div>
        <div class="form-row">
          <div class="form-group"><label>Nouveau mot de passe</label><input type="password" name="nouveau_mdp" required minlength="8"></div>
          <div class="form-group"><label>Confirmer</label><input type="password" name="confirm_mdp" required minlength="8"></div>
        </div>
        <button type="submit" class="btn-save" style="background:linear-gradient(135deg,#1C1917,#292524);color:#fff">
          <i class="ti ti-lock-check"></i> Changer le mot de passe
        </button>
      </form>
    </div>

    <!-- Données personnelles -->
    <div class="profile-card" style="border-color:#fca5a5">
      <div class="profile-card-title" style="color:#dc2626"><i class="ti ti-shield-off" style="color:#dc2626"></i> Données personnelles</div>
      <p style="font-size:13px;color:#6b7280;margin-bottom:16px">
        Conformément au RGPD, vous pouvez demander la suppression de toutes vos données personnelles.
        Cette action est irréversible.
      </p>
      <a href="<?= SITE_URL ?>/rgpd.php" style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;color:#dc2626;font-size:13px;font-weight:600;text-decoration:none">
        <i class="ti ti-trash"></i> Demander la suppression de mes données
      </a>
    </div>

  </main>
</div>

<script>
function previewAvatar(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      const preview = document.getElementById('avatarPreview');
      preview.innerHTML = '<img src="'+e.target.result+'" alt="" style="width:100%;height:100%;object-fit:cover">';
    };
    reader.readAsDataURL(input.files[0]);
  }
}
</script>


<!-- RGPD - Export données -->
<div style="margin-top:24px;padding:16px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px">
  <div style="font-size:13px;font-weight:700;color:#1C1917;margin-bottom:8px">
    <i class="ti ti-shield" style="color:#6b7280"></i> Mes données personnelles
  </div>
  <p style="font-size:12px;color:#6b7280;margin-bottom:12px">Conformément au RGPD, vous pouvez télécharger toutes vos données personnelles.</p>
  <a href="<?= SITE_URL ?>/export_data.php"
     style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:#fff;border:1px solid #e5e7eb;border-radius:8px;font-size:12px;font-weight:600;color:#374151;text-decoration:none">
    <i class="ti ti-download"></i> Télécharger mes données (JSON)
  </a>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
