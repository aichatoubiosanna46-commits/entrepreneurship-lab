-- ============================================================
--  Migration 003 — Correction schema pour bases existantes
--  À exécuter si vous avez l'erreur "Unknown column 'category_id'"
--  ou si certaines tables sont manquantes.
-- ============================================================

USE entrepreneurship_lab;

-- 1. Table catégories (si absente)
CREATE TABLE IF NOT EXISTS categories (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom        VARCHAR(120) NOT NULL,
    slug       VARCHAR(140) NOT NULL UNIQUE,
    icone      VARCHAR(80)  DEFAULT 'ti-folder',
    couleur    VARCHAR(7)   DEFAULT '#6C47D4',
    actif      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Catégories par défaut
INSERT IGNORE INTO categories (nom, slug, icone, couleur) VALUES
('Création d''entreprise', 'creation-entreprise', 'ti-building-store', '#6C47D4'),
('Marketing digital',      'marketing-digital',   'ti-device-mobile',  '#534AB7'),
('Finance & gestion',      'finance-gestion',     'ti-chart-line',     '#4C1D95'),
('Leadership',             'leadership',          'ti-users',          '#8B5CF6');

-- 2. Ajouter category_id à courses si absent
ALTER TABLE courses
  ADD COLUMN IF NOT EXISTS category_id  INT UNSIGNED NOT NULL DEFAULT 1 AFTER id,
  ADD COLUMN IF NOT EXISTS formateur_id INT UNSIGNED DEFAULT NULL AFTER category_id,
  ADD COLUMN IF NOT EXISTS slug         VARCHAR(220) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS niveau       ENUM('debutant','intermediaire','avance') NOT NULL DEFAULT 'debutant',
  ADD COLUMN IF NOT EXISTS type         ENUM('gratuit','payant') NOT NULL DEFAULT 'gratuit',
  ADD COLUMN IF NOT EXISTS tarif        ENUM('decouverte','business_plan','lancement') NOT NULL DEFAULT 'decouverte',
  ADD COLUMN IF NOT EXISTS prix         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  ADD COLUMN IF NOT EXISTS duree_heures DECIMAL(4,1)  DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS certificat   TINYINT(1)    NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS ordre        SMALLINT      NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS statut       ENUM('brouillon','publie','archive') NOT NULL DEFAULT 'brouillon',
  ADD COLUMN IF NOT EXISTS created_by   INT UNSIGNED  DEFAULT NULL;

-- Corriger les slugs NULL
UPDATE courses SET slug = CONCAT('cours-', id) WHERE slug IS NULL OR slug = '';

-- Rendre slug UNIQUE si possible
ALTER TABLE courses MODIFY COLUMN slug VARCHAR(220) NOT NULL;
ALTER TABLE courses ADD UNIQUE KEY IF NOT EXISTS idx_courses_slug (slug);

-- 3. Table notifications utilisateurs
CREATE TABLE IF NOT EXISTS user_notifications (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    titre      VARCHAR(200) NOT NULL,
    message    TEXT         DEFAULT NULL,
    type       ENUM('info','success','warning','error') NOT NULL DEFAULT 'info',
    lien       VARCHAR(500) DEFAULT NULL,
    lu         TINYINT(1)   NOT NULL DEFAULT 0,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Table favoris
CREATE TABLE IF NOT EXISTS favorites (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    course_id  INT UNSIGNED NOT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_fav (user_id, course_id),
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Table ressources bibliothèque
CREATE TABLE IF NOT EXISTS library_resources (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titre       VARCHAR(200) NOT NULL,
    description TEXT         DEFAULT NULL,
    type        ENUM('business_plan','social_media','sales_script','autre') NOT NULL DEFAULT 'autre',
    fichier     VARCHAR(255) DEFAULT NULL,
    actif       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Table paiements (si absente)
CREATE TABLE IF NOT EXISTS payments (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED  NOT NULL,
    plan            VARCHAR(50)   NOT NULL DEFAULT 'business_plan',
    montant         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    telephone       VARCHAR(20)   DEFAULT NULL,
    operateur       ENUM('mtn','moov','autre') NOT NULL DEFAULT 'mtn',
    reference       VARCHAR(100)  DEFAULT NULL,
    statut          ENUM('en_attente','valide','rejete') NOT NULL DEFAULT 'en_attente',
    note_admin      TEXT          DEFAULT NULL,
    created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Table abonnements (si absente)
CREATE TABLE IF NOT EXISTS subscriptions (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    plan        VARCHAR(50)  NOT NULL DEFAULT 'decouverte',
    statut      ENUM('actif','expire','annule') NOT NULL DEFAULT 'actif',
    debut       DATE         DEFAULT NULL,
    fin         DATE         DEFAULT NULL,
    payment_id  INT UNSIGNED DEFAULT NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Table certificats (si absente)
CREATE TABLE IF NOT EXISTS certificates (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NOT NULL,
    course_id    INT UNSIGNED NOT NULL,
    code_unique  VARCHAR(60)  NOT NULL UNIQUE,
    delivered_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Table slides (si absente)
CREATE TABLE IF NOT EXISTS slides (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titre      VARCHAR(200) DEFAULT NULL,
    sous_titre VARCHAR(300) DEFAULT NULL,
    image      VARCHAR(255) DEFAULT NULL,
    lien       VARCHAR(500) DEFAULT NULL,
    ordre      SMALLINT     NOT NULL DEFAULT 0,
    actif      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Table enrollments (si absente)
CREATE TABLE IF NOT EXISTS enrollments (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    course_id  INT UNSIGNED NOT NULL,
    statut     ENUM('actif','expire','annule') NOT NULL DEFAULT 'actif',
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_enroll (user_id, course_id),
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Table progress (si absente)
CREATE TABLE IF NOT EXISTS progress (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    sequence_id INT UNSIGNED NOT NULL,
    terminee    TINYINT(1)   NOT NULL DEFAULT 0,
    updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_progress (user_id, sequence_id),
    FOREIGN KEY (user_id)    REFERENCES users(id)     ON DELETE CASCADE,
    FOREIGN KEY (sequence_id)REFERENCES sequences(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Migration 003 terminée avec succès.' as status;
