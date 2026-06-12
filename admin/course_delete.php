<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();
verifierCSRF('GET');

$pdo = getPDO();
$id  = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('DELETE FROM courses WHERE id = ?');
$stmt->execute([$id]);

redirect(SITE_URL.'/admin/courses.php', 'Cours supprimé.', 'success');
