<?php
// ============================================================
//  mentions_legales.php — Mentions légales
// ============================================================
require_once __DIR__ . '/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mentions légales — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css?v=2">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<div style="max-width:800px;margin:0 auto;padding:48px 20px;font-family:'Plus Jakarta Sans',sans-serif;color:#1a1a2e">
  <h1 style="font-size:28px;font-weight:700;margin-bottom:8px">Mentions légales</h1>
  <p style="color:#6b7280;font-size:13px;margin-bottom:32px">Dernière mise à jour : <?= date('d/m/Y') ?></p>

  <h2 style="font-size:18px;font-weight:600;margin-top:28px">1. Éditeur du site</h2>
  <p style="font-size:14px;line-height:1.7;color:#374151">
    Le site <strong><?= h(SITE_NAME) ?></strong> est édité par Ariziki EntrepreneurshipLab, en partenariat avec
    l'Université de Parakou, dans le cadre d'un programme de formation à l'entrepreneuriat.
  </p>

  <h2 style="font-size:18px;font-weight:600;margin-top:28px">2. Hébergement</h2>
  <p style="font-size:14px;line-height:1.7;color:#374151">
    Le site est hébergé par un prestataire d'hébergement web tiers. Les coordonnées complètes de l'hébergeur
    peuvent être obtenues sur demande auprès de l'éditeur.
  </p>

  <h2 style="font-size:18px;font-weight:600;margin-top:28px">3. Propriété intellectuelle</h2>
  <p style="font-size:14px;line-height:1.7;color:#374151">
    L'ensemble des contenus présents sur le site (textes, vidéos, images, formations, certificats, marques,
    logos) est protégé par le droit d'auteur et la propriété intellectuelle. Toute reproduction ou utilisation
    sans autorisation préalable est interdite.
  </p>

  <h2 style="font-size:18px;font-weight:600;margin-top:28px">4. Responsabilité</h2>
  <p style="font-size:14px;line-height:1.7;color:#374151">
    L'éditeur s'efforce d'assurer l'exactitude et la mise à jour des informations diffusées sur ce site,
    mais ne peut garantir l'absence d'erreurs ou d'omissions. L'éditeur ne pourra être tenu responsable des
    dommages directs ou indirects résultant de l'utilisation du site ou de l'impossibilité d'y accéder.
  </p>

  <h2 style="font-size:18px;font-weight:600;margin-top:28px">5. Liens hypertextes</h2>
  <p style="font-size:14px;line-height:1.7;color:#374151">
    Le site peut contenir des liens vers d'autres sites. L'éditeur n'exerce aucun contrôle sur ces sites tiers
    et n'assume aucune responsabilité quant à leur contenu.
  </p>

  <h2 style="font-size:18px;font-weight:600;margin-top:28px">6. Contact</h2>
  <p style="font-size:14px;line-height:1.7;color:#374151">
    Pour toute question relative aux présentes mentions légales, vous pouvez nous contacter via la page
    <a href="<?= SITE_URL ?>/contact.php" style="color:#6C47D4">Contact</a>.
  </p>

  <p style="margin-top:40px"><a href="<?= SITE_URL ?>/cgu.php" style="color:#6C47D4;font-size:14px">Voir aussi nos Conditions Générales d'Utilisation →</a></p>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
