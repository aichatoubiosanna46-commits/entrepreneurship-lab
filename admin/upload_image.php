<?php
// admin/upload_image.php — Handler upload images TinyMCE
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();

header('Content-Type: application/json');

if (empty($_FILES['file']['tmp_name'])) {
    echo json_encode(['error' => 'Aucun fichier reçu']);
    exit;
}

$file    = $_FILES['file'];
$ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

if (!in_array($ext, $allowed)) {
    echo json_encode(['error' => 'Format non supporté. Utilisez JPG, PNG ou WebP.']);
    exit;
}

if ($file['size'] > 3 * 1024 * 1024) {
    echo json_encode(['error' => 'Image trop lourde (max 3 Mo)']);
    exit;
}

$name = 'img_' . uniqid() . '.' . $ext;
$dest = __DIR__ . '/../assets/uploads/' . $name;

if (move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode(['location' => SITE_URL . '/assets/uploads/' . $name]);
} else {
    echo json_encode(['error' => 'Échec de l\'upload. Vérifiez les permissions du dossier assets/uploads/']);
}