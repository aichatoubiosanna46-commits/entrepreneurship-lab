<?php
// ============================================================
//  admin/tags.php — Gestion des tags étudiants
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

sendSecurityHeaders();
reqAdmin();

$pdo    = getPDO();
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCSRF();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $nom     = trim($_POST['nom'] ?? '');
        $couleur = $_POST['couleur'] ?? '#6C47D4';
        if (strlen($nom) < 1) {
            $erreur = 'Le nom du tag est requis.';
        } else {
            try {
                $pdo->prepare('INSERT INTO tags (nom, couleur) VALUES (?, ?)')->execute([$nom, $couleur]);
                redirect(SITE_URL . '/admin/tags.php', 'Tag créé.', 'success');
            } catch (Exception $e) {
                $erreur = 'Ce tag existe déjà.';
            }
        }
    } elseif ($action === 'delete') {
        $tagId = (int)$_POST['tag_id'];
        $pdo->prepare('DELETE FROM tags WHERE id = ?')->execute([$tagId]);
        redirect(SITE_URL . '/admin/tags.php', 'Tag supprimé.', 'success');
    } elseif ($action === 'assign') {
        $userId = (int)$_POST['user_id'];
        $tagId  = (int)$_POST['tag_id'];
        try {
            $pdo->prepare('INSERT IGNORE INTO user_tags (user_id, tag_id) VALUES (?, ?)')->execute([$userId, $tagId]);
            redirect(SITE_URL . '/admin/tags.php', 'Tag attribué.', 'success');
        } catch (Exception $e) {
            $erreur = 'Erreur lors de l\'attribution.';
        }
    } elseif ($action === 'unassign') {
        $userId = (int)$_POST['user_id'];
        $tagId  = (int)$_POST['tag_id'];
        $pdo->prepare('DELETE FROM user_tags WHERE user_id = ? AND tag_id = ?')->execute([$userId, $tagId]);
        redirect(SITE_URL . '/admin/tags.php', 'Tag retiré.', 'success');
    }
}

$tags  = $pdo->query('SELECT t.*, (SELECT COUNT(*) FROM user_tags ut WHERE ut.tag_id = t.id) AS nb_users FROM tags t ORDER BY t.nom')->fetchAll();
$users = $pdo->query('SELECT id, nom, prenom, email FROM users WHERE actif = 1 ORDER BY nom')->fetchAll();

// Tags par user pour affichage
$selectedTag = (int)($_GET['tag_id'] ?? 0);
$taggedUsers = [];
if ($selectedTag) {
    $stmt = $pdo->prepare('SELECT u.id, u.nom, u.prenom, u.email FROM users u JOIN user_tags ut ON ut.user_id = u.id WHERE ut.tag_id = ?');
    $stmt->execute([$selectedTag]);
    $taggedUsers = $stmt->fetchAll();
}

$pageTitle = 'Tags étudiants';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $pageTitle ?> — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
</head>
<body class="admin-layout">
<?php require_once __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <h1 class="admin-page-title"><i class="ti ti-tag"></i> Tags étudiants</h1>
  </div>
  <div class="admin-content">
    <?= flash() ?>
    <?php if ($erreur): ?><div class="alert alert-error"><i class="ti ti-alert-circle"></i> <?= h($erreur) ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:300px 1fr;gap:24px;align-items:start">

      <!-- Créer tag -->
      <div>
        <div style="background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:20px;margin-bottom:20px">
          <h3 style="font-size:15px;font-weight:600;margin:0 0 16px">Nouveau tag</h3>
          <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
              <label>Nom</label>
              <input type="text" name="nom" required maxlength="80"
                     style="width:100%;padding:8px 12px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:13px;box-sizing:border-box">
            </div>
            <div class="form-group" style="display:flex;align-items:center;gap:8px">
              <label style="margin:0">Couleur</label>
              <input type="color" name="couleur" value="#6C47D4" style="width:40px;height:36px;border:none;border-radius:4px;cursor:pointer">
            </div>
            <button type="submit" class="btn-primary" style="font-size:13px;width:100%">
              <i class="ti ti-plus"></i> Créer
            </button>
          </form>
        </div>

        <!-- Liste des tags -->
        <div style="background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:20px">
          <h3 style="font-size:15px;font-weight:600;margin:0 0 12px">Tags existants</h3>
          <?php if (empty($tags)): ?>
            <p style="color:var(--text-muted);font-size:13px">Aucun tag créé.</p>
          <?php else: ?>
            <?php foreach ($tags as $t): ?>
              <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border,#e5e7eb)">
                <a href="?tag_id=<?= $t['id'] ?>" style="display:flex;align-items:center;gap:8px;text-decoration:none;color:inherit">
                  <span style="width:12px;height:12px;border-radius:50%;background:<?= h($t['couleur']) ?>;flex-shrink:0"></span>
                  <span style="font-size:13px;font-weight:500"><?= h($t['nom']) ?></span>
                  <span style="font-size:11px;color:var(--text-muted)"><?= $t['nb_users'] ?></span>
                </a>
                <form method="POST" onsubmit="return confirm('Supprimer ce tag ?')">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="tag_id" value="<?= $t['id'] ?>">
                  <button type="submit" style="background:none;border:none;color:#dc2626;cursor:pointer;font-size:14px"><i class="ti ti-x"></i></button>
                </form>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Attribution & liste -->
      <div>
        <!-- Attribuer tag -->
        <div style="background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:20px;margin-bottom:20px">
          <h3 style="font-size:15px;font-weight:600;margin:0 0 16px">Attribuer un tag à un étudiant</h3>
          <form method="POST" style="display:flex;gap:10px;flex-wrap:wrap">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="assign">
            <select name="user_id" required style="padding:8px 12px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:13px;min-width:200px">
              <option value="">Étudiant</option>
              <?php foreach ($users as $u): ?>
                <option value="<?= $u['id'] ?>"><?= h($u['prenom'] . ' ' . $u['nom']) ?></option>
              <?php endforeach; ?>
            </select>
            <select name="tag_id" required style="padding:8px 12px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:13px;min-width:150px">
              <option value="">Tag</option>
              <?php foreach ($tags as $t): ?>
                <option value="<?= $t['id'] ?>"><?= h($t['nom']) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-primary" style="font-size:13px">Attribuer</button>
          </form>
        </div>

        <!-- Étudiants avec le tag sélectionné -->
        <?php if ($selectedTag): ?>
          <?php $selTag = array_values(array_filter($tags, fn($t) => $t['id'] == $selectedTag))[0] ?? null; ?>
          <?php if ($selTag): ?>
            <div style="background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:20px">
              <h3 style="font-size:15px;font-weight:600;margin:0 0 12px">
                Étudiants avec le tag
                <span style="background:<?= h($selTag['couleur']) ?>;color:#fff;padding:2px 10px;border-radius:99px;font-size:12px"><?= h($selTag['nom']) ?></span>
              </h3>
              <?php if (empty($taggedUsers)): ?>
                <p style="color:var(--text-muted);font-size:13px">Aucun étudiant avec ce tag.</p>
              <?php else: ?>
                <?php foreach ($taggedUsers as $u): ?>
                  <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border,#e5e7eb)">
                    <div>
                      <div style="font-size:13px;font-weight:500"><?= h($u['prenom'] . ' ' . $u['nom']) ?></div>
                      <div style="font-size:11px;color:var(--text-muted)"><?= h($u['email']) ?></div>
                    </div>
                    <form method="POST">
                      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                      <input type="hidden" name="action" value="unassign">
                      <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                      <input type="hidden" name="tag_id" value="<?= $selectedTag ?>">
                      <button type="submit" style="background:#FEE2E2;border:none;color:#991B1B;padding:4px 10px;border-radius:6px;cursor:pointer;font-size:12px">
                        <i class="ti ti-x"></i> Retirer
                      </button>
                    </form>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
</body>
</html>
