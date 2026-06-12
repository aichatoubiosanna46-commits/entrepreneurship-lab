-- ============================================================
--  patch_tarif.sql — Ajout de la colonne tarif sur courses
--  À exécuter une seule fois sur la base existante
-- ============================================================

ALTER TABLE courses
  ADD COLUMN tarif ENUM('decouverte','business_plan','lancement') NOT NULL DEFAULT 'decouverte'
  AFTER type;

-- Index pour filtrer rapidement par tarif
CREATE INDEX idx_courses_tarif ON courses (tarif);
