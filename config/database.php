<?php
// ============================================================
//  config/database.php — Connexion PDO
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'entrepreneurship_lab');
define('DB_USER', 'root');         // à changer en production
define('DB_PASS', '');             // à changer en production
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME', 'EntreprendreBJ');
define('SITE_URL',  'http://localhost/entrepreneurship-lab');
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');

// Clé secrète pour CSRF et tokens (générer une vraie clé en prod)
define('SECRET_KEY', 'changez-cette-cle-en-production-32chars');

// Clé API Claude (IA)
define('ANTHROPIC_API_KEY', 'sk-ant-votre-cle-ici');

function getPDO(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST
             . ";dbname=" . DB_NAME
             . ";charset=" . DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // En prod : logger l'erreur, ne pas l'afficher
            die('<p style="color:red;font-family:sans-serif">Erreur de connexion à la base de données.</p>');
        }
    }
    return $pdo;
}
