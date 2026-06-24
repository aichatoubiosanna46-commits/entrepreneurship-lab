<?php
// ============================================================
//  verify_certificate.php — Vérification d'un certificat
// ============================================================
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

sendSecurityHeaders();

$pdo  = getPDO();
$code = strtoupper(trim($_GET['code'] ?? ''));

$cert = null;
if ($code) {
    $stmt = $pdo->prepare(
        'SELECT c.*, u.nom, u.prenom, co.titre AS cours_titre
         FROM certificates c
         JOIN users u ON u.id = c.user_id
         JOIN courses co ON co.id = c.course_id
         WHERE c.code_unique = ?
         LIMIT 1'
    );
    $stmt->execute([$code]);
    $cert = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Vérification de certificat — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body style="min-height:100vh;background:#f9fafb;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:32px 16px;font-family:'Plus Jakarta Sans',sans-serif">

  <a href="<?= SITE_URL ?>" style="display:flex;align-items:center;gap:10px;text-decoration:none;margin-bottom:32px">
    <div class="logo-mark" style="width:36px;height:36px;font-size:16px;background:#6C47D4;color:#fff;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:700">E</div>
    <span style="font-size:18px;font-weight:600;color:#111"><?= SITE_NAME ?></span>
  </a>

  <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:40px;max-width:540px;width:100%;text-align:center">
    <h1 style="font-size:22px;font-weight:700;margin:0 0 8px">Vérification de certificat</h1>
    <p style="color:#6b7280;margin:0 0 28px;font-size:14px">Saisissez le code unique figurant sur le certificat</p>

    <form method="GET" style="display:flex;gap:8px;margin-bottom:28px">
      <input type="text" name="code" value="<?= h($code) ?>"
             placeholder="Ex: A1B2C3D4E5F6..."
             style="flex:1;padding:10px 14px;border:1px solid #e5e7eb;border-radius:8px;font-size:14px;font-family:monospace;text-transform:uppercase;outline:none"
             required maxlength="50">
      <button type="submit" class="btn-primary" style="white-space:nowrap">
        <i class="ti ti-search"></i> Vérifier
      </button>
    </form>

    <?php if ($code && $cert): ?>
      <!-- Certificat valide -->
      <div style="border:2px solid #97C459;border-radius:12px;padding:28px;background:#EAF3DE">
        <div style="width:64px;height:64px;background:#97C459;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:32px;color:#fff">
          <i class="ti ti-certificate"></i>
        </div>
        <div style="font-size:14px;font-weight:600;color:#27500A;margin-bottom:4px">
          Certificat valide et authentique ✓
        </div>
        <div style="width:60px;height:2px;background:#97C459;margin:12px auto"></div>

        <div style="font-size:20px;font-weight:700;color:#111;margin-bottom:8px">
          <?= h($cert['prenom'] . ' ' . $cert['nom']) ?>
        </div>
        <div style="font-size:15px;color:#444;margin-bottom:8px">
          a complété avec succès
        </div>
        <div style="font-size:17px;font-weight:600;color:#6C47D4;margin-bottom:12px">
          « <?= h($cert['cours_titre']) ?> »
        </div>
        <div style="font-size:13px;color:#6b7280">
          Délivré le <?= date('d/m/Y', strtotime($cert['delivre_le'])) ?>
        </div>
        <div style="font-size:11px;color:#9ca3af;margin-top:8px;font-family:monospace">
          Code : <?= h($cert['code_unique']) ?>
        </div>
      </div>

    <?php elseif ($code): ?>
      <!-- Code invalide -->
      <div style="border:2px solid #F0997B;border-radius:12px;padding:28px;background:#FAECE7">
        <div style="width:64px;height:64px;background:#F0997B;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:32px;color:#fff">
          <i class="ti ti-certificate-off"></i>
        </div>
        <div style="font-size:16px;font-weight:600;color:#993C1D">
          Certificat introuvable ou invalide
        </div>
        <p style="color:#993C1D;font-size:13px;margin:8px 0 0">
          Vérifiez le code saisi. Si le problème persiste, contactez le support.
        </p>
      </div>
    <?php endif; ?>
  </div>

  <p style="margin-top:24px;font-size:13px;color:#9ca3af">
    <a href="<?= SITE_URL ?>" style="color:#9ca3af">Retour au site</a>
  </p>
</body>
</html>
