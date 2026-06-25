<?php
// mes-cours-instructeur.php — Espace instructeur : cours qui lui sont assignés
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
reqInstructeurOuAdmin();

$pdo = getPDO();
$userId = (int)($_SESSION['user_id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT c.*, cat.nom as categorie,
        (SELECT COUNT(*) FROM modules m WHERE m.course_id = c.id) as nb_modules
     FROM courses c
     JOIN categories cat ON cat.id = c.category_id
     WHERE c.formateur_id = ?
     ORDER BY c.titre'
);
$stmt->execute([$userId]);
$courses = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Mes cours — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>

<div style="max-width:1000px;margin:40px auto;padding:0 20px">
  <h1 style="font-family:'Syne',sans-serif;font-size:28px;margin-bottom:6px">Mes cours assignés</h1>
  <p style="color:var(--text-muted);margin-bottom:24px">Vous pouvez gérer les séquences (vidéos, textes, quiz) des cours qui vous sont assignés par l'administrateur.</p>

  <?php if (empty($courses)): ?>
  <div style="padding:32px;text-align:center;background:#f9fafb;border-radius:12px;color:var(--text-muted)">
    <i class="ti ti-school" style="font-size:32px"></i>
    <p>Aucun cours ne vous est assigné pour le moment. Contactez l'administrateur.</p>
  </div>
  <?php else: ?>
  <div style="display:grid;gap:14px">
    <?php foreach ($courses as $c): ?>
    <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;background:#fff;border:1px solid #eee;border-radius:12px">
      <div>
        <div style="font-weight:600"><?= h($c['titre']) ?></div>
        <div style="font-size:12px;color:var(--text-muted)"><?= h($c['categorie']) ?> · <?= $c['nb_modules'] ?> module(s)</div>
      </div>
      <a href="<?= SITE_URL ?>/admin/modules.php?course_id=<?= $c['id'] ?>" class="btn-outline btn-sm">
        <i class="ti ti-layout-list"></i> Gérer les modules
      </a>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
