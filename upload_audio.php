<?php
// ============================================================
//  upload_audio.php — Réception d'un enregistrement audio (MediaRecorder)
//  Utilisé pour : soumission étudiant (assignment audio) et feedback coach.
// ============================================================
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

sendSecurityHeaders();
header('Content-Type: application/json');

if (empty($_SESSION['user_id']) && empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non connecté.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée.']);
    exit;
}

// CSRF (vérifié manuellement car pas de redirect possible en AJAX)
$token = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(403);
    echo json_encode(['error' => 'Jeton CSRF invalide.']);
    exit;
}

$target = $_POST['target'] ?? '';
if (!in_array($target, ['submission', 'feedback'], true)) {
    echo json_encode(['error' => 'Paramètre target invalide.']);
    exit;
}

if (empty($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'Aucun fichier audio reçu.']);
    exit;
}

$tmpName = $_FILES['audio']['tmp_name'];
$size    = $_FILES['audio']['size'];

// Limite : 15 Mo
if ($size > 15 * 1024 * 1024) {
    echo json_encode(['error' => 'Fichier audio trop volumineux (max 15 Mo).']);
    exit;
}

// On accepte webm/ogg/mp3/wav — le navigateur produit généralement du webm/ogg via MediaRecorder
$origName = $_FILES['audio']['name'] ?? 'audio.webm';
$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
if (!in_array($ext, ['webm', 'ogg', 'mp3', 'wav', 'm4a'])) {
    $ext = 'webm';
}

$uploadDir = __DIR__ . '/assets/uploads';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$filename = 'audio_' . $target . '_' . uniqid() . '.' . $ext;
$dest = $uploadDir . '/' . $filename;

if (!move_uploaded_file($tmpName, $dest)) {
    echo json_encode(['error' => 'Échec de l\'enregistrement du fichier.']);
    exit;
}

echo json_encode(['path' => 'assets/uploads/' . $filename]);
