-- ============================================================
--  migrations/004_complete_schema.sql
--  Ariziki EntrepreneurshipLab — schéma complet
-- ============================================================

-- Tentatives de connexion (rate limiting)
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cle VARCHAR(200) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cle (cle),
    INDEX idx_created (created_at)
);

-- Sessions utilisateurs (single session)
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token VARCHAR(128) NOT NULL UNIQUE,
    fingerprint VARCHAR(64) NOT NULL,
    ip VARCHAR(45),
    user_agent TEXT,
    last_seen TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Réinitialisation de mot de passe
CREATE TABLE IF NOT EXISTS password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    email VARCHAR(180) NOT NULL,
    token VARCHAR(128) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Logs d'audit
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED DEFAULT NULL,
    admin_id INT UNSIGNED DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT DEFAULT NULL,
    ip VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action (action),
    INDEX idx_created (created_at)
);

-- Forum : topics
CREATE TABLE IF NOT EXISTS forum_topics (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED DEFAULT NULL,
    user_id INT UNSIGNED NOT NULL,
    titre VARCHAR(255) NOT NULL,
    contenu TEXT NOT NULL,
    epingle TINYINT(1) NOT NULL DEFAULT 0,
    ferme TINYINT(1) NOT NULL DEFAULT 0,
    nb_vues INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL
);

-- Forum : réponses
CREATE TABLE IF NOT EXISTS forum_replies (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    topic_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    contenu TEXT NOT NULL,
    est_solution TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (topic_id) REFERENCES forum_topics(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Badges
CREATE TABLE IF NOT EXISTS badges (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(120) NOT NULL,
    description TEXT DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    condition_type ENUM('completion_cours','score_quiz','manuel','xp_total') NOT NULL DEFAULT 'manuel',
    condition_valeur INT DEFAULT NULL,
    condition_course_id INT UNSIGNED DEFAULT NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Badges utilisateurs
CREATE TABLE IF NOT EXISTS user_badges (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    badge_id INT UNSIGNED NOT NULL,
    obtenu_le TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_badge (user_id, badge_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
);

-- Points XP
CREATE TABLE IF NOT EXISTS user_xp (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    source ENUM('sequence_complete','quiz_reussi','assignment_soumis','badge_obtenu','connexion_quotidienne') NOT NULL,
    points INT NOT NULL DEFAULT 0,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Assignments (livrables)
CREATE TABLE IF NOT EXISTS assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sequence_id INT UNSIGNED NOT NULL,
    titre VARCHAR(200) NOT NULL,
    consigne TEXT NOT NULL,
    type ENUM('texte','fichier','video','audio') NOT NULL DEFAULT 'texte',
    note_min DECIMAL(4,2) NOT NULL DEFAULT 5.00,
    note_max DECIMAL(4,2) NOT NULL DEFAULT 10.00,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sequence_id) REFERENCES sequences(id) ON DELETE CASCADE
);

-- Soumissions d'assignments
CREATE TABLE IF NOT EXISTS assignment_submissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    contenu TEXT DEFAULT NULL,
    fichier VARCHAR(255) DEFAULT NULL,
    statut ENUM('soumis','en_correction','accepte','refuse') NOT NULL DEFAULT 'soumis',
    note DECIMAL(4,2) DEFAULT NULL,
    feedback TEXT DEFAULT NULL,
    correction_par INT UNSIGNED DEFAULT NULL,
    corrige_le DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tags étudiants
CREATE TABLE IF NOT EXISTS tags (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(80) NOT NULL UNIQUE,
    couleur VARCHAR(7) NOT NULL DEFAULT '#6C47D4',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS user_tags (
    user_id INT UNSIGNED NOT NULL,
    tag_id INT UNSIGNED NOT NULL,
    attribue_le TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, tag_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

-- Cohortes / Groupes
CREATE TABLE IF NOT EXISTS cohorts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(120) NOT NULL,
    description TEXT DEFAULT NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS cohort_members (
    cohort_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    rejoint_le TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (cohort_id, user_id),
    FOREIGN KEY (cohort_id) REFERENCES cohorts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Blog articles
CREATE TABLE IF NOT EXISTS blog_articles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    slug VARCHAR(280) NOT NULL UNIQUE,
    resume TEXT DEFAULT NULL,
    contenu LONGTEXT DEFAULT NULL,
    image_cover VARCHAR(255) DEFAULT NULL,
    auteur_id INT UNSIGNED DEFAULT NULL,
    statut ENUM('brouillon','publie') NOT NULL DEFAULT 'brouillon',
    meta_title VARCHAR(200) DEFAULT NULL,
    meta_description TEXT DEFAULT NULL,
    published_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Prérequis cours
CREATE TABLE IF NOT EXISTS course_prerequisites (
    course_id INT UNSIGNED NOT NULL,
    required_course_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (course_id, required_course_id),
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (required_course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Configuration email SMTP
CREATE TABLE IF NOT EXISTS email_config (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    smtp_host VARCHAR(200) DEFAULT 'smtp.gmail.com',
    smtp_port SMALLINT DEFAULT 587,
    smtp_user VARCHAR(200) DEFAULT NULL,
    smtp_pass VARCHAR(200) DEFAULT NULL,
    smtp_from VARCHAR(200) DEFAULT NULL,
    smtp_from_name VARCHAR(100) DEFAULT 'Ariziki EntrepreneurshipLab',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT IGNORE INTO email_config (id) VALUES (1);

-- Champs supplémentaires utilisateurs
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS universite VARCHAR(150) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS filiere VARCHAR(150) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS promotion VARCHAR(50) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS xp_total INT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS locked_until DATETIME DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS session_token VARCHAR(128) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS email_verified TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS last_login_at DATETIME DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS last_login_ip VARCHAR(45) DEFAULT NULL;
