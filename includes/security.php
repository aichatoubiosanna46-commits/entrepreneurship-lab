<?php
// ============================================================
//  includes/security.php — Headers de sécurité, rate limiting,
//  audit log
// ============================================================

/**
 * Envoie tous les headers HTTP de sécurité recommandés.
 * À appeler très tôt dans le cycle de vie de la page (avant tout output).
 */
function sendSecurityHeaders(): void {
    if (headers_sent()) return;

    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=()');
    header('X-XSS-Protection: 1; mode=block');
    header(
        "Content-Security-Policy: " .
        "default-src 'self'; " .
        "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; " .
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; " .
        "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net data:; " .
        "img-src 'self' data: blob: https:; " .
        "connect-src 'self'; " .
        "frame-src 'self' https://www.youtube.com https://player.vimeo.com;"
    );
}

/**
 * Vérifie si une clé (ex: 'login_user@email.com') a dépassé
 * le nombre maximum de tentatives dans la fenêtre de temps.
 *
 * @param string $key           Identifiant (ex: 'login_email@test.com')
 * @param int    $maxAttempts   Nombre max de tentatives autorisées
 * @param int    $windowSeconds Durée de la fenêtre en secondes
 * @return bool  true = bloqué, false = autorisé
 */
function checkRateLimit(string $key, int $maxAttempts, int $windowSeconds): bool {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE cle = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)'
        );
        $stmt->execute([$key, $windowSeconds]);
        $count = (int) $stmt->fetchColumn();
        return $count >= $maxAttempts;
    } catch (Exception $e) {
        return false; // En cas d'erreur DB, ne pas bloquer
    }
}

/**
 * Enregistre une tentative de connexion échouée.
 *
 * @param string $key  Identifiant de la tentative
 */
function recordFailedAttempt(string $key): void {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $pdo = getPDO();
        $pdo->prepare(
            'INSERT INTO login_attempts (cle, ip) VALUES (?, ?)'
        )->execute([$key, $ip]);
    } catch (Exception $e) { /* silent */ }
}

/**
 * Efface toutes les tentatives pour une clé (après succès).
 *
 * @param string $key  Identifiant de la tentative
 */
function clearAttempts(string $key): void {
    try {
        $pdo = getPDO();
        $pdo->prepare('DELETE FROM login_attempts WHERE cle = ?')->execute([$key]);
    } catch (Exception $e) { /* silent */ }
}

/**
 * Enregistre une action dans le journal d'audit.
 *
 * @param string   $action   Code d'action (ex: 'login_success', 'delete_user')
 * @param string   $details  Détails lisibles
 * @param int|null $userId   ID utilisateur concerné (null si admin seul)
 */
function logAction(string $action, string $details, ?int $userId = null): void {
    try {
        $pdo     = getPDO();
        $ip      = $_SERVER['REMOTE_ADDR'] ?? null;
        $adminId = $_SESSION['admin_id'] ?? null;
        $uid     = $userId ?? ($_SESSION['user_id'] ?? null);

        $pdo->prepare(
            'INSERT INTO audit_logs (user_id, admin_id, action, details, ip)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([$uid, $adminId, $action, $details, $ip]);
    } catch (Exception $e) { /* silent */ }
}
