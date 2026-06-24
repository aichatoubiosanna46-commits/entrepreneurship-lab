<?php
// ============================================================
//  includes/auth.php — Authentification & gestion des rôles
//  Admin et Users sont totalement séparés :
//    - Session admin : admin_id, admin_nom, admin_email
//    - Session user  : user_id,  user_nom,  user_email
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
    ]);
}

require_once __DIR__ . '/../config/database.php';

// ============================================================
//  SECTION ADMIN
// ============================================================

function regenererSession(): void {
    session_regenerate_id(true);
}

/**
 * Connecte un admin en session (clés préfixées admin_)
 */
function connecterAdmin(array $admin): void {
    regenererSession();
    $_SESSION['admin_id']    = $admin['id'];
    $_SESSION['admin_nom']   = $admin['nom'] . ' ' . $admin['prenom'];
    $_SESSION['admin_email'] = $admin['email'];
}

/**
 * Vérifie si un admin est connecté
 */
function estAdmin(): bool {
    return isset($_SESSION['admin_id']);
}

/**
 * Redirige si non admin — renvoie vers la page de login admin
 */
function reqAdmin(): void {
    if (!estAdmin()) {
        header('Location: ' . SITE_URL . '/admin/login.php?error=acces_refuse');
        exit;
    }
}

/**
 * Déconnecte l'admin (ne touche pas aux clés user_*)
 */
function deconnecterAdmin(): void {
    unset(
        $_SESSION['admin_id'],
        $_SESSION['admin_nom'],
        $_SESSION['admin_email']
    );
    // Détruire complètement la session si plus rien dedans
    if (empty(array_filter(array_keys($_SESSION), fn($k) => !in_array($k, ['csrf_token'])))) {
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}

/**
 * Récupère l'admin courant depuis la table admins
 */
function adminCourant(): ?array {
    if (!estAdmin()) return null;
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM admins WHERE id = ? AND actif = 1');
    $stmt->execute([$_SESSION['admin_id']]);
    return $stmt->fetch() ?: null;
}

// ============================================================
//  SECTION UTILISATEURS (front public)
// ============================================================

/**
 * Connecte un utilisateur en session (clés préfixées user_)
 */
function connecterUtilisateur(array $user): void {
    // Générer un token de session unique (anti-partage de compte)
    $sessionToken = bin2hex(random_bytes(16));
    try {
        $pdo = getPDO();
        $pdo->prepare('UPDATE users SET session_token=?, last_seen=NOW() WHERE id=?')
            ->execute([$sessionToken, $user['id']]);
    } catch (Exception $e) {}
    $_SESSION['session_token'] = $sessionToken;
    regenererSession();
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_nom']   = $user['nom'] . ' ' . $user['prenom'];
    $_SESSION['user_email'] = $user['email'];

    // Fingerprint session (IP + user-agent)
    $ip        = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua        = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $fingerprint = hash('sha256', $ip . $ua . SECRET_KEY);
    $_SESSION['user_fingerprint'] = $fingerprint;

    // Token de session unique
    $token = bin2hex(random_bytes(32));
    $_SESSION['user_session_token'] = $token;

    try {
        $pdo = getPDO();
        // Supprimer les anciennes sessions si SESSION_SINGLE activé
        if (defined('SESSION_SINGLE') && SESSION_SINGLE) {
            $pdo->prepare('DELETE FROM user_sessions WHERE user_id = ?')->execute([$user['id']]);
        }
        // Enregistrer la nouvelle session
        $pdo->prepare(
            'INSERT INTO user_sessions (user_id, token, fingerprint, ip, user_agent)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([$user['id'], $token, $fingerprint, $ip, $ua]);
        // Mettre à jour last_login
        $pdo->prepare(
            'UPDATE users SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?'
        )->execute([$ip, $user['id']]);
    } catch (Exception $e) { /* silent */ }
}

/**
 * Vérifie si un utilisateur (front) est connecté
 */
function estConnecte(): bool {
    if (!isset($_SESSION['user_id'])) return false;
    // Vérifier que le token de session correspond (anti-partage)
    if (isset($_SESSION['session_token'])) {
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare('SELECT session_token FROM users WHERE id=? AND actif=1');
            $stmt->execute([$_SESSION['user_id']]);
            $row = $stmt->fetch();
            if ($row && $row['session_token'] && $row['session_token'] !== $_SESSION['session_token']) {
                session_destroy();
                return false;
            }
            // Mettre à jour last_seen toutes les 5 minutes
            if (!isset($_SESSION['last_seen_update']) || time() - $_SESSION['last_seen_update'] > 300) {
                $pdo->prepare('UPDATE users SET last_seen=NOW() WHERE id=?')->execute([$_SESSION['user_id']]);
                $_SESSION['last_seen_update'] = time();
            }
        } catch (Exception $e) {}
    }
    if (!isset($_SESSION['user_id'])) return false;

    // Vérifier fingerprint si présent
    if (isset($_SESSION['user_fingerprint'])) {
        $ip  = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua  = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $fp  = hash('sha256', $ip . $ua . SECRET_KEY);
        if (!hash_equals($_SESSION['user_fingerprint'], $fp)) {
            // Fingerprint invalide → déconnecter
            session_unset();
            session_destroy();
            return false;
        }
    }

    return true;
}

/**
 * Redirige si non connecté (front public)
 */
function reqConnecte(string $redirect = '/login.php'): void {
    if (!estConnecte()) {
        header('Location: ' . SITE_URL . $redirect);
        exit;
    }
}

/**
 * Déconnecte l'utilisateur (ne touche pas aux clés admin_*)
 */
function deconnecter(): void {
    unset(
        $_SESSION['user_id'],
        $_SESSION['user_nom'],
        $_SESSION['user_email']
    );
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/**
 * Récupère l'utilisateur courant depuis la table users
 */
function utilisateurCourant(): ?array {
    if (!estConnecte()) return null;
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND actif = 1');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}


/**
 * Vérifie si l'utilisateur est coach ou admin
 */
function estCoach(): bool {
    if (estAdmin()) return true;
    if (!estConnecte()) return false;
    try {
        $pdo  = getPDO();
        $stmt = $pdo->prepare('SELECT role FROM users WHERE id=? AND actif=1');
        $stmt->execute([$_SESSION['user_id']]);
        $row = $stmt->fetch();
        return in_array($row['role'] ?? '', ['coach', 'moderateur']);
    } catch(Exception $e) { return false; }
}

/**
 * Redirige si non coach
 */
function reqCoach(): void {
    if (!estCoach() && !estAdmin()) {
        header('Location: ' . SITE_URL . '/dashboard.php?error=acces_refuse');
        exit;
    }
}

// ============================================================
//  CSRF (partagé)
// ============================================================

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifierCSRF(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Action non autorisée (CSRF).');
    }
}
