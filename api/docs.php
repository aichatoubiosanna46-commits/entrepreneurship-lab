<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>API Documentation — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<style>
body{background:#0f172a;color:#e2e8f0;font-family:'Plus Jakarta Sans',sans-serif;margin:0;padding:0}
.docs-wrap{max-width:900px;margin:0 auto;padding:48px 24px}
.docs-header{margin-bottom:48px}
.docs-title{font-size:32px;font-weight:800;color:#fff;margin-bottom:8px}
.docs-sub{font-size:14px;color:#94a3b8}
.base-url{display:inline-flex;align-items:center;gap:8px;background:#1e293b;border:1px solid #334155;border-radius:8px;padding:8px 14px;font-family:'JetBrains Mono',monospace;font-size:13px;color:#F59E0B;margin-top:12px}
.endpoint-group{margin-bottom:40px}
.group-title{font-size:16px;font-weight:700;color:#F59E0B;margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid #1e293b;display:flex;align-items:center;gap:8px}
.endpoint{background:#1e293b;border:1px solid #334155;border-radius:12px;margin-bottom:12px;overflow:hidden}
.ep-header{padding:14px 18px;display:flex;align-items:center;gap:12px;cursor:pointer;user-select:none}
.ep-method{padding:3px 10px;border-radius:6px;font-size:11px;font-weight:800;font-family:'JetBrains Mono',monospace;flex-shrink:0}
.get{background:#0d9488;color:#fff}.post{background:#7c3aed;color:#fff}.put{background:#d97706;color:#fff}.delete{background:#dc2626;color:#fff}
.ep-path{font-family:'JetBrains Mono',monospace;font-size:13px;color:#e2e8f0;flex:1}
.ep-desc{font-size:12px;color:#64748b}
.ep-body{display:none;padding:0 18px 16px;border-top:1px solid #334155}
.ep-body.open{display:block}
.ep-auth{display:inline-flex;align-items:center;gap:5px;font-size:11px;padding:3px 8px;border-radius:6px;background:#292524;color:#F59E0B;margin-top:10px;margin-bottom:10px}
.code{background:#0f172a;border:1px solid #334155;border-radius:8px;padding:14px;font-family:'JetBrains Mono',monospace;font-size:12px;color:#86efac;overflow-x:auto;margin-top:10px}
.param-table{width:100%;border-collapse:collapse;font-size:12px;margin-top:10px}
.param-table th{text-align:left;padding:6px 10px;color:#64748b;font-weight:600;border-bottom:1px solid #334155}
.param-table td{padding:8px 10px;border-bottom:1px solid #1e293b;vertical-align:top}
.required{color:#ef4444;font-size:10px;font-weight:700}
.optional{color:#64748b;font-size:10px}
</style>
</head>
<body>
<div class="docs-wrap">
  <div class="docs-header">
    <div class="docs-title">📡 API REST — <?= SITE_NAME ?></div>
    <div class="docs-sub">API publique pour intégrer les données de la plateforme dans vos applications.</div>
    <div class="base-url"><i class="ti ti-link"></i> <?= SITE_URL ?>/api/?endpoint=</div>
  </div>

  <?php
  $endpoints = [
    ['Cours (public)' => [
      ['GET',  'courses',             'Liste tous les cours publiés', false, '?cat=Entrepreneuriat', ['cat' => ['type'=>'string','req'=>false,'desc'=>'Filtrer par catégorie']]],
      ['GET',  'courses/{slug}',      'Détail d\'un cours',           false, '', []],
      ['GET',  'categories',          'Liste des catégories',          false, '', []],
    ]],
    ['Mon compte (Bearer Token requis)' => [
      ['GET',  'me',                  'Profil utilisateur + XP',       true, '', []],
      ['GET',  'me/enrollments',      'Mes formations inscrites',      true, '', []],
      ['GET',  'me/certificates',     'Mes certificats obtenus',       true, '', []],
    ]],
    ['Authentification' => [
      ['POST', 'tokens',              'Créer un token API',            false, '', ['nom' => ['type'=>'string','req'=>false,'desc'=>'Nom de l\'application']]],
    ]],
  ];

  foreach ($endpoints as $group):
    foreach ($group as $title => $eps):
  ?>
  <div class="endpoint-group">
    <div class="group-title"><i class="ti ti-api"></i> <?= $title ?></div>
    <?php foreach ($eps as [$method, $path, $desc, $auth, $example, $params]): ?>
    <div class="endpoint">
      <div class="ep-header" onclick="toggle(this)">
        <span class="ep-method <?= strtolower($method) ?>"><?= $method ?></span>
        <span class="ep-path">/<?= $path ?></span>
        <span class="ep-desc"><?= $desc ?></span>
      </div>
      <div class="ep-body">
        <?php if ($auth): ?>
        <div class="ep-auth"><i class="ti ti-lock" style="font-size:13px"></i> Requiert Authorization: Bearer {token}</div>
        <?php endif; ?>
        <?php if (!empty($params)): ?>
        <table class="param-table">
          <tr><th>Paramètre</th><th>Type</th><th>Description</th></tr>
          <?php foreach ($params as $pname => $pinfo): ?>
          <tr>
            <td><code style="color:#F59E0B"><?= $pname ?></code> <span class="<?= $pinfo['req']?'required':'optional' ?>"><?= $pinfo['req']?'requis':'optionnel' ?></span></td>
            <td style="color:#64748b"><?= $pinfo['type'] ?></td>
            <td style="color:#94a3b8"><?= $pinfo['desc'] ?></td>
          </tr>
          <?php endforeach; ?>
        </table>
        <?php endif; ?>
        <div class="code">curl "<?= SITE_URL ?>/api/?endpoint=<?= $path . $example ?>"<?= $auth ? " \\\n     -H \"Authorization: Bearer VOTRE_TOKEN\"" : '' ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endforeach; endforeach; ?>
</div>
<script>
function toggle(header) { header.nextElementSibling.classList.toggle('open'); }
</script>
</body>
</html>
