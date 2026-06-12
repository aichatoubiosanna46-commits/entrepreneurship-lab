<?php
// admin/user_delete.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();

$id     = (int)($_GET['id']     ?? 0);
$action = $_GET['action'] ?? 'delete';
$csrf   = $_GET['csrf'] ?? '';

if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf) || !$id) {
    redirect(SITE_URL . '/admin/users.php', 'Action non autorisée.', 'error');
}

$pdo = getPDO();
match ($action) {
    'block'   => $pdo->prepare('UPDATE users SET actif = 0 WHERE id = ?')->execute([$id]),
    'unblock' => $pdo->prepare('UPDATE users SET actif = 1 WHERE id = ?')->execute([$id]),
    'delete'  => $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]),
    default   => null,
};

$messages = ['block' => 'Utilisateur bloqué.', 'unblock' => 'Utilisateur débloqué.', 'delete' => 'Utilisateur supprimé.'];
redirect(SITE_URL . '/admin/users.php', $messages[$action] ?? 'Action effectuée.', 'success');
