<?php
// ============================================================
//  cgu.php — Conditions Générales d'Utilisation
// ============================================================
require_once __DIR__ . '/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Conditions Générales d'Utilisation — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css?v=2">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<div style="max-width:800px;margin:0 auto;padding:48px 20px;font-family:'Plus Jakarta Sans',sans-serif;color:#1a1a2e">
  <h1 style="font-size:28px;font-weight:700;margin-bottom:8px">Conditions Générales d'Utilisation</h1>
  <p style="color:#6b7280;font-size:13px;margin-bottom:32px">Dernière mise à jour : <?= date('d/m/Y') ?></p>

  <h2 style="font-size:18px;font-weight:600;margin-top:28px">1. Objet</h2>
  <p style="font-size:14px;line-height:1.7;color:#374151">
    Les présentes Conditions Générales d'Utilisation (CGU) régissent l'accès et l'utilisation de la plateforme
    <strong><?= h(SITE_NAME) ?></strong>, dédiée à la formation en entrepreneuriat. En créant un compte ou en
    utilisant le site, l'utilisateur accepte sans réserve les présentes CGU.
  </p>

  <h2 style="font-size:18px;font-weight:600;margin-top:28px">2. Accès au service</h2>
  <p style="font-size:14px;line-height:1.7;color:#374151">
    L'accès à certaines formations est gratuit, d'autres nécessitent un abonnement payant. Les tarifs en vigueur
    sont affichés sur la page d'abonnement et peuvent être modifiés à tout moment, sans effet rétroactif sur les
    abonnements déjà souscrits.
  </p>

  <h2 style="font-size:18px;font-weight:600;margin-top:28px">3. Compte utilisateur</h2>
  <p style="font-size:14px;line-height:1.7;color:#374151">
    L'utilisateur s'engage à fournir des informations exactes lors de son inscription et à conserver la
    confidentialité de ses identifiants de connexion. Toute activité réalisée depuis le compte est présumée
    effectuée par le titulaire du compte.
  </p>

  <h2 style="font-size:18px;font-weight:600;margin-top:28px">4. Paiement et facturation</h2>
  <p style="font-size:14px;line-height:1.7;color:#374151">
    Les paiements sont traités par notre prestataire de paiement (FedaPay). Une facture est générée et
    disponible au téléchargement dans l'espace « Historique des paiements » de l'utilisateur après chaque
    transaction validée.
  </p>

  <h2 style="font-size:18px;font-weight:600;margin-top:28px">5. Remboursement</h2>
  <p style="font-size:14px;line-height:1.7;color:#374151">
    Toute demande de remboursement doit être effectuée depuis la page « Historique des paiements », dans un
    délai raisonnable après l'achat. Chaque demande est examinée individuellement par notre équipe. Le
    remboursement, lorsqu'il est accordé, est effectué via le même moyen de paiement utilisé lors de l'achat.
  </p>

  <h2 style="font-size:18px;font-weight:600;margin-top:28px">6. Certificats</h2>
  <p style="font-size:14px;line-height:1.7;color:#374151">
    Des certificats de complétion ou de connaissance sont délivrés à l'issue de certaines formations ou
    parcours (bundles), sous réserve de répondre aux critères de réussite définis pour chaque cours. Un
    certificat peut être révoqué par l'administration en cas de fraude constatée ; il devient alors invalide
    et n'est plus vérifiable publiquement.
  </p>

  <h2 style="font-size:18px;font-weight:600;margin-top:28px">7. Comportement des utilisateurs</h2>
  <p style="font-size:14px;line-height:1.7;color:#374151">
    L'utilisateur s'engage à adopter un comportement respectueux dans les espaces communautaires (forum,
    messagerie, commentaires) et à ne publier aucun contenu illicite, injurieux ou contraire aux bonnes mœurs.
    Tout manquement peut entraîner la suspension ou la suppression du compte.
  </p>

  <h2 style="font-size:18px;font-weight:600;margin-top:28px">8. Modification des CGU</h2>
  <p style="font-size:14px;line-height:1.7;color:#374151">
    L'éditeur se réserve le droit de modifier les présentes CGU à tout moment. Les utilisateurs seront informés
    des modifications substantielles par tout moyen approprié.
  </p>

  <h2 style="font-size:18px;font-weight:600;margin-top:28px">9. Droit applicable</h2>
  <p style="font-size:14px;line-height:1.7;color:#374151">
    Les présentes CGU sont soumises au droit en vigueur dans le pays d'exploitation du service. Tout litige
    sera, à défaut de résolution amiable, soumis aux juridictions compétentes.
  </p>

  <p style="margin-top:40px"><a href="<?= SITE_URL ?>/mentions_legales.php" style="color:#6C47D4;font-size:14px">← Voir nos Mentions légales</a></p>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
