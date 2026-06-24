<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();

$pdo      = getPDO();
$courseId = (int)($_GET['course_id'] ?? 0);
if (!$courseId) { redirect(SITE_URL . '/admin/courses.php'); }

try {
    // Récupérer le cours original
    $orig = $pdo->prepare('SELECT * FROM courses WHERE id = ?');
    $orig->execute([$courseId]);
    $orig = $orig->fetch();
    if (!$orig) { redirect(SITE_URL . '/admin/courses.php', 'Cours introuvable.', 'error'); }

    // Dupliquer le cours
    $newSlug = slug($orig['titre']) . '-copie-' . time();
    $pdo->prepare(
        'INSERT INTO courses (category_id, titre, sous_titre, description, slug, niveau, type, prix, duree_heures, miniature, certificat, actif)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,0)'
    )->execute([
        $orig['category_id'], '[Copie] ' . $orig['titre'], $orig['sous_titre'],
        $orig['description'], $newSlug, $orig['niveau'], $orig['type'],
        $orig['prix'], $orig['duree_heures'], $orig['miniature'], $orig['certificat']
    ]);
    $newCourseId = (int)$pdo->lastInsertId();

    // Dupliquer les modules
    $modules = $pdo->prepare('SELECT * FROM modules WHERE course_id = ? ORDER BY ordre');
    $modules->execute([$courseId]);
    foreach ($modules->fetchAll() as $mod) {
        $pdo->prepare(
            'INSERT INTO modules (course_id, titre, description, objectifs, ordre, actif, duree_min)
             VALUES (?,?,?,?,?,?,?)'
        )->execute([$newCourseId, $mod['titre'], $mod['description'], $mod['objectifs'], $mod['ordre'], $mod['actif'], $mod['duree_min']]);
        $newModId = (int)$pdo->lastInsertId();

        // Dupliquer les séquences
        $seqs = $pdo->prepare('SELECT * FROM sequences WHERE module_id = ? ORDER BY ordre');
        $seqs->execute([$mod['id']]);
        foreach ($seqs->fetchAll() as $seq) {
            $newSeqSlug = slug($seq['titre']) . '-' . uniqid();
            $pdo->prepare(
                'INSERT INTO sequences (module_id, titre, slug, description, contenu, video_url, audio_url, duree_min, ordre, actif, xp_reward)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)'
            )->execute([
                $newModId, $seq['titre'], $newSeqSlug, $seq['description'],
                $seq['contenu'], $seq['video_url'], $seq['audio_url'],
                $seq['duree_min'], $seq['ordre'], $seq['actif'], $seq['xp_reward']
            ]);
        }
    }
    redirect(SITE_URL . '/admin/courses.php', 'Cours dupliqué avec succès ! Vous pouvez maintenant le modifier.', 'success');
} catch (Exception $e) {
    redirect(SITE_URL . '/admin/courses.php', 'Erreur lors de la duplication : ' . $e->getMessage(), 'error');
}
