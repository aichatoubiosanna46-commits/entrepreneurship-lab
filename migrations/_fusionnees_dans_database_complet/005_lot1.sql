-- ============================================================
-- LOT 1 : Codes promo + Inscription automatique + Analytics
-- ============================================================

-- Codes promo
CREATE TABLE IF NOT EXISTS promo_codes (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code          VARCHAR(50) NOT NULL UNIQUE,
    type          ENUM('pourcentage','montant') NOT NULL DEFAULT 'pourcentage',
    valeur        DECIMAL(10,2) NOT NULL DEFAULT 0,
    usage_max     INT UNSIGNED DEFAULT NULL COMMENT 'NULL = illimité',
    usage_count   INT UNSIGNED NOT NULL DEFAULT 0,
    date_debut    DATE DEFAULT NULL,
    date_fin      DATE DEFAULT NULL,
    course_id     INT UNSIGNED DEFAULT NULL COMMENT 'NULL = tous les cours',
    actif         TINYINT(1) NOT NULL DEFAULT 1,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Utilisation des codes promo
CREATE TABLE IF NOT EXISTS promo_usages (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    promo_id      INT UNSIGNED NOT NULL,
    user_id       INT UNSIGNED NOT NULL,
    payment_id    INT UNSIGNED DEFAULT NULL,
    used_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (promo_id) REFERENCES promo_codes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)  REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Automations basiques (inscription auto après complétion)
CREATE TABLE IF NOT EXISTS automations (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom           VARCHAR(200) NOT NULL,
    declencheur   VARCHAR(50) NOT NULL COMMENT 'course_completed, quiz_passed, etc.',
    condition_id  INT UNSIGNED DEFAULT NULL COMMENT 'course_id ou quiz_id déclencheur',
    action        VARCHAR(50) NOT NULL COMMENT 'enroll_course, add_tag, send_email',
    action_value  VARCHAR(500) DEFAULT NULL COMMENT 'course_id, tag_name, email_template',
    actif         TINYINT(1) NOT NULL DEFAULT 1,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- XP par activité
ALTER TABLE users ADD COLUMN IF NOT EXISTS xp_total INT UNSIGNED NOT NULL DEFAULT 0;

-- Champs profil étudiant
ALTER TABLE users ADD COLUMN IF NOT EXISTS universite VARCHAR(200) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS filiere    VARCHAR(200) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS promotion  VARCHAR(100) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS bio        TEXT DEFAULT NULL;

-- Codes promo dans payments
ALTER TABLE payments ADD COLUMN IF NOT EXISTS promo_code  VARCHAR(50) DEFAULT NULL;
ALTER TABLE payments ADD COLUMN IF NOT EXISTS remise       DECIMAL(10,2) DEFAULT 0;
