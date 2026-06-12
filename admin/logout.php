<?php
// ============================================================
//  admin/logout.php — Déconnexion admin uniquement
//  Ne touche pas à la session utilisateur front
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

deconnecterAdmin();
redirect(SITE_URL . '/admin/login.php', 'Vous êtes déconnecté.', 'info');
