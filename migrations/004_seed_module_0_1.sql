-- ============================================================
-- 004_seed_module_0_1.sql
-- Seed du contenu réel du Module 0.1 — "Le Déclic Entrepreneurial"
-- (d'après MODULE_0.1_Declic_Entrepreneurial_v1.docx)
--
-- Pré-requis : exécuter 003_module0_features.sql avant ce fichier.
-- ============================================================

-- ── Badge "Explorateur Entrepreneurial" ──────────────────────
INSERT INTO badges (titre, slug, description, icone, couleur)
VALUES (
    'Explorateur Entrepreneurial',
    'explorateur-entrepreneurial',
    'Décerné pour avoir terminé le Module 0.1 : être sorti sur le terrain, avoir observé son environnement et rédigé sa première Note de Positionnement.',
    '🏅',
    '#0F6E56'
);
SET @badge_id = LAST_INSERT_ID();

-- ── Cours ─────────────────────────────────────────────────────
SET @category_id = (SELECT id FROM categories WHERE slug = 'creation-entreprise' LIMIT 1);

INSERT INTO courses
    (category_id, titre, sous_titre, slug, description, niveau, langue, type, tarif, prix,
     duree_heures, certificat, quiz_final, note_min_certificat, badge_id, next_course_id,
     actif, statut, ordre, created_by)
VALUES (
    @category_id,
    'Module 0.1 — Le Déclic Entrepreneurial',
    'Phase 0 — Onboarding : ton premier acte d''entrepreneur',
    'module-0-1-declic-entrepreneurial',
    'Module gratuit d''onboarding (Phase 0 du parcours EntrepreneurshipLab). Tu vas comprendre pourquoi entreprendre pendant tes études, sortir sur le terrain observer ton environnement avec le Radar à Opportunités, et rédiger ta première Note de Positionnement.',
    'debutant', 'Français', 'gratuit', 'decouverte', 0,
    1.0, 0, 0, 60,
    @badge_id, NULL,
    1, 'publie', 0, NULL
);
SET @course_id = LAST_INSERT_ID();

-- ── Module unique "Phase 0 — Onboarding" ─────────────────────
INSERT INTO modules (course_id, titre, description, ordre, actif)
VALUES (@course_id, 'Phase 0 — Onboarding', 'Les 7 phases du Module 0.1 : Ancrage, Rappel, Objectifs, Contenu, Pratique, Livrable, Transfert.', 1, 1);
SET @module_id = LAST_INSERT_ID();

-- ============================================================
-- SÉQUENCE 1 — ① ANCRAGE
-- ============================================================
INSERT INTO sequences (module_id, titre, slug, description, contenu, video_url, duree_min, ordre, actif)
VALUES (
    @module_id,
    '① Ancrage — Et toi, tu attends quoi ?',
    'm01-ancrage',
    'Créer la tension intellectuelle (8 minutes).',
    '<p><strong>« 70 000 »</strong></p>
<p>Chaque année, 60 000 à 70 000 Béninois obtiennent leur diplôme. Le marché formel n''absorbe que 15 000 d''entre eux. Les 55 000 autres ? Ils attendent. En moyenne 3 à 7 ans.</p>
<p>Ces gens que tu vois sur les marchés de Cotonou ou Parakou — certains ont un diplôme universitaire dans leur poche. Pourtant ils ont arrêté d''attendre. Ils ont agi. Pendant leurs études.</p>
<p><strong>Et toi, tu attends quoi ?</strong></p>
<table border="1" cellpadding="6">
<tr><th>Donnée</th><th>Source</th></tr>
<tr><td>30 % des Béninois de 15–35 ans en chômage ou sous-emploi</td><td>INSAE, 2024</td></tr>
<tr><td>3 à 7 ans : délai moyen d''insertion professionnelle après le diplôme</td><td>ANPE Bénin, 2023</td></tr>
<tr><td>Moins de 40 % des entreprises créées au Bénin survivent au-delà de 3 ans</td><td>CePEPE, 2023</td></tr>
</table>',
    NULL, 8, 1, 1
);
SET @seq_ancrage = LAST_INSERT_ID();

INSERT INTO activities (sequence_id, titre, type, consigne, actif)
VALUES (
    @seq_ancrage,
    'Question d''activation',
    'exercice',
    'Selon vous, qu''est-ce qui empêche un étudiant béninois de commencer à entreprendre pendant ses études — avant même d''avoir son diplôme ?\n\n(Réponse libre obligatoire, minimum 3 mots. Non notée, aucun feedback correctif. Cette réponse vous sera rappelée en Phase ⑦ — Transfert.)',
    1
);
SET @act_activation = LAST_INSERT_ID();

-- ============================================================
-- SÉQUENCE 2 — ② RAPPEL
-- ============================================================
INSERT INTO sequences (module_id, titre, slug, description, contenu, prerequis_sequence_id, duree_min, ordre, actif)
VALUES (
    @module_id,
    '② Rappel — Tu ne pars pas de zéro',
    'm01-rappel',
    'Mobiliser les acquis existants (4 minutes, non noté).',
    '<p>C''est le premier module du parcours. Il n''y a pas de livrable précédent à rappeler. Le rappel porte donc sur tes expériences de vie informelles — commerce, services, débrouillardise — pour te montrer que tu sais déjà des choses utiles.</p>',
    @seq_ancrage, 4, 2, 1
);
SET @seq_rappel = LAST_INSERT_ID();

INSERT INTO activities (sequence_id, titre, type, consigne, options_json, actif)
VALUES (
    @seq_rappel,
    'Avez-vous déjà rendu un service à quelqu''un ?',
    'auto_evaluation',
    'Avant de commencer : avez-vous déjà rendu un service, aidé quelqu''un, ou résolu un problème pour quelqu''un — même sans être payé, même de manière informelle ?',
    JSON_OBJECT(
        'boutons', JSON_ARRAY('Oui, ça m''est arrivé', 'Non, jamais vraiment', 'J''y réfléchis'),
        'messages', JSON_OBJECT(
            'Oui, ça m''est arrivé', 'Parfait. Ce que tu as fait, c''est de l''entrepreneuriat informel. Ce module va te donner la méthode pour transformer ça en quelque chose de structuré.',
            'Non, jamais vraiment', 'Pas de problème. Beaucoup de grands entrepreneurs ont dit la même chose au départ. Tu es exactement là où il faut être.',
            'J''y réfléchis', 'C''est déjà une bonne question. Continue — les réponses vont venir.'
        ),
        'reponse_libre', false
    ),
    1
);

-- ============================================================
-- SÉQUENCE 3 — ③ OBJECTIFS
-- ============================================================
INSERT INTO sequences (module_id, titre, slug, description, contenu, prerequis_sequence_id, duree_min, ordre, actif)
VALUES (
    @module_id,
    '③ Objectifs',
    'm01-objectifs',
    'Formuler les bénéfices concrets (3 minutes).',
    '<p>À la fin de ce module, je serai capable de :</p>
<ul>
<li><strong>Expliquer</strong> pourquoi entreprendre pendant ses études est une réponse logique au marché de l''emploi béninois.</li>
<li><strong>Utiliser</strong> le Radar à Opportunités pour observer mon environnement avec les yeux d''un entrepreneur.</li>
<li><strong>Construire</strong> ma première Note de Positionnement à partir d''une observation terrain réelle.</li>
</ul>',
    @seq_rappel, 3, 3, 1
);
SET @seq_objectifs = LAST_INSERT_ID();

-- ============================================================
-- SÉQUENCE 4 — ④ CONTENU (Niveau 1, 2, 3 + Quiz)
-- ============================================================
INSERT INTO sequences (module_id, titre, slug, description, contenu, prerequis_sequence_id, duree_min, ordre, actif)
VALUES (
    @module_id,
    '④ Contenu — Pourquoi et comment entreprendre pendant ses études',
    'm01-contenu',
    '3 niveaux emboîtés : Socle obligatoire → Approfondissement → Expert.',
    '<h3>Niveau 1 — Socle (obligatoire, quiz 70%)</h3>

<h4>Séquence 1 — Concept fondateur : pourquoi entreprendre pendant les études ?</h4>
<p>L''entrepreneuriat étudiant n''est pas une tendance. C''est une réponse logique à un déséquilibre structurel du marché du travail béninois. Le marché formel crée moins d''emplois que le système éducatif ne produit de diplômés. Ce n''est pas provisoire — c''est la réalité d''une économie en développement à croissance rapide.</p>
<p>Entreprendre pendant les études, ce n''est pas sacrifier ses études. C''est les utiliser différemment. L''étudiant dispose de trois actifs que la plupart des entrepreneurs adultes n''ont plus : du temps disponible, un accès gratuit à un réseau académique, et l''absence de charges financières majeures. Ces actifs ont une durée de vie limitée.</p>
<table border="1" cellpadding="6">
<tr><th>Parcours classique</th><th>Parcours EntrepreneurshipLab</th></tr>
<tr><td>Études → Diplôme<br>Recherche d''emploi (3–7 ans)<br>Premier emploi précaire<br>Accumulation lente du capital<br>Éventuelle création à 35+ ans</td>
<td>Études + Observation terrain<br>Premier projet testé (coût zéro)<br>Premiers revenus pendant les études<br>Diplôme + entreprise fonctionnelle<br>Indépendance économique au départ</td></tr>
</table>

<h4>Séquence 2 — Ancrage terrain béninois : le cas Kossi</h4>
<p>Kossi avait 22 ans, en 2e année de Licence à l''Université de Parakou. Il observait un problème simple chaque jour : les étudiants perdaient du temps à chercher des repas le midi autour du campus. Aucune solution structurée n''existait.</p>
<p>Il a testé une idée en 3 semaines — sans capital, sans statut juridique, sans local. Trois points de collecte de commandes via WhatsApp. Livraison à vélo. Paiement par Mobile Money. En 6 mois, ses frais de scolarité de l''année suivante étaient couverts. Il n''a pas attendu son diplôme.</p>
<p><em>Point clé :</em> Kossi n''avait pas de capital. Il avait une observation et un outil. Le Radar à Opportunités que tu vas utiliser dans ce module est l''outil qu''il aurait utilisé s''il avait suivi ce parcours.</p>

<h4>Séquence 3 — L''outil : le Radar à Opportunités (4 zones)</h4>
<p>Le Radar à Opportunités est un outil d''observation structurée. Il te force à regarder autour de toi avec les yeux d''un entrepreneur — pas d''un consommateur.</p>
<ul>
<li><strong>Zone 1 — FRUSTRATIONS :</strong> Qu''est-ce que les gens autour de toi font difficilement, lentement, ou avec beaucoup d''efforts ?</li>
<li><strong>Zone 2 — MANQUES :</strong> Qu''est-ce qui n''existe pas encore autour de toi, mais dont tu vois que les gens auraient besoin ?</li>
<li><strong>Zone 3 — RÉPÉTITIONS :</strong> Qu''est-ce que tu entends souvent comme plainte, demande ou désir autour de toi ?</li>
<li><strong>Zone 4 — COMPÉTENCES INUTILISÉES :</strong> Qu''est-ce que toi ou quelqu''un autour de toi sait faire, mais que personne ne paie pour obtenir ?</li>
</ul>
<p>Objectif : remplir au minimum 8 lignes sur 12 avec des observations réelles (template PDF disponible en ressource ci-dessous).</p>

<h4>Séquence 4 — Mise en situation terrain (Marché de Dantokpa)</h4>
<p><em>Situation :</em> Une femme tente d''envoyer de l''argent à sa famille à Natitingou. Elle doit trouver une boutique Mobile Money, faire la queue 30 minutes, payer des frais élevés, et ne reçoit pas de confirmation immédiate. Elle revient le lendemain pour vérifier.</p>
<p>Exercice (non noté, voir activité ci-dessous) : dans quelle(s) zone(s) du Radar cette situation entre-t-elle ? Quelle serait ton observation brute ?</p>

<h4>Séquence 5 — Synthèse et retour critique</h4>
<p>Parmi les 4 zones du Radar, laquelle te semble la plus facile à remplir dans ton environnement quotidien ? Pourquoi ? Poste ta réponse en 2–3 lignes dans l''espace communautaire et lis au moins 2 réponses d''autres étudiants avant de continuer.</p>

<h3>Niveau 2 — Approfondissement (fortement recommandé)</h3>
<p>Étude de cas : Fatouma Coulibaly, fondatrice d''un service de couture express pour étudiantes à l''UAC, lancé en L2, toujours actif 4 ans après. Vidéo expert (6 min) : « Comment j''ai identifié mon premier problème rentable. »</p>

<h3>Niveau 3 — Ressources expertes</h3>
<ul>
<li>Knowles, M. (1984). <em>The Adult Learner</em>. Gulf Publishing.</li>
<li>Banque Mondiale (2023). <em>Jeunesse, emploi et entrepreneuriat en Afrique subsaharienne.</em></li>
<li>INSAE (2024). <em>Enquête Emploi Bénin — données jeunes 15–35 ans.</em></li>
<li>Osterwalder, A. & Pigneur, Y. (2010). <em>Business Model Generation.</em> Wiley.</li>
<li>Base de données RCCM Bénin (rccm.bj).</li>
</ul>',
    @seq_objectifs, 25, 4, 1
);
SET @seq_contenu = LAST_INSERT_ID();

INSERT INTO activities (sequence_id, titre, type, consigne, actif)
VALUES (
    @seq_contenu,
    'Mise en situation — Marché de Dantokpa',
    'exercice',
    'Dans quelle(s) zone(s) du Radar la situation de Dantokpa entre-t-elle ? Quelle serait ton observation brute ? Écris 2 lignes.',
    1
);
INSERT INTO activities (sequence_id, titre, type, consigne, actif)
VALUES (
    @seq_contenu,
    'Synthèse — ta zone la plus facile',
    'exercice',
    'Parmi les 4 zones du Radar, laquelle te semble la plus facile à remplir dans ton environnement quotidien ? Pourquoi ? (2–3 lignes, à partager aussi sur le forum.)',
    1
);

-- ── Quiz de validation Niveau 1 (seuil 70%) ──────────────────
INSERT INTO quizzes (sequence_id, titre, description, score_min, actif)
VALUES (@seq_contenu, 'Quiz — Niveau 1', '5 questions. Score minimum 70% (4/5) pour déverrouiller la mission terrain.', 70, 1);
SET @quiz_id = LAST_INSERT_ID();

-- Q1
INSERT INTO questions (quiz_id, question, type, ordre, points) VALUES
(@quiz_id, 'Selon les données du module, combien de diplômés béninois le marché formel peut-il absorber chaque année ? (Feedback si faux : revenez aux données contextuelles — la réponse est dans le tableau des chiffres sourcés.)', 'choix_unique', 1, 1);
SET @q1 = LAST_INSERT_ID();
INSERT INTO answers (question_id, texte, est_correct, ordre) VALUES
(@q1, '15 000 sur 60 000–70 000 diplômés (INSAE, 2024)', 1, 1),
(@q1, '55 000 sur 60 000–70 000 diplômés', 0, 2),
(@q1, '70 000 sur 70 000 diplômés', 0, 3),
(@q1, 'Aucune absorption, le marché est saturé', 0, 4);

-- Q2
INSERT INTO questions (quiz_id, question, type, ordre, points) VALUES
(@quiz_id, 'Le Radar à Opportunités est organisé en 4 zones. Laquelle de ces propositions N''EST PAS une zone du Radar ? (Feedback si faux : les concurrents sont analysés plus tard (Série 2). Le Radar observe ce qui se passe autour de vous, pas ce que font les autres entreprises.)', 'choix_unique', 2, 1);
SET @q2 = LAST_INSERT_ID();
INSERT INTO answers (question_id, texte, est_correct, ordre) VALUES
(@q2, 'Frustrations', 0, 1),
(@q2, 'Manques', 0, 2),
(@q2, 'Concurrents', 1, 3),
(@q2, 'Compétences inutilisées', 0, 4);

-- Q3
INSERT INTO questions (quiz_id, question, type, ordre, points) VALUES
(@quiz_id, 'Kossi a lancé son projet sans capital. Qu''a-t-il utilisé à la place ? (Feedback si faux : la leçon du cas Kossi — le capital vient après la validation, pas avant.)', 'choix_unique', 3, 1);
SET @q3 = LAST_INSERT_ID();
INSERT INTO answers (question_id, texte, est_correct, ordre) VALUES
(@q3, 'Une observation (problème réel identifié) + un outil simple (WhatsApp + Mobile Money)', 1, 1),
(@q3, 'Un prêt bancaire étudiant', 0, 2),
(@q3, 'Un investisseur providentiel', 0, 3),
(@q3, 'Une subvention universitaire', 0, 4);

-- Q4
INSERT INTO questions (quiz_id, question, type, ordre, points) VALUES
(@quiz_id, 'Pourquoi la question d''activation posée en début de module ne peut-elle pas avoir de « mauvaise réponse » ? (Feedback si faux : relisez la définition de la question d''activation dans la séquence d''ancrage.)', 'choix_unique', 4, 1);
SET @q4 = LAST_INSERT_ID();
INSERT INTO answers (question_id, texte, est_correct, ordre) VALUES
(@q4, 'Parce que c''est une question de positionnement personnel — elle mesure vos croyances, pas vos connaissances', 1, 1),
(@q4, 'Parce qu''elle n''est pas notée par l''administrateur', 0, 2),
(@q4, 'Parce que toutes les réponses sont automatiquement validées', 0, 3),
(@q4, 'Parce que c''est une question à choix multiple', 0, 4);

-- Q5
INSERT INTO questions (quiz_id, question, type, ordre, points) VALUES
(@quiz_id, 'Complétez : « Entreprendre pendant les études, ce n''est pas sacrifier ses études. C''est les utiliser... » (Feedback si faux : c''est la formulation exacte du principe directeur de ce module.)', 'reponse_courte', 5, 1);
SET @q5 = LAST_INSERT_ID();
INSERT INTO answers (question_id, texte, est_correct, ordre) VALUES
(@q5, 'différemment', 1, 1);

-- ── Ressource : template Radar à Opportunités ────────────────
-- ⚠ Le fichier PDF du template doit être uploadé manuellement via admin/sequences.php
-- (ressource) — il n'existe pas encore physiquement, voir grille d'audit du document source.

-- ============================================================
-- SÉQUENCE 5 — ⑤ PRATIQUE (Mission terrain)
-- ============================================================
INSERT INTO sequences (module_id, titre, slug, description, contenu, prerequis_sequence_id, prerequis_quiz_min, duree_min, ordre, actif)
VALUES (
    @module_id,
    '⑤ Pratique — Mission terrain',
    'm01-pratique',
    'Obligatoire — condition sine qua non du livrable (60 à 90 minutes).',
    '<p><strong>Ta mission : observer et noter.</strong></p>
<p><strong>Le lieu (obligatoire) :</strong> rends-toi dans l''un de ces lieux — ton choix, mais il faut choisir : un marché (Dantokpa, marché central de ta ville, marché de quartier), un campus universitaire ou une école, un arrêt de transport en commun (taxi, zem), ou ton quartier de résidence. Reste sur place au minimum 60 minutes.</p>
<p><strong>L''objectif de collecte :</strong> remplir le Radar à Opportunités avec au minimum 8 observations réelles réparties dans les 4 zones. Chaque observation est une phrase simple décrivant ce que tu as vu ou entendu — pas une idée de business, pas une solution.</p>
<p><strong>La grille de recueil :</strong> utilise le template PDF « Radar à Opportunités — Module 0.1 ». Chaque zone : 2 observations minimum.</p>
<p><strong>Contrainte de temps :</strong> une seule session de 60 à 90 minutes, deadline 7 jours après validation du quiz.</p>
<p><strong>Ce que tu NE dois PAS faire :</strong> rechercher sur internet, réfléchir depuis chez toi, ou inventer des observations. Toutes les observations doivent être des situations vues ou entendues en présence physique dans le lieu choisi.</p>',
    @seq_contenu, 70, 75, 5, 1
);
SET @seq_pratique = LAST_INSERT_ID();

INSERT INTO activities (sequence_id, titre, type, consigne, note_max, actif)
VALUES (
    @seq_pratique,
    'Radar à Opportunités rempli',
    'travail_pratique',
    'Dépose ici ton Radar à Opportunités rempli (photo, scan ou PDF annoté) avec au minimum 8 observations réelles réparties dans les 4 zones (Frustrations, Manques, Répétitions, Compétences inutilisées).',
    NULL, 1
);

-- ============================================================
-- SÉQUENCE 6 — ⑥ LIVRABLE (Note de Positionnement)
-- ============================================================
INSERT INTO sequences (module_id, titre, slug, description, contenu, prerequis_sequence_id, duree_min, ordre, actif)
VALUES (
    @module_id,
    '⑥ Livrable — Note de Positionnement',
    'm01-livrable',
    'Réflexif léger, 1 à 2 pages. Grille communiquée avant rédaction.',
    '<p>Le livrable est la raison d''être de tout ce qui précède. Document personnel de 1 à 2 pages répondant à 4 questions structurées. Il sera conservé dans ton dossier de création d''entreprise tout au long du parcours.</p>
<table border="1" cellpadding="6">
<tr><th>Critère</th><th>Poids</th></tr>
<tr><td>Ancrage terrain (BLOQUANT) — la Question 4 s''appuie sur une observation réelle de la mission terrain</td><td>40 %</td></tr>
<tr><td>Honnêteté et auto-analyse — réflexion personnelle sincère sur les Questions 2 et 3</td><td>30 %</td></tr>
<tr><td>Pertinence pour le projet — problème observé réel et localisé</td><td>20 %</td></tr>
<tr><td>Clarté et structure — document lisible, organisé, relu</td><td>10 %</td></tr>
</table>
<p>Seuil de validation minimal : 6/10. Resoumission illimitée à 7 jours si en-dessous du seuil.</p>',
    @seq_pratique, 10, 6, 1
);
SET @seq_livrable = LAST_INSERT_ID();

INSERT INTO activities (sequence_id, titre, type, consigne, note_max, bareme, actif)
VALUES (
    @seq_livrable,
    'Note de Positionnement',
    'devoir',
    'Rédige ta Note de Positionnement (1 à 2 pages) en répondant aux 4 questions suivantes :\n\n1. Qui je suis (5 lignes) : mon parcours académique, ma filière, mes compétences informelles, ce que je sais faire que les autres ne savent pas.\n2. Ce que je veux (5 lignes) : où je me vois dans 2 ans — une intention honnête, pas une grande déclaration.\n3. Ce qui me freine (5 lignes) : 2 ou 3 obstacles identifiés honnêtement (peur de l''échec, manque de capital, doute sur ma légitimité...).\n4. Mon premier problème observé (terrain obligatoire, 5 lignes) : l''observation la plus intéressante de ton Radar à Opportunités — où tu l''as observée, qui est concerné, ce qui se passe aujourd''hui faute de solution.',
    10,
    'Ancrage terrain (BLOQUANT, 40%) : Q4 s''appuie sur une observation réelle de la mission terrain, lieu précisé, observation concrète.\nHonnêteté et auto-analyse (30%) : Q2/Q3 révèlent une réflexion personnelle sincère, vrais freins nommés.\nPertinence pour le projet (20%) : problème observé réel et localisé, base utile pour les modules suivants.\nClarté et structure (10%) : document lisible, organisé selon les 4 questions, relu avant soumission.\nSeuil de validation : 6/10. Resoumission illimitée à 7 jours.',
    1
);

-- ============================================================
-- SÉQUENCE 7 — ⑦ TRANSFERT
-- ============================================================
INSERT INTO sequences (module_id, titre, slug, description, contenu, prerequis_sequence_id, duree_min, ordre, actif)
VALUES (
    @module_id,
    '⑦ Transfert — Et maintenant ?',
    'm01-transfert',
    'Consolider l''apprentissage + pont vers le Module 0.2 (5 minutes).',
    '<p><strong>Tu as fait quelque chose que la plupart des gens ne feront jamais.</strong></p>
<p>Tu es sorti. Tu as observé. Tu as noté ce que tu as vraiment vu — pas ce que tu pensais trouver. Et tu as mis ça par écrit dans un document qui t''appartient.</p>
<p>Ce n''est pas un devoir. C''est le premier acte de tout entrepreneur : transformer une observation en connaissance utilisable.</p>
<p><strong>🏅 Badge débloqué : Explorateur Entrepreneurial</strong></p>
<p>Ta Note de Positionnement est maintenant enregistrée dans ton dossier de création d''entreprise. Tu y reviendras à la fin de chaque série pour mesurer ta progression.</p>
<hr>
<p><em>Le Radar que tu viens de remplir avec tes observations terrain va directement servir dans le Module 0.2. Tu vas y apprendre quels outils numériques utiliser pour organiser ces observations, les partager avec ton coach, et préparer les entretiens clients de la Série 1. Garde ton Radar ouvert.</em></p>',
    @seq_livrable, 5, 7, 1
);
SET @seq_transfert = LAST_INSERT_ID();

-- Rappel dynamique de la réponse d'activation (Phase ①) + question sur son évolution
INSERT INTO activities (sequence_id, titre, type, consigne, options_json, actif)
VALUES (
    @seq_transfert,
    'Retour à la question d''activation',
    'auto_evaluation',
    'Au début de ce module, tu as répondu à la question : « Qu''est-ce qui empêche un étudiant béninois de commencer à entreprendre pendant ses études ? » (ta réponse est rappelée ci-dessus). Aujourd''hui, après ta mission terrain, ta réponse a-t-elle changé ?',
    JSON_OBJECT(
        'boutons', JSON_ARRAY('Oui, elle a évolué', 'Non, je pense toujours la même chose'),
        'messages', JSON_OBJECT(
            'Oui, elle a évolué', 'Note en 2 lignes ce qui a changé dans ta façon de voir. Ce sera ton premier indicateur de progression.',
            'Non, je pense toujours la même chose', 'C''est aussi une information utile. Garde ta réponse en tête — elle évoluera peut-être après les modules suivants.'
        ),
        'reponse_libre', true,
        'rappel_activite_id', @act_activation
    ),
    1
);

-- ============================================================
-- Notes d'implémentation :
-- - Les feedbacks de quiz sont intégrés au texte de la question
--   (la table `questions` actuelle n'a pas de colonne dédiée).
-- - Le tag d'automation "phase0_module1_complete" et l'octroi du
--   badge sont gérés automatiquement par declencherAutomationsCompletion()
--   (includes/functions.php) à 100% de complétion du cours.
-- - Le template PDF "Radar à Opportunités" et le Module 0.2 (next_course_id)
--   restent à créer/uploader manuellement par l'admin.
-- ============================================================
