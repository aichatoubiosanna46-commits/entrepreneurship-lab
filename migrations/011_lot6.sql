-- ============================================================
-- MIGRATION 011 — Lot 6
-- ============================================================

-- Historique soumissions
CREATE TABLE IF NOT EXISTS submission_history (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED NOT NULL,
    assignment_id INT UNSIGNED NOT NULL,
    contenu       TEXT DEFAULT NULL,
    fichier       VARCHAR(500) DEFAULT NULL,
    note          DECIMAL(4,2) DEFAULT NULL,
    feedback      TEXT DEFAULT NULL,
    version       TINYINT UNSIGNED NOT NULL DEFAULT 1,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pièce jointe feedback coach
ALTER TABLE assignment_submissions ADD COLUMN IF NOT EXISTS feedback_fichier VARCHAR(500) DEFAULT NULL;

-- Grille évaluation rubrique
CREATE TABLE IF NOT EXISTS rubriques (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titre         VARCHAR(200) NOT NULL,
    criteres      JSON NOT NULL COMMENT '[{nom, description, points_max}]',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE assignment_submissions ADD COLUMN IF NOT EXISTS rubrique_id INT UNSIGNED DEFAULT NULL;
ALTER TABLE assignment_submissions ADD COLUMN IF NOT EXISTS rubrique_notes JSON DEFAULT NULL;

-- Bundle/pack cours
CREATE TABLE IF NOT EXISTS bundles (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titre       VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    prix        DECIMAL(10,2) NOT NULL DEFAULT 0,
    image       VARCHAR(500) DEFAULT NULL,
    actif       TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS bundle_courses (
    bundle_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (bundle_id, course_id),
    FOREIGN KEY (bundle_id) REFERENCES bundles(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Commentaires horodatés vidéo
CREATE TABLE IF NOT EXISTS video_comments (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sequence_id INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED NOT NULL,
    timecode    INT UNSIGNED NOT NULL DEFAULT 0,
    commentaire TEXT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sequence_id) REFERENCES sequences(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)     REFERENCES users(id)     ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Rapport cohorte
ALTER TABLE users ADD COLUMN IF NOT EXISTS cohorte VARCHAR(100) DEFAULT NULL;

-- Emails ciblés par tag
CREATE TABLE IF NOT EXISTS email_campagnes (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sujet       VARCHAR(200) NOT NULL,
    contenu     TEXT NOT NULL,
    tag_id      INT UNSIGNED DEFAULT NULL,
    statut      ENUM('brouillon','envoyee') NOT NULL DEFAULT 'brouillon',
    nb_envoyes  INT UNSIGNED NOT NULL DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sauvegarde auto (log)
CREATE TABLE IF NOT EXISTS backup_logs (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fichier    VARCHAR(500) NOT NULL,
    taille_kb  INT UNSIGNED NOT NULL DEFAULT 0,
    statut     ENUM('succes','erreur') NOT NULL DEFAULT 'succes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Factures
CREATE TABLE IF NOT EXISTS invoices (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    course_id  INT UNSIGNED DEFAULT NULL,
    bundle_id  INT UNSIGNED DEFAULT NULL,
    montant    DECIMAL(10,2) NOT NULL,
    numero     VARCHAR(50) NOT NULL UNIQUE,
    statut     ENUM('emise','payee','annulee') NOT NULL DEFAULT 'payee',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Règle de complétion cours
ALTER TABLE courses ADD COLUMN IF NOT EXISTS completion_rule VARCHAR(50) NOT NULL DEFAULT 'toutes_sequences';

-- Import/Export cours JSON
-- (géré par les pages admin/course_export.php et admin/course_import.php)

-- Rôle coach
ALTER TABLE users ADD COLUMN IF NOT EXISTS role ENUM('etudiant','coach','moderateur') NOT NULL DEFAULT 'etudiant';

-- Points XP quiz
ALTER TABLE quizzes ADD COLUMN IF NOT EXISTS points_xp INT UNSIGNED NOT NULL DEFAULT 20;
