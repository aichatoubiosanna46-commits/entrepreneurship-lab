<?php
// ============================================================
//  assignment.php — Soumission d'un assignment
// ============================================================
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

sendSecurityHeaders();
reqConnecte();

$pdo    = getPDO();
$id     = (int)($_GET['id'] ?? 0);
$userId = $_SESSION['user_id'];

if (!$id) redirect(SITE_URL . '/dashboard.php');

$stmt = $pdo->prepare(
    'SELECT a.*, s.titre AS sequence_titre, s.id AS sequence_id,
            m.course_id
     FROM assignments a
     JOIN sequences s ON s.id = a.sequence_id
     JOIN modules m ON m.id = s.module_id
     WHERE a.id = ? AND a.actif = 1'
);
$stmt->execute([$id]);
$assignment = $stmt->fetch();

if (!$assignment) redirect(SITE_URL . '/dashboard.php', 'Assignment introuvable.', 'error');

// Vérifier inscription au cours
if (!estInscrit($userId, $assignment['course_id'])) {
    redirect(SITE_URL . '/course.php?id=' . $assignment['course_id'], 'Vous n\'êtes pas inscrit à ce cours.', 'error');
}

// Soumission précédente
$prevStmt = $pdo->prepare('SELECT * FROM assignment_submissions WHERE assignment_id = ? AND user_id = ? ORDER BY created_at DESC LIMIT 1');
$prevStmt->execute([$id, $userId]);
$previous = $prevStmt->fetch();

// Rubrique d'évaluation associée (lecture seule pour l'étudiant)
$rubrique = null;
try {
    $rStmt = $pdo->prepare('SELECT * FROM rubriques WHERE assignment_id = ? LIMIT 1');
    $rStmt->execute([$id]);
    $rubrique = $rStmt->fetch();
} catch (Exception $e) { $rubrique = null; }
$rubriqueCriteres = [];
$rubriqueTotal    = 0;
if ($rubrique && !empty($rubrique['criteres'])) {
    $rubriqueCriteres = json_decode($rubrique['criteres'], true) ?: [];
    foreach ($rubriqueCriteres as $crit) { $rubriqueTotal += (float)($crit['points'] ?? 0); }
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCSRF();

    $contenu   = trim($_POST['contenu'] ?? '');
    $fichier   = null;
    $audioPath = trim($_POST['audio_path'] ?? '') ?: null;

    if ($assignment['type'] === 'fichier' && !empty($_FILES['fichier']['name'])) {
        $uploaded = uploadFichier($_FILES['fichier'], 'assignments');
        if (!$uploaded) {
            $erreur = 'Fichier invalide ou trop volumineux (max 10 Mo, formats: PDF, Word, PPT, Excel).';
        } else {
            $fichier = $uploaded;
        }
    } elseif ($assignment['type'] === 'texte' && strlen($contenu) < 10) {
        $erreur = 'La soumission est trop courte.';
    } elseif ($assignment['type'] === 'audio' && !$audioPath && empty($previous['audio_path'])) {
        $erreur = 'Veuillez enregistrer un message audio avant de soumettre.';
    }

    if (!$erreur) {
        if ($previous) {
            // Mettre à jour la soumission existante
            $pdo->prepare(
                'UPDATE assignment_submissions SET contenu = ?, fichier = COALESCE(?, fichier), audio_path = COALESCE(?, audio_path), statut = "soumis", note = NULL, feedback = NULL, updated_at = NOW()
                 WHERE id = ?'
            )->execute([$contenu ?: null, $fichier, $audioPath, $previous['id']]);
        } else {
            $pdo->prepare(
                'INSERT INTO assignment_submissions (assignment_id, user_id, contenu, fichier, audio_path) VALUES (?, ?, ?, ?, ?)'
            )->execute([$id, $userId, $contenu ?: null, $fichier, $audioPath]);
        }

        addXP($userId, 'assignment_soumis', 20, 'Assignment soumis : ' . $assignment['titre']);
        logAction('assignment_submitted', 'Assignment ' . $id . ' soumis', $userId);
        
    // Déclencher automations après soumission
    triggerAutomation('assignment_submitted', $userId, $assignment['id'] ?? 0);
    // Notifier le coach
    try {
        $admins = $pdo->query('SELECT id FROM admins WHERE actif=1 LIMIT 3')->fetchAll();
        foreach ($admins as $adm) {
            $pdo->prepare('INSERT INTO user_notifications (user_id, titre, message, type) VALUES (?,?,?,?)')
                ->execute([$adm['id'], 'Nouveau livrable soumis', ($user['prenom'] ?? '') . ' a soumis un livrable pour correction.', 'info']);
        }
    } catch(Exception $e) {}
    redirect(SITE_URL . '/assignment.php?id=' . $id, 'Devoir soumis avec succès !', 'success');
    }
}

$pageTitle = h($assignment['titre']);
require_once __DIR__ . '/includes/header.php';
?>
<div class="container" style="max-width:800px;margin:0 auto;padding:32px 16px">

  <a href="<?= SITE_URL ?>/sequence.php?id=<?= $assignment['sequence_id'] ?>" style="font-size:13px;color:var(--text-muted);text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:16px">
    <i class="ti ti-arrow-left"></i> Retour à la séquence
  </a>

  <!-- En-tête -->
  <div style="background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:24px;margin-bottom:20px">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
      <span style="background:#EDE9FE;color:#6C47D4;padding:4px 12px;border-radius:99px;font-size:12px;font-weight:600">
        <i class="ti ti-clipboard-text"></i> Devoir
      </span>
      <span style="font-size:12px;color:var(--text-muted)">Séquence : <?= h($assignment['sequence_titre']) ?></span>
    </div>
    <h1 style="font-size:22px;font-weight:600;margin:0 0 12px"><?= h($assignment['titre']) ?></h1>
    <div style="font-size:14px;line-height:1.7;color:var(--text);background:#f9fafb;border-radius:8px;padding:16px;white-space:pre-wrap"><?= h($assignment['consigne']) ?></div>
    <div style="margin-top:12px;font-size:12px;color:var(--text-muted)">
      Note : <?= h($assignment['note_min']) ?> / <?= h($assignment['note_max']) ?>
      · Type : <?= h($assignment['type']) ?>
    </div>
  </div>

  <!-- Grille de rubrique (lecture seule) -->
  <?php if (!empty($rubriqueCriteres)): ?>
    <div style="background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:20px;margin-bottom:20px">
      <h2 style="font-size:15px;font-weight:600;margin:0 0 12px"><i class="ti ti-table" style="color:#6C47D4"></i> Grille d'évaluation</h2>
      <table style="width:100%;border-collapse:collapse;font-size:13px">
        <thead>
          <tr style="background:#f9fafb">
            <th style="text-align:left;padding:8px 10px;border-bottom:1px solid #e5e7eb">Critère</th>
            <th style="text-align:right;padding:8px 10px;border-bottom:1px solid #e5e7eb">Points max</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rubriqueCriteres as $crit): ?>
          <tr>
            <td style="padding:8px 10px;border-bottom:1px solid #f3f4f6"><?= h($crit['nom'] ?? '') ?></td>
            <td style="text-align:right;padding:8px 10px;border-bottom:1px solid #f3f4f6"><?= h($crit['points'] ?? 0) ?></td>
          </tr>
          <?php endforeach; ?>
          <tr>
            <td style="padding:8px 10px;font-weight:700">Total</td>
            <td style="text-align:right;padding:8px 10px;font-weight:700"><?= h($rubriqueTotal) ?></td>
          </tr>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <!-- Soumission précédente -->
  <?php if ($previous): ?>
    <div style="background:<?= $previous['statut'] === 'accepte' ? '#EAF3DE' : ($previous['statut'] === 'refuse' ? '#FAECE7' : '#EEF2FF') ?>;border:1px solid <?= $previous['statut'] === 'accepte' ? '#97C459' : ($previous['statut'] === 'refuse' ? '#F0997B' : '#6C47D4') ?>;border-radius:12px;padding:20px;margin-bottom:20px">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
        <strong style="font-size:15px">Votre dernière soumission</strong>
        <span style="font-size:12px;padding:4px 12px;border-radius:99px;background:rgba(0,0,0,.05);font-weight:600">
          <?= match($previous['statut']) {
            'accepte'      => '✓ Accepté',
            'refuse'       => '✗ Refusé',
            'en_correction' => '⏳ En correction',
            default        => '<i class="ti ti-upload"></i> Soumis'
          } ?>
        </span>
      </div>

      <?php if ($previous['contenu']): ?>
        <div style="font-size:14px;line-height:1.6;white-space:pre-wrap;background:rgba(255,255,255,.6);padding:12px;border-radius:8px">
          <?= h($previous['contenu']) ?>
        </div>
      <?php endif; ?>

      <?php if ($previous['fichier']): ?>
        <a href="<?= SITE_URL ?>/assets/uploads/<?= h($previous['fichier']) ?>" target="_blank"
           style="display:inline-flex;align-items:center;gap:6px;margin-top:8px;color:#6C47D4;font-size:13px">
          <i class="ti ti-file-download"></i> Télécharger le fichier soumis
        </a>
      <?php endif; ?>

      <?php if (!empty($previous['audio_path'])): ?>
        <div style="margin-top:10px">
          <div style="font-size:12px;color:var(--text-muted);margin-bottom:4px">Votre enregistrement audio :</div>
          <audio controls src="<?= SITE_URL ?>/<?= h($previous['audio_path']) ?>" style="width:100%"></audio>
        </div>
      <?php endif; ?>

      <?php if ($previous['note'] !== null): ?>
        <div style="margin-top:12px;padding-top:12px;border-top:1px solid rgba(0,0,0,.1)">
          <strong>Note : <?= $previous['note'] ?> / <?= $assignment['note_max'] ?></strong>
          <?php if ($previous['feedback']): ?>
            <div style="margin-top:8px;font-size:13px;font-style:italic;color:#444"><?= h($previous['feedback']) ?></div>
          <?php endif; ?>
          <?php if (!empty($previous['audio_feedback_path'])): ?>
            <div style="margin-top:10px">
              <div style="font-size:12px;color:var(--text-muted);margin-bottom:4px">Feedback audio du coach :</div>
              <audio controls src="<?= SITE_URL ?>/<?= h($previous['audio_feedback_path']) ?>" style="width:100%"></audio>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- Formulaire soumission -->
  <?php if (!$previous || $previous['statut'] === 'soumis' || ($previous['statut'] === 'refuse' && ($previous['peut_resoumettre'] ?? 1))): ?>
    <div style="background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:24px">
      <h2 style="font-size:17px;font-weight:600;margin:0 0 16px">
        <?= $previous ? 'Resoumettre le devoir' : 'Soumettre le devoir' ?>
      </h2>

      <?php if ($erreur): ?>
        <div class="alert alert-error"><i class="ti ti-alert-circle"></i> <?= h($erreur) ?></div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <?php if ($assignment['type'] === 'texte'): ?>
          <div class="form-group">
            <label for="contenu">Votre réponse</label>
            <textarea id="contenu" name="contenu" rows="10" required
                      placeholder="Rédigez votre réponse ici..."
                      style="width:100%;padding:12px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:14px;resize:vertical;font-family:inherit;box-sizing:border-box"><?= h($previous['contenu'] ?? '') ?></textarea>
          </div>
        <?php elseif ($assignment['type'] === 'audio'): ?>
          <div class="form-group">
            <label>Enregistrement audio</label>
            <div id="audioRecorder" data-target="submission" style="border:1px solid var(--border,#e5e7eb);border-radius:8px;padding:14px">
              <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                <button type="button" class="btn-outline btn-sm" data-action="start"><i class="ti ti-microphone"></i> Démarrer</button>
                <button type="button" class="btn-outline btn-sm" data-action="stop" disabled><i class="ti ti-player-stop"></i> Arrêter</button>
                <span class="rec-status" style="font-size:12px;color:var(--text-muted)"></span>
              </div>
              <div class="rec-preview" style="margin-top:10px"></div>
              <input type="hidden" name="audio_path" class="audio-path-input" value="<?= h($previous['audio_path'] ?? '') ?>">
            </div>
            <small style="color:var(--text-muted);font-size:12px">Le navigateur demandera l'accès au microphone.</small>
          </div>
          <div class="form-group">
            <label for="contenu">Commentaire (optionnel)</label>
            <textarea id="contenu" name="contenu" rows="3"
                      placeholder="Ajoutez un commentaire sur votre soumission..."
                      style="width:100%;padding:10px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:13px;font-family:inherit;box-sizing:border-box"><?= h($previous['contenu'] ?? '') ?></textarea>
          </div>
        <?php else: ?>
          <div class="form-group">
            <label for="fichier">Fichier à soumettre (PDF, Word, PPT, Excel)</label>
            <input type="file" id="fichier" name="fichier"
                   accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx"
                   style="display:block;margin-top:6px"
                   <?= !$previous ? 'required' : '' ?>>
            <small style="color:var(--text-muted);font-size:12px">Max 10 Mo</small>
          </div>
          <?php if ($assignment['type'] === 'texte' || $assignment['type'] === 'fichier'): ?>
            <div class="form-group">
              <label for="contenu">Commentaire (optionnel)</label>
              <textarea id="contenu" name="contenu" rows="3"
                        placeholder="Ajoutez un commentaire sur votre soumission..."
                        style="width:100%;padding:10px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:13px;font-family:inherit;box-sizing:border-box"><?= h($previous['contenu'] ?? '') ?></textarea>
            </div>
          <?php endif; ?>
        <?php endif; ?>

        <button type="submit" class="btn-primary">
          <i class="ti ti-upload"></i> Soumettre le devoir
        </button>
      </form>
    </div>
  <?php elseif ($previous && $previous['statut'] === 'accepte'): ?>
    <div style="text-align:center;padding:24px;color:var(--text-muted)">
      <i class="ti ti-circle-check" style="font-size:48px;color:#97C459;display:block;margin-bottom:8px"></i>
      Ce devoir a été accepté. Félicitations !
    </div>
  <?php endif; ?>
</div>

<script>
// ============================================================
// Enregistreur audio réutilisable (MediaRecorder) — start/stop/preview/upload
// Cherche tout élément .audioRecorder ou #audioRecorder sur la page.
// ============================================================
document.querySelectorAll('[id="audioRecorder"], .audioRecorder').forEach(function(box) {
  let mediaRecorder = null;
  let chunks = [];
  const startBtn  = box.querySelector('[data-action="start"]');
  const stopBtn   = box.querySelector('[data-action="stop"]');
  const statusEl  = box.querySelector('.rec-status');
  const previewEl = box.querySelector('.rec-preview');
  const pathInput = box.querySelector('.audio-path-input');
  const target    = box.dataset.target || 'submission';
  const extraId   = box.dataset.id || '';

  if (!startBtn || !stopBtn) return;

  startBtn.addEventListener('click', async function() {
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
      chunks = [];
      mediaRecorder = new MediaRecorder(stream);
      mediaRecorder.ondataavailable = e => { if (e.data.size > 0) chunks.push(e.data); };
      mediaRecorder.onstop = async function() {
        const blob = new Blob(chunks, { type: 'audio/webm' });
        previewEl.innerHTML = '';
        const audio = document.createElement('audio');
        audio.controls = true;
        audio.src = URL.createObjectURL(blob);
        previewEl.appendChild(audio);
        statusEl.textContent = 'Envoi en cours...';

        const fd = new FormData();
        fd.append('audio', blob, 'audio.webm');
        fd.append('target', target);
        fd.append('csrf_token', '<?= csrfToken() ?>');
        if (extraId) fd.append('id', extraId);

        try {
          const res = await fetch('<?= SITE_URL ?>/upload_audio.php', { method: 'POST', body: fd });
          const data = await res.json();
          if (data.path) {
            pathInput.value = data.path;
            statusEl.textContent = 'Enregistrement prêt à être soumis.';
          } else {
            statusEl.textContent = 'Erreur : ' + (data.error || 'upload échoué');
          }
        } catch (e) {
          statusEl.textContent = 'Erreur réseau lors de l\'envoi.';
        }
        stream.getTracks().forEach(t => t.stop());
      };
      mediaRecorder.start();
      startBtn.disabled = true;
      stopBtn.disabled = false;
      statusEl.textContent = 'Enregistrement en cours...';
    } catch (e) {
      statusEl.textContent = 'Impossible d\'accéder au micro.';
    }
  });

  stopBtn.addEventListener('click', function() {
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
      mediaRecorder.stop();
    }
    startBtn.disabled = false;
    stopBtn.disabled = true;
  });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
