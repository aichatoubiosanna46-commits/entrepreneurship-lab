<?php
// ============================================================
//  includes/functions.php — Fonctions utilitaires globales
//  Structure MOOC : courses > modules > sequences
// ============================================================

// ------------------------------------------------------------
// Génère un slug depuis un titre
// ------------------------------------------------------------
function slug(string $texte): string {
    $texte = mb_strtolower($texte, 'UTF-8');
    $map = [
        'à'=>'a','â'=>'a','ä'=>'a','á'=>'a','ã'=>'a',
        'é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
        'î'=>'i','ï'=>'i','í'=>'i',
        'ô'=>'o','ö'=>'o','ó'=>'o','ò'=>'o',
        'û'=>'u','ù'=>'u','ü'=>'u','ú'=>'u',
        'ç'=>'c','ñ'=>'n',
    ];
    $texte = strtr($texte, $map);
    $texte = preg_replace('/[^a-z0-9\s-]/', '', $texte);
    $texte = preg_replace('/[\s-]+/', '-', trim($texte));
    return $texte;
}

// ------------------------------------------------------------
// Échappe le HTML (XSS)
// ------------------------------------------------------------
function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// ------------------------------------------------------------
// Redirige avec message flash
// ------------------------------------------------------------
function redirect(string $url, string $msg = '', string $type = 'success'): never {
    if ($msg) {
        $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
    }
    header('Location: ' . $url);
    exit;
}

// ------------------------------------------------------------
// Affiche et vide le message flash
// ------------------------------------------------------------
function flash(): string {
    if (empty($_SESSION['flash'])) return '';
    ['msg' => $msg, 'type' => $type] = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $colors = [
        'success' => ['bg' => '#EAF3DE', 'border' => '#97C459', 'text' => '#27500A', 'icon' => 'ti-circle-check'],
        'error'   => ['bg' => '#FAECE7', 'border' => '#F0997B', 'text' => '#993C1D', 'icon' => 'ti-alert-circle'],
        'info'    => ['bg' => '#E6F1FB', 'border' => '#85B7EB', 'text' => '#0C447C', 'icon' => 'ti-info-circle'],
    ];
    $c = $colors[$type] ?? $colors['info'];
    return sprintf(
        '<div style="background:%s;border:1px solid %s;color:%s;padding:12px 16px;border-radius:8px;margin-bottom:16px;display:flex;align-items:center;gap:10px;font-size:14px;">
            <i class="ti %s" style="font-size:18px;flex-shrink:0"></i>%s
         </div>',
        $c['bg'], $c['border'], $c['text'], $c['icon'], h($msg)
    );
}

// ------------------------------------------------------------
// Upload sécurisé d'une image
// ------------------------------------------------------------
function uploadImage(array $file, string $dossier, int $maxMo = 2): string|false {
    $maxOctets = $maxMo * 1024 * 1024;
    $extsAutorisees = ['jpg','jpeg','png','webp'];

    if ($file['error'] !== UPLOAD_ERR_OK)            return false;
    if ($file['size'] > $maxOctets)                  return false;

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $extsAutorisees, true))      return false;

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    $mimesAutorisees = ['image/jpeg','image/png','image/webp'];
    if (!in_array($mime, $mimesAutorisees, true))    return false;

    if (!getimagesize($file['tmp_name']))             return false;

    $nomFichier = bin2hex(random_bytes(16)) . '.' . $ext;
    $chemin     = UPLOAD_DIR . $dossier . '/' . $nomFichier;

    if (!is_dir(UPLOAD_DIR . $dossier)) {
        mkdir(UPLOAD_DIR . $dossier, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $chemin)) return false;

    return $dossier . '/' . $nomFichier;
}

// ------------------------------------------------------------
// Upload sécurisé d'un fichier (PDF, Word, PPT, Excel)
// ------------------------------------------------------------
function uploadFichier(array $file, string $dossier, int $maxMo = 10): string|false {
    $maxOctets = $maxMo * 1024 * 1024;
    $extsAutorisees = ['pdf','doc','docx','ppt','pptx','xls','xlsx'];
    $mimesAutorises = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    if ($file['error'] !== UPLOAD_ERR_OK)  return false;
    if ($file['size'] > $maxOctets)        return false;

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $extsAutorisees, true)) return false;

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $mimesAutorises, true)) return false;

    $nomFichier = bin2hex(random_bytes(16)) . '.' . $ext;
    $chemin     = UPLOAD_DIR . $dossier . '/' . $nomFichier;

    if (!is_dir(UPLOAD_DIR . $dossier)) {
        mkdir(UPLOAD_DIR . $dossier, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $chemin)) return false;

    return $dossier . '/' . $nomFichier;
}

// ------------------------------------------------------------
// Formate un prix en FCFA
// ------------------------------------------------------------
function fcfa(float $montant): string {
    if ($montant <= 0) return '<span style="color:#3B6D11;font-weight:500">Gratuit</span>';
    return number_format($montant, 0, ',', ' ') . ' FCFA';
}

// ------------------------------------------------------------
// Génère un slug unique pour une table donnée
// ------------------------------------------------------------
function slugUnique(PDO $pdo, string $table, string $colonne, string $base): string {
    $i = 0;
    do {
        $try = $base . ($i ? '-'.$i : '');
        $st  = $pdo->prepare("SELECT id FROM {$table} WHERE {$colonne} = ?");
        $st->execute([$try]);
        $i++;
    } while ($st->fetch());
    return $try;
}

// ------------------------------------------------------------
// Calcule le % de progression d'un apprenant sur un cours
// ------------------------------------------------------------
function progressionCours(int $userId, int $courseId): int {
    $pdo = getPDO();
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM sequences s
         JOIN modules m ON m.id = s.module_id
         WHERE m.course_id = ? AND s.actif = 1'
    );
    $stmt->execute([$courseId]);
    $total = (int) $stmt->fetchColumn();
    if ($total === 0) return 0;

    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM progress p
         JOIN sequences s ON s.id = p.sequence_id
         JOIN modules m   ON m.id = s.module_id
         WHERE p.user_id = ? AND m.course_id = ? AND p.terminee = 1'
    );
    $stmt->execute([$userId, $courseId]);
    $faites = (int) $stmt->fetchColumn();
    return (int) round($faites / $total * 100);
}

// ------------------------------------------------------------
// Calcule le % de progression sur un module
// ------------------------------------------------------------
function progressionModule(int $userId, int $moduleId): int {
    $pdo = getPDO();
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM sequences WHERE module_id = ? AND actif = 1'
    );
    $stmt->execute([$moduleId]);
    $total = (int) $stmt->fetchColumn();
    if ($total === 0) return 0;

    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM progress p
         JOIN sequences s ON s.id = p.sequence_id
         WHERE p.user_id = ? AND s.module_id = ? AND p.terminee = 1'
    );
    $stmt->execute([$userId, $moduleId]);
    $faites = (int) $stmt->fetchColumn();
    return (int) round($faites / $total * 100);
}

// ------------------------------------------------------------
// Vérifie si un utilisateur est inscrit à un cours
// ------------------------------------------------------------
function estInscrit(int $userId, int $courseId): bool {
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT id FROM enrollments WHERE user_id = ? AND course_id = ? AND statut = "actif"');
    $stmt->execute([$userId, $courseId]);
    return (bool) $stmt->fetch();
}

// ------------------------------------------------------------
// Génère un certificat pour un utilisateur après 100% de progression
// ------------------------------------------------------------
function genererCertificat(int $userId, int $courseId): bool {
    $pdo = getPDO();
    // Vérifier qu'il n'existe pas déjà
    $check = $pdo->prepare('SELECT id FROM certificates WHERE user_id = ? AND course_id = ?');
    $check->execute([$userId, $courseId]);
    if ($check->fetch()) return true;

    $code = strtoupper(bin2hex(random_bytes(12)));
    $pdo->prepare(
        'INSERT INTO certificates (user_id, course_id, code_unique) VALUES (?, ?, ?)'
    )->execute([$userId, $courseId, $code]);

    // Notifier l'utilisateur
    $course = $pdo->prepare('SELECT titre FROM courses WHERE id = ?');
    $course->execute([$courseId]);
    $c = $course->fetch();
    if ($c) {
        notifierUtilisateur($userId, 'Certificat obtenu ! 🎓', 'Félicitations ! Vous avez complété « '.$c['titre'].' ». Votre certificat est disponible.', 'success', SITE_URL . '/certificate.php?course=' . $courseId);
    }
    return true;
}

// ------------------------------------------------------------
// Envoie une notification à un utilisateur
// ------------------------------------------------------------
function notifierUtilisateur(int $userId, string $titre, string $message = '', string $type = 'info', string $lien = ''): void {
    try {
        $pdo = getPDO();
        $pdo->prepare(
            'INSERT INTO user_notifications (user_id, titre, message, type, lien) VALUES (?, ?, ?, ?, ?)'
        )->execute([$userId, $titre, $message, $type, $lien]);
    } catch (Exception $e) { /* silent */ }
}

// ------------------------------------------------------------
// Vérifie si une séquence est déverrouillée pour un apprenant
// (prérequis : séquence précédente terminée + score quiz min)
// ------------------------------------------------------------
function sequenceEstDeverrouillee(int $userId, array $seq): bool {
    if (empty($seq['prerequis_sequence_id'])) return true;

    $pdo = getPDO();
    $prereqId = (int) $seq['prerequis_sequence_id'];

    $stmt = $pdo->prepare('SELECT terminee FROM progress WHERE user_id = ? AND sequence_id = ?');
    $stmt->execute([$userId, $prereqId]);
    $prog = $stmt->fetch();
    if (!$prog || !$prog['terminee']) return false;

    if (!empty($seq['prerequis_quiz_min'])) {
        $stmt = $pdo->prepare(
            'SELECT qr.score FROM quiz_results qr
             JOIN quizzes q ON q.id = qr.quiz_id
             WHERE q.sequence_id = ? AND qr.user_id = ?
             ORDER BY qr.score DESC LIMIT 1'
        );
        $stmt->execute([$prereqId, $userId]);
        $best = $stmt->fetchColumn();
        if ($best === false || (int)$best < (int)$seq['prerequis_quiz_min']) return false;
    }

    return true;
}

// ------------------------------------------------------------
// Attribue un badge à un utilisateur (idempotent)
// ------------------------------------------------------------
function attribuerBadge(int $userId, int $badgeId, ?int $courseId = null): void {
    $pdo = getPDO();
    $stmt = $pdo->prepare(
        'INSERT IGNORE INTO user_badges (user_id, badge_id, course_id) VALUES (?, ?, ?)'
    );
    $stmt->execute([$userId, $badgeId, $courseId]);
    if ($stmt->rowCount() > 0) {
        $b = $pdo->prepare('SELECT titre, icone FROM badges WHERE id = ?');
        $b->execute([$badgeId]);
        $b = $b->fetch();
        if ($b) {
            notifierUtilisateur($userId, 'Badge débloqué ' . $b['icone'], 'Vous avez obtenu le badge « ' . $b['titre'] . ' » !', 'success');
        }
    }
}

// ------------------------------------------------------------
// Ajoute un tag à un utilisateur (idempotent)
// ------------------------------------------------------------
function ajouterTag(int $userId, string $tag): void {
    $pdo = getPDO();
    $pdo->prepare('INSERT IGNORE INTO user_tags (user_id, tag) VALUES (?, ?)')
        ->execute([$userId, $tag]);
}

// ------------------------------------------------------------
// Déclenche les automations de fin de cours : badge, tag,
// inscription automatique au cours suivant
// ------------------------------------------------------------
function declencherAutomationsCompletion(int $userId, int $courseId): void {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM courses WHERE id = ?');
    $stmt->execute([$courseId]);
    $course = $stmt->fetch();
    if (!$course) return;

    ajouterTag($userId, 'cours_complete_' . $course['slug']);

    if (!empty($course['badge_id'])) {
        attribuerBadge($userId, (int)$course['badge_id'], $courseId);
    }

    if (!empty($course['next_course_id'])) {
        $next = $pdo->prepare('SELECT id, titre, slug FROM courses WHERE id = ?');
        $next->execute([(int)$course['next_course_id']]);
        $next = $next->fetch();
        if ($next) {
            $pdo->prepare(
                'INSERT IGNORE INTO enrollments (user_id, course_id, statut, paye) VALUES (?, ?, "actif", 1)'
            )->execute([$userId, $next['id']]);
            notifierUtilisateur(
                $userId, 'Nouveau module débloqué 🚀',
                'Vous avez accès à « ' . $next['titre'] . ' ».',
                'success', SITE_URL . '/module.php?slug=' . urlencode($next['slug'])
            );
        }
    }
}

// ------------------------------------------------------------
// Récupère la soumission d'un apprenant pour une activité
// ------------------------------------------------------------
function recupererSoumission(int $userId, int $activityId): array|false {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM activity_submissions WHERE user_id = ? AND activity_id = ?');
    $stmt->execute([$userId, $activityId]);
    return $stmt->fetch();
}

// ------------------------------------------------------------
// Pagination — retourne [offset, pages, page_courante]
// ------------------------------------------------------------
function paginer(int $total, int $parPage = 12, string $param = 'page'): array {
    $pageCourante = max(1, (int)($_GET[$param] ?? 1));
    $pages        = (int) ceil($total / $parPage);
    $offset       = ($pageCourante - 1) * $parPage;
    return [$offset, $pages, $pageCourante];
}
