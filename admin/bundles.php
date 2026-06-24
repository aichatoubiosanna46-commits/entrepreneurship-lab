<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();
$pdo = getPDO();

// Suppression
if (isset($_GET['delete'])) {
    $pdo->prepare('DELETE FROM bundles WHERE id=?')->execute([(int)$_GET['delete']]);
    redirect(SITE_URL . '/admin/bundles.php', 'Bundle supprimé.', 'success');
}

// Création
$erreur = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre       = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $prix        = (float)($_POST['prix'] ?? 0);
    $actif       = isset($_POST['actif']) ? 1 : 0;
    $courseIds   = array_map('intval', $_POST['course_ids'] ?? []);

    if (!$titre) { $erreur = 'Le titre est requis.'; }
    elseif (count($courseIds) < 2) { $erreur = 'Sélectionnez au moins 2 cours.'; }
    else {
        $pdo->prepare('INSERT INTO bundles (titre, description, prix, actif) VALUES (?,?,?,?)')
            ->execute([$titre, $description, $prix, $actif]);
        $bundleId = (int)$pdo->lastInsertId();
        foreach ($courseIds as $i => $cId) {
            $pdo->prepare('INSERT IGNORE INTO bundle_courses (bundle_id, course_id, ordre) VALUES (?,?,?)')->execute([$bundleId, $cId, $i]);
        }
        redirect(SITE_URL . '/admin/bundles.php', 'Bundle créé avec succès !', 'success');
    }
}

try {
    $bundles = $pdo->query(
        'SELECT b.*, COUNT(bc.course_id) as nb_cours FROM bundles b
         LEFT JOIN bundle_courses bc ON bc.bundle_id=b.id GROUP BY b.id ORDER BY b.created_at DESC'
    )->fetchAll();
    $courses = $pdo->query('SELECT id, titre FROM courses WHERE actif=1 ORDER BY titre')->fetchAll();
} catch(Exception $e) { $bundles = []; $courses = []; }

$currentPage = 'bundles.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Bundles — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
<style>.fld{width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;font-family:inherit;box-sizing:border-box}</style>
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <div><h1 class="admin-page-title">Bundles / Packs de cours</h1><p class="admin-page-sub">Regrouper plusieurs cours à prix réduit</p></div>
  </div>
  <?= flash() ?>
  <?php if ($erreur): ?><div class="alert alert-error"><?= h($erreur) ?></div><?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 360px;gap:20px">
    <!-- Liste bundles -->
    <div class="admin-card">
      <div class="admin-card-title">Bundles actifs</div>
      <?php if (empty($bundles)): ?>
      <p style="text-align:center;color:#9ca3af;padding:32px">Aucun bundle créé</p>
      <?php else: ?>
      <table class="admin-table">
        <thead><tr><th>Titre</th><th>Cours</th><th>Prix</th><th>Statut</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($bundles as $b): ?>
          <tr>
            <td><strong><?= h($b['titre']) ?></strong><br><small style="color:#6b7280"><?= h(mb_substr($b['description']??'',0,60)) ?></small></td>
            <td style="text-align:center"><span style="background:#FBE3DA;color:#C04A22;padding:2px 10px;border-radius:20px;font-size:12px;font-weight:700"><?= $b['nb_cours'] ?> cours</span></td>
            <td><strong><?= number_format($b['prix'],0,'.',',') ?> FCFA</strong></td>
            <td><span class="badge <?= $b['actif']?'badge-success':'badge-neutral' ?>"><?= $b['actif']?'Actif':'Inactif' ?></span></td>
            <td>
              <a href="bundle_edit.php?id=<?= $b['id'] ?>" class="btn-outline btn-sm"><i class="ti ti-edit"></i></a>
              <a href="?delete=<?= $b['id'] ?>" class="btn-outline btn-sm" style="color:#dc2626"
                 onclick="return confirm('Supprimer ce bundle ?')"><i class="ti ti-trash"></i></a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>

    <!-- Formulaire création -->
    <div class="admin-card" style="position:sticky;top:80px;height:fit-content">
      <div class="admin-card-title">Créer un bundle</div>
      <form method="POST">
        <div class="form-group">
          <label>Titre *</label>
          <input type="text" name="titre" class="fld" required placeholder="Ex: Pack Entrepreneur Complet">
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" class="fld" rows="2" placeholder="Description du pack..."></textarea>
        </div>
        <div class="form-group">
          <label>Prix (FCFA)</label>
          <input type="number" name="prix" class="fld" value="0" min="0">
        </div>
        <div class="form-group">
          <label>Cours inclus * <small style="font-weight:400;color:#6b7280">(Ctrl+clic pour sélectionner plusieurs)</small></label>
          <select name="course_ids[]" multiple class="fld" style="height:160px">
            <?php foreach ($courses as $co): ?>
            <option value="<?= $co['id'] ?>"><?= h($co['titre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="checkbox-label"><input type="checkbox" name="actif" value="1" checked> Bundle actif</label>
        </div>
        <button type="submit" class="btn-primary btn-full"><i class="ti ti-package"></i> Créer le bundle</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
