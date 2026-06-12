<?php
// ============================================================
//  parcours_helper.php — Fonctions communes aux pages parcours
// ============================================================

/**
 * Charge les cours d'un tarif avec leurs modules, séquences, activités et quizzes
 */
function chargerParcours(PDO $pdo, string $tarif, int $userId): array {
    $stmt = $pdo->prepare(
        "SELECT c.*,
                cat.nom  as cat_nom,
                cat.icone as cat_icone,
                cat.couleur as cat_couleur,
                (SELECT COUNT(*) FROM modules m WHERE m.course_id = c.id AND m.actif=1) as nb_modules,
                (SELECT COUNT(*) FROM modules m2 JOIN sequences s ON s.module_id=m2.id
                 WHERE m2.course_id=c.id AND s.actif=1) as nb_sequences,
                e.id as enrolled_id, e.statut as enroll_statut
         FROM courses c
         JOIN categories cat ON cat.id = c.category_id
         LEFT JOIN enrollments e ON e.course_id = c.id AND e.user_id = ?
         WHERE c.actif = 1 AND c.statut = 'publie' AND c.tarif = ?
         ORDER BY c.ordre ASC"
    );
    $stmt->execute([$userId, $tarif]);
    $cours = $stmt->fetchAll();

    // Pour chaque cours : charger modules + séquences + progression
    foreach ($cours as &$c) {
        $mods = $pdo->prepare(
            "SELECT m.*,
                    (SELECT COUNT(*) FROM sequences s WHERE s.module_id=m.id AND s.actif=1) as nb_seq
             FROM modules m
             WHERE m.course_id = ? AND m.actif=1
             ORDER BY m.ordre ASC"
        );
        $mods->execute([$c['id']]);
        $c['modules'] = $mods->fetchAll();

        foreach ($c['modules'] as &$mod) {
            $seqs = $pdo->prepare(
                "SELECT s.*,
                        p.terminee,
                        (SELECT COUNT(*) FROM activities a WHERE a.sequence_id=s.id AND a.actif=1) as nb_activites,
                        (SELECT COUNT(*) FROM quizzes q WHERE q.sequence_id=s.id AND q.actif=1) as nb_quizzes
                 FROM sequences s
                 LEFT JOIN progress p ON p.sequence_id=s.id AND p.user_id=?
                 WHERE s.module_id=? AND s.actif=1
                 ORDER BY s.ordre ASC"
            );
            $seqs->execute([$userId, $mod['id']]);
            $mod['sequences'] = $seqs->fetchAll();
        }
        unset($mod);

        // Calcul progression
        $c['progression'] = progressionCours($userId, $c['id']);
    }
    unset($c);
    return $cours;
}
