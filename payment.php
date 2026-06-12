<?php
// payment.php — Choix et paiement d'un abonnement
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
reqConnecte();

$pdo    = getPDO();
$userId = $_SESSION['user_id'];
$user   = utilisateurCourant();

// Abonnement actif
$sub = $pdo->prepare('SELECT * FROM subscriptions WHERE user_id = ? AND statut = "actif" ORDER BY created_at DESC LIMIT 1');
$sub->execute([$userId]);
$activeSub = $sub->fetch();

$plans = [
    'decouverte'    => ['nom' => 'Découverte', 'prix' => 0,      'couleur' => '#3B6D11', 'desc' => 'Accès aux formations gratuites', 'features' => ['Formations gratuites','Accès limité','Support email']],
    'business_plan' => ['nom' => 'Business Plan','prix' => 15000, 'couleur' => '#534AB7', 'desc' => 'Accès complet aux formations', 'features' => ['Toutes les formations','Ressources PDF','Certificats','Support prioritaire']],
    'lancement'     => ['nom' => 'Lancement',  'prix' => 25000, 'couleur' => '#BA7517', 'desc' => 'Accès VIP + accompagnement', 'features' => ['Tout Business Plan','Bibliothèque complète','Coaching mensuel','Accès anticipé']],
];

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCSRF();
    $tarif     = $_POST['tarif'] ?? '';
    $methode   = $_POST['methode'] ?? '';
    $telephone = trim($_POST['telephone'] ?? '');
    $reference = trim($_POST['reference'] ?? '');

    if (!array_key_exists($tarif, $plans)) {
        $msg = 'Offre invalide.';
    } elseif ($tarif === 'decouverte') {
        // Gratuit — activer immédiatement
        $pdo->prepare('INSERT INTO subscriptions (user_id, tarif, statut, paye) VALUES (?, "decouverte", "actif", 1)')->execute([$userId]);
        redirect(SITE_URL . '/dashboard.php', 'Abonnement Découverte activé !', 'success');
    } else {
        $ref = 'PAY-' . strtoupper(bin2hex(random_bytes(6)));
        $pdo->prepare(
            'INSERT INTO payments (user_id, course_id, reference, montant, methode, statut)
             VALUES (?, 0, ?, ?, ?, "en_attente")'
        )->execute([$userId, $ref, $plans[$tarif]['prix'], $methode ?: 'mtn_momo']);
        $payId = $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO subscriptions (user_id, tarif, statut, paye) VALUES (?, ?, "actif", 0)')->execute([$userId, $tarif]);
        redirect(SITE_URL . '/payment_confirm.php?ref=' . urlencode($ref), '', '');
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Abonnements — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/home.css">
<style>
.pricing-wrap { max-width: 1000px; margin: 50px auto; padding: 0 24px 80px; }
.pricing-wrap h1 { text-align:center; font-size:32px; font-weight:800; margin-bottom:8px; }
.pricing-wrap .sub { text-align:center; color:var(--text-muted,#6b7280); margin-bottom:48px; }
.plans-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:24px; margin-bottom:48px; }
.plan-card { border:2px solid var(--border,#e5e7eb); border-radius:16px; padding:28px 24px; text-align:center; position:relative; background:#fff; transition:.2s; }
.plan-card.popular { border-color:#534AB7; }
.plan-badge { position:absolute; top:-12px; left:50%; transform:translateX(-50%); background:#534AB7; color:#fff; font-size:11px; font-weight:700; padding:3px 12px; border-radius:20px; white-space:nowrap; }
.plan-name { font-size:18px; font-weight:700; margin-bottom:4px; }
.plan-price { font-size:32px; font-weight:800; margin:16px 0 4px; }
.plan-price small { font-size:14px; font-weight:400; color:var(--text-muted,#6b7280); }
.plan-desc { font-size:13px; color:var(--text-muted,#6b7280); margin-bottom:20px; }
.plan-features { list-style:none; padding:0; margin:0 0 24px; text-align:left; }
.plan-features li { font-size:13px; padding:6px 0; border-bottom:1px solid var(--border,#e5e7eb); display:flex; align-items:center; gap:8px; }
.plan-features li:last-child { border:none; }
.plan-features li i { color:#16a34a; flex-shrink:0; }
.btn-plan { width:100%; padding:12px; border-radius:10px; font-weight:700; font-size:14px; cursor:pointer; border:none; }
.pay-form { background:#fff; border:1px solid var(--border,#e5e7eb); border-radius:16px; padding:32px; max-width:480px; margin:0 auto; }
.pay-form h2 { font-size:20px; font-weight:700; margin:0 0 20px; }
.field { margin-bottom:16px; }
.field label { display:block; font-size:13px; font-weight:600; margin-bottom:6px; }
.field input, .field select { width:100%; padding:10px 12px; border:1px solid var(--border,#e5e7eb); border-radius:8px; font-size:14px; box-sizing:border-box; }
.field input:focus, .field select:focus { outline:none; border-color:#534AB7; }
@media(max-width:860px){ .plans-grid{grid-template-columns:1fr;} }
</style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<div class="pricing-wrap">
  <h1>Choisissez votre offre</h1>
  <p class="sub">Débloquez l'accès aux formations et boostez votre entrepreneuriat</p>

  <?php if ($activeSub): ?>
  <div style="background:#EAF3DE;border:1px solid #97C459;border-radius:10px;padding:14px 20px;margin-bottom:28px;display:flex;align-items:center;gap:10px;font-size:14px;color:#27500A">
    <i class="ti ti-check-circle" style="font-size:20px"></i>
    Abonnement actif : <strong><?= $plans[$activeSub['tarif']]['nom'] ?? $activeSub['tarif'] ?></strong>
    <?= $activeSub['paye'] ? '(payé)' : '(en attente de validation)' ?>
  </div>
  <?php endif; ?>

  <div class="plans-grid">
    <?php foreach ($plans as $key => $plan): ?>
    <div class="plan-card <?= $key === 'business_plan' ? 'popular' : '' ?>">
      <?php if ($key === 'business_plan'): ?>
        <div class="plan-badge">⭐ Populaire</div>
      <?php endif; ?>
      <div class="plan-name" style="color:<?= $plan['couleur'] ?>"><?= $plan['nom'] ?></div>
      <div class="plan-price">
        <?php if ($plan['prix'] === 0): ?>
          Gratuit
        <?php else: ?>
          <?= number_format($plan['prix'], 0, ',', ' ') ?><small> FCFA/mois</small>
        <?php endif; ?>
      </div>
      <div class="plan-desc"><?= $plan['desc'] ?></div>
      <ul class="plan-features">
        <?php foreach ($plan['features'] as $f): ?>
        <li><i class="ti ti-check"></i> <?= h($f) ?></li>
        <?php endforeach; ?>
      </ul>
      <button type="button" onclick="choisirPlan('<?= $key ?>')"
        class="btn-plan" style="background:<?= $plan['couleur'] ?>;color:#fff">
        <?= $plan['prix'] === 0 ? 'Commencer gratuitement' : 'Choisir cette offre' ?>
      </button>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Formulaire paiement (masqué par défaut) -->
  <div id="pay-form-wrap" style="display:none">
    <div class="pay-form">
      <h2><i class="ti ti-credit-card" style="color:#BA7517"></i> Finaliser le paiement</h2>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="tarif" id="pay-tarif" value="">
        <div style="background:#f9fafb;border-radius:8px;padding:14px;margin-bottom:16px">
          <div style="font-size:13px;color:var(--text-muted,#6b7280)">Offre sélectionnée</div>
          <div id="pay-plan-name" style="font-size:18px;font-weight:700"></div>
          <div id="pay-plan-price" style="font-size:24px;font-weight:800;color:#BA7517"></div>
        </div>
        <div class="field">
          <label>Moyen de paiement</label>
          <select name="methode">
            <option value="mtn_momo">MTN Mobile Money</option>
            <option value="moov_money">Moov Money</option>
          </select>
        </div>
        <div class="field">
          <label>Votre numéro de téléphone</label>
          <input type="tel" name="telephone" placeholder="Ex: 97000000" required>
        </div>
        <div style="background:#fffbf0;border:1px solid #BA7517;border-radius:8px;padding:14px;margin-bottom:16px;font-size:13px">
          <strong>Comment payer :</strong><br>
          1. Effectuez le virement au <strong>+229 01 XX XX XX XX</strong><br>
          2. Notez votre référence de transaction<br>
          3. Soumettez le formulaire — votre accès sera activé sous 24h.
        </div>
        <div class="field">
          <label>Référence de transaction (optionnel)</label>
          <input type="text" name="reference" placeholder="Ex: TXN123456">
        </div>
        <button type="submit" class="btn-plan" style="background:#BA7517;color:#fff;width:100%;padding:14px">
          <i class="ti ti-send"></i> Confirmer ma demande
        </button>
        <button type="button" onclick="document.getElementById('pay-form-wrap').style.display='none'"
          style="width:100%;padding:10px;margin-top:10px;border:1px solid #e5e7eb;border-radius:8px;background:#fff;cursor:pointer;font-size:13px">
          Annuler
        </button>
      </form>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
<script>
const plans = <?= json_encode(array_map(fn($k,$p) => ['key'=>$k,'nom'=>$p['nom'],'prix'=>$p['prix']], array_keys($plans), $plans)) ?>;
function choisirPlan(key) {
  const plan = plans.find(p => p.key === key);
  if (!plan) return;
  document.getElementById('pay-tarif').value = key;
  document.getElementById('pay-plan-name').textContent = plan.nom;
  document.getElementById('pay-plan-price').textContent = plan.prix === 0 ? 'Gratuit' : plan.prix.toLocaleString('fr') + ' FCFA/mois';
  if (key === 'decouverte') {
    document.querySelector('[name=methode]').closest('.field').style.display = 'none';
    document.querySelector('[name=telephone]').closest('.field').style.display = 'none';
  } else {
    document.querySelector('[name=methode]').closest('.field').style.display = '';
    document.querySelector('[name=telephone]').closest('.field').style.display = '';
  }
  document.getElementById('pay-form-wrap').style.display = 'block';
  document.getElementById('pay-form-wrap').scrollIntoView({behavior:'smooth'});
}
</script>
</body>
</html>
