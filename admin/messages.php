<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();
$pdo = getPDO();

// Répondre
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $toUserId = (int)$_POST['to_user_id'];
    $sujet    = trim($_POST['sujet'] ?? '');
    $contenu  = trim($_POST['contenu'] ?? '');
    $adminId  = $_SESSION['admin_id'];
    if ($toUserId && $sujet && $contenu) {
        try {
            $pdo->prepare('INSERT INTO messages (from_admin, from_id, to_user_id, sujet, contenu) VALUES (1,?,?,?,?)')
                ->execute([$adminId, $toUserId, $sujet, $contenu]);
            // Marquer messages lus
            $pdo->prepare('UPDATE messages SET lu=1 WHERE to_user_id=? AND from_admin=0')->execute([$adminId]);
            redirect(SITE_URL . '/admin/messages.php', 'Réponse envoyée !', 'success');
        } catch(Exception $e) { $erreur = $e->getMessage(); }
    }
}

// Récupérer tous les messages groupés par étudiant
try {
    $msgs = $pdo->query(
        'SELECT m.*, u.nom, u.prenom, u.email
         FROM messages m
         LEFT JOIN users u ON u.id = m.from_id AND m.from_admin=0
         ORDER BY m.created_at DESC'
    )->fetchAll();
    // Étudiants uniques
    $students = $pdo->query('SELECT DISTINCT m.from_id, u.nom, u.prenom, u.email FROM messages m JOIN users u ON u.id=m.from_id WHERE m.from_admin=0 ORDER BY u.nom')->fetchAll();
} catch(Exception $e) { $msgs = []; $students = []; }

$selectedUser = (int)($_GET['user_id'] ?? ($students[0]['from_id'] ?? 0));
$currentPage = 'messages.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Messagerie — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
<style>
.msg-layout{display:grid;grid-template-columns:280px 1fr;gap:20px;height:calc(100vh - 160px)}
.msg-sidebar{background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow-y:auto}
.msg-student{padding:14px 16px;border-bottom:1px solid #f3f4f6;cursor:pointer;transition:.15s}
.msg-student:hover,.msg-student.active{background:#FEF3C7}
.msg-student .name{font-size:13px;font-weight:600;color:#1C1917}
.msg-student .email{font-size:11px;color:#9ca3af}
.msg-main{display:flex;flex-direction:column;gap:16px}
.msg-bubble{padding:14px 16px;border-radius:12px;font-size:13px;line-height:1.6;max-width:80%}
.msg-from-student{background:#f3f4f6;color:#1C1917;align-self:flex-start}
.msg-from-admin{background:linear-gradient(135deg,#F59E0B,#D97706);color:#1C1917;align-self:flex-end}
.msg-time{font-size:10px;color:#9ca3af;margin-top:4px}
.reply-form{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px}
</style>
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <div><h1 class="admin-page-title">Messagerie</h1><p class="admin-page-sub">Échanger avec les étudiants</p></div>
  </div>
  <?= flash() ?>

  <div class="msg-layout">
    <!-- Sidebar étudiants -->
    <div class="msg-sidebar">
      <div style="padding:14px 16px;font-size:11px;font-weight:700;text-transform:uppercase;color:#9ca3af;border-bottom:1px solid #f3f4f6">Étudiants</div>
      <?php foreach ($students as $s): ?>
      <a href="?user_id=<?= $s['from_id'] ?>" style="text-decoration:none">
        <div class="msg-student <?= $s['from_id']==$selectedUser?'active':'' ?>">
          <div class="name"><?= h($s['nom'].' '.$s['prenom']) ?></div>
          <div class="email"><?= h($s['email']) ?></div>
        </div>
      </a>
      <?php endforeach; ?>
      <?php if (empty($students)): ?>
      <div style="padding:24px;text-align:center;color:#9ca3af;font-size:13px">Aucun message reçu</div>
      <?php endif; ?>
    </div>

    <!-- Conversation -->
    <div class="msg-main">
      <?php if ($selectedUser): ?>
      <div class="admin-card" style="flex:1;overflow-y:auto;display:flex;flex-direction:column;gap:12px;padding:20px">
        <?php
        $conv = array_filter($msgs, fn($m) => $m['from_id']==$selectedUser || ($m['from_admin']==1 && $m['to_user_id']==$selectedUser));
        $conv = array_reverse(array_values($conv));
        foreach ($conv as $m): ?>
        <div style="display:flex;flex-direction:column;align-items:<?= $m['from_admin']?'flex-end':'flex-start' ?>">
          <div class="msg-bubble <?= $m['from_admin']?'msg-from-admin':'msg-from-student' ?>">
            <div style="font-weight:600;font-size:11px;margin-bottom:4px"><?= $m['from_admin']?'Vous (Coach)':'Étudiant' ?></div>
            <div style="font-weight:600;margin-bottom:4px"><?= h($m['sujet']) ?></div>
            <?= nl2br(h($m['contenu'])) ?>
          </div>
          <div class="msg-time"><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($conv)): ?>
        <div style="text-align:center;color:#9ca3af;padding:40px">Aucun message dans cette conversation</div>
        <?php endif; ?>
      </div>

      <!-- Formulaire réponse -->
      <div class="reply-form">
        <form method="POST" style="display:flex;flex-direction:column;gap:10px">
          <input type="hidden" name="to_user_id" value="<?= $selectedUser ?>">
          <input type="text" name="sujet" placeholder="Sujet..." required
            style="padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;font-family:inherit">
          <div style="display:flex;gap:10px">
            <textarea name="contenu" rows="3" placeholder="Votre réponse..." required
              style="flex:1;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;font-family:inherit;resize:none"></textarea>
            <button type="submit" class="btn-primary" style="padding:0 20px">
              <i class="ti ti-send"></i>
            </button>
          </div>
        </form>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
