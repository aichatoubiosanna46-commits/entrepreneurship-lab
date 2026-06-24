<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
reqConnecte();
$pdo    = getPDO();
$userId = $_SESSION['user_id'];

// Envoyer message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sujet   = trim($_POST['sujet'] ?? '');
    $contenu = trim($_POST['contenu'] ?? '');
    if ($sujet && $contenu) {
        // Trouver un admin pour recevoir le message
        try {
            $admin = $pdo->query('SELECT id FROM admins WHERE actif=1 LIMIT 1')->fetch();
            $toId  = $admin['id'] ?? 1;
            $pdo->prepare('INSERT INTO messages (from_admin, from_id, to_user_id, sujet, contenu) VALUES (0,?,?,?,?)')
                ->execute([$userId, $toId, $sujet, $contenu]);
            redirect(SITE_URL . '/messages.php', 'Message envoyé au coach !', 'success');
        } catch (Exception $e) {
            $erreur = $e->getMessage();
        }
    }
}

// Récupérer messages
try {
    $msgs = $pdo->prepare('SELECT * FROM messages WHERE to_user_id=? OR (from_id=? AND from_admin=0) ORDER BY created_at DESC');
    $msgs->execute([$userId, $userId]);
    $msgs = $msgs->fetchAll();
} catch(Exception $e) { $msgs = []; }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Messages — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<style>
body{background:#FFFBEB;font-family:'Plus Jakarta Sans',sans-serif}
.msg-wrap{max-width:800px;margin:0 auto;padding:40px 24px}
.msg-card{background:#fff;border-radius:16px;padding:24px;border:1px solid #e5e7eb;margin-bottom:14px}
.msg-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
.msg-sujet{font-size:15px;font-weight:700;color:#1C1917}
.msg-meta{font-size:11px;color:#9ca3af}
.msg-body{font-size:13px;color:#374151;line-height:1.7}
.msg-badge{font-size:11px;padding:2px 10px;border-radius:20px;font-weight:600}
.badge-coach{background:#EDE9FE;color:#7c3aed}
.badge-moi{background:#FEF3C7;color:#D97706}
.form-card{background:#fff;border-radius:16px;padding:28px;border:1px solid #fde68a;margin-bottom:24px}
</style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<?= flash() ?>
<div class="msg-wrap">
  <h1 style="font-size:24px;font-weight:800;color:#1C1917;margin-bottom:24px">
    <i class="ti ti-messages" style="color:#F59E0B"></i> Messages avec le coach
  </h1>

  <!-- Nouveau message -->
  <div class="form-card">
    <h2 style="font-size:15px;font-weight:700;color:#1C1917;margin-bottom:16px">Envoyer un message</h2>
    <form method="POST">
      <div style="display:flex;flex-direction:column;gap:12px">
        <input type="text" name="sujet" placeholder="Sujet *" required
          style="padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;font-family:inherit">
        <textarea name="contenu" rows="4" placeholder="Votre message..." required
          style="padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;font-family:inherit;resize:vertical"></textarea>
        <button type="submit" style="padding:11px 20px;background:#F59E0B;color:#1C1917;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;align-self:flex-start">
          <i class="ti ti-send"></i> Envoyer
        </button>
      </div>
    </form>
  </div>

  <!-- Liste messages -->
  <?php if (empty($msgs)): ?>
  <div style="text-align:center;padding:40px;background:#fff;border-radius:16px;color:#9ca3af">
    <i class="ti ti-messages" style="font-size:48px;display:block;margin-bottom:12px"></i>
    Aucun message pour l'instant
  </div>
  <?php else: ?>
  <?php foreach ($msgs as $m): ?>
  <div class="msg-card">
    <div class="msg-header">
      <div class="msg-sujet"><?= h($m['sujet']) ?></div>
      <div style="display:flex;align-items:center;gap:10px">
        <span class="msg-badge <?= $m['from_admin'] ? 'badge-coach' : 'badge-moi' ?>">
          <?= $m['from_admin'] ? 'Coach' : 'Moi' ?>
        </span>
        <span class="msg-meta"><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></span>
      </div>
    </div>
    <div class="msg-body"><?= nl2br(h($m['contenu'])) ?></div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
