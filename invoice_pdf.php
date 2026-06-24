<?php
// ============================================================
//  invoice_pdf.php — Téléchargement de la facture PDF (étudiant)
// ============================================================
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/minipdf.php';

reqConnecte();
sendSecurityHeaders();

$pdo    = getPDO();
$userId = $_SESSION['user_id'];
$id     = (int)($_GET['id'] ?? 0);

if (!$id) {
    redirect(SITE_URL . '/dashboard.php', 'Facture introuvable.', 'error');
}

try {
    $stmt = $pdo->prepare(
        "SELECT i.*, u.nom, u.prenom, u.email, c.titre AS cours_titre, b.titre AS bundle_titre
         FROM invoices i
         JOIN users u ON u.id = i.user_id
         LEFT JOIN courses c ON c.id = i.course_id
         LEFT JOIN bundles b ON b.id = i.bundle_id
         WHERE i.id = ? AND i.user_id = ?
         LIMIT 1"
    );
    $stmt->execute([$id, $userId]);
    $inv = $stmt->fetch();
} catch (Exception $e) {
    $stmt = $pdo->prepare(
        "SELECT i.*, u.nom, u.prenom, u.email, c.titre AS cours_titre
         FROM invoices i
         JOIN users u ON u.id = i.user_id
         LEFT JOIN courses c ON c.id = i.course_id
         WHERE i.id = ? AND i.user_id = ?
         LIMIT 1"
    );
    $stmt->execute([$id, $userId]);
    $inv = $stmt->fetch();
}

if (!$inv) {
    redirect(SITE_URL . '/dashboard.php', 'Facture introuvable.', 'error');
}

$libelle = !empty($inv['bundle_id']) ? ($inv['bundle_titre'] ?? 'Bundle') : ($inv['cours_titre'] ?? 'Formation / Abonnement');
$ht  = round($inv['montant'] / 1.18, 0);
$tva = $inv['montant'] - $ht;

$pdf = new MiniPDF();

// En-tête
$pdf->rect(0, 0, 595.28, 90, '#6C47D4');
$pdf->text(40, 45, SITE_NAME, 22, true);
$pdf->text(40, 68, 'Facture / Reçu de paiement', 11);

// Bloc infos facture
$y = 130;
$pdf->text(40, $y, 'Facture N° : ' . $inv['numero'], 12, true);
$pdf->text(40, $y + 20, 'Date : ' . date('d/m/Y', strtotime($inv['created_at'])), 10);
$pdf->text(40, $y + 38, 'Statut : ' . ucfirst($inv['statut']), 10);

$y2 = 130;
$pdf->text(330, $y2, 'Facturé à :', 11, true);
$pdf->text(330, $y2 + 20, $inv['prenom'] . ' ' . $inv['nom'], 10);
$pdf->text(330, $y2 + 36, $inv['email'], 10);

$pdf->line(40, 210, 555, 210);

// Tableau ligne produit
$ty = 240;
$pdf->text(40, $ty, 'Description', 11, true);
$pdf->text(330, $ty, 'Montant HT', 11, true);
$pdf->text(430, $ty, 'TVA (18%)', 11, true);
$pdf->text(500, $ty, 'TTC', 11, true);
$pdf->line(40, $ty + 8, 555, $ty + 8);

$ty += 30;
$pdf->text(40, $ty, mb_substr($libelle, 0, 45), 10);
$pdf->text(330, $ty, number_format($ht, 0, ',', ' ') . ' FCFA', 10);
$pdf->text(430, $ty, number_format($tva, 0, ',', ' ') . ' FCFA', 10);
$pdf->text(500, $ty, number_format($inv['montant'], 0, ',', ' ') . ' FCFA', 10);

$pdf->line(40, $ty + 15, 555, $ty + 15);

$ty += 45;
$pdf->text(400, $ty, 'Total TTC :', 12, true);
$pdf->text(490, $ty, number_format($inv['montant'], 0, ',', ' ') . ' FCFA', 12, true);

$ty += 60;
$pdf->text(40, $ty, 'Merci pour votre confiance — ' . SITE_NAME, 9);
$pdf->text(40, $ty + 16, 'Ce document fait office de reçu de paiement.', 8);

logAction('invoice_pdf_download', 'Téléchargement facture #' . $inv['id'] . ' (' . $inv['numero'] . ')', $userId);

$pdf->streamDownload('facture-' . preg_replace('/[^A-Za-z0-9_-]/', '', $inv['numero']) . '.pdf');
exit;
