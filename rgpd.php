<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

sendSecurityHeaders();

$sent   = false;
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deletion_request'])) {
    verifierCSRF();
    $email  = trim($_POST['email'] ?? '');
    $raison = trim($_POST['raison'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = 'Adresse e-mail invalide.';
    } else {
        logAction('rgpd_deletion_request', 'Demande de suppression pour : ' . $email . '. Raison : ' . $raison);
        $sent = true;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>RGPD & Confidentialité-Entrepreneurship-lab</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css?v=2">
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<div style="max-width:860px;margin:0 auto;padding:48px 20px 80px">

  <!-- En-tête -->
  <div style="margin-bottom:40px">
    <div style="display:inline-flex;align-items:center;gap:6px;background:#FBE3DA;border:1px solid #fde68a;border-radius:20px;padding:4px 14px;font-size:12px;font-weight:600;color:#C04A22;margin-bottom:14px">
      <i class="ti ti-shield-check" aria-hidden="true"></i> Protection des données
    </div>
    <h1 style="font-size:28px;font-weight:800;color:#1A1A18;margin-bottom:8px">
      Politique de confidentialité
    </h1>
    <p style="font-size:14px;color:#6b7280">Dernière mise à jour : <?= date('d/m/Y') ?> · Entrepreneurship-lab Parakou, Bénin</p>
  </div>

  <!-- Sections -->
  <?php
  $sections = [
    ['1. Qui sommes-nous ?', 'ti-building',
      SITE_NAME . ' est une plateforme de formation en ligne dédiée à l\'entrepreneuriat au Bénin. L\'éditeur est Ariziki EntrepreneurshipLab, opérant depuis Cotonou.'],
    ['2. Données collectées', 'ti-database',
      '<ul style="margin:10px 0;padding-left:20px;line-height:2;color:#374151">
        <li>Nom, prénom, adresse e-mail (à l\'inscription)</li>
        <li>Numéro de téléphone et ville (optionnel)</li>
        <li>Données de progression (cours, quiz, certificats)</li>
        <li>Référence de transaction FedaPay (pas de données bancaires)</li>
        <li>Adresse IP et user-agent (sécurité)</li>
      </ul>'],
    ['3. Utilisation des données', 'ti-list-check',
      '<ul style="margin:10px 0;padding-left:20px;line-height:2;color:#374151">
        <li>Gérer votre compte et accès aux formations</li>
        <li>Suivre votre progression pédagogique</li>
        <li>Émettre vos certificats de complétion</li>
        <li>Vous envoyer des notifications liées à vos formations</li>
        <li>Sécuriser la plateforme (audit des connexions)</li>
      </ul>'],
    ['4. Cookies', 'ti-cookie',
      '<ul style="margin:10px 0;padding-left:20px;line-height:2;color:#374151">
        <li><strong>PHPSESSID</strong> : cookie de session (expire à la fermeture du navigateur)</li>
        <li><strong>cookies_accepted</strong> : mémorise votre consentement (30 jours)</li>
      </ul>
      <p style="color:#374151">Nous n\'utilisons <strong>aucun cookie publicitaire ou de tracking tiers</strong>.</p>'],
    ['5. Durée de conservation', 'ti-clock',
      'Vos données sont conservées pendant toute la durée de votre compte actif, et supprimées dans un délai de 30 jours après votre demande de suppression.'],
    ['6. Vos droits', 'ti-user-check',
      '<ul style="margin:10px 0;padding-left:20px;line-height:2;color:#374151">
        <li>Droit d\'accès à vos données personnelles</li>
        <li>Droit de rectification des données inexactes</li>
        <li>Droit à l\'effacement ("droit à l\'oubli")</li>
        <li>Droit à la portabilité de vos données</li>
        <li>Droit d\'opposition au traitement</li>
      </ul>'],
  ];
  foreach ($sections as [$titre, $icon, $contenu]):
  ?>
  <div style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:24px;margin-bottom:16px">
    <h2 style="font-size:16px;font-weight:700;color:#1A1A18;margin-bottom:12px;display:flex;align-items:center;gap:10px">
      <span style="width:32px;height:32px;background:#FBE3DA;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0">
        <i class="ti <?= $icon ?>" style="font-size:16px;color:#C04A22" aria-hidden="true"></i>
      </span>
      <?= $titre ?>
    </h2>
    <div style="font-size:14px;line-height:1.75;color:#374151"><?= $contenu ?></div>
  </div>
  <?php endforeach; ?>

  <!-- Formulaire droit à l'oubli -->
  <div style="background:#1A1A18;border-radius:16px;padding:32px;margin-bottom:16px">
    <h2 style="font-size:18px;font-weight:700;color:#FBE3DA;margin-bottom:8px;display:flex;align-items:center;gap:10px">
      <i class="ti ti-trash" style="font-size:20px;color:#085041" aria-hidden="true"></i>
      Droit à l'effacement
    </h2>
    <p style="font-size:13px;color:rgba(255,255,255,.55);margin-bottom:24px;line-height:1.7">
      Utilisez ce formulaire pour demander la suppression de votre compte et de toutes vos données.
      Votre demande sera traitée dans un délai de 30 jours.
    </p>

    <?php if ($sent): ?>
      <div style="background:#ECFDF5;border:1px solid #86efac;color:#15803d;padding:14px 18px;border-radius:10px;display:flex;gap:10px;align-items:center;font-size:14px">
        <i class="ti ti-circle-check" style="font-size:20px;flex-shrink:0" aria-hidden="true"></i>
        Votre demande a été enregistrée. Nous vous contacterons dans les 30 jours.
      </div>
    <?php else: ?>
      <?php if ($erreur): ?>
        <div style="background:#F0F9F5;border:1px solid #fca5a5;color:#dc2626;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:13px;display:flex;gap:8px">
          <i class="ti ti-alert-circle" style="font-size:16px;flex-shrink:0" aria-hidden="true"></i>
          <?= h($erreur) ?>
        </div>
      <?php endif; ?>

      <form method="POST" style="display:flex;flex-direction:column;gap:14px">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="deletion_request" value="1">

        <div>
          <label style="font-size:13px;font-weight:600;color:rgba(255,255,255,.7);display:block;margin-bottom:6px">
            Votre adresse e-mail
          </label>
          <input type="email" name="email"
                 value="<?= estConnecte() ? h($_SESSION['user_email'] ?? '') : '' ?>"
                 placeholder="votre@email.com" required
                 style="width:100%;padding:11px 14px;border:1.5px solid rgba(255,255,255,.15);border-radius:10px;font-size:14px;font-family:inherit;background:rgba(255,255,255,.08);color:#fff;box-sizing:border-box;outline:none">
        </div>

        <div>
          <label style="font-size:13px;font-weight:600;color:rgba(255,255,255,.7);display:block;margin-bottom:6px">
            Raison de la demande (optionnel)
          </label>
          <textarea name="raison" rows="3"
                    placeholder="Je souhaite supprimer mon compte car..."
                    style="width:100%;padding:11px 14px;border:1.5px solid rgba(255,255,255,.15);border-radius:10px;font-size:14px;font-family:inherit;background:rgba(255,255,255,.08);color:#fff;box-sizing:border-box;resize:vertical;outline:none"></textarea>
        </div>

        <button type="submit"
                style="display:inline-flex;align-items:center;gap:8px;background:#085041;color:#fff;border:none;padding:12px 22px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;width:fit-content">
          <i class="ti ti-trash" aria-hidden="true"></i> Demander la suppression de mes données
        </button>
      </form>
    <?php endif; ?>
  </div>

  <!-- Contact -->
  <div style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:24px">
    <h2 style="font-size:16px;font-weight:700;color:#1A1A18;margin-bottom:10px;display:flex;align-items:center;gap:10px">
      <span style="width:32px;height:32px;background:#FBE3DA;border-radius:8px;display:inline-flex;align-items:center;justify-content:center">
        <i class="ti ti-mail" style="font-size:16px;color:#C04A22" aria-hidden="true"></i>
      </span>
      7. Contact
    </h2>
    <p style="font-size:14px;color:#374151">
      Pour toute question relative à la protection de vos données, contactez-nous à :
      <a href="mailto:privacy@ariziki.org" style="color:#D85A30;font-weight:600">privacy@ariziki.org</a>
    </p>
  </div>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>