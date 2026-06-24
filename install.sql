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
    tarif         ENUM('decouverte','essentiel','business_plan','lancement') NOT NULL DEFAULT 'decouverte',
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
