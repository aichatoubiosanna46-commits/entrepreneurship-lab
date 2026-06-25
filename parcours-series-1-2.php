<?php
// ============================================================
//  parcours-series-1-2.php — Parcours "Séries 1 + 2" (15 000 FCFA)
// ============================================================
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/parcours_helper.php';
reqConnecte();

$pdo    = getPDO();
$user   = utilisateurCourant();
$userId = (int)$_SESSION['user_id'];
$cours  = chargerParcours($pdo, 'serie_1_2', $userId);

$config = [
  'tarif'       => 'serie_1_2',
  'nom'         => 'Séries 1 + 2',
  'emoji'       => 'ti-chart-bar',
  'accroche'    => 'Construire et structurer son business',
  'desc'        => 'Coaching avancé, modules pratiques et accompagnement pour structurer durablement ton activité.',
  'prix'        => 15000,
  'badge'       => '<i class="ti ti-star"></i> Le plus choisi',
  'badge_cls'   => 'pro',
  'grad_hero'   => 'linear-gradient(135deg,#085041 0%,#0f7a63 100%)',
  'accent'      => '#0f7a63',
  'upgrade_tarif'=> 'complet',
  'upgrade_nom' => 'Parcours Complet',
  'upgrade_prix'=> '25 000 FCFA',
];
include __DIR__ . '/includes/parcours_template.php';
