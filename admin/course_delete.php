<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();

$pdo  = getPDO();
$id   = (int)($_GET['id'] ?? 0);
$token = $_GET['csrf'] ?? '';

if (!$id || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    redirect(SITE_URL . '/admin/courses.php', 'Action non autorisée.', 'error');
}

$stmt = $pdo->prepare('SELECT titre FROM courses WHERE id = ?');
$stmt->execute([$id]);
$course = $stmt->fetch();
if (!$course) {
    redirect(SITE_URL . '/admin/courses.php', 'Cours introuvable.', 'error');
}

$pdo->prepare('DELETE FROM courses WHERE id = ?')->execute([$id]);

redirect(SITE_URL . '/admin/courses.php', 'Cours "' . $course['titre'] . '" supprimé.', 'success');
