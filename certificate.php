<?php
// certificate.php — Afficher / télécharger un certificat
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
reqConnecte();

$pdo    = getPDO();
$userId = $_SESSION['user_id'];
$code   = trim($_GET['code'] ?? '');
$courseId = (int)($_GET['course'] ?? 0);
$bundleId = (int)($_GET['bundle'] ?? 0);

$selectSQL = "SELECT cert.*, c.titre as course_titre, c.slug as course_slug,
                     b.titre as bundle_titre,
                     u.prenom, u.nom
              FROM certificates cert
              LEFT JOIN courses c ON c.id = cert.course_id
              LEFT JOIN bundles b ON b.id = cert.bundle_id
              JOIN users u   ON u.id = cert.user_id";

if ($code) {
    $cert = $pdo->prepare($selectSQL . ' WHERE cert.code_unique = ?');
    $cert->execute([$code]);
} elseif ($bundleId) {
    $cert = $pdo->prepare($selectSQL . ' WHERE cert.bundle_id = ? AND cert.user_id = ?');
    $cert->execute([$bundleId, $userId]);
} elseif ($courseId) {
    $cert = $pdo->prepare($selectSQL . ' WHERE cert.course_id = ? AND cert.user_id = ?');
    $cert->execute([$courseId, $userId]);
} else {
    header('Location: ' . SITE_URL . '/dashboard.php'); exit;
}
$cert = $cert->fetch();
if (!$cert) {
    redirect(SITE_URL . '/dashboard.php', 'Certificat introuvable.', 'error');
}

$estBundle  = !empty($cert['bundle_id']);
$estRevoque = !empty($cert['revoque']);
$typeLabel  = ($cert['type'] ?? 'completion') === 'connaissance' ? 'Certificat de connaissance' : 'Certificat de complétion';
$intitule   = $estBundle ? ($cert['bundle_titre'] ?? '') : ($cert['course_titre'] ?? '');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Certificat — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<style>
.cert-page { min-height: 100vh; background: #f9fafb; display: flex; flex-direction: column; align-items: center; padding: 40px 20px; }
.cert-actions { display: flex; gap: 12px; margin-bottom: 32px; }
.btn { padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; border: none; }
.btn-primary { background: #534AB7; color: #fff; }
.btn-outline { background: #fff; border: 1px solid #e5e7eb; color: #111; }

/* Certificat visuel */
.certificate {
  width: 800px; max-width: 100%;
  background: #fff;
  border: 12px solid #6C47D4;
  border-radius: 4px;
  padding: 60px 70px;
  text-align: center;
  box-shadow: 0 20px 60px rgba(0,0,0,.12);
  position: relative;
  font-family: 'Outfit', sans-serif;
}
.cert-corner {
  position: absolute; width: 60px; height: 60px;
  border: 4px solid #6C47D4; border-radius: 2px;
}
.cert-corner.tl { top: 10px; left: 10px; border-right: none; border-bottom: none; }
.cert-corner.tr { top: 10px; right: 10px; border-left: none; border-bottom: none; }
.cert-corner.bl { bottom: 10px; left: 10px; border-right: none; border-top: none; }
.cert-corner.br { bottom: 10px; right: 10px; border-left: none; border-top: none; }
.cert-logo { font-size: 28px; font-weight: 800; color: #6C47D4; letter-spacing: -1px; margin-bottom: 6px; }
.cert-subtitle { font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: .1em; margin-bottom: 32px; }
.cert-title { font-size: 42px; font-weight: 700; color: #1a1a2e; margin-bottom: 6px; font-family: 'Outfit', sans-serif; }
.cert-label { font-size: 13px; color: #6b7280; margin-bottom: 0; }
.cert-name { font-size: 36px; font-weight: 700; color: #6C47D4; margin: 4px 0 24px; }
.cert-course-label { font-size: 14px; color: #6b7280; margin-bottom: 4px; }
.cert-course { font-size: 22px; font-weight: 700; color: #1a1a2e; margin-bottom: 32px; }
.cert-divider { width: 80px; height: 3px; background: #6C47D4; margin: 0 auto 32px; border-radius: 99px; }
.cert-date { font-size: 13px; color: #6b7280; margin-bottom: 4px; }
.cert-code { font-size: 11px; color: #9ca3af; font-family: monospace; }
@media print {
  .cert-actions, nav, main > *:not(.cert-page) { display: none !important; }
  .cert-page { padding: 0; background: white; }
}
@media (max-width: 860px) {
  .certificate { padding: 40px 30px; }
  .cert-title { font-size: 28px; }
  .cert-name { font-size: 26px; }
}
</style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<div class="cert-page">
  <div class="cert-actions">
    <a href="<?= SITE_URL ?>/dashboard.php" class="btn btn-outline">
      <i class="ti ti-arrow-left"></i> Tableau de bord
    </a>
    <button onclick="window.print()" class="btn btn-primary">
      <i class="ti ti-printer"></i> Imprimer / Sauvegarder PDF
    </button>
    <?php if ($cert['fichier_pdf']): ?>
    <a href="<?= SITE_URL ?>/assets/uploads/<?= h($cert['fichier_pdf']) ?>" download class="btn btn-primary">
      <i class="ti ti-download"></i> Télécharger PDF
    </a>
    <?php endif; ?>
  </div>

  <?php if ($estRevoque): ?>
  <div class="certificate" id="certificat" style="border-color:#dc2626">
    <div class="cert-corner tl" style="border-color:#dc2626"></div>
    <div class="cert-corner tr" style="border-color:#dc2626"></div>
    <div class="cert-corner bl" style="border-color:#dc2626"></div>
    <div class="cert-corner br" style="border-color:#dc2626"></div>

    <div class="cert-logo" style="color:#dc2626"><?= SITE_NAME ?></div>
    <div class="cert-subtitle" style="color:#dc2626">Certificat révoqué</div>
    <div class="cert-divider" style="background:#dc2626"></div>

    <div class="cert-title" style="color:#dc2626"><i class="ti ti-ban"></i> Certificat révoqué</div>
    <p style="font-size:14px;color:#6b7280;margin:16px 0">
      Ce certificat délivré à <strong><?= h($cert['prenom'] . ' ' . $cert['nom']) ?></strong>
      pour « <?= h($intitule) ?> » a été révoqué
      <?php if (!empty($cert['revoque_le'])): ?>le <?= date('d/m/Y', strtotime($cert['revoque_le'])) ?><?php endif; ?>
      et n'est plus valide.
    </p>
    <div class="cert-code">Code : <?= h($cert['code_unique']) ?></div>
  </div>
  <?php else: ?>
  <div class="certificate" id="certificat">
    <div class="cert-corner tl"></div>
    <div class="cert-corner tr"></div>
    <div class="cert-corner bl"></div>
    <div class="cert-corner br"></div>

    <div class="cert-logo"><?= SITE_NAME ?></div>
    <div class="cert-subtitle"><?= h($typeLabel) ?><?= $estBundle ? ' — Parcours multi-cours' : '' ?></div>
    <div class="cert-divider"></div>

    <div class="cert-title">Certificat</div>
    <div class="cert-label">Décerné à</div>
    <div class="cert-name"><?= h($cert['prenom'] . ' ' . $cert['nom']) ?></div>

    <div class="cert-course-label">Pour avoir complété avec succès <?= $estBundle ? 'le parcours' : 'la formation' ?></div>
    <div class="cert-course">« <?= h($intitule) ?> »</div>

    <div class="cert-divider"></div>

    <div class="cert-date">
      Délivré le <?= date('d/m/Y', strtotime($cert['delivre_le'])) ?>
    </div>
    <div class="cert-code">Code de vérification : <?= h($cert['code_unique']) ?></div>
  </div>
  <?php endif; ?>
</div>

<?php if ($certificate ?? null): ?>
<div style="display:flex;gap:10px;margin-top:16px;flex-wrap:wrap">
  <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?= urlencode(SITE_URL . '/verify_certificate.php?code=' . $certificate['code_unique']) ?>&title=<?= urlencode('Certificat ' . $course['titre']) ?>&summary=<?= urlencode('J\'ai obtenu mon certificat ' . $course['titre'] . ' sur Ariziki EntrepreneurshipLab, certifié par l\'Université de Parakou.') ?>"
     target="_blank"
     style="display:inline-flex;align-items:center;gap:8px;padding:10px 18px;background:#0A66C2;color:#fff;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none">
    <i class="ti ti-brand-linkedin"></i> Partager sur LinkedIn
  </a>
  <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(SITE_URL . '/verify_certificate.php?code=' . $certificate['code_unique']) ?>"
     target="_blank"
     style="display:inline-flex;align-items:center;gap:8px;padding:10px 18px;background:#1877F2;color:#fff;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none">
    <i class="ti ti-brand-facebook"></i> Partager sur Facebook
  </a>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
