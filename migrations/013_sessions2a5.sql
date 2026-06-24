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
