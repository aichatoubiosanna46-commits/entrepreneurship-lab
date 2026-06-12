<?php
// ============================================================
//  setup_admin.php — Crée le premier compte admin
//  IMPORTANT : À SUPPRIMER après la première utilisation !
//  Accès : http://localhost/entrepreneurship-lab/setup_admin.php
//  Insère dans la table `admins` — aucun lien avec `users`
// ============================================================
require_once __DIR__ . '/config/database.php';

$email  = 'admin@entrepreneurship-lab.bj';
$mdp    = 'Admin@2025';  // ← Changez ici avant d'exécuter
$nom    = 'Admin';
$prenom = 'Lab';
$hash   = password_hash($mdp, PASSWORD_BCRYPT, ['cost' => 12]);

$pdo  = getPDO();
$stmt = $pdo->prepare('SELECT id FROM admins WHERE email = ?');
$stmt->execute([$email]);
$existing = $stmt->fetch();

if ($existing) {
    $pdo->prepare('UPDATE admins SET password = ? WHERE email = ?')
        ->execute([$hash, $email]);
    echo "<p style='font-family:sans-serif;color:green'>✓ Mot de passe admin mis à jour pour <strong>$email</strong></p>";
} else {
    $pdo->prepare(
        "INSERT INTO admins (nom, prenom, email, password) VALUES (?,?,?,?)"
    )->execute([$nom, $prenom, $email, $hash]);
    echo "<p style='font-family:sans-serif;color:green'>✓ Compte admin créé : <strong>$email</strong> / <strong>$mdp</strong></p>";
}

echo "<p style='font-family:sans-serif;color:red;margin-top:12px'>⚠️ Supprimez ce fichier maintenant : <code>setup_admin.php</code></p>";
echo "<p><a href='admin/login.php' style='color:#534AB7'>→ Aller à la page de connexion admin</a></p>";
