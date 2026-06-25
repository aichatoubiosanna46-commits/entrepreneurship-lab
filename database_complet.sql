-- ============================================================
--  EntreprendreBJ — Installation complète (base propre)
--  Exécuter UNE SEULE FOIS dans phpMyAdmin
-- ============================================================

DROP DATABASE IF EXISTS entrepreneurship_lab;
CREATE DATABASE entrepreneurship_lab
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE entrepreneurship_lab;

-- 1. UTILISATEURS
CREATE TABLE users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom           VARCHAR(100)  NOT NULL,
    prenom        VARCHAR(100)  NOT NULL,
    email         VARCHAR(180)  NOT NULL UNIQUE,
    password      VARCHAR(255)  NOT NULL,
    role          ENUM('admin','formateur','apprenant') NOT NULL DEFAULT 'apprenant',
    avatar        VARCHAR(255)  DEFAULT NULL,
    telephone     VARCHAR(20)   DEFAULT NULL,
    ville         VARCHAR(100)  DEFAULT NULL,
    bio           TEXT          DEFAULT NULL,
    actif         TINYINT(1)    NOT NULL DEFAULT 1,
    reset_token   VARCHAR(255)  DEFAULT NULL,
    reset_expires DATETIME      DEFAULT NULL,
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. ADMINISTRATEURS
CREATE TABLE admins (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom           VARCHAR(100)  NOT NULL,
    prenom        VARCHAR(100)  NOT NULL,
    email         VARCHAR(180)  NOT NULL UNIQUE,
    password      VARCHAR(255)  NOT NULL,
    actif         TINYINT(1)    NOT NULL DEFAULT 1,
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Compte admin par défaut : admin@lab.bj / admin123
INSERT INTO admins (nom, prenom, email, password) VALUES
('Admin', 'Principal', 'admin@lab.bj', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- 3. CATÉGORIES
CREATE TABLE categories (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom        VARCHAR(120) NOT NULL,
    slug       VARCHAR(140) NOT NULL UNIQUE,
    icone      VARCHAR(80)  DEFAULT 'ti-folder',
    couleur    VARCHAR(7)   DEFAULT '#6C47D4',
    actif      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO categories (nom, slug, icone, couleur) VALUES
('Création d''entreprise', 'creation-entreprise', 'ti-building-store', '#6C47D4'),
('Marketing digital',      'marketing-digital',   'ti-device-mobile',  '#534AB7'),
('Finance & gestion',      'finance-gestion',     'ti-chart-line',     '#4C1D95'),
('Leadership',             'leadership',          'ti-users',          '#8B5CF6');

-- 4. COURS
CREATE TABLE courses (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id   INT UNSIGNED  NOT NULL DEFAULT 1,
    formateur_id  INT UNSIGNED  DEFAULT NULL,
    titre         VARCHAR(200)  NOT NULL,
    slug          VARCHAR(220)  NOT NULL UNIQUE,
    description   TEXT          DEFAULT NULL,
    miniature     VARCHAR(255)  DEFAULT NULL,
    video_intro   VARCHAR(500)  DEFAULT NULL,
    niveau        ENUM('debutant','intermediaire','avance') NOT NULL DEFAULT 'debutant',
    type          ENUM('gratuit','payant') NOT NULL DEFAULT 'gratuit',
    tarif         ENUM('decouverte','serie_1_2','parcours_complet') NOT NULL DEFAULT 'decouverte',
    prix          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    duree_heures  DECIMAL(4,1)  DEFAULT NULL,
    certificat    TINYINT(1)    NOT NULL DEFAULT 0,
    actif         TINYINT(1)    NOT NULL DEFAULT 1,
    ordre         SMALLINT      NOT NULL DEFAULT 0,
    statut        ENUM('brouillon','publie','archive') NOT NULL DEFAULT 'brouillon',
    created_by    INT UNSIGNED  DEFAULT NULL,
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id)  REFERENCES categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (formateur_id) REFERENCES users(id)      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. MODULES
CREATE TABLE modules (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id        INT UNSIGNED  NOT NULL,
    titre            VARCHAR(200)  NOT NULL,
    description      TEXT          DEFAULT NULL,
    objectifs        TEXT          DEFAULT NULL,
    nb_sequences_prev INT          DEFAULT 0,
    duree_min        SMALLINT      DEFAULT NULL,
    ordre            SMALLINT      NOT NULL DEFAULT 0,
    actif            TINYINT(1)    NOT NULL DEFAULT 1,
    created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. SÉQUENCES
CREATE TABLE sequences (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module_id   INT UNSIGNED  NOT NULL,
    titre       VARCHAR(200)  NOT NULL,
    slug        VARCHAR(220)  NOT NULL UNIQUE,
    description TEXT          DEFAULT NULL,
    contenu     LONGTEXT      DEFAULT NULL,
    video_url   VARCHAR(500)  DEFAULT NULL,
    audio_url   VARCHAR(500)  DEFAULT NULL,
    image_seq   VARCHAR(255)  DEFAULT NULL,
    fichier_pdf VARCHAR(255)  DEFAULT NULL,
    duree_min   SMALLINT      DEFAULT NULL,
    ordre       SMALLINT      NOT NULL DEFAULT 0,
    actif       TINYINT(1)    NOT NULL DEFAULT 1,
    created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. RESSOURCES PAR SÉQUENCE
CREATE TABLE resources (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sequence_id INT UNSIGNED  NOT NULL,
    nom         VARCHAR(200)  NOT NULL,
    fichier     VARCHAR(255)  NOT NULL,
    type        ENUM('pdf','word','ppt','excel','autre') NOT NULL DEFAULT 'pdf',
    taille_ko   INT UNSIGNED  DEFAULT NULL,
    created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sequence_id) REFERENCES sequences(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. QUIZ
CREATE TABLE quizzes (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sequence_id INT UNSIGNED  NOT NULL,
    titre       VARCHAR(200)  NOT NULL DEFAULT 'Quiz',
    description TEXT          DEFAULT NULL,
    score_min   TINYINT       NOT NULL DEFAULT 70,
    actif       TINYINT(1)    NOT NULL DEFAULT 1,
    created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sequence_id) REFERENCES sequences(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. QUESTIONS
CREATE TABLE questions (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quiz_id    INT UNSIGNED  NOT NULL,
    question   TEXT          NOT NULL,
    type       ENUM('choix_unique','choix_multiple','vrai_faux','reponse_courte') NOT NULL DEFAULT 'choix_unique',
    ordre      SMALLINT      NOT NULL DEFAULT 0,
    points     TINYINT       NOT NULL DEFAULT 1,
    created_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. RÉPONSES
CREATE TABLE answers (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_id INT UNSIGNED  NOT NULL,
    texte       VARCHAR(500)  NOT NULL,
    est_correct TINYINT(1)    NOT NULL DEFAULT 0,
    ordre       SMALLINT      NOT NULL DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. INSCRIPTIONS
CREATE TABLE enrollments (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    course_id  INT UNSIGNED NOT NULL,
    statut     ENUM('actif','expire','rembourse') NOT NULL DEFAULT 'actif',
    paye       TINYINT(1)   NOT NULL DEFAULT 0,
    montant    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_enrollment (user_id, course_id),
    FOREIGN KEY (user_id)   REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. PROGRESSION
CREATE TABLE progress (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    sequence_id     INT UNSIGNED NOT NULL,
    terminee        TINYINT(1)   NOT NULL DEFAULT 0,
    temps_passe_min SMALLINT     NOT NULL DEFAULT 0,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_progress (user_id, sequence_id),
    FOREIGN KEY (user_id)     REFERENCES users(id)     ON DELETE CASCADE,
    FOREIGN KEY (sequence_id) REFERENCES sequences(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. RÉSULTATS QUIZ
CREATE TABLE quiz_results (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    quiz_id    INT UNSIGNED NOT NULL,
    score      TINYINT      NOT NULL DEFAULT 0,
    total      TINYINT      NOT NULL DEFAULT 0,
    reussi     TINYINT(1)   NOT NULL DEFAULT 0,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. PAIEMENTS
CREATE TABLE payments (
    id         INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED  NOT NULL,
    plan       VARCHAR(50)   NOT NULL DEFAULT 'business_plan',
    montant    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    telephone  VARCHAR(20)   DEFAULT NULL,
    operateur  ENUM('mtn','moov','autre') NOT NULL DEFAULT 'mtn',
    reference  VARCHAR(100)  DEFAULT NULL UNIQUE,
    statut     ENUM('en_attente','valide','rejete') NOT NULL DEFAULT 'en_attente',
    note_admin TEXT          DEFAULT NULL,
    created_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. ABONNEMENTS
CREATE TABLE subscriptions (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    plan       VARCHAR(50)  NOT NULL DEFAULT 'decouverte',
    paye       TINYINT(1)   NOT NULL DEFAULT 0,
    statut     ENUM('actif','expire','annule') NOT NULL DEFAULT 'actif',
    debut      DATE         DEFAULT NULL,
    fin        DATE         DEFAULT NULL,
    payment_id INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. CERTIFICATS
CREATE TABLE certificates (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NOT NULL,
    course_id    INT UNSIGNED NOT NULL,
    code_unique  VARCHAR(64)  NOT NULL UNIQUE,
    fichier_pdf  VARCHAR(255) DEFAULT NULL,
    delivre_le   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_certificate (user_id, course_id),
    FOREIGN KEY (user_id)   REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. FAVORIS
CREATE TABLE favorites (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    course_id  INT UNSIGNED NOT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_fav (user_id, course_id),
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. NOTIFICATIONS
CREATE TABLE user_notifications (
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

-- 19. BIBLIOTHÈQUE RESSOURCES
CREATE TABLE library_resources (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titre       VARCHAR(200) NOT NULL,
    description TEXT         DEFAULT NULL,
    type        ENUM('business_plan','social_media','sales_script','autre') NOT NULL DEFAULT 'autre',
    fichier     VARCHAR(255) DEFAULT NULL,
    actif       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. SLIDES ACCUEIL
CREATE TABLE slides (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titre      VARCHAR(200) DEFAULT NULL,
    sous_titre VARCHAR(300) DEFAULT NULL,
    image      VARCHAR(255) DEFAULT NULL,
    lien       VARCHAR(500) DEFAULT NULL,
    ordre      SMALLINT     NOT NULL DEFAULT 0,
    actif      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Installation terminée. Compte admin : admin@lab.bj / admin123' AS status;
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
    tarif_min   ENUM('decouverte','serie_1_2','parcours_complet') NOT NULL DEFAULT 'decouverte',
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
    tarif      ENUM('decouverte','serie_1_2','parcours_complet') NOT NULL,
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
  ADD COLUMN IF NOT EXISTS tarif        ENUM('decouverte','serie_1_2','parcours_complet') NOT NULL DEFAULT 'decouverte',
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

-- 12. Restructuration des tarifs (3 plans : decouverte/serie_1_2/parcours_complet)
ALTER TABLE courses MODIFY COLUMN tarif ENUM('decouverte','serie_1_2','parcours_complet') NOT NULL DEFAULT 'decouverte';

-- 13. Ajouter colonnes optionnelles au module si absentes (module_add.php étendu)
ALTER TABLE modules
  ADD COLUMN IF NOT EXISTS objectifs       TEXT     DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS nb_sequences_prev INT    DEFAULT 0,
  ADD COLUMN IF NOT EXISTS nb_activites_prev INT    DEFAULT 0,
  ADD COLUMN IF NOT EXISTS duree_min       SMALLINT DEFAULT NULL;

SELECT 'Migration 003 terminée avec succès.' as status;
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
-- ============================================================
-- LOT 2 : Profil avancé, Sessions, SEO, XP, Défis, Deadline
-- ============================================================

ALTER TABLE users ADD COLUMN IF NOT EXISTS universite    VARCHAR(200) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS filiere       VARCHAR(200) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS promotion     VARCHAR(100) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS bio           TEXT DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS xp_total      INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_seen     TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS session_token VARCHAR(64) DEFAULT NULL;

ALTER TABLE sequences ADD COLUMN IF NOT EXISTS xp_reward INT UNSIGNED NOT NULL DEFAULT 10;
ALTER TABLE sequences ADD COLUMN IF NOT EXISTS deadline  DATETIME DEFAULT NULL;

ALTER TABLE courses ADD COLUMN IF NOT EXISTS seo_title       VARCHAR(255) DEFAULT NULL;
ALTER TABLE courses ADD COLUMN IF NOT EXISTS seo_description TEXT DEFAULT NULL;
ALTER TABLE courses ADD COLUMN IF NOT EXISTS seo_keywords    VARCHAR(500) DEFAULT NULL;
ALTER TABLE courses ADD COLUMN IF NOT EXISTS duree_acces     INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = illimité';

ALTER TABLE enrollments ADD COLUMN IF NOT EXISTS expires_at DATETIME DEFAULT NULL;

CREATE TABLE IF NOT EXISTS challenges (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titre       VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    semaine     DATE NOT NULL,
    xp_reward   INT UNSIGNED NOT NULL DEFAULT 50,
    actif       TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Index pour performances
CREATE INDEX IF NOT EXISTS idx_users_xp ON users(xp_total DESC);
CREATE INDEX IF NOT EXISTS idx_enrollments_expires ON enrollments(expires_at);
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
-- ============================================================
-- LOT 4 : Quiz interactif + Éditeur riche + Player vidéo avancé
-- ============================================================

-- Ajouter explication/feedback par question
ALTER TABLE questions 
  ADD COLUMN IF NOT EXISTS explication TEXT DEFAULT NULL COMMENT 'Explication affichée après réponse',
  ADD COLUMN IF NOT EXISTS image_url VARCHAR(500) DEFAULT NULL COMMENT 'Image illustrant la question';

-- Ajouter feedback par réponse
ALTER TABLE answers
  ADD COLUMN IF NOT EXISTS feedback TEXT DEFAULT NULL COMMENT 'Message si cette réponse est choisie';

-- Chapitres vidéo
CREATE TABLE IF NOT EXISTS video_chapters (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sequence_id INT UNSIGNED NOT NULL,
    titre       VARCHAR(200) NOT NULL,
    timecode    INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'En secondes',
    ordre       SMALLINT NOT NULL DEFAULT 0,
    FOREIGN KEY (sequence_id) REFERENCES sequences(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Questions intégrées dans la vidéo
CREATE TABLE IF NOT EXISTS video_questions (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sequence_id INT UNSIGNED NOT NULL,
    question    TEXT NOT NULL,
    timecode    INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Apparaît à ce moment en secondes',
    reponse_correcte VARCHAR(500) NOT NULL,
    choix       JSON DEFAULT NULL COMMENT 'Array de choix multiples',
    explication TEXT DEFAULT NULL,
    FOREIGN KEY (sequence_id) REFERENCES sequences(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Type de contenu séquence étendu
ALTER TABLE sequences 
  ADD COLUMN IF NOT EXISTS type_contenu ENUM('video','texte','ebook','quiz','assignment','audio') DEFAULT 'video',
  ADD COLUMN IF NOT EXISTS contenu_riche LONGTEXT DEFAULT NULL COMMENT 'Contenu HTML riche TinyMCE';

-- ============================================================
--  Migrations fusionnées (002 à 013) — fusion automatique
--  Toutes idempotentes (IF NOT EXISTS), exécution en une seule fois
-- ============================================================

-- ---- migrations/002_missing_features.sql ----
-- ============================================================
--  Migration 002 — Fonctionnalités manquantes
-- ============================================================


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
    tarif_min   ENUM('decouverte','serie_1_2','parcours_complet') NOT NULL DEFAULT 'decouverte',
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
    tarif      ENUM('decouverte','serie_1_2','parcours_complet') NOT NULL,
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

-- ---- migrations/003_fix_schema.sql ----
-- ============================================================
--  Migration 003 — Correction schema pour bases existantes
--  À exécuter si vous avez l'erreur "Unknown column 'category_id'"
--  ou si certaines tables sont manquantes.
-- ============================================================


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
  ADD COLUMN IF NOT EXISTS tarif        ENUM('decouverte','serie_1_2','parcours_complet') NOT NULL DEFAULT 'decouverte',
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

-- 12. Restructuration des tarifs (3 plans : decouverte/serie_1_2/parcours_complet)
ALTER TABLE courses MODIFY COLUMN tarif ENUM('decouverte','serie_1_2','parcours_complet') NOT NULL DEFAULT 'decouverte';

-- 13. Ajouter colonnes optionnelles au module si absentes (module_add.php étendu)
ALTER TABLE modules
  ADD COLUMN IF NOT EXISTS objectifs       TEXT     DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS nb_sequences_prev INT    DEFAULT 0,
  ADD COLUMN IF NOT EXISTS nb_activites_prev INT    DEFAULT 0,
  ADD COLUMN IF NOT EXISTS duree_min       SMALLINT DEFAULT NULL;

SELECT 'Migration 003 terminée avec succès.' as status;

-- ---- migrations/004_complete_schema.sql ----
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

-- ---- migrations/005_lot1.sql ----
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

-- ---- migrations/006_lot2.sql ----
-- ============================================================
-- LOT 2 : Profil avancé, Sessions, SEO, XP, Défis, Deadline
-- ============================================================

ALTER TABLE users ADD COLUMN IF NOT EXISTS universite    VARCHAR(200) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS filiere       VARCHAR(200) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS promotion     VARCHAR(100) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS bio           TEXT DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS xp_total      INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_seen     TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS session_token VARCHAR(64) DEFAULT NULL;

ALTER TABLE sequences ADD COLUMN IF NOT EXISTS xp_reward INT UNSIGNED NOT NULL DEFAULT 10;
ALTER TABLE sequences ADD COLUMN IF NOT EXISTS deadline  DATETIME DEFAULT NULL;

ALTER TABLE courses ADD COLUMN IF NOT EXISTS seo_title       VARCHAR(255) DEFAULT NULL;
ALTER TABLE courses ADD COLUMN IF NOT EXISTS seo_description TEXT DEFAULT NULL;
ALTER TABLE courses ADD COLUMN IF NOT EXISTS seo_keywords    VARCHAR(500) DEFAULT NULL;
ALTER TABLE courses ADD COLUMN IF NOT EXISTS duree_acces     INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = illimité';

ALTER TABLE enrollments ADD COLUMN IF NOT EXISTS expires_at DATETIME DEFAULT NULL;

CREATE TABLE IF NOT EXISTS challenges (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titre       VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    semaine     DATE NOT NULL,
    xp_reward   INT UNSIGNED NOT NULL DEFAULT 50,
    actif       TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Index pour performances
CREATE INDEX IF NOT EXISTS idx_users_xp ON users(xp_total DESC);
CREATE INDEX IF NOT EXISTS idx_enrollments_expires ON enrollments(expires_at);

-- ---- migrations/007_lot3.sql ----
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

-- ---- migrations/008_lot4.sql ----
-- ============================================================
-- LOT 4 : Quiz interactif + Éditeur riche + Player vidéo avancé
-- ============================================================

-- Ajouter explication/feedback par question
ALTER TABLE questions 
  ADD COLUMN IF NOT EXISTS explication TEXT DEFAULT NULL COMMENT 'Explication affichée après réponse',
  ADD COLUMN IF NOT EXISTS image_url VARCHAR(500) DEFAULT NULL COMMENT 'Image illustrant la question';

-- Ajouter feedback par réponse
ALTER TABLE answers
  ADD COLUMN IF NOT EXISTS feedback TEXT DEFAULT NULL COMMENT 'Message si cette réponse est choisie';

-- Chapitres vidéo
CREATE TABLE IF NOT EXISTS video_chapters (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sequence_id INT UNSIGNED NOT NULL,
    titre       VARCHAR(200) NOT NULL,
    timecode    INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'En secondes',
    ordre       SMALLINT NOT NULL DEFAULT 0,
    FOREIGN KEY (sequence_id) REFERENCES sequences(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Questions intégrées dans la vidéo
CREATE TABLE IF NOT EXISTS video_questions (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sequence_id INT UNSIGNED NOT NULL,
    question    TEXT NOT NULL,
    timecode    INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Apparaît à ce moment en secondes',
    reponse_correcte VARCHAR(500) NOT NULL,
    choix       JSON DEFAULT NULL COMMENT 'Array de choix multiples',
    explication TEXT DEFAULT NULL,
    FOREIGN KEY (sequence_id) REFERENCES sequences(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Type de contenu séquence étendu
ALTER TABLE sequences 
  ADD COLUMN IF NOT EXISTS type_contenu ENUM('video','texte','ebook','quiz','assignment','audio') DEFAULT 'video',
  ADD COLUMN IF NOT EXISTS contenu_riche LONGTEXT DEFAULT NULL COMMENT 'Contenu HTML riche TinyMCE';

-- ---- migrations/009_2days.sql ----
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

-- ---- migrations/010_lot5.sql ----
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

-- ---- migrations/011_lot6.sql ----
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

-- ---- migrations/012_session1.sql ----
-- ============================================================
--  012_session1.sql
--  - Rôle Instructeur
--  - Tirage aléatoire de questions depuis la banque
--  - Suivi des relances d'inactivité (7j / 30j)
-- ============================================================

ALTER TABLE users MODIFY COLUMN role ENUM('etudiant','coach','moderateur','instructeur') NOT NULL DEFAULT 'etudiant';

ALTER TABLE quizzes ADD COLUMN IF NOT EXISTS bank_categorie VARCHAR(100) DEFAULT NULL COMMENT 'Catégorie de la banque de questions à piocher, NULL = toutes';
ALTER TABLE quizzes ADD COLUMN IF NOT EXISTS bank_nb_questions TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Nombre de questions tirées aléatoirement depuis la banque, 0 = désactivé';

ALTER TABLE users ADD COLUMN IF NOT EXISTS inactive7_notified_at DATETIME DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS inactive30_notified_at DATETIME DEFAULT NULL;

-- ---- migrations/013_sessions2a5.sql ----
-- ============================================================
--  013_sessions2a5.sql
--  Session 2 : Rubriques + audio (feedback coach / soumission étudiant)
--  Session 3 : Forum général + réactions (déjà supportées en partie) + commentaires
--  Session 4 : Bundles de cours (certif multi-cours) + révocation + type certificat
--  Session 5 : Factures PDF + remboursement FedaPay + pages légales
-- ============================================================

-- ----------------------------------------------------------------
-- SESSION 2 — Rubriques d'évaluation + audio
-- ----------------------------------------------------------------

-- Harmoniser la table rubriques (010 a créé assignment_id+nom, 011 a créé titre).
-- On s'assure que les deux ensembles de colonnes existent pour ne pas casser
-- l'un ou l'autre schéma selon l'ordre d'application des migrations précédentes.
ALTER TABLE rubriques ADD COLUMN IF NOT EXISTS assignment_id INT UNSIGNED DEFAULT NULL;
ALTER TABLE rubriques ADD COLUMN IF NOT EXISTS titre VARCHAR(200) DEFAULT NULL;
ALTER TABLE rubriques ADD COLUMN IF NOT EXISTS nom VARCHAR(200) DEFAULT NULL;

-- Lien soumission -> rubrique + notes par critère (au cas où 011 n'a pas tourné)
ALTER TABLE assignment_submissions ADD COLUMN IF NOT EXISTS rubrique_id INT UNSIGNED DEFAULT NULL;
ALTER TABLE assignment_submissions ADD COLUMN IF NOT EXISTS rubrique_notes JSON DEFAULT NULL;

-- Audio : soumission étudiant + feedback coach
ALTER TABLE assignment_submissions ADD COLUMN IF NOT EXISTS audio_path VARCHAR(500) DEFAULT NULL;
ALTER TABLE assignment_submissions ADD COLUMN IF NOT EXISTS audio_feedback_path VARCHAR(500) DEFAULT NULL;

-- Type 'audio' pour les assignments (le ENUM existant le couvre déjà via database_complet.sql,
-- mais on le garantit ici pour les bases déjà migrées avec un ENUM restreint)
ALTER TABLE assignments MODIFY COLUMN type ENUM('texte','fichier','video','audio') NOT NULL DEFAULT 'texte';

-- ----------------------------------------------------------------
-- SESSION 3 — Commentaires génériques (déjà supportés via table `comments`)
-- Rien à ajouter : `comments` (sequence_id, user_id, contenu) existe déjà
-- et `video_comments` gère les commentaires horodatés vidéo.
-- `forum_topics.course_id` est déjà NULLABLE (forum général).
-- ----------------------------------------------------------------

-- ----------------------------------------------------------------
-- SESSION 4 — Bundles (parcours multi-cours) -> certificats
-- ----------------------------------------------------------------

-- Réutilise bundles / bundle_courses créés par 010/011. On ajoute ordre.
ALTER TABLE bundle_courses ADD COLUMN IF NOT EXISTS ordre SMALLINT NOT NULL DEFAULT 0;

-- Certificats : rattachement à un bundle (multi-cours), type, révocation
ALTER TABLE certificates ADD COLUMN IF NOT EXISTS bundle_id INT UNSIGNED DEFAULT NULL;
ALTER TABLE certificates ADD COLUMN IF NOT EXISTS type ENUM('completion','connaissance') NOT NULL DEFAULT 'completion';
ALTER TABLE certificates ADD COLUMN IF NOT EXISTS revoque TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE certificates ADD COLUMN IF NOT EXISTS revoque_le DATETIME DEFAULT NULL;

-- course_id doit pouvoir être NULL pour un certificat de bundle
ALTER TABLE certificates MODIFY COLUMN course_id INT UNSIGNED DEFAULT NULL;

-- Seuil de connaissance par cours (score quiz moyen requis pour le certificat "connaissance")
ALTER TABLE courses ADD COLUMN IF NOT EXISTS seuil_connaissance TINYINT UNSIGNED NOT NULL DEFAULT 70;

-- ----------------------------------------------------------------
-- SESSION 5 — Factures PDF + remboursement FedaPay
-- ----------------------------------------------------------------

ALTER TABLE payments ADD COLUMN IF NOT EXISTS fedapay_txn_id VARCHAR(50) DEFAULT NULL;
ALTER TABLE payments ADD COLUMN IF NOT EXISTS refund_status ENUM('aucun','demande','rembourse','refuse') NOT NULL DEFAULT 'aucun';
ALTER TABLE payments ADD COLUMN IF NOT EXISTS refund_demande_le DATETIME DEFAULT NULL;
