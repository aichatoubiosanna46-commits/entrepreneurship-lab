<?php
// review-center.php — Espace formateur : soumissions d'activités à corriger
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
reqConnecte();
if (!estCoach()) {
    header('Location: ' . SITE_URL . '/dashboard.php?error=acces_refuse');
    exit;
}

$pdo  = getPDO();
$user = utilisateurCourant();

$where  = ["sub.statut = 'soumis'"];
$params = [];
if (!estAdmin()) {
    $where[]  = 'co.formateur_id = ?';
    $params[] = $_SESSION['user_id'];
}
$whereSQL = 'WHERE ' . implode(' AND ', $where);

$countStmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM activity_submissions sub
     JOIN activities a ON a.id = sub.activity_id
     JOIN sequences s ON s.id = a.sequence_id
     JOIN modules mo ON mo.id = s.module_id
     JOIN courses co ON co.id = mo.course_id
     $whereSQL"
);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

[$offset, $pages, $pageCourante] = paginer($total, 20);

$stmt = $pdo->prepare(
    "SELECT sub.id, sub.activity_id, sub.created_at,
            u.nom, u.prenom, u.email,
            a.titre AS titre_devoir, a.note_max,
            s.titre AS sequence_titre,
            co.titre AS course_titre
     FROM activity_submissions sub
     JOIN activities a ON a.id = sub.activity_id
     JOIN sequences s ON s.id = a.sequence_id
     JOIN modules mo ON mo.id = s.module_id
     JOIN courses co ON co.id = mo.course_id
     JOIN users u ON u.id = sub.user_id
     $whereSQL
     ORDER BY sub.created_at ASC
     LIMIT 20 OFFSET $offset"
);
$stmt->execute($params);
$submissions = $stmt->fetchAll();

$pageTitle = 'Review Center';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle) ?> — <?= SITE_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/home.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/user-dashboard.css">
</head>
<body class="user-dash-page">

<?php include __DIR__ . '/includes/header.php'; ?>

<?= flash() ?>

<div class="user-dash-layout">
  <!-- Sidebar utilisateur -->
  <aside class="user-sidebar">
    <div class="user-sidebar-profile">
      <div class="user-avatar-ring">
        <?php if ($user['avatar']): ?>
          <img src="<?= SITE_URL ?>/assets/uploads/<?= h($user['avatar']) ?>" alt="Avatar">
        <?php else: ?>
          <div class="user-avatar-placeholder"><?= mb_strtoupper(mb_substr($user['prenom'], 0, 1)) ?></div>
        <?php endif; ?>
      </div>
      <div class="user-sidebar-name"><?= h($user['prenom'].' '.$user['nom']) ?></div>
      <div class="user-sidebar-email"><?= h($user['email']) ?></div>
    </div>
    <nav class="user-sidebar-nav">
      <a href="<?= SITE_URL ?>/dashboard.php" class="user-nav-item">
        <i class="ti ti-layout-dashboard"></i> Tableau de bord
      </a>
      <a href="<?= SITE_URL ?>/review-center.php" class="user-nav-item active">
        <i class="ti ti-clipboard-check"></i> Review Center
      </a>
      <a href="<?= SITE_URL ?>/profil.php" class="user-nav-item">
        <i class="ti ti-user"></i> Mon profil
      </a>
    </nav>
    <div class="user-sidebar-footer">
      <a href="<?= SITE_URL ?>/logout.php" class="user-nav-item" style="color:#F0997B">
        <i class="ti ti-logout"></i> Déconnexion
      </a>
    </div>
  </aside>

  <!-- Contenu principal -->
  <main class="user-dash-main">
    <div class="dash-topbar">
      <div>
        <h1 class="dash-title">Review Center</h1>
        <p class="dash-sub"><?= $total ?> soumission<?= $total > 1 ? 's' : '' ?> en attente de correction</p>
      </div>
    </div>

    <div style="background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:14px;padding:0;overflow:hidden">
      <table style="width:100%;border-collapse:collapse">
        <thead>
          <tr style="border-bottom:1px solid var(--border,#e5e7eb)">
            <th style="text-align:left;padding:12px 16px;font-size:12px;color:var(--text-muted)">Étudiant</th>
            <th style="text-align:left;padding:12px 16px;font-size:12px;color:var(--text-muted)">Activité</th>
            <th style="text-align:left;padding:12px 16px;font-size:12px;color:var(--text-muted)">Cours</th>
            <th style="text-align:left;padding:12px 16px;font-size:12px;color:var(--text-muted)">Soumis le</th>
            <th style="text-align:left;padding:12px 16px;font-size:12px;color:var(--text-muted)">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($submissions)): ?>
          <tr>
            <td colspan="5" style="text-align:center;padding:48px;color:var(--text-muted)">
              <i class="ti ti-circle-check" style="font-size:36px;display:block;margin-bottom:10px;color:#16a34a"></i>
              Aucune soumission en attente. Tout est à jour !
            </td>
          </tr>
          <?php else: ?>
          <?php foreach ($submissions as $sub): ?>
          <tr style="border-bottom:1px solid var(--border,#e5e7eb)">
            <td style="padding:12px 16px">
              <div style="font-weight:500"><?= h($sub['prenom'].' '.$sub['nom']) ?></div>
              <div style="font-size:11px;color:var(--text-muted)"><?= h($sub['email']) ?></div>
            </td>
            <td style="padding:12px 16px">
              <div style="font-weight:500;font-size:13px"><?= h($sub['titre_devoir']) ?></div>
              <div style="font-size:11px;color:var(--text-muted)">
                <?= h($sub['sequence_titre']) ?><?= $sub['note_max'] ? ' · /'.$sub['note_max'] : '' ?>
              </div>
            </td>
            <td style="padding:12px 16px"><?= h($sub['course_titre']) ?></td>
            <td style="padding:12px 16px;color:var(--text-muted);font-size:12px"><?= date('d/m/Y H:i', strtotime($sub['created_at'])) ?></td>
            <td style="padding:12px 16px">
              <a href="<?= SITE_URL ?>/admin/submissions.php?activity_id=<?= $sub['activity_id'] ?>" class="btn-primary btn-sm">
                <i class="ti ti-pencil"></i> Corriger
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($pages > 1): ?>
    <div style="display:flex;gap:6px;justify-content:center;margin-top:20px">
      <?php for ($p = 1; $p <= $pages; $p++): ?>
        <a href="?page=<?= $p ?>" class="btn-outline <?= $p === $pageCourante ? 'active' : '' ?>"><?= $p ?></a>
      <?php endfor; ?>
    </div>
    <?php endif; ?>

  </main>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
