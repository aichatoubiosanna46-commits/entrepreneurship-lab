<?php
// admin/courses.php — Liste des cours
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();

$pdo = getPDO();
$courses = $pdo->query(
    'SELECT c.*, cat.nom as categorie,
            (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) as nb_inscrits,
            (SELECT COUNT(*) FROM modules m WHERE m.course_id = c.id) as nb_modules
     FROM courses c
     JOIN categories cat ON cat.id = c.category_id
     ORDER BY c.created_at DESC'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Cours — Admin <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/dashboard.css">
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<div class="admin-content">
  <div class="admin-topbar">
    <div>
      <h1 class="admin-page-title">Cours</h1>
      <p class="admin-page-sub"><?= count($courses) ?> cours au total</p>
    </div>
    <a href="<?= SITE_URL ?>/admin/course_add.php" class="btn-primary btn-sm">
      <i class="ti ti-plus"></i> Nouveau cours
    </a>
  </div>

  <?= flash() ?>

  <div class="admin-card" style="padding:0;overflow:hidden">
    <table class="admin-table" style="margin:0">
      <thead>
        <tr>
          <th>Cours</th><th>Catégorie</th><th>Parcours</th><th>Modules</th><th>Type</th>
          <th>Prix</th><th>Inscrits</th><th>Statut</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($courses as $c): ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:10px">
              <?php if ($c['miniature']): ?>
                <img src="<?= SITE_URL ?>/assets/uploads/<?= h($c['miniature']) ?>" style="width:40px;height:40px;border-radius:6px;object-fit:cover">
              <?php else: ?>
                <div style="width:40px;height:40px;border-radius:6px;background:#FAEEDA;display:flex;align-items:center;justify-content:center;color:#BA7517">
                  <i class="ti ti-school"></i>
                </div>
              <?php endif; ?>
              <div>
                <div style="font-weight:500;font-size:13px"><?= h($c['titre']) ?></div>
                <?php if ($c['certificat']): ?>
                  <span style="font-size:11px;color:#534AB7"><i class="ti ti-certificate"></i> Certificat</span>
                <?php endif; ?>
              </div>
            </div>
          </td>
          <td><span class="badge badge-neutral"><?= h($c['categorie']) ?></span></td>
          <td>
            <?php
            $tarifMap = [
              'decouverte'    => ['💡','Découverte','#EAF3DE','#27500A'],
              'business_plan' => ['📊','Business Plan','#FEF3C7','#92400E'],
              'lancement'     => ['🚀','Lancement','#D1FAE5','#065F46'],
            ];
            [$tEm,$tNom,$tBg,$tCol] = $tarifMap[$c['tarif'] ?? 'decouverte'] ?? ['📚','—','#f4f4f4','#666'];
            ?>
            <span style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:3px 10px;border-radius:100px;background:<?= $tBg ?>;color:<?= $tCol ?>">
              <?= $tEm ?> <?= $tNom ?>
            </span>
          </td>
          <td>
            <a href="<?= SITE_URL ?>/admin/modules.php?course_id=<?= $c['id'] ?>"
               style="font-weight:600;color:var(--amber)">
              <?= $c['nb_modules'] ?> mod.
            </a>
          </td>
          <td>
            <?php if ($c['type'] === 'gratuit'): ?>
              <span class="badge badge-success">Gratuit</span>
            <?php else: ?>
              <span class="badge badge-amber">Payant</span>
            <?php endif; ?>
          </td>
          <td><?= fcfa((float)$c['prix']) ?></td>
          <td><strong><?= $c['nb_inscrits'] ?></strong></td>
          <td>
            <?php
            $badgeMap = [
                'publie'    => ['badge-success', 'ti-eye',       'Publié'],
                'brouillon' => ['badge-neutral',  'ti-pencil',    'Brouillon'],
                'archive'   => ['badge-amber',    'ti-archive',   'Archivé'],
            ];
            [$cls, $icon, $label] = $badgeMap[$c['statut']] ?? ['badge-neutral','ti-question-mark','?'];
            ?>
            <span class="badge <?= $cls ?>"><i class="ti <?= $icon ?>" style="font-size:11px"></i> <?= $label ?></span>
          </td>
          <td>
            <div style="display:flex;gap:6px">
              <a href="<?= SITE_URL ?>/admin/modules.php?course_id=<?= $c['id'] ?>" class="btn-icon" title="Modules" style="color:#534AB7">
                <i class="ti ti-layout-list"></i>
              </a>
              <a href="<?= SITE_URL ?>/admin/course_edit.php?id=<?= $c['id'] ?>" class="btn-icon" title="Modifier">
                <i class="ti ti-edit"></i>
              </a>
              <a href="<?= SITE_URL ?>/admin/course_delete.php?id=<?= $c['id'] ?>&csrf=<?= csrfToken() ?>"
                 class="btn-icon btn-icon-danger"
                 onclick="return confirm('Supprimer ce cours et tout son contenu ?')" title="Supprimer">
                <i class="ti ti-trash"></i>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($courses)): ?>
        <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--text-muted)">
          Aucun cours. <a href="<?= SITE_URL ?>/admin/course_add.php">Créer le premier</a>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="<?= SITE_URL ?>/assets/js/dashboard.js"></script>
</body>
</html>
