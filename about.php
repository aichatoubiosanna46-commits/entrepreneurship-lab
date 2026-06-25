<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>À propos — <?= SITE_NAME ?></title>
<meta name="description" content="Découvrez Ariziki EntrepreneurshipLab et CPID ONG — programme d'e-learning en entrepreneuriat pour les étudiants béninois.">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css?v=2">
<style>
body{background:#F5F0E8;font-family:'Plus Jakarta Sans',sans-serif}
.about-hero{background:linear-gradient(135deg,#1A1A18,#292524);padding:80px 24px;text-align:center;color:#fff}
.about-hero h1{font-size:36px;font-weight:800;margin-bottom:16px}
.about-hero h1 em{font-style:normal;color:#D85A30}
.about-hero p{font-size:16px;color:rgba(255,255,255,.7);max-width:600px;margin:0 auto;line-height:1.8}
.about-wrap{max-width:1000px;margin:0 auto;padding:60px 24px}
.about-section{margin-bottom:60px}
.about-section h2{font-size:24px;font-weight:800;color:#1A1A18;margin-bottom:8px;padding-bottom:12px;border-bottom:3px solid #D85A30;display:inline-block}
.about-section p{font-size:15px;color:#374151;line-height:1.8;margin-top:16px}
.mission-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-top:28px}
.mission-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:24px;text-align:center}
.mission-card .icon{width:56px;height:56px;border-radius:14px;background:#FBE3DA;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:24px}
.mission-card h3{font-size:15px;font-weight:700;color:#1A1A18;margin-bottom:8px}
.mission-card p{font-size:13px;color:#6b7280;line-height:1.6}
.team-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;margin-top:28px}
.team-card{background:#fff;border:1px solid #fde68a;border-radius:16px;overflow:hidden;text-align:center;padding-bottom:20px}
.team-avatar{height:160px;background:linear-gradient(135deg,#D85A30,#085041);display:flex;align-items:center;justify-content:center;font-size:48px;font-weight:800;color:#fff}
.team-avatar img{width:100%;height:100%;object-fit:cover;display:block}
.team-name{font-size:16px;font-weight:700;color:#1A1A18;margin:14px 14px 4px}
.team-role{font-size:12px;color:#D85A30;font-weight:600;margin-bottom:8px}
.team-bio{font-size:12px;color:#6b7280;line-height:1.6;padding:0 14px}
.values-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:14px;margin-top:20px}
.value-item{display:flex;align-items:flex-start;gap:12px;padding:16px;background:#fff;border-radius:12px;border:1px solid #e5e7eb}
.value-item i{font-size:20px;color:#D85A30;flex-shrink:0;margin-top:2px}
.value-item strong{display:block;font-size:14px;font-weight:700;color:#1A1A18;margin-bottom:4px}
.value-item span{font-size:13px;color:#6b7280;line-height:1.5}
.partner-logo{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px 28px;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:600;color:#374151}
@media(max-width:768px){.mission-grid,.team-grid,.values-grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="about-hero">
  <h1>À propos d'<em>Ariziki</em></h1>
  <p>Un programme d'entrepreneuriat conçu pour les étudiants béninois — pratique, certifié, et adapté au contexte africain.</p>
</div>

<div class="about-wrap">

  <!-- Mission -->
  <div class="about-section">
    <h2>Notre mission</h2>
    <p>Ariziki EntrepreneurshipLab est un programme d'e-learning en entrepreneuriat développé par <strong>CPID ONG</strong>, destiné aux étudiants béninois de niveau Licence Professionnelle. Notre mission est de former la prochaine génération d'entrepreneurs béninois en leur donnant les outils concrets pour lancer leur activité avant même d'obtenir leur diplôme.</p>
    <div class="mission-grid">
      <div class="mission-card">
        <div class="icon"><i class="ti ti-bulb" style="color:#D85A30"></i></div>
        <h3>Pratique avant tout</h3>
        <p>Des formations ancrées dans le contexte béninois, avec des cas réels et des exercices terrain.</p>
      </div>
      <div class="mission-card">
        <div class="icon"><i class="ti ti-certificate" style="color:#D85A30"></i></div>
        <h3>Certifié officiellement</h3>
        <p>Partenariat avec l'Université de Parakou pour une reconnaissance académique de vos compétences.</p>
      </div>
      <div class="mission-card">
        <div class="icon"><i class="ti ti-users" style="color:#D85A30"></i></div>
        <h3>Accompagnement humain</h3>
        <p>Coaching 1:1, forum communautaire et mentors disponibles pour vous guider à chaque étape.</p>
      </div>
    </div>
  </div>

  <!-- Valeurs -->
  <div class="about-section">
    <h2>Nos valeurs</h2>
    <div class="values-grid">
      <div class="value-item"><i class="ti ti-target"></i><div><strong>Impact local</strong><span>Chaque formation est pensée pour le contexte économique du Bénin et de l'Afrique francophone.</span></div></div>
      <div class="value-item"><i class="ti ti-shield-check"></i><div><strong>Excellence académique</strong><span>Certification reconnue par l'Université de Parakou, standard institutionnel.</span></div></div>
      <div class="value-item"><i class="ti ti-device-mobile"></i><div><strong>Accessibilité</strong><span>Paiement Mobile Money, contenu adapté aux connexions 3G, accès depuis n'importe quel appareil.</span></div></div>
      <div class="value-item"><i class="ti ti-heart"></i><div><strong>Bienveillance</strong><span>Un environnement d'apprentissage encourageant, sans jugement, centré sur la progression de chacun.</span></div></div>
    </div>
  </div>

  <!-- Équipe -->
  <div class="about-section">
    <h2>Notre équipe</h2>
    <div class="team-grid">
      <div class="team-card">
        <div class="team-avatar"><img src="<?= SITE_URL ?>/assets/images/equipes/1.png" alt="Prof. Bertrand SOGBOSSI"></div>
        <div class="team-name">Prof. Bertrand SOGBOSSI</div>
        <div class="team-role">Président du Conseil d'administration</div>
        <div class="team-bio">Recteur de l'Université de Parakou, il apporte sa rigueur scientifique et son expérience académique au programme Ariziki.</div>
      </div>
      <div class="team-card">
        <div class="team-avatar"><img src="<?= SITE_URL ?>/assets/images/equipes/2.png" alt="Céphas HOUNZANDJI"></div>
        <div class="team-name">Céphas HOUNZANDJI</div>
        <div class="team-role">Directeur — Gestionnaire de projet</div>
        <div class="team-bio">20 ans d'expérience en accompagnement entrepreneurial au Bénin. Directeur du centre d'incubation Ariziki.</div>
      </div>
      <div class="team-card">
        <div class="team-avatar"><img src="<?= SITE_URL ?>/assets/images/equipes/3.png" alt="Annick Chaffa"></div>
        <div class="team-name">Annick Chaffa</div>
        <div class="team-role">Chargée des programmes</div>
        <div class="team-bio">Ingénieure agronome et experte en entrepreneuriat vert, elle supervise la qualité des programmes pédagogiques.</div>
      </div>
    </div>
  </div>

  <!-- Partenaires -->
  <div class="about-section">
    <h2>Nos partenaires</h2>
    <div style="display:flex;gap:16px;flex-wrap:wrap;margin-top:20px">
      <div class="partner-logo"><i class="ti ti-school" style="color:#D85A30;margin-right:8px"></i> Université de Parakou</div>
      <div class="partner-logo"><i class="ti ti-building" style="color:#D85A30;margin-right:8px"></i> CPID ONG Bénin</div>
      <div class="partner-logo"><i class="ti ti-device-mobile" style="color:#D85A30;margin-right:8px"></i> MTN Mobile Money</div>
      <div class="partner-logo"><i class="ti ti-device-mobile" style="color:#D85A30;margin-right:8px"></i> Moov Money</div>
    </div>
  </div>

  <!-- CTA -->
  <div style="background:linear-gradient(135deg,#1A1A18,#292524);border-radius:20px;padding:48px;text-align:center">
    <h2 style="font-size:24px;font-weight:800;color:#fff;margin-bottom:12px">Prêt à lancer ton entreprise ?</h2>
    <p style="font-size:14px;color:rgba(255,255,255,.6);margin-bottom:24px">Rejoins plus de 1 200 étudiants entrepreneurs béninois sur Ariziki.</p>
    <a href="<?= SITE_URL ?>/register.php" style="display:inline-flex;align-items:center;gap:8px;padding:14px 28px;background:#D85A30;color:#1A1A18;border-radius:10px;font-size:15px;font-weight:700;text-decoration:none">
      <i class="ti ti-rocket"></i> Commencer gratuitement
    </a>
  </div>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
