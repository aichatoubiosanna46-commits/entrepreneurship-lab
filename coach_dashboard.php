<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (!estCoach() && !estAdmin()) {
    redirect(SITE_URL . '/dashboard.php', 'Accès réservé aux coachs.', 'error');
}

$pdo = getPDO();
$coachId = $_SESSION['admin_id'] ?? $_SESSION['user_id'];

// Stats coach
try {
    $enAttente = $pdo->query(
        "SELECT COUNT(*) FROM assignment_submissions WHERE statut IN ('soumis','en_correction')"
    )->fetchColumn();

    $corrigés = $pdo->query(
        "SELECT COUNT(*) FROM assignment_submissions WHERE statut IN ('accepte','refuse') AND DATE(corrige_le) = CURDATE()"
    )->fetchColumn();

    $messages = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE from_admin=0 AND lu=0");
    $messages->execute();
    $messagesNonLus = $messages->fetchColumn();

    $derniersSoumis = $pdo->query(
        "SELECT asub.*, u.nom, u.prenom, a.titre as assignment_titre, c.titre as cours_titre
         FROM assignment_submissions asub
         JOIN users u ON u.id=asub.user_id
         JOIN assignments a ON a.id=asub.assignment_id
         JOIN sequences s ON s.id=a.sequence_id
         JOIN modules m ON m.id=s.module_id
         JOIN courses c ON c.id=m.course_id
         WHERE asub.statut='soumis'
         ORDER BY asub.created_at DESC LIMIT 10"
    )->fetchAll();
} catch(Exception $e) {
    $enAttente = $corrigés = $messagesNonLus = 0;
    $derniersSoumis = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard Coach — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<style>
body{background:#F5F0E8;font-family:'Plus Jakarta Sans',sans-serif}
.coach-wrap{max-width:1100px;margin:0 auto;padding:32px 24px}
.stat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:28px}
.stat-card{background:#fff;border-radius:14px;padding:20px 24px;border:1px solid #e5e7eb}
.stat-num{font-size:32px;font-weight:800;color:#1A1A18;margin-bottom:4px}
.stat-label{font-size:13px;color:#6b7280}
.action-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:28px}
.action-card{background:#fff;border-radius:12px;padding:20px;border:1px solid #e5e7eb;text-decoration:none;color:#1A1A18;display:flex;align-items:center;gap:14px;transition:.15s}
.action-card:hover{border-color:#D85A30;background:#F5F0E8}
.action-icon{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
</style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<div class="coach-wrap">
  <div style="margin-bottom:24px">
    <h1 style="font-size:24px;font-weight:800;color:#1A1A18;margin-bottom:4px">
      <i class="ti ti-star" style="color:#D85A30"></i> Espace Coach
    </h1>
    <p style="font-size:14px;color:#6b7280">Gérez les livrables et accompagnez les apprenants</p>
  </div>

  <!-- Stats -->
  <div class="stat-grid">
    <div class="stat-card">
      <div class="stat-num" style="color:#D85A30"><?= $enAttente ?></div>
      <div class="stat-label">Livrables en attente de correction</div>
    </div>
    <div class="stat-card">
      <div class="stat-num" style="color:#16a34a"><?= $corrigés ?></div>
      <div class="stat-label">Corrections effectuées aujourd'hui</div>
    </div>
    <div class="stat-card">
      <div class="stat-num" style="color:#6C47D4"><?= $messagesNonLus ?></div>
      <div class="stat-label">Messages non lus</div>
    </div>
  </div>

  <!-- Actions rapides -->
  <div class="action-grid" style="margin-bottom:28px">
    <a href="<?= SITE_URL ?>/admin/review_center.php" class="action-card">
      <div class="action-icon" style="background:#FBE3DA"><i class="ti ti-clipboard-check" style="color:#C04A22"></i></div>
      <div><div style="font-weight:700;font-size:14px">Review Center</div><div style="font-size:12px;color:#6b7280">Corriger les livrables</div></div>
    </a>
    <a href="<?= SITE_URL ?>/admin/messages.php" class="action-card">
      <div class="action-icon" style="background:#EDE9FE"><i class="ti ti-messages" style="color:#7c3aed"></i></div>
      <div><div style="font-weight:700;font-size:14px">Messagerie</div><div style="font-size:12px;color:#6b7280">Répondre aux étudiants</div></div>
    </a>
    <a href="<?= SITE_URL ?>/admin/gradebook.php" class="action-card">
      <div class="action-icon" style="background:#ECFDF5"><i class="ti ti-chart-bar" style="color:#16a34a"></i></div>
      <div><div style="font-weight:700;font-size:14px">Gradebook</div><div style="font-size:12px;color:#6b7280">Voir les notes</div></div>
    </a>
  </div>

  <!-- Derniers livrables -->
  <div style="background:#fff;border-radius:14px;border:1px solid #e5e7eb;overflow:hidden">
    <div style="padding:16px 20px;border-bottom:1px solid #f3f4f6;font-size:14px;font-weight:700;color:#1A1A18">
      Livrables en attente
    </div>
    <?php if (empty($derniersSoumis)): ?>
    <div style="padding:40px;text-align:center;color:#9ca3af;font-size:13px">
      <i class="ti ti-check-circle" style="font-size:32px;display:block;margin-bottom:8px;color:#e5e7eb"></i>
      Aucun livrable en attente — bravo !
    </div>
    <?php else: ?>
    <table style="width:100%;border-collapse:collapse">
      <thead>
        <tr style="background:#f9fafb;font-size:12px;color:#9ca3af;font-weight:700;text-transform:uppercase;letter-spacing:.04em">
          <th style="padding:10px 16px;text-align:left">Étudiant</th>
          <th style="padding:10px 16px;text-align:left">Livrable</th>
          <th style="padding:10px 16px;text-align:left">Cours</th>
          <th style="padding:10px 16px;text-align:left">Soumis le</th>
          <th style="padding:10px 16px"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($derniersSoumis as $sub): ?>
        <tr style="border-top:1px solid #f3f4f6">
          <td style="padding:12px 16px;font-size:13px;font-weight:600"><?= h($sub['prenom'].' '.$sub['nom']) ?></td>
          <td style="padding:12px 16px;font-size:13px"><?= h($sub['assignment_titre']) ?></td>
          <td style="padding:12px 16px;font-size:12px;color:#6b7280"><?= h($sub['cours_titre']) ?></td>
          <td style="padding:12px 16px;font-size:12px;color:#9ca3af"><?= date('d/m/Y H:i', strtotime($sub['created_at'])) ?></td>
          <td style="padding:12px 16px">
            <a href="<?= SITE_URL ?>/admin/review_submission.php?id=<?= $sub['id'] ?>"
               style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;background:#D85A30;color:#1A1A18;border-radius:6px;font-size:12px;font-weight:700;text-decoration:none">
              <i class="ti ti-pencil-check"></i> Corriger
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
