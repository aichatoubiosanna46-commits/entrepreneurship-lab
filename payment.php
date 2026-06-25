<?php
// ============================================================
//  payment.php — Paiement via FedaPay (widget JS + API)
// ============================================================
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
reqConnecte();

$pdo    = getPDO();
$userId = $_SESSION['user_id'];
$user   = utilisateurCourant();

$sub = $pdo->prepare('SELECT * FROM subscriptions WHERE user_id = ? AND statut = "actif" ORDER BY created_at DESC LIMIT 1');
$sub->execute([$userId]);
$activeSub = $sub->fetch();

$plans = [
    'decouverte'    => [
        'nom'      => 'Découverte',
        'prix'     => 0,
        'emoji'    => 'ti-bulb',
        'couleur'  => '#16a34a',
        'bg'       => '#ECFDF5',
        'desc'     => 'Pour valider ton idée et découvrir l\'entrepreneuriat',
        'features' => ['Formations gratuites','Accès illimité','Support email','Accès à la communauté'],
    ],
    'essentiel'     => [
        'nom'      => 'Essentiel',
        'prix'     => 5000,
        'emoji'    => 'ti-star-filled',
        'couleur'  => '#C04A22',
        'bg'       => '#F5F0E8',
        'desc'     => 'Formations essentielles pour démarrer ton business',
        'features' => ['Formations Essentiel','Ressources PDF de base','Certificats de complétion','Support prioritaire'],
    ],
    'business_plan' => [
        'nom'      => 'Business Plan',
        'prix'     => 15000,
        'emoji'    => 'ti-chart-bar',
        'couleur'  => '#D85A30',
        'bg'       => '#FBE3DA',
        'desc'     => 'Accès complet aux formations avancées + coaching',
        'features' => ['Tout Essentiel inclus','Toutes les formations','Bibliothèque ressources complète','Coaching groupe mensuel'],
        'popular'  => true,
    ],
    'lancement'     => [
        'nom'      => 'Lancement',
        'prix'     => 25000,
        'emoji'    => 'ti-rocket',
        'couleur'  => '#085041',
        'bg'       => '#F0F9F5',
        'desc'     => 'Accompagnement VIP pour lancer ton activité',
        'features' => ['Tout Business Plan inclus','Bibliothèque complète','Coaching 1-1 mensuel','Accès anticipé nouveautés'],
    ],
];

// ── PLAN GRATUIT ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['tarif'] ?? '') === 'decouverte') {
    verifierCSRF();
    $exist = $pdo->prepare('SELECT id FROM subscriptions WHERE user_id = ? AND plan = "decouverte"');
    $exist->execute([$userId]);
    if (!$exist->fetch()) {
        $pdo->prepare('INSERT INTO subscriptions (user_id, plan, statut, paye) VALUES (?, "decouverte", "actif", 1)')->execute([$userId]);
    }
    redirect(SITE_URL . '/dashboard.php', 'Abonnement Découverte activé !', 'success');
}

// ── VÉRIFICATION CODE PROMO (AJAX) ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'check_promo') {
    verifierCSRF();
    header('Content-Type: application/json');
    $code  = strtoupper(trim($_POST['code'] ?? ''));
    $tarif = $_POST['tarif'] ?? '';
    $prix  = isset($plans[$tarif]) ? $plans[$tarif]['prix'] : 0;

    if (!$code || !$prix) { echo json_encode(['error' => 'Code invalide']); exit; }

    $stmt = $pdo->prepare(
        'SELECT * FROM promo_codes WHERE code = ? AND actif = 1
         AND (usage_max IS NULL OR usage_count < usage_max)
         AND (date_fin IS NULL OR date_fin >= CURDATE())
         AND (course_id IS NULL OR course_id = (SELECT id FROM courses WHERE slug = ? LIMIT 1))
         LIMIT 1'
    );
    $stmt->execute([$code, $tarif]);
    $promo = $stmt->fetch();

    if (!$promo) { echo json_encode(['error' => 'Code invalide ou expiré']); exit; }

    $remise    = $promo['type'] === 'pourcentage'
        ? round($prix * $promo['valeur'] / 100)
        : min($promo['valeur'], $prix);
    $prixFinal = max(0, $prix - $remise);

    echo json_encode([
        'success'    => true,
        'remise'     => $remise,
        'prix_final' => $prixFinal,
        'message'    => 'Code appliqué : -' . ($promo['type']==='pourcentage' ? $promo['valeur'].'%' : number_format($remise,0,',',' ').' FCFA'),
    ]);
    exit;
}

// ── CRÉATION TRANSACTION FEDAPAY (AJAX) ──────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_transaction') {
    verifierCSRF();
    header('Content-Type: application/json');

    $tarif = $_POST['tarif'] ?? '';
    if (!isset($plans[$tarif]) || $plans[$tarif]['prix'] === 0) {
        echo json_encode(['error' => 'Offre invalide']); exit;
    }

    $plan     = $plans[$tarif];
    // Appliquer remise si code promo valide
    $montant  = $plan['prix'];
    $promoCode = strtoupper(trim($_POST['promo_code'] ?? ''));
    if ($promoCode) {
        $ps = $pdo->prepare('SELECT * FROM promo_codes WHERE code=? AND actif=1 AND (usage_max IS NULL OR usage_count<usage_max) AND (date_fin IS NULL OR date_fin>=CURDATE()) LIMIT 1');
        $ps->execute([$promoCode]);
        $promo = $ps->fetch();
        if ($promo) {
            $remise  = $promo['type']==='pourcentage' ? round($montant*$promo['valeur']/100) : min($promo['valeur'],$montant);
            $montant = max(0, $montant - $remise);
            // Incrémenter usage
            $pdo->prepare('UPDATE promo_codes SET usage_count=usage_count+1 WHERE id=?')->execute([$promo['id']]);
        }
    }

    $ref      = 'ARI-' . strtoupper(bin2hex(random_bytes(6)));
    $callback = SITE_URL . '/payment_success.php?ref=' . urlencode($ref);

    $payload = json_encode([
        'description'  => 'Ariziki EntrepreneurshipLab — ' . $plan['nom'],
        'amount'       => $montant,
        'currency'     => ['iso' => 'XOF'],
        'callback_url' => $callback,
        'customer'     => [
            'firstname' => $user['prenom'],
            'lastname'  => $user['nom'],
            'email'     => $user['email'],
        ],
        'metadata' => ['user_id' => $userId, 'plan' => $tarif, 'ref' => $ref],
    ]);

    $ch = curl_init(FEDAPAY_API_URL . '/transactions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . FEDAPAY_SECRET_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr || ($httpCode !== 200 && $httpCode !== 201)) {
        echo json_encode(['error' => 'Erreur de connexion au service de paiement.']); exit;
    }

    $data  = json_decode($response, true);
    $txnId = $data['v1/transaction']['id'] ?? null;
    if (!$txnId) { echo json_encode(['error' => 'Réponse FedaPay invalide']); exit; }

    $ch2 = curl_init(FEDAPAY_API_URL . '/transactions/' . $txnId . '/token');
    curl_setopt_array($ch2, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => '{}',
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . FEDAPAY_SECRET_KEY, 'Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $tokenResp = curl_exec($ch2);
    curl_close($ch2);
    $tokenData = json_decode($tokenResp, true);
    $token     = $tokenData['token'] ?? null;
    if (!$token) { echo json_encode(['error' => 'Impossible d\'obtenir le token']); exit; }

    $pdo->prepare(
        'INSERT INTO payments (user_id, plan, montant, reference, operateur, statut, promo_code) VALUES (?, ?, ?, ?, "autre", "en_attente", ?)'
    )->execute([$userId, $tarif, $montant, $ref, $promoCode ?: null]);

    echo json_encode(['token' => $token, 'txn_id' => $txnId, 'ref' => $ref, 'amount' => $montant, 'plan_nom' => $plan['nom']]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Choisir mon parcours — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css?v=2">
<script src="https://cdn.fedapay.com/checkout.js?v=1.1.7"></script>
<style>
.pay-page { background:#F5F0E8; min-height:100vh; padding:0 0 80px; }
.pay-hero { background:linear-gradient(135deg,#1A1A18 0%,#292524 100%); padding:52px 24px 40px; text-align:center; }
.pay-hero h1 { font-size:32px; font-weight:800; color:#fff; margin-bottom:8px; }
.pay-hero h1 em { font-style:normal; color:#D85A30; }
.pay-hero p { font-size:14px; color:rgba(255,255,255,.6); margin-bottom:20px; }
.pay-badges { display:flex; justify-content:center; gap:12px; flex-wrap:wrap; }
.pay-badge-item { display:inline-flex; align-items:center; gap:6px; background:rgba(245,158,11,.15); border:1px solid rgba(245,158,11,.3); border-radius:20px; padding:5px 14px; font-size:11px; color:#D85A30; font-weight:600; }
.pay-wrap { max-width:1080px; margin:0 auto; padding:0 20px; }
.plans-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:20px; margin:-28px auto 32px; position:relative; z-index:10; }
.plan-card { background:#fff; border:1.5px solid #e5e7eb; border-radius:18px; overflow:hidden; transition:transform .2s,box-shadow .2s; display:flex; flex-direction:column; box-shadow:0 4px 16px rgba(0,0,0,.06); }
.plan-card:hover { transform:translateY(-5px); box-shadow:0 12px 32px rgba(0,0,0,.12); }
.plan-card.popular { border-color:#D85A30; box-shadow:0 8px 32px rgba(245,158,11,.2); }
.plan-header { padding:24px 20px 18px; text-align:center; position:relative; }
.plan-popular-badge { position:absolute; top:-1px; left:50%; transform:translateX(-50%); background:linear-gradient(90deg,#D85A30,#085041); color:#fff; font-size:10px; font-weight:800; padding:4px 16px; border-radius:0 0 10px 10px; white-space:nowrap; }
.plan-emoji { font-size:36px; display:block; margin-bottom:10px; }
.plan-name { font-size:16px; font-weight:800; margin-bottom:6px; }
.plan-price { margin:10px 0 6px; }
.plan-price .amount { font-size:34px; font-weight:800; color:#1A1A18; line-height:1; }
.plan-price .amount.free { color:#16a34a; font-size:26px; }
.plan-price .currency { font-size:13px; color:#6b7280; font-weight:500; }
.plan-desc { font-size:11px; color:#6b7280; line-height:1.6; }
.plan-body { padding:0 20px 20px; flex:1; display:flex; flex-direction:column; }
.plan-divider { height:1px; background:#f3f4f6; margin-bottom:14px; }
.plan-features { list-style:none; padding:0; margin:0 0 20px; flex:1; }
.plan-features li { display:flex; align-items:flex-start; gap:8px; font-size:12px; color:#374151; padding:5px 0; border-bottom:1px solid #f9fafb; }
.plan-features li:last-child { border:none; }
.plan-features li i { font-size:14px; flex-shrink:0; margin-top:1px; }
.btn-plan-free { display:block; width:100%; padding:12px; background:#16a34a; color:#fff; border:none; border-radius:10px; font-size:13px; font-weight:700; cursor:pointer; transition:background .15s; }
.btn-plan-free:hover { background:#15803d; }
.btn-plan-paid { display:block; width:100%; padding:12px; border:none; border-radius:10px; font-size:13px; font-weight:700; cursor:pointer; color:#fff; transition:opacity .15s,transform .1s; }
.btn-plan-paid:hover { opacity:.9; transform:translateY(-1px); }
.trust-bar { text-align:center; margin-top:16px; }
.trust-bar span { display:inline-flex; align-items:center; gap:6px; font-size:12px; color:#6b7280; background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:8px 16px; }
.sub-active-banner { background:#ECFDF5; border:1px solid #86efac; border-radius:12px; padding:14px 20px; display:flex; align-items:center; gap:10px; font-size:14px; color:#15803d; margin-bottom:24px; }
.overlay-pay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.65); z-index:1000; align-items:center; justify-content:center; }
.overlay-pay.show { display:flex; }
.overlay-card { background:#fff; border-radius:20px; padding:40px 32px; max-width:440px; width:90%; text-align:center; box-shadow:0 24px 60px rgba(0,0,0,.2); }
.overlay-emoji { font-size:52px; display:block; margin-bottom:12px; }
.overlay-card h3 { font-size:22px; font-weight:800; margin:0 0 6px; color:#1A1A18; }
.overlay-card p { font-size:13px; color:#6b7280; margin:0 0 16px; line-height:1.6; }
.overlay-amount { font-size:38px; font-weight:800; background:linear-gradient(135deg,#D85A30,#085041); -webkit-background-clip:text; -webkit-text-fill-color:transparent; margin-bottom:6px; }
.overlay-amount-original { font-size:14px; color:#9ca3af; text-decoration:line-through; margin-bottom:16px; display:none; }
.promo-wrap { display:flex; gap:8px; margin-bottom:16px; }
.promo-input { flex:1; padding:10px 12px; border:1.5px solid #e5e7eb; border-radius:8px; font-size:13px; font-family:inherit; text-transform:uppercase; }
.promo-input:focus { outline:none; border-color:#D85A30; }
.promo-btn { padding:10px 14px; background:#1A1A18; color:#fff; border:none; border-radius:8px; font-size:12px; font-weight:700; cursor:pointer; white-space:nowrap; }
.promo-msg { font-size:12px; margin-bottom:12px; padding:8px 12px; border-radius:8px; display:none; }
.promo-msg.ok { background:#ECFDF5; color:#15803d; display:block; }
.promo-msg.ko { background:#F0F9F5; color:#dc2626; display:block; }
.btn-fp { display:block; width:100%; padding:14px; background:linear-gradient(135deg,#D85A30,#085041); color:#fff; border:none; border-radius:12px; font-size:15px; font-weight:800; cursor:pointer; margin-bottom:10px; transition:opacity .15s; }
.btn-fp:hover { opacity:.9; }
.btn-cancel-pay { display:block; width:100%; padding:11px; background:transparent; color:#6b7280; border:1.5px solid #e5e7eb; border-radius:12px; font-size:13px; cursor:pointer; }
.spin { display:inline-block; width:18px; height:18px; border:3px solid rgba(255,255,255,.3); border-top-color:#fff; border-radius:50%; animation:spin .7s linear infinite; vertical-align:middle; margin-right:8px; }
@keyframes spin { to { transform:rotate(360deg); } }
@media(max-width:900px) { .plans-grid { grid-template-columns:1fr 1fr; } }
@media(max-width:540px) { .plans-grid { grid-template-columns:1fr; } .pay-hero h1 { font-size:24px; } }
</style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="pay-page">
  <div class="pay-hero">
    <h1>Choisis ton <em>parcours</em></h1>
    <p>Commence gratuitement · Paiement Mobile Money · Certification Université de Parakou</p>
    <div class="pay-badges">
      <span class="pay-badge-item"><i class="ti ti-device-mobile"></i> MTN MoMo · Moov Money</span>
      <span class="pay-badge-item"><i class="ti ti-shield-check"></i> Paiement sécurisé SSL</span>
      <span class="pay-badge-item"><i class="ti ti-infinity"></i> Accès à vie</span>
      <span class="pay-badge-item"><i class="ti ti-certificate"></i> Certifié Univ. Parakou</span>
    </div>
  </div>

  <div class="pay-wrap">
    <?php if ($activeSub): ?>
    <div class="sub-active-banner" style="margin-top:40px">
      <i class="ti ti-check-circle" style="font-size:22px;flex-shrink:0"></i>
      <div>Abonnement actif : <strong><?= $plans[$activeSub['plan']]['nom'] ?? $activeSub['plan'] ?></strong>
        <?= $activeSub['paye'] ? ' · Payé et validé ✓' : ' · En attente de validation' ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="plans-grid" style="<?= $activeSub ? 'margin-top:20px' : '' ?>">
      <?php foreach ($plans as $key => $plan): ?>
      <div class="plan-card <?= !empty($plan['popular']) ? 'popular' : '' ?>">
        <div class="plan-header" style="background:<?= $plan['bg'] ?>">
          <?php if (!empty($plan['popular'])): ?>
            <div class="plan-popular-badge"><i class="ti ti-star-filled"></i> Le plus populaire</div>
          <?php endif; ?>
          <span class="plan-emoji"><i class="ti <?= $plan['emoji'] ?>"></i></span>
          <div class="plan-name" style="color:<?= $plan['couleur'] ?>"><?= h($plan['nom']) ?></div>
          <div class="plan-price">
            <?php if ($plan['prix'] === 0): ?>
              <span class="amount free">Gratuit</span>
            <?php else: ?>
              <span class="amount"><?= number_format($plan['prix'], 0, ',', ' ') ?></span>
              <span class="currency"> FCFA</span>
            <?php endif; ?>
          </div>
          <div class="plan-desc"><?= h($plan['desc']) ?></div>
        </div>
        <div class="plan-body">
          <div class="plan-divider"></div>
          <ul class="plan-features">
            <?php foreach ($plan['features'] as $f): ?>
            <li><i class="ti ti-check" style="color:<?= $plan['couleur'] ?>"></i><?= h($f) ?></li>
            <?php endforeach; ?>
          </ul>
          <?php if ($key === 'decouverte'): ?>
            <form method="POST" style="margin:0">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="tarif" value="decouverte">
              <button type="submit" class="btn-plan-free">
                <i class="ti ti-arrow-right"></i> Commencer gratuitement
              </button>
            </form>
          <?php else: ?>
            <button type="button" class="btn-plan-paid"
                    style="background:linear-gradient(135deg,<?= $plan['couleur'] ?>,<?= $plan['couleur'] ?>dd)"
                    onclick="ouvrirPaiement('<?= $key ?>','<?= h($plan['nom']) ?>',<?= $plan['prix'] ?>)">
              <i class="ti ti-credit-card"></i>
              Choisir — <?= number_format($plan['prix'], 0, ',', ' ') ?> FCFA
            </button>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="trust-bar">
      <span><i class="ti ti-shield-lock" style="color:#16a34a"></i>
        Paiement sécurisé via FedaPay · MTN Mobile Money · Moov Money · Visa · Mastercard
      </span>
    </div>
  </div>
</div>

<!-- Overlay avec champ code promo -->
<div class="overlay-pay" id="overlay-pay">
  <div class="overlay-card">
    <span class="overlay-emoji" id="ol-emoji"><i class="ti ti-credit-card"></i></span>
    <h3 id="ol-plan-name">Business Plan</h3>
    <p>Paiement sécurisé via FedaPay — Mobile Money, Visa, Mastercard.</p>

    <!-- Code promo -->
    <div class="promo-wrap">
      <input type="text" class="promo-input" id="promo-input" placeholder="CODE PROMO">
      <button class="promo-btn" onclick="appliquerPromo()"><i class="ti ti-ticket"></i> Appliquer</button>
    </div>
    <div class="promo-msg" id="promo-msg"></div>

    <div class="overlay-amount" id="ol-plan-price">15 000 FCFA</div>
    <div class="overlay-amount-original" id="ol-prix-original"></div>

    <button class="btn-fp" id="btn-pay-now" onclick="lancerPaiement()">
      <i class="ti ti-credit-card"></i> Payer maintenant
    </button>
    <button class="btn-cancel-pay" onclick="fermerOverlay()">Annuler</button>
    <p style="margin-top:14px;font-size:11px;color:#9ca3af">
      <i class="ti ti-lock"></i> Données chiffrées SSL · Aucune info bancaire stockée
    </p>
  </div>
</div>

<script>
let currentTarif    = '';
let currentNom      = '';
let currentPrix     = 0;
let currentPrixFinal = 0;
let currentPromo    = '';
const csrfToken     = '<?= csrfToken() ?>';
const fedapayPK     = '<?= FEDAPAY_PUBLIC_KEY ?>';

function ouvrirPaiement(tarif, nom, prix, emoji) {
    currentTarif     = tarif;
    currentNom       = nom;
    currentPrix      = prix;
    currentPrixFinal = prix;
    currentPromo     = '';
    document.getElementById('ol-plan-name').textContent   = nom;
    document.getElementById('ol-plan-price').textContent  = prix.toLocaleString('fr') + ' FCFA';
    document.getElementById('ol-prix-original').style.display = 'none';
    document.getElementById('promo-input').value = '';
    document.getElementById('promo-msg').className = 'promo-msg';
    document.getElementById('promo-msg').textContent = '';
    document.getElementById('overlay-pay').classList.add('show');
}

function fermerOverlay() {
    document.getElementById('overlay-pay').classList.remove('show');
}

async function appliquerPromo() {
    const code = document.getElementById('promo-input').value.trim();
    const msg  = document.getElementById('promo-msg');
    if (!code) { msg.className='promo-msg ko'; msg.textContent='Entrez un code promo.'; return; }

    const fd = new FormData();
    fd.append('csrf_token', csrfToken);
    fd.append('action', 'check_promo');
    fd.append('code', code);
    fd.append('tarif', currentTarif);

    try {
        const res  = await fetch('payment.php', {method:'POST', body:fd});
        const data = await res.json();
        if (data.error) {
            msg.className = 'promo-msg ko';
            msg.textContent = data.error;
            currentPromo    = '';
            currentPrixFinal = currentPrix;
            document.getElementById('ol-plan-price').textContent = currentPrix.toLocaleString('fr') + ' FCFA';
            document.getElementById('ol-prix-original').style.display = 'none';
        } else {
            msg.className = 'promo-msg ok';
            msg.textContent = '✓ ' + data.message;
            currentPromo    = code;
            currentPrixFinal = data.prix_final;
            document.getElementById('ol-plan-price').textContent = data.prix_final.toLocaleString('fr') + ' FCFA';
            document.getElementById('ol-prix-original').textContent = currentPrix.toLocaleString('fr') + ' FCFA';
            document.getElementById('ol-prix-original').style.display = 'block';
        }
    } catch(e) {
        msg.className = 'promo-msg ko';
        msg.textContent = 'Erreur réseau.';
    }
}

async function lancerPaiement() {
    const btn = document.getElementById('btn-pay-now');
    btn.disabled = true;
    btn.innerHTML = '<span class="spin"></span> Préparation…';

    const fd = new FormData();
    fd.append('csrf_token', csrfToken);
    fd.append('action', 'create_transaction');
    fd.append('tarif', currentTarif);
    if (currentPromo) fd.append('promo_code', currentPromo);

    try {
        const res  = await fetch('payment.php', {method:'POST', body:fd});
        const data = await res.json();
        if (data.error) {
            alert('Erreur : ' + data.error);
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-credit-card"></i> Payer maintenant';
            return;
        }
        fermerOverlay();
        FedaPay.init({
            public_key: fedapayPK,
            transaction: { token: data.token },
            onComplete: function(resp) {
                if (resp.reason === FedaPay.DIALOG_DISMISSED) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="ti ti-credit-card"></i> Payer maintenant';
                    return;
                }
                window.location.href = 'payment_success.php?ref=' + encodeURIComponent(data.ref) + '&txn_id=' + resp.transaction.id;
            }
        }).open();
    } catch(e) {
        alert('Erreur réseau. Veuillez réessayer.');
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-credit-card"></i> Payer maintenant';
    }
}

document.getElementById('overlay-pay').addEventListener('click', function(e) {
    if (e.target === this) fermerOverlay();
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
