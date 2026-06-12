-- ============================================================
--  entrepreneurship-lab — Structure MOOC complète
--  Hiérarchie : Cours > Modules > Séquences
--  Encodage : UTF-8 | Moteur : InnoDB
-- ============================================================

CREATE DATABASE IF NOT EXISTS entrepreneurship_lab
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE entrepreneurship_lab;

-- ------------------------------------------------------------
-- 1. UTILISATEURS (Administrateur, Formateur, Apprenant)
-- ------------------------------------------------------------
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

-- ------------------------------------------------------------
-- 2. ADMINISTRATEURS (table séparée — accès backoffice)
-- ------------------------------------------------------------
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

-- ------------------------------------------------------------
-- 3. CATÉGORIES DE COURS
-- ------------------------------------------------------------
CREATE TABLE categories (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom        VARCHAR(120) NOT NULL,
    slug       VARCHAR(140) NOT NULL UNIQUE,
    icone      VARCHAR(80)  DEFAULT 'ti-folder',
    couleur    VARCHAR(7)   DEFAULT '#BA7517',
    actif      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO categories (nom, slug, icone, couleur) VALUES
('Création d\'entreprise', 'creation-entreprise', 'ti-building-store', '#BA7517'),
('Marketing digital',      'marketing-digital',   'ti-device-mobile',  '#3B6D11'),
('Finance & gestion',      'finance-gestion',     'ti-chart-line',     '#534AB7'),
('Leadership',             'leadership',          'ti-users',          '#0F6E56');

-- ------------------------------------------------------------
-- 4. COURS (niveau 1 — anciennement "modules")
-- ------------------------------------------------------------
CREATE TABLE courses (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id   INT UNSIGNED  NOT NULL,
    formateur_id  INT UNSIGNED  DEFAULT NULL,  -- FK vers users (rôle formateur)
    titre         VARCHAR(200)  NOT NULL,
    slug          VARCHAR(220)  NOT NULL UNIQUE,
    description   TEXT          DEFAULT NULL,
    miniature     VARCHAR(255)  DEFAULT NULL,
    video_intro   VARCHAR(500)  DEFAULT NULL,
    niveau        ENUM('debutant','intermediaire','avance') NOT NULL DEFAULT 'debutant',
    type          ENUM('gratuit','payant') NOT NULL DEFAULT 'gratuit',
    tarif         ENUM('decouverte','business_plan','lancement') NOT NULL DEFAULT 'decouverte',
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

-- ------------------------------------------------------------
-- 5. MODULES (niveau 2 — regroupent des séquences dans un cours)
-- ------------------------------------------------------------
CREATE TABLE modules (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id   INT UNSIGNED  NOT NULL,
    titre       VARCHAR(200)  NOT NULL,
    description TEXT          DEFAULT NULL,
    ordre       SMALLINT      NOT NULL DEFAULT 0,
    actif       TINYINT(1)    NOT NULL DEFAULT 1,
    created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6. SÉQUENCES (niveau 3 — contenu pédagogique réel)
-- ------------------------------------------------------------
CREATE TABLE sequences (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module_id   INT UNSIGNED  NOT NULL,
    titre       VARCHAR(200)  NOT NULL,
    slug        VARCHAR(220)  NOT NULL UNIQUE,
    description TEXT          DEFAULT NULL,
    contenu     LONGTEXT      DEFAULT NULL,     -- texte riche
    video_url   VARCHAR(500)  DEFAULT NULL,     -- YouTube/Vimeo
    audio_url   VARCHAR(500)  DEFAULT NULL,     -- fichier audio
    image_seq   VARCHAR(255)  DEFAULT NULL,     -- image illustration
    fichier_pdf VARCHAR(255)  DEFAULT NULL,     -- PDF joint
    duree_min   SMALLINT      DEFAULT NULL,
    ordre       SMALLINT      NOT NULL DEFAULT 0,
    actif       TINYINT(1)    NOT NULL DEFAULT 1,
    created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 7. RESSOURCES TÉLÉCHARGEABLES (par séquence)
-- ------------------------------------------------------------
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

-- ------------------------------------------------------------
-- 8. ACTIVITÉS PÉDAGOGIQUES (devoirs, exercices, cas pratiques…)
-- ------------------------------------------------------------
CREATE TABLE activities (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sequence_id INT UNSIGNED  NOT NULL,
    titre       VARCHAR(200)  NOT NULL,
    type        ENUM('devoir','exercice','cas_pratique','travail_pratique') NOT NULL DEFAULT 'exercice',
    consigne    TEXT          NOT NULL,
    fichier     VARCHAR(255)  DEFAULT NULL,   -- fichier joint à l'activité
    date_limite DATETIME      DEFAULT NULL,
    actif       TINYINT(1)    NOT NULL DEFAULT 1,
    created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sequence_id) REFERENCES sequences(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 9. QUIZ (par séquence)
-- ------------------------------------------------------------
CREATE TABLE quizzes (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sequence_id INT UNSIGNED  NOT NULL,
    titre       VARCHAR(200)  NOT NULL DEFAULT 'Quiz',
    description TEXT          DEFAULT NULL,
    score_min   TINYINT       NOT NULL DEFAULT 70,  -- % minimum pour valider
    actif       TINYINT(1)    NOT NULL DEFAULT 1,
    created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sequence_id) REFERENCES sequences(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 10. QUESTIONS DE QUIZ
-- ------------------------------------------------------------
CREATE TABLE questions (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quiz_id    INT UNSIGNED  NOT NULL,
    question   TEXT          NOT NULL,
    type       ENUM('choix_unique','choix_multiple','vrai_faux','reponse_courte','reponse_longue') NOT NULL DEFAULT 'choix_unique',
    ordre      SMALLINT      NOT NULL DEFAULT 0,
    points     TINYINT       NOT NULL DEFAULT 1,
    created_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 11. RÉPONSES DE QUESTIONS
-- ------------------------------------------------------------
CREATE TABLE answers (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_id INT UNSIGNED  NOT NULL,
    texte       VARCHAR(500)  NOT NULL,
    est_correct TINYINT(1)    NOT NULL DEFAULT 0,
    ordre       SMALLINT      NOT NULL DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 12. INSCRIPTIONS (enrollments)
-- ------------------------------------------------------------
CREATE TABLE enrollments (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    course_id   INT UNSIGNED NOT NULL,
    statut      ENUM('actif','expire','rembourse') NOT NULL DEFAULT 'actif',
    paye        TINYINT(1)   NOT NULL DEFAULT 0,
    montant     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_enrollment (user_id, course_id),
    FOREIGN KEY (user_id)   REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 13. PROGRESSION DES APPRENANTS (par séquence)
-- ------------------------------------------------------------
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

-- ------------------------------------------------------------
-- 14. RÉSULTATS DE QUIZ
-- ------------------------------------------------------------
CREATE TABLE quiz_results (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    quiz_id     INT UNSIGNED NOT NULL,
    score       TINYINT      NOT NULL DEFAULT 0,
    total       TINYINT      NOT NULL DEFAULT 0,
    reussi      TINYINT(1)   NOT NULL DEFAULT 0,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 15. PAIEMENTS
-- ------------------------------------------------------------
CREATE TABLE payments (
    id             INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    user_id        INT UNSIGNED  NOT NULL,
    course_id      INT UNSIGNED  NOT NULL,
    reference      VARCHAR(100)  NOT NULL UNIQUE,
    montant        DECIMAL(10,2) NOT NULL,
    devise         VARCHAR(10)   NOT NULL DEFAULT 'XOF',
    methode        ENUM('mtn_momo','moov_money','cinetpay','fedapay','paypal','gratuit') NOT NULL,
    statut         ENUM('en_attente','valide','echoue','rembourse') NOT NULL DEFAULT 'en_attente',
    donnees_brutes JSON          DEFAULT NULL,
    created_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)   REFERENCES users(id)    ON DELETE RESTRICT,
    FOREIGN KEY (course_id) REFERENCES courses(id)  ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 16. CERTIFICATS
-- ------------------------------------------------------------
CREATE TABLE certificates (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    course_id   INT UNSIGNED NOT NULL,
    code_unique VARCHAR(64)  NOT NULL UNIQUE,
    fichier_pdf VARCHAR(255) DEFAULT NULL,
    delivre_le  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_certificate (user_id, course_id),
    FOREIGN KEY (user_id)   REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 17. SLIDES HOMEPAGE
-- ------------------------------------------------------------
CREATE TABLE slides (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titre       VARCHAR(200)  NOT NULL,
    sous_titre  VARCHAR(300)  DEFAULT NULL,
    image       VARCHAR(255)  NOT NULL,
    lien        VARCHAR(500)  DEFAULT NULL,
    texte_btn   VARCHAR(80)   DEFAULT 'En savoir plus',
    ordre       SMALLINT      NOT NULL DEFAULT 0,
    actif       TINYINT(1)    NOT NULL DEFAULT 1,
    created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 18. CHAT IA (historique)
-- ------------------------------------------------------------
CREATE TABLE ia_messages (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    sequence_id INT UNSIGNED DEFAULT NULL,
    role        ENUM('user','assistant') NOT NULL,
    contenu     TEXT         NOT NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)     REFERENCES users(id)     ON DELETE CASCADE,
    FOREIGN KEY (sequence_id) REFERENCES sequences(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 19. FORUM (discussions par cours)
-- ------------------------------------------------------------
CREATE TABLE forum (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    user_id   INT UNSIGNED NOT NULL,
    parent_id INT UNSIGNED DEFAULT NULL,
    message   TEXT         NOT NULL,
    created_at TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES forum(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- NOTE : Le compte admin est créé via setup_admin.php
-- ============================================================
