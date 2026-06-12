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
    regenererSession();
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_nom']   = $user['nom'] . ' ' . $user['prenom'];
    $_SESSION['user_email'] = $user['email'];
}

/**
 * Vérifie si un utilisateur (front) est connecté
 */
function estConnecte(): bool {
    return isset($_SESSION['user_id']);
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
