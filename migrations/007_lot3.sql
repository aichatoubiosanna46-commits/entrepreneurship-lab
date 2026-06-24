-- ============================================================
-- LOT 3 : Google Analytics, WhatsApp, Zoom/Calendly, API REST
-- ============================================================

-- Sessions live / coaching planifié
CREATE TABLE IF NOT EXISTS coaching_sessions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    formateur_id    INT UNSIGNED NOT NULL,
    user_id         INT UNSIGNED NOT NULL,
    titre           VARCHAR(200) NOT NULL,
    description     TEXT DEFAULT NULL,
    date_heure      DATETIME NOT NULL,
    duree_minutes   INT UNSIGNED NOT NULL DEFAULT 30,
    lien_zoom       VARCHAR(500) DEFAULT NULL,
    lien_calendly   VARCHAR(500) DEFAULT NULL,
    statut          ENUM('planifie','confirme','annule','termine') DEFAULT 'planifie',
    notes_coach     TEXT DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (formateur_id) REFERENCES admins(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)      REFERENCES users(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- WhatsApp notifications log
CREATE TABLE IF NOT EXISTS whatsapp_logs (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED DEFAULT NULL,
    telephone   VARCHAR(30) NOT NULL,
    message     TEXT NOT NULL,
    statut      ENUM('envoye','echec','pending') DEFAULT 'pending',
    sent_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- API tokens (REST publique)
CREATE TABLE IF NOT EXISTS api_tokens (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    token       VARCHAR(64) NOT NULL UNIQUE,
    nom         VARCHAR(100) NOT NULL DEFAULT 'Mon application',
    permissions JSON DEFAULT NULL,
    last_used   TIMESTAMP NULL DEFAULT NULL,
    expires_at  TIMESTAMP NULL DEFAULT NULL,
    actif       TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Config globale (Google Analytics, WhatsApp, etc.)
CREATE TABLE IF NOT EXISTS settings (
    cle     VARCHAR(100) NOT NULL PRIMARY KEY,
    valeur  TEXT DEFAULT NULL,
    groupe  VARCHAR(50) DEFAULT 'general',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO settings (cle, valeur, groupe) VALUES
('ga4_measurement_id',    '',       'analytics'),
('fb_pixel_id',           '',       'analytics'),
('whatsapp_api_url',      '',       'whatsapp'),
('whatsapp_api_token',    '',       'whatsapp'),
('whatsapp_numero',       '',       'whatsapp'),
('calendly_url',          '',       'coaching'),
('zoom_api_key',          '',       'coaching'),
('site_name',             'Ariziki EntrepreneurshipLab', 'general'),
('site_email',            '',       'general'),
('maintenance_mode',      '0',      'general');
