<?php
// admin/course_import.php — Importer un cours depuis JSON
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();

$pdo    = getPDO();
$erreur = '';
$succes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_FILES['json_file']['tmp_name'])) {
        $content = file_get_contents($_FILES['json_file']['tmp_name']);
        $data    = json_decode($content, true);

        if (!$data || !isset($data['cours'])) {
            $erreur = 'Fichier JSON invalide ou corrompu.';
        } else {
            try {
                $co = $data['cours'];
                // Trouver la catégorie par défaut
                $catId = $pdo->query('SELECT id FROM categories LIMIT 1')->fetchColumn() ?: 1;

                $newSlug = slug($co['titre']) . '-import-' . time();
                $pdo->prepare(
                    'INSERT INTO courses (category_id, titre, sous_titre, description, slug, niveau, type, prix, duree_heures, certificat, actif)
                     VALUES (?,?,?,?,?,?,?,?,?,?,0)'
                )->execute([
                    $catId, '[Import] '.$co['titre'], $co['sous_titre']??'',
                    $co['description']??'', $newSlug,
                    $co['niveau']??'debutant', $co['type']??'gratuit',
                    $co['prix']??0, $co['duree_heures']??0, $co['certificat']??0
                ]);
                $newCourseId = (int)$pdo->lastInsertId();

                foreach ($data['modules'] ?? [] as $mod) {
                    $pdo->prepare(
                        'INSERT INTO modules (course_id, titre, description, objectifs, ordre, actif, duree_min) VALUES (?,?,?,?,?,1,?)'
                    )->execute([$newCourseId, $mod['titre'], $mod['description']??'', $mod['objectifs']??'', $mod['ordre']??0, $mod['duree_min']??null]);
                    $newModId = (int)$pdo->lastInsertId();

                    foreach ($mod['sequences'] ?? [] as $seq) {
                        $newSeqSlug = slug($seq['titre']) . '-' . uniqid();
                        $pdo->prepare(
                            'INSERT INTO sequences (module_id, titre, slug, description, contenu, video_url, audio_url, duree_min, ordre, actif, xp_reward)
                             VALUES (?,?,?,?,?,?,?,?,?,1,?)'
                        )->execute([
                            $newModId, $seq['titre'], $newSeqSlug,
                            $seq['description']??'', $seq['contenu']??'',
                            $seq['video_url']??null, $seq['audio_url']??null,
                            $seq['duree_min']??null, $seq['ordre']??0, $seq['xp_reward']??10
                        ]);
                    }
                }
                $succes = 'Cours importé avec succès ! ' . count($data['modules']??[]) . ' modules créés. <a href="' . SITE_URL . '/admin/courses.php">Voir les cours →</a>';
            } catch (Exception $e) {
                $erreur = 'Erreur lors de l\'import : ' . $e->getMessage();
            }
        }
    } else {
        $erreur = 'Aucun fichier sélectionné.';
    }
}

$currentPage = 'course_import.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Importer un cours — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <div><h1 class="admin-page-title">Importer un cours</h1><p class="admin-page-sub">Depuis un fichier JSON exporté d'Ariziki</p></div>
    <a href="<?= SITE_URL ?>/admin/courses.php" class="btn-outline"><i class="ti ti-arrow-left"></i> Retour</a>
  </div>

  <?php if ($erreur): ?><div class="alert alert-error"><i class="ti ti-alert-circle"></i> <?= $erreur ?></div><?php endif; ?>
  <?php if ($succes): ?><div class="alert alert-success"><i class="ti ti-check-circle"></i> <?= $succes ?></div><?php endif; ?>

  <div style="max-width:500px">
    <div class="admin-card">
      <div class="admin-card-title"><i class="ti ti-file-import" style="color:var(--primary)"></i> Importer un fichier JSON</div>
      <p style="font-size:13px;color:#6b7280;margin-bottom:20px">Sélectionnez un fichier <code>.json</code> exporté depuis Ariziki. La structure du cours (modules + séquences) sera recréée.</p>
      <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
          <label>Fichier JSON *</label>
          <input type="file" name="json_file" accept=".json" required
                 style="width:100%;padding:10px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;box-sizing:border-box">
        </div>
        <div style="background:#FEF3C7;padding:12px 14px;border-radius:8px;font-size:12px;color:#92400E;margin-bottom:16px">
          <i class="ti ti-info-circle"></i> Le cours sera importé en mode brouillon (inactif). Vous pourrez l'activer après vérification.
        </div>
        <button type="submit" class="btn-primary btn-full"><i class="ti ti-upload"></i> Importer le cours</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
