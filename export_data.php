<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
reqConnecte();

$pdo    = getPDO();
$userId = $_SESSION['user_id'];

header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="mes-donnees-ariziki-' . date('Y-m-d') . '.json"');

$user = $pdo->prepare('SELECT id, nom, prenom, email, telephone, created_at, last_login_at FROM users WHERE id=?');
$user->execute([$userId]);
$user = $user->fetch();

$enrollments = $pdo->prepare('SELECT c.titre, e.statut, e.created_at FROM enrollments e JOIN courses c ON c.id=e.course_id WHERE e.user_id=?');
$enrollments->execute([$userId]);

$progress = $pdo->prepare('SELECT s.titre as sequence, m.titre as module, c.titre as cours, p.terminee, p.created_at FROM progress p JOIN sequences s ON s.id=p.sequence_id JOIN modules m ON m.id=s.module_id JOIN courses c ON c.id=m.course_id WHERE p.user_id=?');
$progress->execute([$userId]);

$quizResults = $pdo->prepare('SELECT qz.titre, qr.score, qr.reussi, qr.created_at FROM quiz_results qr JOIN quizzes qz ON qz.id=qr.quiz_id WHERE qr.user_id=?');
$quizResults->execute([$userId]);

$certificates = $pdo->prepare('SELECT c.titre, cert.code_unique, cert.created_at FROM certificates cert JOIN courses c ON c.id=cert.course_id WHERE cert.user_id=?');
$certificates->execute([$userId]);

echo json_encode([
    'export_date'  => date('Y-m-d H:i:s'),
    'profil'       => $user,
    'inscriptions' => $enrollments->fetchAll(),
    'progression'  => $progress->fetchAll(),
    'quiz'         => $quizResults->fetchAll(),
    'certificats'  => $certificates->fetchAll(),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit;
