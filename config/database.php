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

// FedaPay
define('FEDAPAY_PUBLIC_KEY',     'pk_sandbox_votre-cle-publique');   // pk_live_xxx en production
define('FEDAPAY_SECRET_KEY',     'sk_sandbox_votre-cle-secrete');    // sk_live_xxx en production
define('FEDAPAY_WEBHOOK_SECRET', 'votre-secret-webhook-fedapay');    // depuis le dashboard FedaPay
define('FEDAPAY_ENV',            'sandbox');                         // 'sandbox' ou 'live'
// URL API FedaPay (change automatiquement selon l'environnement)
define('FEDAPAY_API_URL', FEDAPAY_ENV === 'live'
    ? 'https://api.fedapay.com/v1'
    : 'https://sandbox-api.fedapay.com/v1'
);

// Email (override avec DB config)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('SMTP_FROM', 'noreply@ariziki.org');
define('SMTP_FROM_NAME', 'Ariziki EntrepreneurshipLab');

// Sécurité
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);
define('SESSION_SINGLE', true); // une seule session par compte

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
