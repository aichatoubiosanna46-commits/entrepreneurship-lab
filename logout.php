<?php
// logout.php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
deconnecter();
redirect(SITE_URL . '/login.php', 'Vous êtes déconnecté.', 'info');
