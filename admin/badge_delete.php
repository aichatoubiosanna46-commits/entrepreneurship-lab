<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();
verifierCSRF('GET');

$id = (int)($_GET['id'] ?? 0);
getPDO()->prepare('DELETE FROM badges WHERE id = ?')->execute([$id]);

redirect(SITE_URL.'/admin/badges.php', 'Badge supprimé.', 'success');
