<?php
// admin/course_export.php — Exporter un cours en JSON
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();

$pdo      = getPDO();
$courseId = (int)($_GET['course_id'] ?? 0);
if (!$courseId) redirect(SITE_URL . '/admin/courses.php', 'Cours introuvable.', 'error');

$course = $pdo->prepare('SELECT * FROM courses WHERE id=?');
$course->execute([$courseId]);
$course = $course->fetch();
if (!$course) redirect(SITE_URL . '/admin/courses.php', 'Cours introuvable.', 'error');

// Récupérer modules + séquences
$modules = $pdo->prepare('SELECT * FROM modules WHERE course_id=? ORDER BY ordre');
$modules->execute([$courseId]);
$modules = $modules->fetchAll();

foreach ($modules as &$mod) {
    $seqs = $pdo->prepare('SELECT * FROM sequences WHERE module_id=? ORDER BY ordre');
    $seqs->execute([$mod['id']]);
    $mod['sequences'] = $seqs->fetchAll();
}
unset($mod);

$export = [
    'version'    => '1.0',
    'export_date'=> date('Y-m-d H:i:s'),
    'site'       => SITE_NAME,
    'cours'      => [
        'titre'       => $course['titre'],
        'sous_titre'  => $course['sous_titre'],
        'description' => $course['description'],
        'niveau'      => $course['niveau'],
        'type'        => $course['type'],
        'prix'        => $course['prix'],
        'duree_heures'=> $course['duree_heures'],
        'certificat'  => $course['certificat'],
    ],
    'modules' => $modules,
];

$filename = slug($course['titre']) . '-export-' . date('Y-m-d') . '.json';
header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit;
