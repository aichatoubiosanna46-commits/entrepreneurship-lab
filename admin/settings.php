<?php
// admin/settings.php — Configuration globale (Analytics, WhatsApp, Zoom, etc.)
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();
$pdo = getPDO();

// Sauvegarde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCSRF();
    $fields = [
        'ga4_measurement_id', 'fb_pixel_id',
        'whatsapp_api_url', 'whatsapp_api_token', 'whatsapp_numero',
        'calendly_url', 'zoom_api_key',
        'site_name', 'site_email', 'maintenance_mode',
    ];
    foreach ($fields as $key) {
        $val = trim($_POST[$key] ?? '');
        $pdo->prepare(
            'INSERT INTO settings (cle, valeur) VALUES (?,?) ON DUPLICATE KEY UPDATE valeur=?'
        )->execute([$key, $val, $val]);
    }
    redirect(SITE_URL . '/admin/settings.php', 'Paramètres sauvegardés.', 'success');
}

// Charger settings
$settings = [];
foreach ($pdo->query('SELECT cle, valeur FROM settings')->fetchAll() as $row) {
    $settings[$row['cle']] = $row['valeur'];
}
$s = fn($k) => h($settings[$k] ?? '');

$currentPage = 'settings.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Paramètres — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <div><div class="admin-page-title">Paramètres</div><div class="admin-page-sub">Configuration globale de la plateforme</div></div>
  </div>
  <?= flash() ?>

  <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

    <!-- Général -->
    <div class="admin-card" style="margin-bottom:20px">
      <div class="admin-card-title"><i class="ti ti-settings" style="color:var(--primary)"></i> Général</div>
      <div class="form-row">
        <div class="form-group"><label>Nom du site</label><input type="text" name="site_name" value="<?= $s('site_name') ?>"></div>
        <div class="form-group"><label>Email de contact</label><input type="email" name="site_email" value="<?= $s('site_email') ?>" placeholder="contact@ariziki.bj"></div>
      </div>
      <div class="form-group">
        <label>Mode maintenance</label>
        <select name="maintenance_mode">
          <option value="0" <?= ($settings['maintenance_mode']??'0')==='0'?'selected':'' ?>>Désactivé — site accessible</option>
          <option value="1" <?= ($settings['maintenance_mode']??'0')==='1'?'selected':'' ?>>Activé — site en maintenance</option>
        </select>
      </div>
    </div>

    <!-- Google Analytics -->
    <div class="admin-card" style="margin-bottom:20px">
      <div class="admin-card-title">
        <i class="ti ti-chart-area" style="color:#E37400"></i> Google Analytics 4
        <a href="https://analytics.google.com" target="_blank" class="btn-link" style="margin-left:auto;font-size:12px">Ouvrir GA4 →</a>
      </div>
      <div class="form-group">
        <label>Measurement ID <small style="font-weight:400;color:var(--text-muted)">(ex: G-XXXXXXXXXX)</small></label>
        <input type="text" name="ga4_measurement_id" value="<?= $s('ga4_measurement_id') ?>" placeholder="G-XXXXXXXXXX">
        <small style="color:var(--text-muted);font-size:11px">Laissez vide pour désactiver le tracking Analytics.</small>
      </div>
      <div class="form-group">
        <label>Facebook Pixel ID <small style="font-weight:400;color:var(--text-muted)">(optionnel)</small></label>
        <input type="text" name="fb_pixel_id" value="<?= $s('fb_pixel_id') ?>" placeholder="123456789012345">
      </div>
      <?php if ($settings['ga4_measurement_id'] ?? ''): ?>
      <div class="alert" style="background:#FEF3C7;border:1px solid #fde68a;color:#92400e;display:flex;gap:8px;padding:10px 14px;border-radius:8px;font-size:12px">
        <i class="ti ti-check-circle" style="flex-shrink:0"></i>
        Google Analytics actif — ID : <strong><?= $s('ga4_measurement_id') ?></strong>
      </div>
      <?php endif; ?>
    </div>

    <!-- WhatsApp -->
    <div class="admin-card" style="margin-bottom:20px">
      <div class="admin-card-title"><i class="ti ti-brand-whatsapp" style="color:#25D366"></i> WhatsApp Business API</div>
      <div class="alert alert-success" style="margin-bottom:16px">
        <i class="ti ti-info-circle" style="flex-shrink:0"></i>
        Compatible avec <strong>WhatsApp Business Cloud API</strong> (Meta) ou services tiers (Twilio, Vonage, etc.)
      </div>
      <div class="form-group">
        <label>URL de l'API WhatsApp</label>
        <input type="url" name="whatsapp_api_url" value="<?= $s('whatsapp_api_url') ?>" placeholder="https://graph.facebook.com/v18.0/PHONE_NUMBER_ID/messages">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Token d'accès</label>
          <input type="password" name="whatsapp_api_token" value="<?= $s('whatsapp_api_token') ?>" placeholder="EAA...">
        </div>
        <div class="form-group">
          <label>Numéro WhatsApp Business</label>
          <input type="text" name="whatsapp_numero" value="<?= $s('whatsapp_numero') ?>" placeholder="+22997000000">
        </div>
      </div>
    </div>

    <!-- Coaching / Zoom / Calendly -->
    <div class="admin-card" style="margin-bottom:20px">
      <div class="admin-card-title"><i class="ti ti-video" style="color:#2D8CFF"></i> Coaching 1:1 — Zoom & Calendly</div>
      <div class="form-group">
        <label>Lien Calendly (prise de RDV)</label>
        <input type="url" name="calendly_url" value="<?= $s('calendly_url') ?>" placeholder="https://calendly.com/votre-nom/coaching-30min">
        <small style="color:var(--text-muted);font-size:11px">Affiché sur les pages de cours payants pour réserver une session.</small>
      </div>
      <div class="form-group">
        <label>Zoom API Key (optionnel)</label>
        <input type="text" name="zoom_api_key" value="<?= $s('zoom_api_key') ?>" placeholder="Votre Zoom SDK Key">
      </div>
    </div>

    <button type="submit" class="btn-primary">
      <i class="ti ti-device-floppy"></i> Sauvegarder tous les paramètres
    </button>
  </form>
</div>
</body>
</html>
