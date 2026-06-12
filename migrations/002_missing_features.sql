-- ============================================================
--  Migration 002 — Fonctionnalités manquantes
-- ============================================================

USE entrepreneurship_lab;

-- Commentaires sous les leçons
CREATE TABLE IF NOT EXISTS comments (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sequence_id INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED NOT NULL,
    parent_id   INT UNSIGNED DEFAULT NULL,
    contenu     TEXT NOT NULL,
    actif       TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sequence_id) REFERENCES sequences(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)     REFERENCES users(id)     ON DELETE CASCADE,
    FOREIGN KEY (parent_id)   REFERENCES comments(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Favoris
CREATE TABLE IF NOT EXISTS favorites (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    course_id  INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_favorite (user_id, course_id),
    FOREIGN KEY (user_id)   REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bibliothèque de ressources
CREATE TABLE IF NOT EXISTS library_resources (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titre       VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    fichier     VARCHAR(255) NOT NULL,
    type        ENUM('business_plan','social_media','sales_script','autre') NOT NULL DEFAULT 'autre',
    tarif_min   ENUM('decouverte','business_plan','lancement') NOT NULL DEFAULT 'decouverte',
    actif       TINYINT(1) NOT NULL DEFAULT 1,
    downloads   INT UNSIGNED NOT NULL DEFAULT 0,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Parcours d'apprentissage
CREATE TABLE IF NOT EXISTS learning_paths (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug        VARCHAR(120) NOT NULL UNIQUE,
    titre       VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    icone       VARCHAR(80) DEFAULT 'ti-road',
    couleur     VARCHAR(7) DEFAULT '#BA7517',
    ordre       SMALLINT NOT NULL DEFAULT 0,
    actif       TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS learning_path_courses (
    path_id   INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    ordre     SMALLINT NOT NULL DEFAULT 0,
    PRIMARY KEY (path_id, course_id),
    FOREIGN KEY (path_id)  REFERENCES learning_paths(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Abonnements
CREATE TABLE IF NOT EXISTS subscriptions (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    tarif      ENUM('decouverte','business_plan','lancement') NOT NULL,
    statut     ENUM('actif','expire','annule') NOT NULL DEFAULT 'actif',
    paye       TINYINT(1) NOT NULL DEFAULT 0,
    expire_le  DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications utilisateurs
CREATE TABLE IF NOT EXISTS user_notifications (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    titre      VARCHAR(200) NOT NULL,
    message    TEXT DEFAULT NULL,
    type       ENUM('info','success','warning','error') NOT NULL DEFAULT 'info',
    lien       VARCHAR(500) DEFAULT NULL,
    lu         TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données parcours par défaut
INSERT IGNORE INTO learning_paths (slug, titre, description, icone, couleur, ordre) VALUES
('creer-son-business',       'Créer son Business',            'De l\'idée au lancement de votre entreprise',          'ti-building-store', '#BA7517', 1),
('branding',                  'Branding & Image de marque',    'Construire une marque forte et mémorable',              'ti-palette',        '#534AB7', 2),
('marketing-digital',         'Marketing Digital',             'Attirer et convertir vos clients en ligne',             'ti-device-mobile',  '#3B6D11', 3),
('vente-acquisition-clients', 'Vente & Acquisition Clients',   'Techniques de vente et acquisition client efficaces',   'ti-target',         '#0F6E56', 4);
