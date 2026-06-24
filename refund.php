<?php
// ============================================================
//  refund.php — Demande de remboursement (squelette FedaPay)
//  L'étudiant demande un remboursement sur un paiement validé.
//  Le traitement effectif (appel API FedaPay) est laissé en
//  squelette : à ce stade on enregistre la demande et notifie
//  l'admin ; le remboursement réel doit être validé manuellement
//  ou via un futur job qui appelle l'API FedaPay refunds.
// ============================================================
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

sendSecurityHeaders();
reqConnecte();

$pdo    = getPDO();
$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCSRF();
    $paymentId = (int)($_POST['payment_id'] ?? 0);
    $motif     = trim($_POST['motif'] ?? '');

    $stmt = $pdo->prepare('SELECT * FROM payments WHERE id = ? AND user_id = ?');
    $stmt->execute([$paymentId, $userId]);
    $payment = $stmt->fetch();

    if (!$payment) {
        redirect(SITE_URL . '/payment_history.php', 'Paiement introuvable.', 'error');
    }
    if ($payment['statut'] !== 'valide') {
        redirect(SITE_URL . '/payment_history.php', 'Seul un paiement validé peut faire l\'objet d\'un remboursement.', 'error');
    }

    try {
        $current = $payment['refund_status'] ?? 'aucun';
        if ($current === 'demande' || $current === 'rembourse') {
            redirect(SITE_URL . '/payment_history.php', 'Une demande de remboursement est déjà en cours ou traitée pour ce paiement.', 'error');
        }
        $pdo->prepare('UPDATE payments SET refund_status = "demande", refund_demande_le = NOW() WHERE id = ?')
            ->execute([$paymentId]);

        logAction('refund_requested', 'Demande de remboursement pour paiement #' . $paymentId . ' (' . $payment['montant'] . ' FCFA). Motif : ' . $motif, $userId);

        // Notifier les admins (squelette — pas d'appel API FedaPay refunds ici,
        // un opérateur doit valider manuellement la demande dans l'admin avant
        // tout remboursement réel via FedaPay).
        try {
            $admins = $pdo->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll();
            foreach ($admins as $a) {
                notifierUtilisateur(
                    (int)$a['id'],
                    'Nouvelle demande de remboursement',
                    'Une demande de remboursement a été soumise pour le paiement #' . $paymentId . '.',
                    'info',
                    SITE_URL . '/admin/payments.php'
                );
            }
        } catch (Exception $e) {}

        redirect(SITE_URL . '/payment_history.php', 'Votre demande de remboursement a été enregistrée. Notre équipe la traitera sous peu.', 'success');
    } catch (Exception $e) {
        redirect(SITE_URL . '/payment_history.php', 'La fonctionnalité de remboursement n\'est pas encore disponible (migration en attente).', 'error');
    }
}

redirect(SITE_URL . '/payment_history.php');
