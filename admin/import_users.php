<?php
// ============================================================
//  admin/import_users.php — Import CSV d'utilisateurs
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

sendSecurityHeaders();
reqAdmin();

$pdo     = getPDO();
$rapport = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCSRF();

    if (empty($_FILES['csv']['name']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
        redirect(SITE_URL . '/admin/import_users.php', 'Aucun fichier valide.', 'error');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($_FILES['csv']['tmp_name']);
    if (!in_array($mime, ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel'])) {
        redirect(SITE_URL . '/admin/import_users.php', 'Format de fichier invalide (CSV uniquement).', 'error');
    }

    $handle = fopen($_FILES['csv']['tmp_name'], 'r');
    if (!$handle) redirect(SITE_URL . '/admin/import_users.php', 'Impossible de lire le fichier.', 'error');

    // Détecter le séparateur (virgule ou point-virgule)
    $firstLine = fgets($handle);
    rewind($handle);
    $sep = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

    // Sauter l'en-tête
    $header = fgetcsv($handle, 1000, $sep);

    $created = 0;
    $errors  = [];
    $rowNum  = 1;

    $insertStmt = $pdo->prepare(
        'INSERT INTO users (nom, prenom, email, password, role, universite, filiere, promotion, actif)
         VALUES (?, ?, ?, ?, "apprenant", ?, ?, ?, 1)'
    );

    while (($row = fgetcsv($handle, 1000, $sep)) !== false) {
        $rowNum++;

        if (count($row) < 3) {
            $errors[] = "Ligne $rowNum : données insuffisantes (minimum: nom, prénom, email)";
            continue;
        }

        // Normaliser les colonnes selon l'index
        $nom        = trim($row[0] ?? '');
        $prenom     = trim($row[1] ?? '');
        $email      = trim($row[2] ?? '');
        $universite = trim($row[3] ?? '');
        $filiere    = trim($row[4] ?? '');
        $promotion  = trim($row[5] ?? '');

        if (empty($nom)) { $errors[] = "Ligne $rowNum : nom manquant"; continue; }
        if (empty($prenom)) { $errors[] = "Ligne $rowNum : prénom manquant"; continue; }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Ligne $rowNum : email invalide ($email)";
            continue;
        }

        // Vérifier si email existe déjà
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetch()) {
            $errors[] = "Ligne $rowNum : email déjà existant ($email)";
            continue;
        }

        // Mot de passe temporaire = hash de l'email
        $tempPwd = password_hash($email, PASSWORD_DEFAULT);

        try {
            $insertStmt->execute([$nom, $prenom, $email, $tempPwd, $universite ?: null, $filiere ?: null, $promotion ?: null]);
            logAction('user_imported', "Utilisateur importé : $email");
            $created++;
        } catch (Exception $e) {
            $errors[] = "Ligne $rowNum : erreur base de données pour $email";
        }
    }

    fclose($handle);
    $rapport = ['created' => $created, 'errors' => $errors];
}

$pageTitle = 'Import utilisateurs';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $pageTitle ?> — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
</head>
<body class="admin-layout">
<?php require_once __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <h1 class="admin-page-title"><i class="ti ti-file-import"></i> Import d'utilisateurs CSV</h1>
    <a href="export_users.php" class="btn-outline" style="font-size:13px"><i class="ti ti-download"></i> Exporter les existants</a>
  </div>
  <div class="admin-content" style="max-width:800px">
    <?= flash() ?>

    <?php if ($rapport): ?>
      <div style="background:<?= $rapport['created'] > 0 ? '#EAF3DE' : '#f9fafb' ?>;border:1px solid <?= $rapport['created'] > 0 ? '#97C459' : '#e5e7eb' ?>;border-radius:12px;padding:20px;margin-bottom:20px">
        <h3 style="margin:0 0 12px;font-size:16px;font-weight:600">Rapport d'import</h3>
        <div style="font-size:14px;margin-bottom:8px">
          <i class="ti ti-circle-check" style="color:#27500A"></i>
          <strong><?= $rapport['created'] ?></strong> compte(s) créé(s) avec succès.
        </div>
        <?php if (!empty($rapport['errors'])): ?>
          <div style="margin-top:12px">
            <div style="font-size:13px;font-weight:600;color:#993C1D;margin-bottom:6px">
              <?= count($rapport['errors']) ?> erreur(s) :
            </div>
            <ul style="margin:0;padding-left:20px;font-size:12px;color:#993C1D">
              <?php foreach ($rapport['errors'] as $e): ?>
                <li><?= h($e) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
        <?php if ($rapport['created'] > 0): ?>
          <div style="margin-top:12px;font-size:12px;color:var(--text-muted)">
            <i class="ti ti-info-circle"></i> Les mots de passe temporaires sont égaux à l'adresse email de chaque utilisateur.
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div style="background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:28px;margin-bottom:20px">
      <h3 style="font-size:16px;font-weight:600;margin:0 0 8px">Importer un fichier CSV</h3>
      <p style="color:var(--text-muted);font-size:13px;margin-bottom:20px">
        Format attendu (séparateur virgule ou point-virgule, avec ou sans en-tête) :
      </p>
      <div style="background:#f9fafb;border:1px solid var(--border,#e5e7eb);border-radius:8px;padding:12px;font-family:monospace;font-size:12px;margin-bottom:20px">
        nom;prenom;email;universite;filiere;promotion<br>
        Ahoua;Jean;jean.ahoua@email.com;UAC;Gestion;2024<br>
        Kossou;Marie;marie.k@email.com;;;
      </div>

      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <div class="form-group">
          <label>Fichier CSV <span style="color:#dc2626">*</span></label>
          <input type="file" name="csv" accept=".csv,.txt" required style="display:block;margin-top:6px">
        </div>
        <button type="submit" class="btn-primary">
          <i class="ti ti-file-import"></i> Lancer l'import
        </button>
      </form>
    </div>

    <div style="background:#EEF2FF;border:1px solid #C7D2FE;border-radius:8px;padding:14px 18px;font-size:13px;color:#4338CA">
      <i class="ti ti-info-circle"></i>
      <strong>Note :</strong> Les comptes importés reçoivent un mot de passe temporaire = leur adresse email.
      Ils devront changer leur mot de passe à la première connexion ou via le lien "Mot de passe oublié".
    </div>
  </div>
</div>
</body>
</html>
