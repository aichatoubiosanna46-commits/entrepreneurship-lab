-- ============================================================
--  2026_06_restructure_tarifs.sql
--  Remplace les 4 anciens tarifs (decouverte / essentiel /
--  business_plan / lancement) par 3 nouveaux plans :
--    - decouverte       (Phase 0 — gratuit, inchangé)
--    - serie_1_2        (Séries 1+2 — 15 000 FCFA, fusion essentiel+business_plan)
--    - parcours_complet (Parcours Complet — 25 000 FCFA, ex-lancement)
--  À exécuter UNE SEULE FOIS sur une base existante.
-- ============================================================

-- 1. Élargir temporairement l'ENUM pour accepter les nouvelles valeurs
ALTER TABLE courses
  MODIFY COLUMN tarif ENUM('decouverte','essentiel','business_plan','lancement','serie_1_2','parcours_complet')
  NOT NULL DEFAULT 'decouverte';

-- 2. Migrer les données existantes
UPDATE courses SET tarif = 'serie_1_2'        WHERE tarif IN ('essentiel','business_plan');
UPDATE courses SET tarif = 'parcours_complet' WHERE tarif = 'lancement';

-- 3. Réduire l'ENUM aux 3 valeurs finales
ALTER TABLE courses
  MODIFY COLUMN tarif ENUM('decouverte','serie_1_2','parcours_complet')
  NOT NULL DEFAULT 'decouverte';

-- 4. Idem pour library_resources.tarif_min (si la colonne existe)
ALTER TABLE library_resources
  MODIFY COLUMN tarif_min ENUM('decouverte','essentiel','business_plan','lancement','serie_1_2','parcours_complet')
  NOT NULL DEFAULT 'decouverte';

UPDATE library_resources SET tarif_min = 'serie_1_2'        WHERE tarif_min IN ('essentiel','business_plan');
UPDATE library_resources SET tarif_min = 'parcours_complet' WHERE tarif_min = 'lancement';

ALTER TABLE library_resources
  MODIFY COLUMN tarif_min ENUM('decouverte','serie_1_2','parcours_complet')
  NOT NULL DEFAULT 'decouverte';

-- 5. subscriptions.plan / payments.plan sont des VARCHAR(50) libres :
--    on aligne les anciennes valeurs textuelles sur les nouveaux plans.
UPDATE subscriptions SET plan = 'serie_1_2'        WHERE plan IN ('essentiel','business_plan');
UPDATE subscriptions SET plan = 'parcours_complet' WHERE plan = 'lancement';

UPDATE payments SET plan = 'serie_1_2'        WHERE plan IN ('essentiel','business_plan');
UPDATE payments SET plan = 'parcours_complet' WHERE plan = 'lancement';
