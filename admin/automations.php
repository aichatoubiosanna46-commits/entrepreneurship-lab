<?php
// admin/automations.php — Automations QUAND/SI/ALORS
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();
$pdo = getPDO();

if (($_GET['action'] ?? '') === 'delete' && isset($_GET['id'])) {
    $pdo->prepare('DELETE FROM automations WHERE id = ?')->execute([(int)$_GET['id']]);
    redirect(SITE_URL . '/admin/automations.php', 'Automation supprimée.', 'success');
}
if (($_GET['action'] ?? '') === 'toggle' && isset($_GET['id'])) {
    $pdo->prepare('UPDATE automations SET actif = NOT actif WHERE id = ?')->execute([(int)$_GET['id']]);
    redirect(SITE_URL . '/admin/automations.php');
}

$erreur = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCSRF();
    $nom          = trim($_POST['nom'] ?? '');
    $declencheur  = $_POST['declencheur'] ?? '';
    $condition_id = $_POST['condition_id'] ? (int)$_POST['condition_id'] : null;
    $action       = $_POST['action_type'] ?? '';
    $action_value = trim($_POST['action_value'] ?? '');

    if (!$nom || !$declencheur || !$action) {
        $erreur = 'Nom, déclencheur et action sont requis.';
    } else {
        $pdo->prepare(
            'INSERT INTO automations (nom, declencheur, condition_id, action, action_value) VALUES (?,?,?,?,?)'
        )->execute([$nom, $declencheur, $condition_id, $action, $action_value]);
        redirect(SITE_URL . '/admin/automations.php', 'Automation créée !', 'success');
    }
}

$automations = $pdo->query(
    'SELECT a.*, c.titre as cours_titre FROM automations a
     LEFT JOIN courses c ON c.id = a.condition_id
     ORDER BY a.created_at DESC'
)->fetchAll();

$courses = $pdo->query('SELECT id, titre FROM courses WHERE actif = 1 ORDER BY titre')->fetchAll();

$declencheurs = [
    'course_completed'    => 'Complétion d\'un cours',
    'quiz_passed'         => 'Quiz réussi',
    'quiz_submitted'      => 'Quiz soumis (réussi ou non)',
    'sequence_completed'  => 'Séquence complétée',
    'assignment_submitted'=> 'Livrable soumis',
    'user_registered'     => 'Nouvelle inscription',
    'course_enrolled'     => 'Inscription à un cours',
    'payment_success'  => 'Paiement confirmé',
    'inactive_7days'   => 'Inactif depuis 7 jours',
    'inactive_30days'  => 'Inactif depuis 30 jours',
];
$actions = [
    'enroll_course'  => 'Inscrire à un cours',
    'add_tag'        => 'Ajouter un tag',
    'send_email'     => 'Envoyer un email',
    'award_badge'    => 'Attribuer un badge',
    'add_xp'         => 'Ajouter des XP',
    'remove_access'  => 'Retirer l\'accès à un cours',
    'notify'        => 'Notification in-app',
];

$currentPage = 'automations.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Automations — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <div><div class="admin-page-title">Automations</div><div class="admin-page-sub">Logique QUAND / ALORS — automatiser les actions récurrentes</div></div>
  </div>
  <?= flash() ?>

  <div class="admin-two-col">
    <!-- Liste -->
    <div class="admin-card">
      <div class="admin-card-title"><i class="ti ti-robot" style="color:var(--primary)"></i> Automations actives</div>
      <div style="display:flex;flex-direction:column;gap:10px">
        <?php foreach ($automations as $a): ?>
        <div style="background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:16px;display:flex;align-items:flex-start;gap:14px">
          <div style="width:36px;height:36px;border-radius:9px;background:<?= $a['actif'] ? 'var(--primary-light)' : '#f3f4f6' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="ti ti-robot" style="color:<?= $a['actif'] ? 'var(--primary)' : '#9ca3af' ?>"></i>
          </div>
          <div style="flex:1">
            <div style="font-weight:700;font-size:14px;color:var(--text);margin-bottom:4px"><?= h($a['nom']) ?></div>
            <div style="font-size:12px;color:var(--text-muted)">
              QUAND : <strong><?= $declencheurs[$a['declencheur']] ?? $a['declencheur'] ?></strong>
              <?php if ($a['cours_titre']): ?> → <em><?= h($a['cours_titre']) ?></em><?php endif; ?>
              &nbsp;·&nbsp; ALORS : <strong><?= $actions[$a['action']] ?? $a['action'] ?></strong>
              <?php if ($a['action_value']): ?> = <em><?= h($a['action_value']) ?></em><?php endif; ?>
            </div>
          </div>
          <div style="display:flex;gap:6px;flex-shrink:0">
            <a href="?action=toggle&id=<?= $a['id'] ?>" class="btn-icon" title="Activer/Désactiver">
              <i class="ti <?= $a['actif'] ? 'ti-pause' : 'ti-player-play' ?>"></i>
            </a>
            <a href="?action=delete&id=<?= $a['id'] ?>" class="btn-icon btn-icon-danger" onclick="return confirm('Supprimer cette automation ?')">
              <i class="ti ti-trash"></i>
            </a>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($automations)): ?>
        <div style="text-align:center;padding:32px;color:var(--text-muted)">Aucune automation créée.</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Formulaire -->
    <div class="admin-card">
      <div class="admin-card-title"><i class="ti ti-plus" style="color:var(--primary)"></i> Nouvelle automation</div>
      <?php if ($erreur): ?><div class="alert alert-error"><i class="ti ti-alert-circle"></i> <?= h($erreur) ?></div><?php endif; ?>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <div class="form-group">
          <label>Nom de l'automation</label>
          <input type="text" name="nom" placeholder="Ex: Inscription auto module 2 après module 1" required>
        </div>
        <div class="form-group">
          <label>QUAND (déclencheur)</label>
          <select name="declencheur" required>
            <option value="">-- Choisir --</option>
            <?php foreach ($declencheurs as $k => $v): ?>
            <option value="<?= $k ?>"><?= $v ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Cours déclencheur (si applicable)</label>
          <select name="condition_id">
            <option value="">— N'importe lequel —</option>
            <?php foreach ($courses as $c): ?>
            <option value="<?= $c['id'] ?>"><?= h($c['titre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>ALORS (action)</label>
          <select name="action_type" required>
            <option value="">-- Choisir --</option>
            <?php foreach ($actions as $k => $v): ?>
            <option value="<?= $k ?>"><?= $v ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Valeur de l'action (cours ID, tag, message...)</label>
          <input type="text" name="action_value" placeholder="Ex: 3 (pour enroll_course) ou 'phase2_enrolled' (pour add_tag)">
          <small style="color:var(--text-muted);font-size:11px">Pour 'Inscrire à un cours' : entrer l'ID du cours. Pour 'Ajouter un tag' : entrer le nom du tag.</small>
        </div>
        <button type="submit" class="btn-primary btn-full">
          <i class="ti ti-robot"></i> Créer l'automation
        </button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
