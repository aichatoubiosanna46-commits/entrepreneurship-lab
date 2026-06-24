-- ============================================================
-- MIGRATION 009 — Fonctionnalités 2 jours
-- ============================================================

-- Minuteur quiz
ALTER TABLE quizzes ADD COLUMN IF NOT EXISTS duree_minutes INT UNSIGNED DEFAULT NULL COMMENT 'NULL = pas de limite';
ALTER TABLE quizzes ADD COLUMN IF NOT EXISTS ordre_aleatoire TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE quizzes ADD COLUMN IF NOT EXISTS tentatives_max INT UNSIGNED DEFAULT NULL COMMENT 'NULL = illimitées';

-- Séquences : type optionnel + embed iframe
ALTER TABLE sequences ADD COLUMN IF NOT EXISTS est_optionnel TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE sequences ADD COLUMN IF NOT EXISTS embed_code TEXT DEFAULT NULL COMMENT 'Code iframe HTML';
ALTER TABLE sequences ADD COLUMN IF NOT EXISTS pdf_url VARCHAR(500) DEFAULT NULL;

-- Resoumission assignment
ALTER TABLE assignment_submissions ADD COLUMN IF NOT EXISTS nb_soumissions INT UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE assignment_submissions ADD COLUMN IF NOT EXISTS peut_resoumettre TINYINT(1) NOT NULL DEFAULT 0;

-- Formulaire satisfaction
CREATE TABLE IF NOT EXISTS satisfaction_forms (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id   INT UNSIGNED NOT NULL,
    titre       VARCHAR(200) NOT NULL DEFAULT 'Votre avis compte !',
    questions   JSON NOT NULL,
    actif       TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS satisfaction_reponses (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    form_id     INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED DEFAULT NULL COMMENT 'NULL = anonyme',
    reponses    JSON NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (form_id) REFERENCES satisfaction_forms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Réactions forum
CREATE TABLE IF NOT EXISTS forum_reactions (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    topic_id    INT UNSIGNED DEFAULT NULL,
    reply_id    INT UNSIGNED DEFAULT NULL,
    user_id     INT UNSIGNED NOT NULL,
    emoji       VARCHAR(10) NOT NULL DEFAULT '👍',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_reaction (topic_id, reply_id, user_id, emoji)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Factures
CREATE TABLE IF NOT EXISTS invoices (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_id  INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED NOT NULL,
    numero      VARCHAR(50) NOT NULL UNIQUE,
    montant     DECIMAL(10,2) NOT NULL,
    pdf_path    VARCHAR(500) DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Messagerie coach-étudiant
CREATE TABLE IF NOT EXISTS messages (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    from_admin  TINYINT(1) NOT NULL DEFAULT 0,
    from_id     INT UNSIGNED NOT NULL,
    to_user_id  INT UNSIGNED NOT NULL,
    sujet       VARCHAR(200) NOT NULL,
    contenu     TEXT NOT NULL,
    lu          TINYINT(1) NOT NULL DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Page contact
CREATE TABLE IF NOT EXISTS contact_messages (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom         VARCHAR(100) NOT NULL,
    email       VARCHAR(200) NOT NULL,
    sujet       VARCHAR(200) NOT NULL,
    message     TEXT NOT NULL,
    lu          TINYINT(1) NOT NULL DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
