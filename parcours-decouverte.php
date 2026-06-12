<?php
// ============================================================
//  parcours-decouverte.php — Parcours "Découverte" (Gratuit)
// ============================================================
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/parcours_helper.php';
reqConnecte();

$pdo    = getPDO();
$user   = utilisateurCourant();
$userId = (int)$_SESSION['user_id'];
$cours  = chargerParcours($pdo, 'decouverte', $userId);

$config = [
  'tarif'       => 'decouverte',
  'nom'         => 'Découverte',
  'emoji'       => '💡',
  'accroche'    => 'Trouver son idée de business',
  'desc'        => 'Valide ton projet en 4h avec notre méthode simple et adaptée au contexte béninois.',
  'prix'        => 0,
  'badge'       => 'Gratuit',
  'badge_cls'   => 'free',
  'grad_hero'   => 'linear-gradient(135deg,#1a1710 0%,#2d2108 100%)',
  'accent'      => '#22c55e',
  'upgrade_tarif'=> 'business-plan',
  'upgrade_nom' => 'Business Plan',
  'upgrade_prix'=> '5 000 FCFA',
];
include __DIR__ . '/includes/parcours_template.php';
