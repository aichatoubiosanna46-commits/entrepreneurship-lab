<?php
// ============================================================
//  forum_new_topic.php — Créer un nouveau topic
// ============================================================
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

sendSecurityHeaders();
reqConnecte();

$pdo    = getPDO();
$erreur = '';

$coursesStmt = $pdo->query('SELECT id, titre FROM courses WHERE actif = 1 AND statut = "publie" ORDER BY titre');
$cours       = $coursesStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCSRF();

    $titre    = trim($_POST['titre'] ?? '');
    $contenu  = trim($_POST['contenu'] ?? '');
    $courseId = (int)($_POST['course_id'] ?? 0) ?: null;

    if (strlen($titre) < 5) {
        $erreur = 'Le titre doit comporter au moins 5 caractères.';
    } elseif (strlen($contenu) < 10) {
        $erreur = 'La description est trop courte.';
    } else {
        $pdo->prepare(
            'INSERT INTO forum_topics (course_id, user_id, titre, contenu) VALUES (?, ?, ?, ?)'
        )->execute([$courseId, $_SESSION['user_id'], $titre, $contenu]);

        $newId = $pdo->lastInsertId();
        logAction('forum_topic_created', 'Nouveau topic : ' . $titre, $_SESSION['user_id']);
        redirect(SITE_URL . '/forum_topic.php?id=' . $newId, 'Sujet créé avec succès.', 'success');
    }
}

$pageTitle = 'Nouveau sujet';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container" style="max-width:720px;margin:0 auto;padding:32px 16px">

  <a href="<?= SITE_URL ?>/forum.php" style="font-size:13px;color:var(--text-muted);text-decoration:none;margin-bottom:16px;display:inline-flex;align-items:center;gap:4px">
    <i class="ti ti-arrow-left"></i> Retour au forum
  </a>

  <div style="background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:32px;margin-top:12px">
    <h1 style="font-size:22px;font-weight:600;margin:0 0 24px">Créer un nouveau sujet</h1>

    <?php if ($erreur): ?>
      <div class="alert alert-error"><i class="ti ti-alert-circle"></i> <?= h($erreur) ?></div>
    <?php endif; ?>

    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

      <div class="form-group">
        <label for="titre">Titre du sujet <span style="color:#dc2626">*</span></label>
        <input type="text" id="titre" name="titre" value="<?= h($_POST['titre'] ?? '') ?>"
               placeholder="Décrivez votre question en quelques mots..." required maxlength="255"
               style="width:100%;padding:10px 14px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:14px;box-sizing:border-box">
      </div>

      <div class="form-group">
        <label for="course_id">Cours associé (optionnel)</label>
        <select id="course_id" name="course_id"
                style="width:100%;padding:10px 14px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:14px">
          <option value="">-- Aucun cours spécifique --</option>
          <?php foreach ($cours as $c): ?>
            <option value="<?= $c['id'] ?>" <?= ($_POST['course_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
              <?= h($c['titre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="contenu">Contenu / Description <span style="color:#dc2626">*</span></label>
        <textarea id="contenu" name="contenu" rows="8" required
                  placeholder="Décrivez votre question ou sujet en détail..."
                  style="width:100%;padding:12px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:14px;resize:vertical;font-family:inherit;box-sizing:border-box"><?= h($_POST['contenu'] ?? '') ?></textarea>
      </div>

      <div style="display:flex;gap:12px">
        <button type="submit" class="btn-primary">
          <i class="ti ti-send"></i> Publier le sujet
        </button>
        <a href="<?= SITE_URL ?>/forum.php" class="btn-outline">Annuler</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
