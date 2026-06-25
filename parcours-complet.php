<?php
// ============================================================
//  parcours-complet.php — Parcours "Parcours Complet" (25 000 FCFA)
// ============================================================
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/parcours_helper.php';
reqConnecte();

$pdo    = getPDO();
$user   = utilisateurCourant();
$userId = (int)$_SESSION['user_id'];
$cours  = chargerParcours($pdo, 'parcours_complet', $userId);

$config = [
  'tarif'       => 'parcours_complet',
  'nom'         => 'Parcours Complet',
  'emoji'       => 'ti-rocket',
  'accroche'    => 'Structurer et scaler son entreprise',
  'desc'        => 'L\'expérience complète : toutes les séries, coaching intensif, accompagnement terrain et certification universitaire.',
  'prix'        => 25000,
  'badge'       => '<i class="ti ti-trophy"></i> Complet',
  'badge_cls'   => 'pro',
  'grad_hero'   => 'linear-gradient(135deg,#0d3b2e 0%,#1a6b52 100%)',
  'accent'      => '#10b981',
  'upgrade_tarif'=> null,
  'upgrade_nom' => null,
  'upgrade_prix'=> null,
];
include __DIR__ . '/includes/parcours_template.php';
