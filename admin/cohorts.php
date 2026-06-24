<?php
// ============================================================
//  admin/cohorts.php — Gestion des cohortes / groupes
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

    switch ($action) {
        case 'create':
            $nom  = trim($_POST['nom'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            if (strlen($nom) < 2) { $erreur = 'Nom requis.'; break; }
            $pdo->prepare('INSERT INTO cohorts (nom, description) VALUES (?, ?)')->execute([$nom, $desc]);
            redirect(SITE_URL . '/admin/cohorts.php', 'Cohorte créée.', 'success');
            break;

        case 'delete':
            $pdo->prepare('DELETE FROM cohorts WHERE id = ?')->execute([(int)$_POST['cohort_id']]);
            redirect(SITE_URL . '/admin/cohorts.php', 'Cohorte supprimée.', 'success');
            break;

        case 'add_member':
            $cohortId = (int)$_POST['cohort_id'];
            $userId   = (int)$_POST['user_id'];
            try {
                $pdo->prepare('INSERT IGNORE INTO cohort_members (cohort_id, user_id) VALUES (?, ?)')->execute([$cohortId, $userId]);
                redirect(SITE_URL . '/admin/cohorts.php?view=' . $cohortId, 'Membre ajouté.', 'success');
            } catch (Exception $e) {
                redirect(SITE_URL . '/admin/cohorts.php?view=' . $cohortId, 'Erreur.', 'error');
            }
            break;

        case 'remove_member':
            $cohortId = (int)$_POST['cohort_id'];
            $userId   = (int)$_POST['user_id'];
            $pdo->prepare('DELETE FROM cohort_members WHERE cohort_id = ? AND user_id = ?')->execute([$cohortId, $userId]);
            redirect(SITE_URL . '/admin/cohorts.php?view=' . $cohortId, 'Membre retiré.', 'success');
            break;
    }
}

$cohorts   = $pdo->query('SELECT c.*, (SELECT COUNT(*) FROM cohort_members cm WHERE cm.cohort_id = c.id) AS nb_membres FROM cohorts c ORDER BY c.nom')->fetchAll();
$users     = $pdo->query('SELECT id, nom, prenom, email FROM users WHERE actif = 1 ORDER BY nom')->fetchAll();

$viewId    = (int)($_GET['view'] ?? 0);
$members   = [];
$viewCohort = null;
if ($viewId) {
    $stmt = $pdo->prepare('SELECT * FROM cohorts WHERE id = ?');
    $stmt->execute([$viewId]);
    $viewCohort = $stmt->fetch();

    $mStmt = $pdo->prepare('SELECT u.id, u.nom, u.prenom, u.email FROM users u JOIN cohort_members cm ON cm.user_id = u.id WHERE cm.cohort_id = ? ORDER BY u.nom');
    $mStmt->execute([$viewId]);
    $members = $mStmt->fetchAll();
}

$pageTitle = 'Cohortes';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $pageTitle ?> — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
</head>
<body class="admin-layout">
<?php require_once __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <h1 class="admin-page-title"><i class="ti ti-users-group"></i> Cohortes / Groupes</h1>
  </div>
  <div class="admin-content">
    <?= flash() ?>
    <?php if ($erreur): ?><div class="alert alert-error"><?= h($erreur) ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:280px 1fr;gap:24px;align-items:start">

      <!-- Créer + liste cohortes -->
      <div>
        <div style="background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:20px;margin-bottom:16px">
          <h3 style="font-size:15px;font-weight:600;margin:0 0 14px">Nouvelle cohorte</h3>
          <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
              <input type="text" name="nom" placeholder="Nom de la cohorte" required maxlength="120"
                     style="width:100%;padding:8px 12px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:13px;box-sizing:border-box">
            </div>
            <div class="form-group">
              <textarea name="description" rows="2" placeholder="Description (optionnel)"
                        style="width:100%;padding:8px 12px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:13px;font-family:inherit;resize:vertical;box-sizing:border-box"></textarea>
            </div>
            <button type="submit" class="btn-primary" style="width:100%;font-size:13px"><i class="ti ti-plus"></i> Créer</button>
          </form>
        </div>

        <div style="background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:20px">
          <h3 style="font-size:15px;font-weight:600;margin:0 0 12px">Cohortes (<?= count($cohorts) ?>)</h3>
          <?php if (empty($cohorts)): ?>
            <p style="color:var(--text-muted);font-size:13px">Aucune cohorte.</p>
          <?php else: ?>
            <?php foreach ($cohorts as $c): ?>
              <div style="border-bottom:1px solid var(--border,#e5e7eb);padding:10px 0;display:flex;justify-content:space-between;align-items:center">
                <a href="?view=<?= $c['id'] ?>" style="text-decoration:none;color:<?= $viewId == $c['id'] ? 'var(--primary)' : 'inherit' ?>">
                  <div style="font-size:13px;font-weight:<?= $viewId == $c['id'] ? '600' : '400' ?>"><?= h($c['nom']) ?></div>
                  <div style="font-size:11px;color:var(--text-muted)"><?= $c['nb_membres'] ?> membre(s)</div>
                </a>
                <form method="POST" onsubmit="return confirm('Supprimer cette cohorte ?')">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="cohort_id" value="<?= $c['id'] ?>">
                  <button type="submit" class="btn-icon btn-icon-danger"><i class="ti ti-trash"></i></button>
                </form>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Détail cohorte -->
      <?php if ($viewCohort): ?>
        <div>
          <div style="background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:24px;margin-bottom:20px">
            <h2 style="font-size:18px;font-weight:600;margin:0 0 4px"><?= h($viewCohort['nom']) ?></h2>
            <?php if ($viewCohort['description']): ?>
              <p style="color:var(--text-muted);font-size:13px;margin:4px 0 16px"><?= h($viewCohort['description']) ?></p>
            <?php endif; ?>

            <h3 style="font-size:14px;font-weight:600;margin:16px 0 10px">Ajouter un membre</h3>
            <form method="POST" style="display:flex;gap:10px">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="action" value="add_member">
              <input type="hidden" name="cohort_id" value="<?= $viewCohort['id'] ?>">
              <select name="user_id" required style="flex:1;padding:8px 12px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:13px">
                <option value="">Sélectionner un étudiant</option>
                <?php foreach ($users as $u): ?>
                  <?php if (!in_array($u['id'], array_column($members, 'id'))): ?>
                    <option value="<?= $u['id'] ?>"><?= h($u['prenom'] . ' ' . $u['nom']) ?></option>
                  <?php endif; ?>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="btn-primary" style="font-size:13px"><i class="ti ti-user-plus"></i> Ajouter</button>
            </form>
          </div>

          <div style="background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:12px;overflow:hidden">
            <div style="padding:16px 20px;border-bottom:1px solid var(--border,#e5e7eb);font-weight:600;font-size:14px">
              Membres (<?= count($members) ?>)
            </div>
            <?php if (empty($members)): ?>
              <div style="padding:24px;text-align:center;color:var(--text-muted);font-size:13px">Aucun membre dans cette cohorte.</div>
            <?php else: ?>
              <?php foreach ($members as $u): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 20px;border-bottom:1px solid var(--border,#e5e7eb)">
                  <div>
                    <div style="font-size:13px;font-weight:500"><?= h($u['prenom'] . ' ' . $u['nom']) ?></div>
                    <div style="font-size:11px;color:var(--text-muted)"><?= h($u['email']) ?></div>
                  </div>
                  <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action" value="remove_member">
                    <input type="hidden" name="cohort_id" value="<?= $viewCohort['id'] ?>">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <button type="submit" class="btn-outline-danger btn-sm">
                      <i class="ti ti-user-minus"></i> Retirer
                    </button>
                  </form>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      <?php else: ?>
        <div style="text-align:center;padding:60px;color:var(--text-muted)">
          <i class="ti ti-users-group" style="font-size:48px;display:block;margin-bottom:12px;opacity:.4"></i>
          Sélectionnez une cohorte pour voir ses membres.
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
