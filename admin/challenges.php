<?php
// admin/challenges.php — Défis hebdomadaires
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();
$pdo = getPDO();

if (($_GET['action'] ?? '') === 'delete' && isset($_GET['id'])) {
    $pdo->prepare('DELETE FROM challenges WHERE id = ?')->execute([(int)$_GET['id']]);
    redirect(SITE_URL . '/admin/challenges.php', 'Défi supprimé.', 'success');
}
if (($_GET['action'] ?? '') === 'toggle' && isset($_GET['id'])) {
    $pdo->prepare('UPDATE challenges SET actif = NOT actif WHERE id = ?')->execute([(int)$_GET['id']]);
    redirect(SITE_URL . '/admin/challenges.php');
}

$erreur = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCSRF();
    $titre       = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $semaine     = $_POST['semaine'] ?? date('Y-m-d', strtotime('monday this week'));
    $xp_reward   = (int)($_POST['xp_reward'] ?? 50);

    if (!$titre || !$description) {
        $erreur = 'Titre et description sont requis.';
    } else {
        $pdo->prepare(
            'INSERT INTO challenges (titre, description, semaine, xp_reward) VALUES (?,?,?,?)'
        )->execute([$titre, $description, $semaine, $xp_reward]);
        redirect(SITE_URL . '/admin/challenges.php', 'Défi créé !', 'success');
    }
}

$challenges = $pdo->query('SELECT * FROM challenges ORDER BY semaine DESC')->fetchAll();
$currentPage = 'challenges.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Défis hebdomadaires — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <div><div class="admin-page-title">Défis hebdomadaires</div><div class="admin-page-sub">Exercices optionnels affichés sur le dashboard étudiant</div></div>
  </div>
  <?= flash() ?>
  <div class="admin-two-col">
    <div class="admin-card">
      <div class="admin-card-title"><i class="ti ti-trophy" style="color:var(--primary)"></i> Défis</div>
      <div style="display:flex;flex-direction:column;gap:12px">
        <?php foreach ($challenges as $ch): ?>
        <div style="background:var(--bg);border:1px solid var(--border);border-radius:12px;padding:16px;display:flex;gap:14px;align-items:flex-start">
          <div style="width:42px;height:42px;border-radius:10px;background:<?= $ch['actif'] ? 'var(--amber-light,#FEF3C7)' : '#f3f4f6' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="ti ti-trophy" style="font-size:20px;color:<?= $ch['actif'] ? '#D97706' : '#9ca3af' ?>"></i>
          </div>
          <div style="flex:1">
            <div style="font-weight:700;font-size:14px;margin-bottom:4px"><?= h($ch['titre']) ?></div>
            <div style="font-size:12px;color:var(--text-muted);margin-bottom:6px"><?= h(substr($ch['description'],0,100)) ?>...</div>
            <div style="display:flex;gap:12px;font-size:11px;color:var(--text-muted)">
              <span><i class="ti ti-calendar"></i> Semaine du <?= date('d/m/Y', strtotime($ch['semaine'])) ?></span>
              <span><i class="ti ti-star"></i> <?= $ch['xp_reward'] ?> XP</span>
              <span class="badge <?= $ch['actif'] ? 'badge-success' : 'badge-neutral' ?>"><?= $ch['actif'] ? 'Actif' : 'Inactif' ?></span>
            </div>
          </div>
          <div style="display:flex;gap:6px;flex-shrink:0">
            <a href="?action=toggle&id=<?= $ch['id'] ?>" class="btn-icon" title="Toggle"><i class="ti <?= $ch['actif'] ? 'ti-pause' : 'ti-player-play' ?>"></i></a>
            <a href="?action=delete&id=<?= $ch['id'] ?>" class="btn-icon btn-icon-danger" onclick="return confirm('Supprimer ?')"><i class="ti ti-trash"></i></a>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($challenges)): ?>
        <div style="text-align:center;padding:32px;color:var(--text-muted)">Aucun défi créé.</div>
        <?php endif; ?>
      </div>
    </div>

    <div class="admin-card">
      <div class="admin-card-title"><i class="ti ti-plus" style="color:var(--primary)"></i> Nouveau défi</div>
      <?php if ($erreur): ?><div class="alert alert-error"><i class="ti ti-alert-circle"></i> <?= h($erreur) ?></div><?php endif; ?>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <div class="form-group"><label>Titre du défi</label><input type="text" name="titre" placeholder="Ex: Mission terrain : trouve 3 problèmes à résoudre" required></div>
        <div class="form-group"><label>Description complète</label><textarea name="description" rows="4" placeholder="Décris la mission, les attendus, et comment soumettre la réponse..." required></textarea></div>
        <div class="form-row">
          <div class="form-group">
            <label>Semaine (date du lundi)</label>
            <input type="date" name="semaine" value="<?= date('Y-m-d', strtotime('monday this week')) ?>" required>
          </div>
          <div class="form-group">
            <label>XP récompense</label>
            <input type="number" name="xp_reward" value="50" min="10" max="500">
          </div>
        </div>
        <button type="submit" class="btn-primary btn-full"><i class="ti ti-trophy"></i> Créer le défi</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
