<?php
// admin/promo_codes.php — Gestion des codes promo
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();
$pdo = getPDO();

// Suppression
if (($_GET['action'] ?? '') === 'delete' && isset($_GET['id'])) {
    $pdo->prepare('DELETE FROM promo_codes WHERE id = ?')->execute([(int)$_GET['id']]);
    redirect(SITE_URL . '/admin/promo_codes.php', 'Code supprimé.', 'success');
}

// Toggle actif
if (($_GET['action'] ?? '') === 'toggle' && isset($_GET['id'])) {
    $pdo->prepare('UPDATE promo_codes SET actif = NOT actif WHERE id = ?')->execute([(int)$_GET['id']]);
    redirect(SITE_URL . '/admin/promo_codes.php');
}

// Création
$erreur = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCSRF();
    $code      = strtoupper(trim($_POST['code'] ?? ''));
    $type      = $_POST['type'] ?? 'pourcentage';
    $valeur    = (float)($_POST['valeur'] ?? 0);
    $usage_max = $_POST['usage_max'] ? (int)$_POST['usage_max'] : null;
    $date_fin  = $_POST['date_fin'] ?: null;
    $course_id = $_POST['course_id'] ? (int)$_POST['course_id'] : null;

    if (!$code) $erreur = 'Le code est requis.';
    elseif ($valeur <= 0) $erreur = 'La valeur doit être > 0.';
    else {
        try {
            $pdo->prepare(
                'INSERT INTO promo_codes (code, type, valeur, usage_max, date_fin, course_id) VALUES (?,?,?,?,?,?)'
            )->execute([$code, $type, $valeur, $usage_max, $date_fin, $course_id]);
            redirect(SITE_URL . '/admin/promo_codes.php', 'Code promo créé !', 'success');
        } catch (Exception $e) {
            $erreur = 'Ce code existe déjà.';
        }
    }
}

$codes = $pdo->query(
    'SELECT pc.*, c.titre as cours_titre,
     (SELECT COUNT(*) FROM promo_usages pu WHERE pu.promo_id = pc.id) as nb_utilises
     FROM promo_codes pc LEFT JOIN courses c ON c.id = pc.course_id
     ORDER BY pc.created_at DESC'
)->fetchAll();

$courses = $pdo->query('SELECT id, titre FROM courses WHERE actif = 1 ORDER BY titre')->fetchAll();

$currentPage = 'promo_codes.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Codes promo — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <div><div class="admin-page-title">Codes promo</div><div class="admin-page-sub">Créer et gérer les codes de réduction</div></div>
  </div>
  <?= flash() ?>

  <div class="admin-two-col">
    <!-- Liste -->
    <div class="admin-card">
      <div class="admin-card-title"><i class="ti ti-ticket" style="color:var(--primary)"></i> Codes actifs</div>
      <table class="admin-table">
        <thead><tr><th>Code</th><th>Type</th><th>Valeur</th><th>Utilisations</th><th>Expiration</th><th>Cours</th><th>Statut</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($codes as $c): ?>
          <tr>
            <td><strong style="font-family:monospace;font-size:14px;color:var(--primary)"><?= h($c['code']) ?></strong></td>
            <td><?= $c['type'] === 'pourcentage' ? '%' : 'FCFA' ?></td>
            <td><?= $c['type']==='pourcentage' ? $c['valeur'].'%' : number_format($c['valeur'],0,',',' ').' FCFA' ?></td>
            <td><?= $c['nb_utilises'] ?> / <?= $c['usage_max'] ?? '∞' ?></td>
            <td><?= $c['date_fin'] ? date('d/m/Y', strtotime($c['date_fin'])) : '—' ?></td>
            <td><?= h($c['cours_titre'] ?? 'Tous') ?></td>
            <td>
              <a href="?action=toggle&id=<?= $c['id'] ?>">
                <span class="badge <?= $c['actif'] ? 'badge-success' : 'badge-neutral' ?>"><?= $c['actif'] ? 'Actif' : 'Inactif' ?></span>
              </a>
            </td>
            <td>
              <a href="?action=delete&id=<?= $c['id'] ?>" class="btn-icon btn-icon-danger" onclick="return confirm('Supprimer ce code ?')"><i class="ti ti-trash"></i></a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($codes)): ?>
          <tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:32px">Aucun code promo créé.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Formulaire création -->
    <div class="admin-card">
      <div class="admin-card-title"><i class="ti ti-plus" style="color:var(--primary)"></i> Nouveau code</div>
      <?php if ($erreur): ?><div class="alert alert-error"><i class="ti ti-alert-circle"></i> <?= h($erreur) ?></div><?php endif; ?>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <div class="form-group">
          <label>Code promo</label>
          <input type="text" name="code" placeholder="EX: ARIZIKI20" required style="text-transform:uppercase" value="<?= h($_POST['code'] ?? '') ?>">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Type de réduction</label>
            <select name="type">
              <option value="pourcentage">Pourcentage (%)</option>
              <option value="montant">Montant fixe (FCFA)</option>
            </select>
          </div>
          <div class="form-group">
            <label>Valeur</label>
            <input type="number" name="valeur" min="1" placeholder="20" required value="<?= h($_POST['valeur'] ?? '') ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Utilisations max (vide = illimité)</label>
            <input type="number" name="usage_max" min="1" placeholder="100" value="<?= h($_POST['usage_max'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Date d'expiration</label>
            <input type="date" name="date_fin" value="<?= h($_POST['date_fin'] ?? '') ?>">
          </div>
        </div>
        <div class="form-group">
          <label>Limiter à un cours (optionnel)</label>
          <select name="course_id">
            <option value="">Tous les cours</option>
            <?php foreach ($courses as $c): ?>
            <option value="<?= $c['id'] ?>"><?= h($c['titre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn-primary btn-full">
          <i class="ti ti-ticket"></i> Créer le code promo
        </button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
