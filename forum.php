<?php
// ============================================================
//  forum.php — Liste des topics du forum communautaire
// ============================================================
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

sendSecurityHeaders();

$pdo = getPDO();

// Filtre optionnel par cours
$courseFilter = (int)($_GET['course_id'] ?? 0);

// Comptage total
$whereClause = $courseFilter ? 'WHERE ft.course_id = ?' : 'WHERE 1=1';
$params      = $courseFilter ? [$courseFilter] : [];

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM forum_topics ft $whereClause");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

[$offset, $pages, $pageCourante] = paginer($total, 20);

$topicsStmt = $pdo->prepare(
    "SELECT ft.*, u.nom, u.prenom,
            (SELECT COUNT(*) FROM forum_replies fr WHERE fr.topic_id = ft.id) AS nb_reponses,
            c.titre AS cours_titre
     FROM forum_topics ft
     JOIN users u ON u.id = ft.user_id
     LEFT JOIN courses c ON c.id = ft.course_id
     $whereClause
     ORDER BY ft.epingle DESC, ft.updated_at DESC
     LIMIT 20 OFFSET $offset"
);
$topicsStmt->execute($params);
$topics = $topicsStmt->fetchAll();

// Liste des cours pour filtre
$coursesStmt = $pdo->query('SELECT id, titre FROM courses WHERE actif = 1 AND statut = "publie" ORDER BY titre');
$cours       = $coursesStmt->fetchAll();

$pageTitle = 'Forum communautaire';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container" style="max-width:1100px;margin:0 auto;padding:32px 16px">

  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px">
    <div>
      <h1 style="font-size:26px;font-weight:600;margin:0">Forum communautaire</h1>
      <p style="color:var(--text-muted);margin:4px 0 0">Posez vos questions, partagez vos expériences</p>
    </div>
    <?php if (estConnecte()): ?>
      <a href="<?= SITE_URL ?>/forum_new_topic.php" class="btn-primary">
        <i class="ti ti-plus"></i> Nouveau sujet
      </a>
    <?php else: ?>
      <a href="<?= SITE_URL ?>/login.php" class="btn-outline">Se connecter pour participer</a>
    <?php endif; ?>
  </div>

  <!-- Filtre cours -->
  <form method="GET" style="margin-bottom:20px;display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    <select name="course_id" onchange="this.form.submit()" style="padding:8px 12px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:14px">
      <option value="">Tous les cours</option>
      <?php foreach ($cours as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $courseFilter == $c['id'] ? 'selected' : '' ?>>
          <?= h($c['titre']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php if ($courseFilter): ?>
      <a href="forum.php" style="font-size:13px;color:var(--text-muted)">Réinitialiser</a>
    <?php endif; ?>
  </form>

  <!-- Liste des topics -->
  <div style="background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:12px;overflow:hidden">
    <?php if (empty($topics)): ?>
      <div style="padding:48px;text-align:center;color:var(--text-muted)">
        <i class="ti ti-message-circle" style="font-size:48px;display:block;margin-bottom:12px;opacity:.4"></i>
        Aucun sujet pour le moment. Soyez le premier à en créer un !
      </div>
    <?php else: ?>
      <?php foreach ($topics as $i => $t): ?>
        <a href="<?= SITE_URL ?>/forum_topic.php?id=<?= $t['id'] ?>"
           style="display:flex;gap:16px;padding:16px 20px;text-decoration:none;color:inherit;border-bottom:<?= $i < count($topics)-1 ? '1px solid var(--border,#e5e7eb)' : 'none' ?>;transition:.15s;align-items:flex-start"
           onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background=''">

          <div style="flex-shrink:0;width:40px;height:40px;border-radius:50%;background:var(--primary-light,#EDE9FE);display:flex;align-items:center;justify-content:center;font-weight:600;color:var(--primary,#6C47D4)">
            <?= mb_strtoupper(mb_substr($t['nom'], 0, 1)) ?>
          </div>

          <div style="flex:1;min-width:0">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px">
              <?php if ($t['epingle']): ?>
                <span style="background:#FEF3C7;color:#92400E;font-size:11px;font-weight:600;padding:2px 8px;border-radius:99px">
                  <i class="ti ti-pin"></i> Épinglé
                </span>
              <?php endif; ?>
              <?php if ($t['ferme']): ?>
                <span style="background:#FEE2E2;color:#991B1B;font-size:11px;font-weight:600;padding:2px 8px;border-radius:99px">
                  <i class="ti ti-lock"></i> Fermé
                </span>
              <?php endif; ?>
              <span style="font-weight:500;font-size:15px"><?= h($t['titre']) ?></span>
            </div>
            <div style="font-size:12px;color:var(--text-muted)">
              Par <strong><?= h($t['prenom'] . ' ' . $t['nom']) ?></strong>
              <?php if ($t['cours_titre']): ?>
                · dans <em><?= h($t['cours_titre']) ?></em>
              <?php endif; ?>
              · <?= date('d/m/Y', strtotime($t['created_at'])) ?>
            </div>
          </div>

          <div style="display:flex;gap:16px;flex-shrink:0;font-size:12px;color:var(--text-muted);align-items:center">
            <span title="Réponses"><i class="ti ti-message"></i> <?= $t['nb_reponses'] ?></span>
            <span title="Vues"><i class="ti ti-eye"></i> <?= $t['nb_vues'] ?></span>
          </div>
        </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Pagination -->
  <?php if ($pages > 1): ?>
    <div style="display:flex;gap:6px;justify-content:center;margin-top:24px">
      <?php for ($p = 1; $p <= $pages; $p++): ?>
        <a href="?page=<?= $p ?><?= $courseFilter ? '&course_id='.$courseFilter : '' ?>"
           style="padding:6px 12px;border-radius:6px;font-size:13px;text-decoration:none;<?= $p === $pageCourante ? 'background:var(--primary,#6C47D4);color:#fff' : 'background:#f3f4f6;color:var(--text)' ?>">
          <?= $p ?>
        </a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
