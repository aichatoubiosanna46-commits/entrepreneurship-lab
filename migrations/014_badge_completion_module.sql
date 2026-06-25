-- 014_badge_completion_module.sql
-- Ajoute le type de condition "completion_module" aux badges (badge attribué
-- à la complétion d'un module, en plus de la complétion d'un cours entier),
-- conformément au CDC fonctionnel Ariziki (section Gamification / Badges
-- visuels par module).
--
-- À exécuter manuellement sur la base de données (non appliquée automatiquement).

ALTER TABLE badges
    MODIFY condition_type ENUM('completion_cours','completion_module','score_quiz','manuel','xp_total')
    NOT NULL DEFAULT 'manuel';

ALTER TABLE badges
    ADD COLUMN condition_module_id INT UNSIGNED DEFAULT NULL AFTER condition_course_id;
