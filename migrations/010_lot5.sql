-- ============================================================
-- MIGRATION 010 — Lot 5 fonctionnalités avancées
-- ============================================================

-- Prérequis entre cours
ALTER TABLE courses ADD COLUMN IF NOT EXISTS prerequis_course_id INT UNSIGNED DEFAULT NULL;

-- Activités optionnelles
ALTER TABLE sequences ADD COLUMN IF NOT EXISTS est_optionnel TINYINT(1) NOT NULL DEFAULT 0;

-- Redirection après complétion
ALTER TABLE courses ADD COLUMN IF NOT EXISTS redirect_completion VARCHAR(500) DEFAULT NULL;

-- Protection par mot de passe activité
ALTER TABLE sequences ADD COLUMN IF NOT EXISTS mot_de_passe VARCHAR(100) DEFAULT NULL;

-- UTM tracking
ALTER TABLE enrollments ADD COLUMN IF NOT EXISTS utm_source VARCHAR(100) DEFAULT NULL;
ALTER TABLE enrollments ADD COLUMN IF NOT EXISTS utm_medium VARCHAR(100) DEFAULT NULL;
ALTER TABLE enrollments ADD COLUMN IF NOT EXISTS utm_campaign VARCHAR(100) DEFAULT NULL;

-- Banque de questions
CREATE TABLE IF NOT EXISTS question_bank (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question    TEXT NOT NULL,
    type        ENUM('choix_unique','choix_multiple','vrai_faux','texte_libre') NOT NULL DEFAULT 'choix_unique',
    categorie   VARCHAR(100) DEFAULT NULL,
    points      TINYINT NOT NULL DEFAULT 1,
    explication TEXT DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS question_bank_answers (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_id INT UNSIGNED NOT NULL,
    texte       VARCHAR(500) NOT NULL,
    est_correct TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES question_bank(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Grille évaluation rubrique
CREATE TABLE IF NOT EXISTS rubriques (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT UNSIGNED NOT NULL,
    nom           VARCHAR(200) NOT NULL,
    criteres      JSON NOT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Templates feedback
CREATE TABLE IF NOT EXISTS feedback_templates (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titre     VARCHAR(200) NOT NULL,
    contenu   TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pièce jointe feedback
ALTER TABLE assignment_submissions ADD COLUMN IF NOT EXISTS feedback_fichier VARCHAR(500) DEFAULT NULL;

-- Historique soumissions
CREATE TABLE IF NOT EXISTS submission_history (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    submission_id INT UNSIGNED NOT NULL,
    user_id       INT UNSIGNED NOT NULL,
    assignment_id INT UNSIGNED NOT NULL,
    contenu       TEXT DEFAULT NULL,
    fichier       VARCHAR(500) DEFAULT NULL,
    version       TINYINT UNSIGNED NOT NULL DEFAULT 1,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Factures PDF
CREATE TABLE IF NOT EXISTS invoices (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    course_id  INT UNSIGNED NOT NULL,
    montant    DECIMAL(10,2) NOT NULL,
    numero     VARCHAR(50) NOT NULL UNIQUE,
    statut     ENUM('emise','payee','annulee') NOT NULL DEFAULT 'emise',
    pdf_path   VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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

-- Peer review
CREATE TABLE IF NOT EXISTS peer_reviews (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    submission_id INT UNSIGNED NOT NULL,
    reviewer_id   INT UNSIGNED NOT NULL,
    note          TINYINT UNSIGNED DEFAULT NULL,
    commentaire   TEXT DEFAULT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_review (submission_id, reviewer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bundle/pack cours
CREATE TABLE IF NOT EXISTS bundles (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titre       VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    prix        DECIMAL(10,2) NOT NULL DEFAULT 0,
    actif       TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS bundle_courses (
    bundle_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (bundle_id, course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- NPS/suivi satisfaction admin
INSERT IGNORE INTO feedback_templates (titre, contenu) VALUES
('Bon travail', 'Votre travail est de bonne qualité. Continuez dans cette direction.'),
('À améliorer', 'Votre livrable nécessite des améliorations. Veuillez revoir les points suivants.'),
('Excellent', 'Excellent travail ! Votre analyse est pertinente et bien structurée.'),
('Incomplet', 'Votre livrable est incomplet. Merci de soumettre à nouveau avec tous les éléments requis.');
