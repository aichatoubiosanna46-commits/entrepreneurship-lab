<?php
// ============================================================
//  leaderboard.php — Classement des étudiants par XP
// ============================================================
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

sendSecurityHeaders();

$pdo = getPDO();

$stmt = $pdo->query(
    'SELECT u.id, u.nom, u.prenom, u.avatar, u.xp_total,
            (SELECT COUNT(*) FROM user_badges ub WHERE ub.user_id = u.id) AS nb_badges,
            (SELECT COUNT(*) FROM enrollments e WHERE e.user_id = u.id AND e.statut = "actif") AS nb_cours
     FROM users u
     WHERE u.actif = 1 AND u.role = "apprenant"
     ORDER BY u.xp_total DESC
     LIMIT 20'
);
$leaders = $stmt->fetchAll();

$monRang = 0;
if (estConnecte()) {
    $monRang = getUserRank($_SESSION['user_id']);
}

$pageTitle = 'Classement des apprenants';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container" style="max-width:800px;margin:0 auto;padding:32px 16px">

  <div style="text-align:center;margin-bottom:32px">
    <h1 style="font-size:28px;font-weight:700;margin-bottom:8px">
      <i class="ti ti-trophy" style="color:#D85A30"></i> Classement
    </h1>
    <p style="color:var(--text-muted)">Top 20 des apprenants les plus actifs</p>
    <?php if (estConnecte() && $monRang): ?>
      <div style="margin-top:12px;display:inline-flex;align-items:center;gap:8px;background:#EDE9FE;color:#6C47D4;padding:8px 20px;border-radius:99px;font-weight:600">
        <i class="ti ti-user"></i> Votre classement : #<?= $monRang ?>
      </div>
    <?php endif; ?>
  </div>

  <div style="background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:16px;overflow:hidden">
    <?php if (empty($leaders)): ?>
      <div style="padding:48px;text-align:center;color:var(--text-muted)">
        Aucun apprenant classé pour le moment.
      </div>
    <?php else: ?>
      <?php foreach ($leaders as $i => $u): ?>
        <?php
          $rang = $i + 1;
          $isMe = estConnecte() && $_SESSION['user_id'] == $u['id'];
          $medalColors = [1 => '#D85A30', 2 => '#6B7280', 3 => '#A83E1C'];
          $medalColor  = $medalColors[$rang] ?? null;
        ?>
        <div style="display:flex;align-items:center;gap:16px;padding:16px 24px;border-bottom:<?= $i < count($leaders)-1 ? '1px solid var(--border,#e5e7eb)' : 'none' ?>;background:<?= $isMe ? '#F5F3FF' : '' ?>">

          <!-- Rang -->
          <div style="width:36px;text-align:center;font-weight:700;font-size:<?= $rang <= 3 ? '20px' : '16px' ?>;color:<?= $medalColor ?? 'var(--text-muted)' ?>;flex-shrink:0">
            <?php if ($rang <= 3): ?>
              <i class="ti ti-medal"></i>
            <?php else: ?>
              <?= $rang ?>
            <?php endif; ?>
          </div>

          <!-- Avatar -->
          <div style="width:44px;height:44px;border-radius:50%;background:var(--primary-light,#EDE9FE);display:flex;align-items:center;justify-content:center;font-weight:600;font-size:18px;color:var(--primary,#6C47D4);flex-shrink:0">
            <?= mb_strtoupper(mb_substr($u['nom'], 0, 1)) ?>
          </div>

          <!-- Nom & stats -->
          <div style="flex:1;min-width:0">
            <div style="font-weight:600;font-size:15px">
              <?= h($u['prenom'] . ' ' . $u['nom']) ?>
              <?php if ($isMe): ?>
                <span style="background:#6C47D4;color:#fff;font-size:10px;padding:2px 8px;border-radius:99px;margin-left:6px">Vous</span>
              <?php endif; ?>
            </div>
            <div style="font-size:12px;color:var(--text-muted)">
              <?= $u['nb_cours'] ?> cours · <?= $u['nb_badges'] ?> badge(s)
            </div>
          </div>

          <!-- XP -->
          <div style="font-weight:700;font-size:16px;color:var(--primary,#6C47D4);text-align:right">
            <?= number_format($u['xp_total']) ?>
            <div style="font-size:11px;font-weight:400;color:var(--text-muted)">XP</div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
