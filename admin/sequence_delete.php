<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();

$id       = (int)($_GET['id'] ?? 0);
$moduleId = (int)($_GET['module_id'] ?? 0);
$token    = $_GET['csrf'] ?? '';

if (!$id || !$moduleId || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    redirect(SITE_URL . '/admin/sequences.php?module_id=' . $moduleId, 'Action non autorisée.', 'error');
}

$pdo  = getPDO();
$stmt = $pdo->prepare('DELETE FROM sequences WHERE id = ?');
$stmt->execute([$id]);

redirect(SITE_URL . '/admin/sequences.php?module_id=' . $moduleId, 'Séquence supprimée.', 'success');
