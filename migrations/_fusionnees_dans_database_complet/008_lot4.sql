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
