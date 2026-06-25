-- ============================================================
--  Migration 003 — Fonctionnalités Module 0 (LearnWorlds-like)
--  Prérequis/navigation séquentielle, badges, devoirs notés,
--  auto-évaluation, tags utilisateurs, automations de base
-- ============================================================

USE entrepreneurship_lab;

-- ------------------------------------------------------------
-- Prérequis entre séquences (navigation avec verrouillage)
-- ------------------------------------------------------------
ALTER TABLE sequences
    ADD COLUMN prerequis_sequence_id INT UNSIGNED DEFAULT NULL AFTER ordre,
    ADD COLUMN prerequis_quiz_min    TINYINT       DEFAULT NULL AFTER prerequis_sequence_id,
    ADD CONSTRAINT fk_seq_prerequis FOREIGN KEY (prerequis_sequence_id)
        REFERENCES sequences(id) ON DELETE SET NULL;

-- ------------------------------------------------------------
-- Badges
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS badges (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titre       VARCHAR(120) NOT NULL,
    slug        VARCHAR(140) NOT NULL UNIQUE,
    description TEXT         DEFAULT NULL,
    icone       VARCHAR(10)  DEFAULT '🏅',
    couleur     VARCHAR(7)   DEFAULT '#BA7517',
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_badges (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    badge_id   INT UNSIGNED NOT NULL,
    course_id  INT UNSIGNED DEFAULT NULL,
    obtenu_le  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_badge (user_id, badge_id),
    FOREIGN KEY (user_id)  REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (badge_id) REFERENCES badges(id)  ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Badge à débloquer en fin de cours + cours suivant à auto-inscrire
ALTER TABLE courses
    ADD COLUMN badge_id        INT UNSIGNED DEFAULT NULL AFTER certificat,
    ADD COLUMN next_course_id  INT UNSIGNED DEFAULT NULL AFTER badge_id,
    ADD CONSTRAINT fk_course_badge FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_course_next  FOREIGN KEY (next_course_id) REFERENCES courses(id) ON DELETE SET NULL;

-- ------------------------------------------------------------
-- Activités : ajout du type auto-évaluation + champs devoir noté
-- ------------------------------------------------------------
ALTER TABLE activities
    MODIFY COLUMN type ENUM('devoir','exercice','cas_pratique','travail_pratique','auto_evaluation')
        NOT NULL DEFAULT 'exercice',
    ADD COLUMN note_max     SMALLINT DEFAULT NULL AFTER fichier,
    ADD COLUMN bareme       TEXT     DEFAULT NULL AFTER note_max,
    ADD COLUMN options_json TEXT     DEFAULT NULL AFTER bareme;

-- ------------------------------------------------------------
-- Soumissions des apprenants (devoir noté + auto-évaluation)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS activity_submissions (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    activity_id   INT UNSIGNED NOT NULL,
    user_id       INT UNSIGNED NOT NULL,
    contenu       TEXT         DEFAULT NULL,   -- texte libre soumis / réponse d'activation
    fichier       VARCHAR(255) DEFAULT NULL,   -- fichier joint à la soumission
    choix         VARCHAR(80)  DEFAULT NULL,   -- bouton choisi (auto-évaluation)
    note          SMALLINT     DEFAULT NULL,
    feedback      TEXT         DEFAULT NULL,
    statut        ENUM('soumis','corrige') NOT NULL DEFAULT 'soumis',
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    corrected_at  TIMESTAMP    NULL DEFAULT NULL,
    UNIQUE KEY uq_submission (activity_id, user_id),
    FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)     REFERENCES users(id)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tags utilisateurs (base des automations)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS user_tags (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    tag        VARCHAR(80)  NOT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_tag (user_id, tag),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
