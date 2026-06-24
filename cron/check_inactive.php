<?php
// ============================================================
//  cron/check_inactive.php
//  Déclenche les automations inactive_7days / inactive_30days.
//  À appeler périodiquement par un service externe (cron-job.org)
//  car InfinityFree ne propose pas de vrai cron :
//    https://votre-site/cron/check_inactive.php?token=CRON_SECRET
// ============================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!hash_equals(CRON_SECRET, $_GET['token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'Token invalide.']);
    exit;
}

try {
    runInactivityCheck();
    echo json_encode(['success' => true, 'checked_at' => date('c')]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
