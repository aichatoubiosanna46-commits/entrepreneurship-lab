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
    if ($check->fetch()) {
        verifierCertificatBundle($userId, $courseId);
        return true;
    }

    // Détermine le type de certificat : "connaissance" si le score moyen aux
    // quiz du cours dépasse le seuil défini (sinon "completion" par défaut).
    $type = determinerTypeCertificat($userId, $courseId);

    $code = strtoupper(bin2hex(random_bytes(12)));
    try {
        $pdo->prepare(
            'INSERT INTO certificates (user_id, course_id, code_unique, type) VALUES (?, ?, ?, ?)'
        )->execute([$userId, $courseId, $code, $type]);
    } catch (Exception $e) {
        // Colonne "type" absente (migration 013 non appliquée) : repli sans type
        $pdo->prepare(
            'INSERT INTO certificates (user_id, course_id, code_unique) VALUES (?, ?, ?)'
        )->execute([$userId, $courseId, $code]);
    }

    // Notifier l'utilisateur
    $course = $pdo->prepare('SELECT titre FROM courses WHERE id = ?');
    $course->execute([$courseId]);
    $c = $course->fetch();
    if ($c) {
        notifierUtilisateur($userId, 'Certificat obtenu !', 'Félicitations ! Vous avez complété « '.$c['titre'].' ». Votre certificat est disponible.', 'success', SITE_URL . '/certificate.php?course=' . $courseId);
    }

    // Vérifier si un certificat de bundle doit être délivré
    verifierCertificatBundle($userId, $courseId);

    return true;
}

// ------------------------------------------------------------
// Détermine si le certificat doit être de type "connaissance" (score moyen
// des quiz du cours >= seuil_connaissance, défaut 70%) ou "completion".
// ------------------------------------------------------------
function determinerTypeCertificat(int $userId, int $courseId): string {
    $pdo = getPDO();
    try {
        $seuilStmt = $pdo->prepare('SELECT seuil_connaissance FROM courses WHERE id = ?');
        $seuilStmt->execute([$courseId]);
        $seuil = (int)($seuilStmt->fetchColumn() ?: 70);

        $avgStmt = $pdo->prepare(
            'SELECT AVG(qr.score) FROM quiz_results qr
             JOIN quizzes q ON q.id = qr.quiz_id
             JOIN sequences s ON s.id = q.sequence_id
             JOIN modules m ON m.id = s.module_id
             WHERE qr.user_id = ? AND m.course_id = ?'
        );
        $avgStmt->execute([$userId, $courseId]);
        $avg = $avgStmt->fetchColumn();

        if ($avg !== null && (float)$avg >= $seuil) {
            return 'connaissance';
        }
    } catch (Exception $e) { /* colonnes/tables absentes : repli completion */ }
    return 'completion';
}

// ------------------------------------------------------------
// Après l'obtention d'un certificat de cours, vérifie si l'utilisateur a
// complété tous les cours d'un bundle contenant ce cours, et délivre dans
// ce cas un certificat de bundle (course_id NULL, bundle_id renseigné).
// ------------------------------------------------------------
function verifierCertificatBundle(int $userId, int $courseId): void {
    $pdo = getPDO();
    try {
        $bundlesStmt = $pdo->prepare(
            'SELECT DISTINCT bc.bundle_id FROM bundle_courses bc WHERE bc.course_id = ?'
        );
        $bundlesStmt->execute([$courseId]);
        $bundleIds = $bundlesStmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($bundleIds as $bundleId) {
            // Déjà délivré ?
            $already = $pdo->prepare('SELECT id FROM certificates WHERE user_id = ? AND bundle_id = ?');
            $already->execute([$userId, $bundleId]);
            if ($already->fetch()) continue;

            // Tous les cours du bundle ont-ils un certificat pour cet utilisateur ?
            $coursesStmt = $pdo->prepare('SELECT course_id FROM bundle_courses WHERE bundle_id = ?');
            $coursesStmt->execute([$bundleId]);
            $bundleCourseIds = $coursesStmt->fetchAll(PDO::FETCH_COLUMN);
            if (empty($bundleCourseIds)) continue;

            $allDone = true;
            foreach ($bundleCourseIds as $bcId) {
                $certCheck = $pdo->prepare('SELECT id FROM certificates WHERE user_id = ? AND course_id = ?');
                $certCheck->execute([$userId, $bcId]);
                if (!$certCheck->fetch()) { $allDone = false; break; }
            }

            if ($allDone) {
                $code = strtoupper(bin2hex(random_bytes(12)));
                $pdo->prepare(
                    'INSERT INTO certificates (user_id, bundle_id, code_unique, type) VALUES (?, ?, ?, ?)'
                )->execute([$userId, $bundleId, $code, 'completion']);

                $bTitre = $pdo->prepare('SELECT titre FROM bundles WHERE id = ?');
                $bTitre->execute([$bundleId]);
                $titre = $bTitre->fetchColumn();
                if ($titre) {
                    notifierUtilisateur($userId, 'Certificat de parcours obtenu !',
                        'Vous avez complété tous les cours du pack « '.$titre.' ». Votre certificat de parcours est disponible.',
                        'success', SITE_URL . '/certificate.php?bundle=' . $bundleId);
                }
            }
        }
    } catch (Exception $e) { /* tables bundle absentes ou migration non appliquée : on ignore */ }
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
// Pagination — retourne [offset, pages, page_courante]
// ------------------------------------------------------------
function paginer(int $total, int $parPage = 12, string $param = 'page'): array {
    $pageCourante = max(1, (int)($_GET[$param] ?? 1));
    $pages        = (int) ceil($total / $parPage);
    $offset       = ($pageCourante - 1) * $parPage;
    return [$offset, $pages, $pageCourante];
}

// ------------------------------------------------------------
// Ajoute des points XP à un utilisateur
// ------------------------------------------------------------
function addXP(int $userId, string $source, int $points, string $desc = ''): void {
    try {
        $pdo = getPDO();
        $pdo->prepare(
            'INSERT INTO user_xp (user_id, source, points, description) VALUES (?, ?, ?, ?)'
        )->execute([$userId, $source, $points, $desc]);
        $pdo->prepare(
            'UPDATE users SET xp_total = xp_total + ? WHERE id = ?'
        )->execute([$points, $userId]);
    } catch (Exception $e) { /* silent */ }
}

// ------------------------------------------------------------
// Attribue un badge à un utilisateur (si pas déjà obtenu)
// ------------------------------------------------------------
function awardBadge(int $userId, int $badgeId): bool {
    try {
        $pdo = getPDO();
        $check = $pdo->prepare('SELECT id FROM user_badges WHERE user_id = ? AND badge_id = ?');
        $check->execute([$userId, $badgeId]);
        if ($check->fetch()) return false; // déjà obtenu

        $pdo->prepare(
            'INSERT INTO user_badges (user_id, badge_id) VALUES (?, ?)'
        )->execute([$userId, $badgeId]);

        // XP bonus pour badge obtenu
        addXP($userId, 'badge_obtenu', 50, 'Badge obtenu');

        // Récupérer nom du badge pour notification
        $b = $pdo->prepare('SELECT nom FROM badges WHERE id = ?');
        $b->execute([$badgeId]);
        $badge = $b->fetch();
        if ($badge) {
            sendNotification($userId, 'Badge obtenu !', 'Vous avez débloqué le badge « ' . $badge['nom'] . ' » !', 'success', SITE_URL . '/badges.php');
        }
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// ------------------------------------------------------------
// Envoie une notification à un utilisateur (alias enrichi)
// ------------------------------------------------------------
function sendNotification(int $userId, string $titre, string $message, string $type = 'info', string $lien = ''): void {
    notifierUtilisateur($userId, $titre, $message, $type, $lien);
}

// ------------------------------------------------------------
// Vérifie et attribue automatiquement les badges éligibles
// ------------------------------------------------------------
function checkAndAwardBadges(int $userId): void {
    try {
        $pdo = getPDO();
        $badges = $pdo->prepare(
            'SELECT * FROM badges WHERE actif = 1 AND condition_type != "manuel"'
        );
        $badges->execute();

        foreach ($badges->fetchAll() as $badge) {
            switch ($badge['condition_type']) {
                case 'xp_total':
                    $xp = $pdo->prepare('SELECT xp_total FROM users WHERE id = ?');
                    $xp->execute([$userId]);
                    $total = (int)($xp->fetchColumn() ?? 0);
                    if ($total >= (int)$badge['condition_valeur']) {
                        awardBadge($userId, $badge['id']);
                    }
                    break;

                case 'completion_cours':
                    $courseId = (int)$badge['condition_course_id'];
                    if ($courseId && progressionCours($userId, $courseId) >= 100) {
                        awardBadge($userId, $badge['id']);
                    }
                    break;

                case 'completion_module':
                    $moduleId = (int)($badge['condition_module_id'] ?? 0);
                    if ($moduleId && progressionModule($userId, $moduleId) >= 100) {
                        awardBadge($userId, $badge['id']);
                    }
                    break;

                case 'score_quiz':
                    $minScore = (int)$badge['condition_valeur'];
                    $hasScore = $pdo->prepare(
                        'SELECT id FROM quiz_results WHERE user_id = ? AND score >= ? AND reussi = 1 LIMIT 1'
                    );
                    $hasScore->execute([$userId, $minScore]);
                    if ($hasScore->fetch()) {
                        awardBadge($userId, $badge['id']);
                    }
                    break;
            }
        }
    } catch (Exception $e) { /* silent */ }
}

// ------------------------------------------------------------
// Retourne le total XP d'un utilisateur
// ------------------------------------------------------------
function getUserXPTotal(int $userId): int {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT xp_total FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        return (int)($stmt->fetchColumn() ?? 0);
    } catch (Exception $e) {
        return 0;
    }
}

// ------------------------------------------------------------
// Retourne le classement global d'un utilisateur (par XP)
// ------------------------------------------------------------
function getUserRank(int $userId): int {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) + 1 FROM users WHERE xp_total > (SELECT xp_total FROM users WHERE id = ?)'
        );
        $stmt->execute([$userId]);
        return (int)($stmt->fetchColumn() ?? 0);
    } catch (Exception $e) {
        return 0;
    }
}

// ============================================================
//  functions.php — Email de bienvenue
// ============================================================
function emailBienvenue(string $email, string $prenom): bool
{
    $siteName = defined('SITE_NAME') ? SITE_NAME : 'Entrepreneurship Lab';
    $siteUrl  = defined('SITE_URL')  ? SITE_URL  : '#';

    $sujet = "Bienvenue sur $siteName, $prenom ! 🎉";

    $message = "
    <!DOCTYPE html>
    <html lang='fr'>
    <head><meta charset='UTF-8'></head>
    <body style='margin:0;padding:0;background:#F5F0E8;font-family:Plus Jakarta Sans,Arial,sans-serif;'>
      <table width='100%' cellpadding='0' cellspacing='0'>
        <tr><td align='center' style='padding:40px 16px;'>
          <table width='520' cellpadding='0' cellspacing='0'
                 style='background:#fff;border-radius:16px;overflow:hidden;
                        box-shadow:0 4px 24px rgba(0,0,0,.08);'>

            <!-- Header -->
            <tr>
              <td style='background:linear-gradient(135deg,#D85A30,#085041);
                         padding:32px 40px;text-align:center;'>
                <div style='width:52px;height:52px;border-radius:14px;
                            background:rgba(255,255,255,.2);
                            display:inline-flex;align-items:center;justify-content:center;
                            font-size:22px;font-weight:800;color:#fff;
                            margin-bottom:12px;'>E</div>
                <h1 style='margin:0;color:#fff;font-size:22px;font-weight:800;'>
                  Bienvenue, $prenom !
                </h1>
              </td>
            </tr>

            <!-- Body -->
            <tr>
              <td style='padding:36px 40px;'>
                <p style='margin:0 0 16px;color:#1A1A18;font-size:15px;line-height:1.7;'>
                  Ton compte <strong>$siteName</strong> est prêt. 🚀<br>
                  Tu peux dès maintenant accéder à tes cours et commencer ton parcours entrepreneurial.
                </p>

                <table cellpadding='0' cellspacing='0' style='margin:24px 0;'>
                  <tr>
                    <td style='background:#FBE3DA;border-radius:10px;padding:14px 20px;
                               border-left:4px solid #D85A30;'>
                      <p style='margin:0;color:#92400E;font-size:13px;line-height:1.6;'>
                        ✅ Cours gratuits illimités dès maintenant<br>
                        🎓 Certificat Université de Parakou à l'obtention<br>
                        💬 Coaching 1:1 avec un mentor dédié<br>
                        📱 Paiement Mobile Money (MTN, Moov)
                      </p>
                    </td>
                  </tr>
                </table>

                <div style='text-align:center;margin:28px 0;'>
                  <a href='$siteUrl/dashboard.php'
                     style='display:inline-block;padding:13px 32px;
                            background:linear-gradient(135deg,#D85A30,#085041);
                            color:#fff;text-decoration:none;border-radius:10px;
                            font-weight:800;font-size:14px;
                            box-shadow:0 3px 10px rgba(245,158,11,.3);'>
                    Accéder à mon espace →
                  </a>
                </div>

                <p style='margin:0;color:#6b7280;font-size:12px;line-height:1.6;'>
                  Si tu n'es pas à l'origine de cette inscription, ignore simplement cet e-mail.
                </p>
              </td>
            </tr>

            <!-- Footer -->
            <tr>
              <td style='background:#f9fafb;padding:20px 40px;
                         border-top:1px solid #f3f4f6;text-align:center;'>
                <p style='margin:0;color:#9ca3af;font-size:11px;'>
                  © " . date('Y') . " $siteName · Tous droits réservés
                </p>
              </td>
            </tr>

          </table>
        </td></tr>
      </table>
    </body>
    </html>";

    $mailerFile = __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
    if (file_exists($mailerFile)) {
        require_once $mailerFile;
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = 'tls';
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = 'UTF-8';
            $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
            $mail->addAddress($email, $prenom);
            $mail->isHTML(true);
            $mail->Subject = $sujet;
            $mail->Body    = $message;
            return $mail->send();
        } catch (\Exception $e) {
            error_log('emailBienvenue PHPMailer: ' . $e->getMessage());
            return false;
        }
    }

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: $siteName <no-reply@entrepreneurship-lab.com>\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    return mail($email, $sujet, $message, $headers);
}

// ============================================================
//  MOTEUR D'AUTOMATIONS
// ============================================================
function triggerAutomation(string $declencheur, int $userId, int $conditionId = 0): void {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare(
            'SELECT * FROM automations WHERE declencheur = ? AND actif = 1
             AND (condition_id IS NULL OR condition_id = ?)'
        );
        $stmt->execute([$declencheur, $conditionId]);
        $automations = $stmt->fetchAll();

        foreach ($automations as $auto) {
            switch ($auto['action']) {
                case 'enroll_course':
                    $courseId = (int)$auto['action_value'];
                    if ($courseId) {
                        $chk = $pdo->prepare('SELECT id FROM enrollments WHERE user_id=? AND course_id=?');
                        $chk->execute([$userId, $courseId]);
                        if (!$chk->fetch()) {
                            $pdo->prepare('INSERT INTO enrollments (user_id,course_id,statut) VALUES (?,?,"actif")')
                                ->execute([$userId, $courseId]);
                        }
                    }
                    break;
                case 'add_tag':
                    $tag = trim($auto['action_value']);
                    if ($tag) {
                        $tagStmt = $pdo->prepare('SELECT id FROM tags WHERE nom=?');
                        $tagStmt->execute([$tag]);
                        $tagRow = $tagStmt->fetch();
                        if (!$tagRow) {
                            $pdo->prepare('INSERT INTO tags (nom) VALUES (?)')->execute([$tag]);
                            $tagId = $pdo->lastInsertId();
                        } else {
                            $tagId = $tagRow['id'];
                        }
                        $pdo->prepare('INSERT IGNORE INTO user_tags (user_id,tag_id) VALUES (?,?)')->execute([$userId, $tagId]);
                    }
                    break;
                case 'notify':
                    $pdo->prepare(
                        'INSERT INTO user_notifications (user_id,titre,message,type) VALUES (?,?,?,"info")'
                    )->execute([$userId, 'Félicitations !', $auto['action_value'] ?: 'Vous avez accompli une nouvelle étape !']);
                    break;

                case 'send_email':
                    try {
                        $userRow = $pdo->prepare('SELECT email, prenom FROM users WHERE id=?');
                        $userRow->execute([$userId]);
                        $userRow = $userRow->fetch();
                        if ($userRow) {
                            $emailBody = str_replace(
                                ['{{prenom}}', '{{site}}'],
                                [$userRow['prenom'], SITE_NAME],
                                $auto['action_value'] ?: 'Bonjour {{prenom}} !'
                            );
                            // Utiliser PHPMailer si disponible, sinon mail() natif
                            $mailerFile = __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
                            if (file_exists($mailerFile)) {
                                require_once $mailerFile;
                                require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
                                require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
                                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                                $mail->isSMTP();
                                $mail->Host       = SMTP_HOST;
                                $mail->SMTPAuth   = true;
                                $mail->Username   = SMTP_USER;
                                $mail->Password   = SMTP_PASS;
                                $mail->SMTPSecure = 'tls';
                                $mail->Port       = SMTP_PORT;
                                $mail->CharSet    = 'UTF-8';
                                $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
                                $mail->addAddress($userRow['email'], $userRow['prenom']);
                                $mail->isHTML(true);
                                $mail->Subject = 'Message de ' . SITE_NAME;
                                $mail->Body    = $emailBody;
                                $mail->send();
                            } else {
                                $headers = "Content-Type: text/html; charset=UTF-8\r\nFrom: " . SITE_NAME . " <" . SMTP_FROM . ">\r\n";
                                @mail($userRow['email'], 'Message de ' . SITE_NAME, $emailBody, $headers);
                            }
                        }
                    } catch(Exception $e) { error_log('Automation send_email: ' . $e->getMessage()); }
                    break;

                case 'award_badge':
                    // Attribuer un badge
                    $badgeId = (int)$auto['action_value'];
                    if ($badgeId) {
                        awardBadge($userId, $badgeId);
                    }
                    break;

                case 'add_xp':
                    // Ajouter des points XP
                    $xpPoints = (int)$auto['action_value'];
                    if ($xpPoints > 0) {
                        addXP($userId, 'automation', $xpPoints, 'Automation: ' . $auto['declencheur']);
                    }
                    break;

                case 'remove_access':
                    // Retirer l'accès à un cours
                    $courseIdRemove = (int)$auto['action_value'];
                    if ($courseIdRemove) {
                        $pdo->prepare('UPDATE enrollments SET statut="inactif" WHERE user_id=? AND course_id=?')
                            ->execute([$userId, $courseIdRemove]);
                    }
                    break;
            }
        }
    } catch (Exception $e) {
        error_log('Automation error: ' . $e->getMessage());
    }
}


// ============================================================
//  WHATSAPP NOTIFICATIONS
// ============================================================
function envoyerWhatsApp(string $telephone, string $message): bool {
    try {
        $pdo = getPDO();
        $apiUrl   = $pdo->query("SELECT valeur FROM settings WHERE cle='whatsapp_api_url'")->fetchColumn();
        $apiToken = $pdo->query("SELECT valeur FROM settings WHERE cle='whatsapp_api_token'")->fetchColumn();

        if (!$apiUrl || !$apiToken) return false;

        // Format numéro (supprimer +, espaces)
        $tel = preg_replace('/[^0-9]/', '', $telephone);

        $payload = json_encode([
            'messaging_product' => 'whatsapp',
            'to'      => $tel,
            'type'    => 'text',
            'text'    => ['body' => $message],
        ]);

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $apiToken,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Log
        $pdo->prepare(
            'INSERT INTO whatsapp_logs (user_id, telephone, message, statut) VALUES (NULL, ?, ?, ?)'
        )->execute([$telephone, $message, ($code === 200 ? 'envoye' : 'echec')]);

        return $code === 200;
    } catch (Exception $e) {
        error_log('WhatsApp error: ' . $e->getMessage());
        return false;
    }
}

function notifierWhatsAppInscription(string $telephone, string $prenom): void {
    $msg = "Bonjour {$prenom} 👋\n\nBienvenue sur Ariziki EntrepreneurshipLab ! 🎓\n\nVotre compte a été créé avec succès. Connectez-vous pour commencer votre parcours entrepreneurial.\n\nBonne formation ! 🚀";
    envoyerWhatsApp($telephone, $msg);
}

function notifierWhatsAppCertificat(string $telephone, string $prenom, string $coursTitre): void {
    $msg = "Félicitations {$prenom} ! 🎉\n\nVous avez obtenu votre certificat pour la formation :\n📜 *{$coursTitre}*\n\nCertifié par l'Université de Parakou. Téléchargez votre certificat sur votre espace Ariziki.";
    envoyerWhatsApp($telephone, $msg);
}

function notifierWhatsAppCoaching(string $telephone, string $prenom, string $dateHeure): void {
    $msg = "Bonjour {$prenom} 👋\n\nVotre session coaching 1:1 est confirmée pour le :\n📅 *{$dateHeure}*\n\nVous recevrez le lien Zoom par email. À bientôt !";
    envoyerWhatsApp($telephone, $msg);
}

// ============================================================
//  RÔLE INSTRUCTEUR — vérification de propriété d'un cours
// ============================================================

/**
 * Un instructeur ne peut gérer que les cours dont il est le formateur.
 * Les admins ont toujours accès à tout.
 */
function courseAppartientInstructeur(int $courseId): bool {
    if (estAdmin()) return true;
    if (!$courseId) return false;
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT 1 FROM courses WHERE id = ? AND formateur_id = ?');
    $stmt->execute([$courseId, $_SESSION['user_id'] ?? 0]);
    return (bool)$stmt->fetchColumn();
}

/**
 * Vérifie la propriété d'un cours à partir d'un module_id.
 */
function moduleAppartientInstructeur(int $moduleId): bool {
    if (estAdmin()) return true;
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT course_id FROM modules WHERE id = ?');
    $stmt->execute([$moduleId]);
    $courseId = (int)($stmt->fetchColumn() ?: 0);
    return courseAppartientInstructeur($courseId);
}

/**
 * Vérifie la propriété d'un cours à partir d'un sequence_id.
 */
function sequenceAppartientInstructeur(int $sequenceId): bool {
    if (estAdmin()) return true;
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT m.course_id FROM sequences s JOIN modules m ON m.id = s.module_id WHERE s.id = ?');
    $stmt->execute([$sequenceId]);
    $courseId = (int)($stmt->fetchColumn() ?: 0);
    return courseAppartientInstructeur($courseId);
}

/**
 * Vérifie la propriété d'un cours à partir d'un quiz_id.
 */
function quizAppartientInstructeur(int $quizId): bool {
    if (estAdmin()) return true;
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT m.course_id FROM quizzes q JOIN sequences s ON s.id = q.sequence_id JOIN modules m ON m.id = s.module_id WHERE q.id = ?');
    $stmt->execute([$quizId]);
    $courseId = (int)($stmt->fetchColumn() ?: 0);
    return courseAppartientInstructeur($courseId);
}

// ============================================================
//  AUTOMATIONS — RELANCE D'INACTIVITÉ (inactive_7days / inactive_30days)
//  Pas de vrai cron sur InfinityFree : ce check est appelé soit par
//  /cron/check_inactive.php (via un service externe type cron-job.org),
//  soit opportunistement à chaque requête (pseudo-cron, voir includes/auth.php).
// ============================================================
function runInactivityCheck(): void {
    $pdo = getPDO();

    $stmt = $pdo->prepare(
        "SELECT id FROM users
         WHERE actif = 1
           AND last_seen <= DATE_SUB(NOW(), INTERVAL 7 DAY)
           AND (inactive7_notified_at IS NULL OR inactive7_notified_at < last_seen)"
    );
    $stmt->execute();
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $userId) {
        triggerAutomation('inactive_7days', (int)$userId);
        $pdo->prepare('UPDATE users SET inactive7_notified_at = NOW() WHERE id = ?')->execute([$userId]);
    }

    $stmt = $pdo->prepare(
        "SELECT id FROM users
         WHERE actif = 1
           AND last_seen <= DATE_SUB(NOW(), INTERVAL 30 DAY)
           AND (inactive30_notified_at IS NULL OR inactive30_notified_at < last_seen)"
    );
    $stmt->execute();
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $userId) {
        triggerAutomation('inactive_30days', (int)$userId);
        $pdo->prepare('UPDATE users SET inactive30_notified_at = NOW() WHERE id = ?')->execute([$userId]);
    }
}

/**
 * Pseudo-cron : déclenché avec une faible probabilité à chaque requête front,
 * throttlé pour ne pas tourner plus d'une fois par heure.
 */
function maybeRunInactivityCheck(): void {
    if (mt_rand(1, 200) !== 1) return;
    $marker = sys_get_temp_dir() . '/elab_inactivity_check.lock';
    if (is_file($marker) && (time() - filemtime($marker)) < 3600) return;
    @touch($marker);
    try { runInactivityCheck(); } catch (Exception $e) { error_log('runInactivityCheck: ' . $e->getMessage()); }
}

