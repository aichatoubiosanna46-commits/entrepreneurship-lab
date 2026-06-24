<?php
// admin/coaching.php — Gestion des sessions de coaching 1:1
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();
$pdo = getPDO();

// Actions
if (($_GET['action'] ?? '') === 'delete' && isset($_GET['id'])) {
    $pdo->prepare('DELETE FROM coaching_sessions WHERE id = ?')->execute([(int)$_GET['id']]);
    redirect(SITE_URL . '/admin/coaching.php', 'Session supprimée.', 'success');
}
if (($_GET['action'] ?? '') === 'confirm' && isset($_GET['id'])) {
    $pdo->prepare("UPDATE coaching_sessions SET statut='confirme' WHERE id=?")->execute([(int)$_GET['id']]);
    // Notifier l'étudiant
    $sess = $pdo->query("SELECT cs.*, u.prenom, u.nom, u.telephone FROM coaching_sessions cs JOIN users u ON u.id=cs.user_id WHERE cs.id=".(int)$_GET['id'])->fetch();
    if ($sess) {
        $pdo->prepare("INSERT INTO user_notifications (user_id,titre,message,type) VALUES (?,?,?,'success')")
            ->execute([$sess['user_id'], 'Session coaching confirmée', 'Votre session "'.$sess['titre'].'" est confirmée pour le '.date('d/m/Y à H:i', strtotime($sess['date_heure']))]);
    }
    // WhatsApp si téléphone dispo
    if (!empty($sess['telephone'])) {
        notifierWhatsAppCoaching($sess['telephone'], $sess['prenom'], date('d/m/Y à H:i', strtotime($sess['date_heure'])));
    }
    redirect(SITE_URL . '/admin/coaching.php', 'Session confirmée et étudiant notifié.', 'success');
}

// Création
$erreur = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCSRF();
    $userId     = (int)($_POST['user_id'] ?? 0);
    $titre      = trim($_POST['titre'] ?? '');
    $dateHeure  = $_POST['date_heure'] ?? '';
    $duree      = (int)($_POST['duree'] ?? 30);
    $lienZoom   = trim($_POST['lien_zoom'] ?? '');
    $lienCal    = trim($_POST['lien_calendly'] ?? '');
    $adminId    = $_SESSION['admin_id'] ?? 1;

    if (!$userId || !$titre || !$dateHeure) {
        $erreur = 'Étudiant, titre et date sont requis.';
    } else {
        $pdo->prepare(
            'INSERT INTO coaching_sessions (formateur_id,user_id,titre,date_heure,duree_minutes,lien_zoom,lien_calendly) VALUES (?,?,?,?,?,?,?)'
        )->execute([$adminId, $userId, $titre, $dateHeure, $duree, $lienZoom, $lienCal]);
        // Notifier l'étudiant
        $pdo->prepare("INSERT INTO user_notifications (user_id,titre,message,type) VALUES (?,?,?,'info')")
            ->execute([$userId, 'Session coaching planifiée', 'Une session coaching "'.$titre.'" a été planifiée pour vous le '.date('d/m/Y à H:i', strtotime($dateHeure))]);
        redirect(SITE_URL . '/admin/coaching.php', 'Session créée et étudiant notifié !', 'success');
    }
}

$sessions = $pdo->query(
    'SELECT cs.*, u.prenom, u.nom, u.email, u.telephone
     FROM coaching_sessions cs JOIN users u ON u.id = cs.user_id
     ORDER BY cs.date_heure DESC LIMIT 50'
)->fetchAll();

$users = $pdo->query("SELECT id, prenom, nom, email FROM users WHERE actif=1 ORDER BY nom ASC")->fetchAll();

// Récupérer Calendly depuis settings
try { $calendlyUrl = $pdo->query("SELECT valeur FROM settings WHERE cle='calendly_url'")->fetchColumn(); } catch(Exception $e){$calendlyUrl='';}

$currentPage = 'coaching.php';
$statusColors = ['planifie'=>'badge-amber','confirme'=>'badge-success','annule'=>'badge-danger','termine'=>'badge-neutral'];
$statusLabels = ['planifie'=>'Planifié','confirme'=>'Confirmé','annule'=>'Annulé','termine'=>'Terminé'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sessions coaching — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <div><div class="admin-page-title">Sessions coaching 1:1</div><div class="admin-page-sub">Planifier et suivre les séances avec les étudiants</div></div>
    <?php if ($calendlyUrl): ?>
    <a href="<?= h($calendlyUrl) ?>" target="_blank" class="btn-outline">
      <i class="ti ti-calendar"></i> Ouvrir Calendly
    </a>
    <?php endif; ?>
  </div>
  <?= flash() ?>

  <div class="admin-two-col">
    <!-- Liste sessions -->
    <div class="admin-card">
      <div class="admin-card-title"><i class="ti ti-video" style="color:var(--primary)"></i> Sessions planifiées</div>
      <div style="display:flex;flex-direction:column;gap:12px">
        <?php foreach ($sessions as $s): ?>
        <div style="background:var(--bg);border:1px solid var(--border);border-radius:12px;padding:16px;display:flex;align-items:flex-start;gap:14px">
          <div style="width:44px;height:44px;border-radius:10px;background:var(--primary-light);display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="ti ti-video" style="color:var(--primary);font-size:20px"></i>
          </div>
          <div style="flex:1;min-width:0">
            <div style="font-weight:700;font-size:14px;margin-bottom:2px"><?= h($s['titre']) ?></div>
            <div style="font-size:12px;color:var(--text-muted);margin-bottom:6px">
              👤 <?= h($s['prenom'].' '.$s['nom']) ?> · <?= h($s['email']) ?>
            </div>
            <div style="display:flex;gap:12px;flex-wrap:wrap;font-size:12px;color:var(--text-muted)">
              <span><i class="ti ti-calendar"></i> <?= date('d/m/Y à H:i', strtotime($s['date_heure'])) ?></span>
              <span><i class="ti ti-clock"></i> <?= $s['duree_minutes'] ?> min</span>
              <span class="badge <?= $statusColors[$s['statut']] ?? 'badge-neutral' ?>"><?= $statusLabels[$s['statut']] ?></span>
            </div>
            <?php if ($s['lien_zoom']): ?>
            <a href="<?= h($s['lien_zoom']) ?>" target="_blank" style="display:inline-flex;align-items:center;gap:4px;margin-top:8px;font-size:12px;color:#2D8CFF;text-decoration:none;font-weight:600">
              <i class="ti ti-video"></i> Lien Zoom
            </a>
            <?php endif; ?>
          </div>
          <div style="display:flex;gap:6px;flex-shrink:0;flex-direction:column">
            <?php if ($s['statut'] === 'planifie'): ?>
            <a href="?action=confirm&id=<?= $s['id'] ?>" class="btn-icon" title="Confirmer"><i class="ti ti-check"></i></a>
            <?php endif; ?>
            <a href="?action=delete&id=<?= $s['id'] ?>" class="btn-icon btn-icon-danger" onclick="return confirm('Supprimer ?')"><i class="ti ti-trash"></i></a>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($sessions)): ?>
        <div style="text-align:center;padding:32px;color:var(--text-muted)">Aucune session planifiée.</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Formulaire création -->
    <div class="admin-card">
      <div class="admin-card-title"><i class="ti ti-plus" style="color:var(--primary)"></i> Nouvelle session</div>
      <?php if ($erreur): ?><div class="alert alert-error"><i class="ti ti-alert-circle"></i> <?= h($erreur) ?></div><?php endif; ?>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <div class="form-group">
          <label>Étudiant</label>
          <select name="user_id" required>
            <option value="">-- Choisir un étudiant --</option>
            <?php foreach ($users as $u): ?>
            <option value="<?= $u['id'] ?>"><?= h($u['prenom'].' '.$u['nom']) ?> — <?= h($u['email']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Titre de la session</label>
          <input type="text" name="titre" placeholder="Ex: Coaching Business Plan — Session 1" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Date et heure</label>
            <input type="datetime-local" name="date_heure" required>
          </div>
          <div class="form-group">
            <label>Durée (minutes)</label>
            <select name="duree">
              <option value="30">30 min</option>
              <option value="45">45 min</option>
              <option value="60" selected>60 min</option>
              <option value="90">90 min</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label>Lien Zoom <small style="font-weight:400;color:var(--text-muted)">(optionnel)</small></label>
          <input type="url" name="lien_zoom" placeholder="https://zoom.us/j/...">
        </div>
        <div class="form-group">
          <label>Lien Calendly <small style="font-weight:400;color:var(--text-muted)">(optionnel)</small></label>
          <input type="url" name="lien_calendly" value="<?= h($calendlyUrl) ?>" placeholder="https://calendly.com/...">
        </div>
        <button type="submit" class="btn-primary btn-full">
          <i class="ti ti-video-plus"></i> Créer la session
        </button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
