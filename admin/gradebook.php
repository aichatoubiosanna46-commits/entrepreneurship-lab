<?php
// ============================================================
//  admin/gradebook.php — Carnet de notes
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

sendSecurityHeaders();
reqAdmin();

$pdo      = getPDO();
$courseId = (int)($_GET['course_id'] ?? 0);
$studentId = (int)($_GET['student_id'] ?? 0);
$export   = isset($_GET['export']) && $courseId;

// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv' && $courseId) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="notes_cours_' . $courseId . '_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($out, ['Prénom', 'Nom', 'Email', 'Quiz', 'Score', 'Réussi', 'Date'], ';');
    try {
        $rows = getPDO()->prepare(
            'SELECT u.prenom, u.nom, u.email, qz.titre as quiz,
             qr.score, qr.reussi, qr.created_at
             FROM quiz_results qr
             JOIN users u ON u.id = qr.user_id
             JOIN quizzes qz ON qz.id = qr.quiz_id
             LEFT JOIN sequences s ON s.id = qz.sequence_id
             LEFT JOIN modules m ON m.id = s.module_id
             WHERE m.course_id = ?
             ORDER BY u.nom, qz.titre'
        );
        $rows->execute([$courseId]);
        foreach ($rows->fetchAll() as $row) {
            fputcsv($out, [
                $row['prenom'], $row['nom'], $row['email'],
                $row['quiz'], $row['score'].'%',
                $row['reussi'] ? 'Oui' : 'Non',
                date('d/m/Y H:i', strtotime($row['created_at']))
            ], ';');
        }
    } catch (Exception $e) {}
    fclose($out);
    exit;
}

$courses = $pdo->query('SELECT id, titre FROM courses WHERE actif = 1 ORDER BY titre')->fetchAll();

$students    = [];
$quizzes     = [];
$assignments = [];
$grades      = [];

if ($courseId) {
    // Étudiants inscrits
    $stStmt = $pdo->prepare(
        'SELECT u.id, u.nom, u.prenom, u.email FROM users u
         JOIN enrollments e ON e.user_id = u.id
         WHERE e.course_id = ? AND e.statut = "actif"
         ORDER BY u.nom, u.prenom'
    );
    $stStmt->execute([$courseId]);
    $students = $stStmt->fetchAll();

    // Quiz du cours
    $qStmt = $pdo->prepare(
        'SELECT q.id, q.titre FROM quizzes q
         JOIN sequences s ON s.id = q.sequence_id
         JOIN modules m ON m.id = s.module_id
         WHERE m.course_id = ? AND q.actif = 1 ORDER BY q.id'
    );
    $qStmt->execute([$courseId]);
    $quizzes = $qStmt->fetchAll();

    // Assignments du cours
    $aStmt = $pdo->prepare(
        'SELECT a.id, a.titre, a.note_max FROM assignments a
         JOIN sequences s ON s.id = a.sequence_id
         JOIN modules m ON m.id = s.module_id
         WHERE m.course_id = ? AND a.actif = 1 ORDER BY a.id'
    );
    $aStmt->execute([$courseId]);
    $assignments = $aStmt->fetchAll();

    // Charger toutes les notes
    foreach ($students as $st) {
        $uid = $st['id'];
        $grades[$uid] = ['quiz' => [], 'assignments' => []];

        foreach ($quizzes as $q) {
            $r = $pdo->prepare('SELECT score, total, reussi FROM quiz_results WHERE user_id = ? AND quiz_id = ? ORDER BY created_at DESC LIMIT 1');
            $r->execute([$uid, $q['id']]);
            $grades[$uid]['quiz'][$q['id']] = $r->fetch() ?: null;
        }

        foreach ($assignments as $a) {
            $r = $pdo->prepare('SELECT note, statut FROM assignment_submissions WHERE user_id = ? AND assignment_id = ? ORDER BY updated_at DESC LIMIT 1');
            $r->execute([$uid, $a['id']]);
            $grades[$uid]['assignments'][$a['id']] = $r->fetch() ?: null;
        }
    }

    // Export CSV
    if ($export) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="notes_cours_' . $courseId . '_' . date('Ymd') . '.csv"');
        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF"); // BOM UTF-8

        // En-têtes
        $headers = ['Nom', 'Prénom', 'Email'];
        foreach ($quizzes as $q) $headers[] = 'Quiz: ' . $q['titre'];
        foreach ($assignments as $a) $headers[] = 'Devoir: ' . $a['titre'] . ' /' . $a['note_max'];
        fputcsv($out, $headers, ';');

        foreach ($students as $st) {
            $uid  = $st['id'];
            $row  = [$st['nom'], $st['prenom'], $st['email']];
            foreach ($quizzes as $q) {
                $g = $grades[$uid]['quiz'][$q['id']];
                $row[] = $g ? round($g['score'] / max(1, $g['total']) * 100) . '%' : '-';
            }
            foreach ($assignments as $a) {
                $g = $grades[$uid]['assignments'][$a['id']];
                $row[] = $g && $g['note'] !== null ? $g['note'] : '-';
            }
            fputcsv($out, $row, ';');
        }
        fclose($out);
        exit;
    }
}

$pageTitle = 'Gradebook';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $pageTitle ?> — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
</head>
<body class="admin-layout">
<?php require_once __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <h1 class="admin-page-title"><i class="ti ti-table"></i> Gradebook — Carnet de notes</h1>
    <?php if ($courseId): ?>
      <a href="?course_id=<?= $courseId ?>&export=1" class="btn-outline" style="font-size:13px">
        <i class="ti ti-download"></i> Exporter CSV
      </a>
    <?php endif; ?>
  </div>
  <div class="admin-content">
    <?= flash() ?>

    <!-- Sélecteur de cours -->
    <form method="GET" style="margin-bottom:24px;display:flex;gap:10px">
      <select name="course_id" style="padding:8px 12px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:14px;min-width:280px">
        <option value="">-- Sélectionner un cours --</option>
        <?php foreach ($courses as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $courseId == $c['id'] ? 'selected' : '' ?>><?= h($c['titre']) ?></option>
        <?php endforeach; ?>
      </select>
      <?php
    try {
      $students_list = $pdo->query("SELECT DISTINCT u.id, u.nom, u.prenom FROM users u JOIN quiz_results qr ON qr.user_id=u.id ORDER BY u.nom")->fetchAll();
    } catch(Exception $e) { $students_list = []; }
    ?>
    <select name="student_id" style="padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;font-family:inherit">
      <option value="">— Tous les étudiants —</option>
      <?php foreach ($students_list as $st): ?>
      <option value="<?= $st['id'] ?>" <?= $st['id']==$studentId?'selected':'' ?>><?= h($st['nom'].' '.$st['prenom']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-primary">Afficher</button>
    </form>

    <?php if ($courseId && empty($students)): ?>
      <div style="text-align:center;padding:40px;color:var(--text-muted)">Aucun étudiant inscrit à ce cours.</div>
    <?php elseif ($courseId): ?>
      <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:13px;min-width:600px">
          <thead>
            <tr style="background:#f9fafb">
              <th style="padding:10px 14px;text-align:left;font-weight:600;border:1px solid var(--border,#e5e7eb)">Étudiant</th>
              <?php foreach ($quizzes as $q): ?>
                <th style="padding:10px 14px;text-align:center;font-weight:600;border:1px solid var(--border,#e5e7eb);background:#EEF2FF;min-width:120px" title="<?= h($q['titre']) ?>">
                  <i class="ti ti-help-circle" style="font-size:12px"></i> <?= h(mb_substr($q['titre'], 0, 20)) ?>...
                </th>
              <?php endforeach; ?>
              <?php foreach ($assignments as $a): ?>
                <th style="padding:10px 14px;text-align:center;font-weight:600;border:1px solid var(--border,#e5e7eb);background:#EAF3DE;min-width:120px" title="<?= h($a['titre']) ?>">
                  <i class="ti ti-clipboard-text" style="font-size:12px"></i> <?= h(mb_substr($a['titre'], 0, 20)) ?>...
                </th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($students as $st): ?>
              <?php $uid = $st['id']; ?>
              <tr>
                <td style="padding:10px 14px;border:1px solid var(--border,#e5e7eb);font-weight:500">
                  <?= h($st['prenom'] . ' ' . $st['nom']) ?>
                  <div style="font-size:11px;font-weight:400;color:var(--text-muted)"><?= h($st['email']) ?></div>
                </td>
                <?php foreach ($quizzes as $q): ?>
                  <?php $g = $grades[$uid]['quiz'][$q['id']]; ?>
                  <td style="padding:10px 14px;text-align:center;border:1px solid var(--border,#e5e7eb)">
                    <?php if ($g): ?>
                      <?php $pct = round($g['score'] / max(1, $g['total']) * 100); ?>
                      <span style="font-weight:600;color:<?= $pct >= 70 ? '#27500A' : '#993C1D' ?>">
                        <?= $pct ?>%
                      </span>
                      <div style="font-size:10px;color:var(--text-muted)"><?= $g['score'] ?>/<?= $g['total'] ?></div>
                    <?php else: ?>
                      <span style="color:#9ca3af">—</span>
                    <?php endif; ?>
                  </td>
                <?php endforeach; ?>
                <?php foreach ($assignments as $a): ?>
                  <?php $g = $grades[$uid]['assignments'][$a['id']]; ?>
                  <td style="padding:10px 14px;text-align:center;border:1px solid var(--border,#e5e7eb)">
                    <?php if ($g && $g['note'] !== null): ?>
                      <span style="font-weight:600;color:<?= $g['statut'] === 'accepte' ? '#27500A' : '#993C1D' ?>">
                        <?= $g['note'] ?>/<?= $a['note_max'] ?>
                      </span>
                    <?php elseif ($g): ?>
                      <span style="font-size:11px;color:#92400E;background:#FEF3C7;padding:2px 8px;border-radius:99px"><?= $g['statut'] ?></span>
                    <?php else: ?>
                      <span style="color:#9ca3af">—</span>
                    <?php endif; ?>
                  </td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div style="margin-top:16px;font-size:12px;color:var(--text-muted)">
        <span style="display:inline-block;width:12px;height:12px;background:var(--primary-light);border:1px solid var(--primary-mid);margin-right:4px"></span> Quiz
        <span style="display:inline-block;width:12px;height:12px;background:#EAF3DE;border:1px solid #97C459;margin:0 4px 0 12px"></span> Devoirs
      </div>
    <?php elseif (!$courseId): ?>
      <div style="text-align:center;padding:60px;color:var(--text-muted)">
        <i class="ti ti-table" style="font-size:48px;display:block;margin-bottom:12px;opacity:.4"></i>
        Sélectionnez un cours pour voir le carnet de notes.
      </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
