<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getPDO();
$succes = false;
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom     = trim($_POST['nom'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $sujet   = trim($_POST['sujet'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!$nom || !$email || !$message) {
        $erreur = 'Veuillez remplir tous les champs obligatoires.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = 'Adresse email invalide.';
    } else {
        try {
            $pdo->prepare(
                'INSERT INTO contact_messages (nom, email, sujet, message) VALUES (?,?,?,?)'
            )->execute([$nom, $email, $sujet, $message]);
            $succes = true;
        } catch (Exception $e) {
            $erreur = 'Erreur lors de l\'envoi. Réessayez.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Contact — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css?v=2">
<style>
body{background:#F5F0E8;font-family:'Plus Jakarta Sans',sans-serif}
.contact-hero{background:linear-gradient(135deg,#1A1A18,#292524);padding:60px 24px;text-align:center;color:#fff}
.contact-hero h1{font-size:32px;font-weight:800;margin-bottom:10px}
.contact-hero h1 em{font-style:normal;color:#D85A30}
.contact-hero p{font-size:14px;color:rgba(255,255,255,.6)}
.contact-wrap{max-width:900px;margin:0 auto;padding:48px 24px;display:grid;grid-template-columns:1fr 360px;gap:32px}
.contact-form-card{background:#fff;border-radius:16px;padding:32px;border:1px solid #e5e7eb}
.form-group{display:flex;flex-direction:column;gap:6px;margin-bottom:16px}
.form-group label{font-size:13px;font-weight:600;color:#1A1A18}
.form-group input,.form-group textarea,.form-group select{padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;font-family:inherit;width:100%;box-sizing:border-box}
.form-group input:focus,.form-group textarea:focus{outline:none;border-color:#D85A30;box-shadow:0 0 0 3px rgba(245,158,11,.12)}
.form-group textarea{resize:vertical;min-height:120px}
.btn-send{width:100%;padding:13px;background:linear-gradient(135deg,#D85A30,#C04A22);color:#1A1A18;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit}
.info-card{background:#fff;border-radius:16px;padding:24px;border:1px solid #e5e7eb;height:fit-content}
.info-item{display:flex;align-items:flex-start;gap:12px;padding:14px 0;border-bottom:1px solid #f3f4f6}
.info-item:last-child{border:none}
.info-item i{width:36px;height:36px;background:#FBE3DA;border-radius:9px;display:flex;align-items:center;justify-content:center;color:#C04A22;font-size:18px;flex-shrink:0}
.info-item strong{display:block;font-size:13px;font-weight:700;color:#1A1A18;margin-bottom:2px}
.info-item span{font-size:12px;color:#6b7280}
.alert-success{background:#ECFDF5;border:1px solid #86efac;color:#15803d;padding:14px 16px;border-radius:10px;display:flex;gap:10px;align-items:center;margin-bottom:16px;font-size:13px}
.alert-error{background:#F0F9F5;border:1px solid #fca5a5;color:#dc2626;padding:14px 16px;border-radius:10px;display:flex;gap:10px;align-items:center;margin-bottom:16px;font-size:13px}
@media(max-width:768px){.contact-wrap{grid-template-columns:1fr}}
</style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="contact-hero">
  <h1>Nous <em>contacter</em></h1>
  <p>Une question, une suggestion ou besoin d'aide ? Notre équipe vous répond.</p>
</div>

<div class="contact-wrap">
  <div class="contact-form-card">
    <h2 style="font-size:18px;font-weight:700;color:#1A1A18;margin-bottom:20px">Envoyer un message</h2>

    <?php if ($succes): ?>
    <div class="alert-success">
      <i class="ti ti-check-circle" style="font-size:18px;flex-shrink:0"></i>
      Votre message a été envoyé avec succès ! Nous vous répondrons dans les 24h.
    </div>
    <?php endif; ?>

    <?php if ($erreur): ?>
    <div class="alert-error">
      <i class="ti ti-alert-circle" style="font-size:18px;flex-shrink:0"></i>
      <?= h($erreur) ?>
    </div>
    <?php endif; ?>

    <?php if (!$succes): ?>
    <form method="POST">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div class="form-group">
          <label>Nom complet *</label>
          <input type="text" name="nom" value="<?= h($_POST['nom'] ?? (estConnecte() ? $_SESSION['user_nom'] : '')) ?>" required>
        </div>
        <div class="form-group">
          <label>Email *</label>
          <input type="email" name="email" value="<?= h($_POST['email'] ?? (estConnecte() ? $_SESSION['user_email'] : '')) ?>" required>
        </div>
      </div>
      <div class="form-group">
        <label>Sujet</label>
        <select name="sujet">
          <option value="Question générale">Question générale</option>
          <option value="Problème technique">Problème technique</option>
          <option value="Question sur une formation">Question sur une formation</option>
          <option value="Paiement">Paiement</option>
          <option value="Partenariat">Partenariat institutionnel</option>
          <option value="Autre">Autre</option>
        </select>
      </div>
      <div class="form-group">
        <label>Message *</label>
        <textarea name="message" placeholder="Décrivez votre question ou demande..." required><?= h($_POST['message'] ?? '') ?></textarea>
      </div>
      <button type="submit" class="btn-send">
        <i class="ti ti-send"></i> Envoyer le message
      </button>
    </form>
    <?php endif; ?>
  </div>

  <div>
    <div class="info-card">
      <h3 style="font-size:15px;font-weight:700;color:#1A1A18;margin-bottom:16px">Nos coordonnées</h3>
      <div class="info-item">
        <i class="ti ti-map-pin"></i>
        <div><strong>Adresse</strong><span>Parakou, République du Bénin</span></div>
      </div>
      <div class="info-item">
        <i class="ti ti-mail"></i>
        <div><strong>Email</strong><span>contact@ariziki.org</span></div>
      </div>
      <div class="info-item">
        <i class="ti ti-brand-whatsapp"></i>
        <div><strong>WhatsApp</strong><span>+229 97 00 00 00</span></div>
      </div>
      <div class="info-item">
        <i class="ti ti-clock"></i>
        <div><strong>Disponibilité</strong><span>Lun–Ven · 8h–18h (WAT)</span></div>
      </div>
    </div>

    <div class="info-card" style="margin-top:16px">
      <h3 style="font-size:15px;font-weight:700;color:#1A1A18;margin-bottom:14px">Liens utiles</h3>
      <a href="<?= SITE_URL ?>/catalogue.php" style="display:flex;align-items:center;gap:8px;padding:10px 0;color:#374151;text-decoration:none;border-bottom:1px solid #f3f4f6;font-size:13px">
        <i class="ti ti-book" style="color:#D85A30"></i> Catalogue des formations
      </a>
      <a href="<?= SITE_URL ?>/about.php" style="display:flex;align-items:center;gap:8px;padding:10px 0;color:#374151;text-decoration:none;border-bottom:1px solid #f3f4f6;font-size:13px">
        <i class="ti ti-info-circle" style="color:#D85A30"></i> À propos d'Ariziki
      </a>
      <a href="<?= SITE_URL ?>/rgpd.php" style="display:flex;align-items:center;gap:8px;padding:10px 0;color:#374151;text-decoration:none;font-size:13px">
        <i class="ti ti-shield" style="color:#D85A30"></i> Politique de confidentialité
      </a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
