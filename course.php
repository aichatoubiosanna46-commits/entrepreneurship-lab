<?php
// course.php — Redirige vers module.php (ancienne URL maintenue pour compatibilité)
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$slug = trim($_GET['slug'] ?? '');
$id   = (int)($_GET['id'] ?? 0);

if ($slug) {
    header('Location: ' . SITE_URL . '/module.php?slug=' . urlencode($slug), true, 301);
    exit;
}

if ($id) {
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT slug FROM courses WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row  = $stmt->fetch();
    if ($row) {
        header('Location: ' . SITE_URL . '/module.php?slug=' . urlencode($row['slug']), true, 301);
        exit;
    }
}

header('Location: ' . SITE_URL . '/search.php', true, 302);
exit;
