<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();
verifierCSRF('GET');

$pdo   = getPDO();
$id    = (int)($_GET['id'] ?? 0);
$seqId = (int)($_GET['sequence_id'] ?? 0);

$pdo->prepare('DELETE FROM activities WHERE id = ?')->execute([$id]);

redirect(SITE_URL.'/admin/activities.php?sequence_id='.$seqId, 'Activité supprimée.', 'success');
