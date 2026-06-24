<?php
// ============================================================
//  admin/export_users.php — Export CSV des utilisateurs
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

sendSecurityHeaders();
reqAdmin();

$pdo = getPDO();

$stmt = $pdo->query(
    'SELECT id, nom, prenom, email, role, ville,
            COALESCE(universite, "") AS universite,
            COALESCE(filiere, "") AS filiere,
            COALESCE(promotion, "") AS promotion,
            created_at
     FROM users
     WHERE actif = 1
     ORDER BY nom, prenom'
);
$users = $stmt->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="utilisateurs_' . date('Ymd_His') . '.csv"');

$out = fopen('php://output', 'w');
fputs($out, "\xEF\xBB\xBF"); // BOM UTF-8 pour Excel

fputcsv($out, ['ID', 'Nom', 'Prénom', 'Email', 'Rôle', 'Ville', 'Université', 'Filière', 'Promotion', 'Créé le'], ';');

foreach ($users as $u) {
    fputcsv($out, [
        $u['id'],
        $u['nom'],
        $u['prenom'],
        $u['email'],
        $u['role'],
        $u['ville'] ?? '',
        $u['universite'],
        $u['filiere'],
        $u['promotion'],
        date('d/m/Y H:i', strtotime($u['created_at'])),
    ], ';');
}

fclose($out);
exit;
