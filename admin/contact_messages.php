<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();
$pdo = getPDO();

if (isset($_GET['lu'])) {
    $pdo->prepare('UPDATE contact_messages SET lu=1 WHERE id=?')->execute([(int)$_GET['lu']]);
    redirect(SITE_URL . '/admin/contact_messages.php', 'Message marqué comme lu.', 'success');
}

try {
    $messages = $pdo->query('SELECT * FROM contact_messages ORDER BY created_at DESC')->fetchAll();
} catch(Exception $e) { $messages = []; }

$currentPage = 'contact_messages.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Messages contact — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <div>
      <h1 class="admin-page-title">Messages de contact</h1>
      <p class="admin-page-sub"><?= count($messages) ?> message(s) reçu(s)</p>
    </div>
  </div>
  <?= flash() ?>
  <div class="admin-card">
    <table class="admin-table">
      <thead><tr><th>Date</th><th>Nom</th><th>Email</th><th>Sujet</th><th>Message</th><th>Statut</th><th>Action</th></tr></thead>
      <tbody>
        <?php foreach($messages as $m): ?>
        <tr style="<?= !$m['lu'] ? 'background:#FFFBEB;font-weight:500' : '' ?>">
          <td style="font-size:12px;color:#6b7280"><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></td>
          <td><?= h($m['nom']) ?></td>
          <td><a href="mailto:<?= h($m['email']) ?>" style="color:var(--primary)"><?= h($m['email']) ?></a></td>
          <td><?= h($m['sujet']) ?></td>
          <td style="max-width:300px;font-size:13px"><?= h(mb_substr($m['message'],0,100)) ?>...</td>
          <td>
            <?php if (!$m['lu']): ?>
            <span class="badge badge-warning">Non lu</span>
            <?php else: ?>
            <span class="badge badge-neutral">Lu</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if (!$m['lu']): ?>
            <a href="?lu=<?= $m['id'] ?>" class="btn-outline btn-sm">Marquer lu</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($messages)): ?>
        <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:40px">Aucun message reçu</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
