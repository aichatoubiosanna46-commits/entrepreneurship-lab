<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
reqAdmin();
$pdo = getPDO();

$erreur = '';
$succes = '';

// Envoi campagne
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'envoyer') {
    $sujet   = trim($_POST['sujet'] ?? '');
    $contenu = trim($_POST['contenu'] ?? '');
    $tagId   = (int)($_POST['tag_id'] ?? 0);
    $tous    = isset($_POST['tous']);

    if (!$sujet || !$contenu) { $erreur = 'Sujet et contenu requis.'; }
    else {
        // Récupérer destinataires
        if ($tous) {
            $stmt = $pdo->query('SELECT email, prenom FROM users WHERE actif=1');
        } else {
            $stmt = $pdo->prepare('SELECT u.email, u.prenom FROM users u JOIN user_tags ut ON ut.user_id=u.id WHERE ut.tag_id=? AND u.actif=1');
            $stmt->execute([$tagId]);
        }
        $destinataires = $stmt->fetchAll();
        $nb = 0;
        foreach ($destinataires as $d) {
            $msg = str_replace(['{{prenom}}', '{{nom}}'], [$d['prenom'], ''], $contenu);
            $headers = "Content-Type: text/html; charset=UTF-8\r\nFrom: " . SITE_NAME . " <" . SMTP_FROM . ">\r\n";
            if (@mail($d['email'], $sujet, $msg, $headers)) $nb++;
        }
        // Enregistrer campagne
        $pdo->prepare('INSERT INTO email_campagnes (sujet, contenu, tag_id, statut, nb_envoyes) VALUES (?,?,?,\"envoyee\",?)')
            ->execute([$sujet, $contenu, $tagId ?: null, $nb]);
        $succes = "Campagne envoyée à $nb destinataires.";
    }
}

try {
    $tags      = $pdo->query('SELECT * FROM tags ORDER BY nom')->fetchAll();
    $campagnes = $pdo->query('SELECT ec.*, t.nom as tag_nom FROM email_campagnes ec LEFT JOIN tags t ON t.id=ec.tag_id ORDER BY ec.created_at DESC LIMIT 50')->fetchAll();
} catch(Exception $e) { $tags = []; $campagnes = []; }

$currentPage = 'email_campagnes.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Emails ciblés — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
<style>.fld{width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;font-family:inherit;box-sizing:border-box}</style>
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <div><h1 class="admin-page-title">Emails ciblés par tag</h1><p class="admin-page-sub">Envoyer des emails à des groupes d'étudiants</p></div>
  </div>
  <?php if ($erreur): ?><div class="alert alert-error"><?= h($erreur) ?></div><?php endif; ?>
  <?php if ($succes): ?><div class="alert alert-success"><i class="ti ti-check-circle"></i> <?= h($succes) ?></div><?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 380px;gap:20px">
    <!-- Historique -->
    <div class="admin-card">
      <div class="admin-card-title">Campagnes envoyées</div>
      <?php if (empty($campagnes)): ?>
      <p style="text-align:center;color:#9ca3af;padding:32px">Aucune campagne envoyée</p>
      <?php else: ?>
      <table class="admin-table">
        <thead><tr><th>Sujet</th><th>Tag ciblé</th><th>Envoyés</th><th>Date</th></tr></thead>
        <tbody>
          <?php foreach ($campagnes as $camp): ?>
          <tr>
            <td><strong><?= h($camp['sujet']) ?></strong></td>
            <td><?= $camp['tag_nom'] ? h($camp['tag_nom']) : '<em style="color:#9ca3af">Tous</em>' ?></td>
            <td style="text-align:center"><strong><?= $camp['nb_envoyes'] ?></strong></td>
            <td style="font-size:12px;color:#9ca3af"><?= date('d/m/Y H:i', strtotime($camp['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>

    <!-- Formulaire -->
    <div class="admin-card" style="position:sticky;top:80px;height:fit-content">
      <div class="admin-card-title">Nouvelle campagne</div>
      <form method="POST">
        <input type="hidden" name="action" value="envoyer">
        <div class="form-group">
          <label>Destinataires</label>
          <select name="tag_id" class="fld" id="tagSelect">
            <option value="">— Choisir un tag —</option>
            <?php foreach ($tags as $t): ?>
            <option value="<?= $t['id'] ?>"><?= h($t['nom']) ?></option>
            <?php endforeach; ?>
          </select>
          <label class="checkbox-label" style="margin-top:8px">
            <input type="checkbox" name="tous" onchange="document.getElementById('tagSelect').disabled=this.checked">
            Envoyer à TOUS les étudiants actifs
          </label>
        </div>
        <div class="form-group">
          <label>Sujet *</label>
          <input type="text" name="sujet" class="fld" required placeholder="Ex: Nouvelle formation disponible !">
        </div>
        <div class="form-group">
          <label>Contenu (HTML supporté)</label>
          <textarea name="contenu" class="fld" rows="8" required placeholder="Bonjour {{prenom}},&#10;&#10;..."></textarea>
          <small style="color:#9ca3af;font-size:11px">Variables : {{prenom}}</small>
        </div>
        <button type="submit" class="btn-primary btn-full" onclick="return confirm('Envoyer cette campagne email ?')">
          <i class="ti ti-send"></i> Envoyer la campagne
        </button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
