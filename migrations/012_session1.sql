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
