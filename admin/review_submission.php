<?php
// admin/review_submission.php — Corriger une soumission
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

sendSecurityHeaders();
// Accessible aux admins ET aux coachs
if (!estAdmin() && !estCoach()) {
    header('Location: ' . SITE_URL . '/dashboard.php?error=acces_refuse');
    exit;
}

$pdo = getPDO();
$id  = (int)($_GET['id'] ?? 0);

if (!$id) redirect(SITE_URL . '/admin/review_center.php');

$stmt = $pdo->prepare(
    'SELECT asub.*, a.titre AS assignment_titre, a.note_min, a.note_max, a.type,
            s.titre AS sequence_titre,
            co.titre AS course_titre,
            u.nom, u.prenom, u.email, u.id AS student_id
     FROM assignment_submissions asub
     JOIN assignments a ON a.id = asub.assignment_id
     JOIN sequences s ON s.id = a.sequence_id
     JOIN modules m ON m.id = s.module_id
     JOIN courses co ON co.id = m.course_id
     JOIN users u ON u.id = asub.user_id
     WHERE asub.id = ?'
);
$stmt->execute([$id]);
$sub = $stmt->fetch();

if (!$sub) redirect(SITE_URL . '/admin/review_center.php', 'Soumission introuvable.', 'error');

if ($sub['statut'] === 'soumis') {
    $pdo->prepare('UPDATE assignment_submissions SET statut = "en_correction" WHERE id = ?')->execute([$id]);
    $sub['statut'] = 'en_correction';
}

// Charger historique soumissions
try {
    $history = $pdo->prepare('SELECT * FROM submission_history WHERE user_id=? AND assignment_id=? ORDER BY version DESC');
    $history->execute([$sub['user_id'], $sub['assignment_id']]);
    $history = $history->fetchAll();
} catch(Exception $e) { $history = []; }

// Rubrique d'évaluation associée à l'assignment
$rubrique = null;
try {
    $rStmt = $pdo->prepare('SELECT * FROM rubriques WHERE assignment_id = ? LIMIT 1');
    $rStmt->execute([$sub['assignment_id']]);
    $rubrique = $rStmt->fetch();
} catch (Exception $e) { $rubrique = null; }
$rubriqueCriteres = [];
if ($rubrique && !empty($rubrique['criteres'])) {
    $rubriqueCriteres = json_decode($rubrique['criteres'], true) ?: [];
}
$existingNotes = [];
if (!empty($sub['rubrique_notes'])) {
    $existingNotes = json_decode($sub['rubrique_notes'], true) ?: [];
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCSRF();

    $feedback = trim($_POST['feedback'] ?? '');
    $statut   = in_array($_POST['statut'], ['accepte', 'refuse']) ? $_POST['statut'] : 'accepte';
    $audioFeedbackPath = trim($_POST['audio_feedback_path'] ?? '') ?: ($sub['audio_feedback_path'] ?? null);

    // Si une rubrique existe, la note globale = somme des notes par critère
    $rubriqueNotesJson = null;
    if (!empty($rubriqueCriteres)) {
        $critNotes = [];
        $sumNote = 0;
        foreach ($rubriqueCriteres as $i => $crit) {
            $val = (float)str_replace(',', '.', $_POST['critere_note'][$i] ?? 0);
            $max = (float)($crit['points'] ?? 0);
            if ($val < 0) $val = 0;
            if ($val > $max) $val = $max;
            $critNotes[] = ['nom' => $crit['nom'] ?? '', 'note' => $val, 'max' => $max];
            $sumNote += $val;
        }
        $rubriqueNotesJson = json_encode($critNotes, JSON_UNESCAPED_UNICODE);
        $note = $sumNote;
    } else {
        $note = (float)str_replace(',', '.', $_POST['note'] ?? 0);
    }

    if ($note < 0 || $note > (float)$sub['note_max']) {
        $erreur = 'La note doit être comprise entre 0 et ' . $sub['note_max'] . '.';
    } else {
        // Pièce jointe feedback
        $feedbackFichier = $sub['feedback_fichier'] ?? null;
        if (!empty($_FILES['feedback_fichier']['tmp_name']) && $_FILES['feedback_fichier']['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES['feedback_fichier']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['pdf', 'docx', 'jpg', 'jpeg', 'png'])) {
                $fname = 'feedback_' . uniqid() . '.' . $ext;
                $dest  = __DIR__ . '/../assets/uploads/' . $fname;
                if (move_uploaded_file($_FILES['feedback_fichier']['tmp_name'], $dest)) {
                    $feedbackFichier = $fname;
                }
            }
        }

        $adminId = $_SESSION['admin_id'];

        // Note minimale — refus automatique si en dessous
        $noteMin = (float)($sub['note_min'] ?? 0);
        if ($noteMin > 0 && $note < $noteMin && $statut === 'accepte') {
            $statut = 'refuse';
        }

        $pdo->prepare(
            'UPDATE assignment_submissions
             SET statut=?, note=?, feedback=?, feedback_fichier=?, rubrique_notes=?, audio_feedback_path=?, correction_par=?, corrige_le=NOW(), updated_at=NOW()
             WHERE id=?'
        )->execute([$statut, $note, $feedback, $feedbackFichier, $rubriqueNotesJson, $audioFeedbackPath, $adminId, $id]);

        // Sauvegarder dans l'historique
        try {
            $version = count($history) + 1;
            $pdo->prepare(
                'INSERT INTO submission_history (user_id, assignment_id, contenu, fichier, note, feedback, version)
                 VALUES (?,?,?,?,?,?,?)'
            )->execute([$sub['user_id'], $sub['assignment_id'], $sub['contenu'], $sub['fichier'], $note, $feedback, $version]);
        } catch(Exception $e) {}

        // Resoumission si refusé
        if ($statut === 'refuse') {
            try {
                $pdo->prepare('UPDATE assignment_submissions SET peut_resoumettre=1 WHERE id=?')->execute([$id]);
            } catch(Exception $e) {}
        }

        // Notification étudiant
        $notifMsg = $statut === 'accepte'
            ? 'Votre devoir « '.$sub['assignment_titre'].' » a été accepté — Note : '.$note.'/'.$sub['note_max'].'.'
            : 'Votre devoir « '.$sub['assignment_titre'].' » a été refusé — Note : '.$note.'/'.$sub['note_max'].'. Consultez le feedback et resoumettez.';

        notifierUtilisateur($sub['student_id'],
            $statut === 'accepte' ? '✅ Devoir accepté !' : '❌ Devoir refusé',
            $notifMsg,
            $statut === 'accepte' ? 'success' : 'error',
            SITE_URL . '/assignment.php?id=' . $sub['assignment_id']
        );

        redirect(SITE_URL . '/admin/review_center.php', 'Correction enregistrée.', 'success');
    }
}
$currentPage = 'review_submission.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Corriger — <?= h($sub['assignment_titre']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
</head>
<body class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
  <div class="admin-topbar">
    <div>
      <h1 class="admin-page-title"><i class="ti ti-clipboard-check"></i> Correction de devoir</h1>
      <p class="admin-page-sub"><?= h($sub['course_titre']) ?> · <?= h($sub['sequence_titre']) ?></p>
    </div>
    <a href="review_center.php" class="btn-outline"><i class="ti ti-arrow-left"></i> Review Center</a>
  </div>

  <?= flash() ?>
  <?php if ($erreur): ?>
  <div class="alert alert-error"><i class="ti ti-alert-circle"></i> <?= h($erreur) ?></div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 360px;gap:20px">
    <div>
      <!-- Info étudiant + devoir -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px">
        <div class="admin-card">
          <div style="font-size:12px;font-weight:700;color:#9ca3af;margin-bottom:10px">ÉTUDIANT</div>
          <div style="font-size:15px;font-weight:700;color:#1C1917"><?= h($sub['prenom'].' '.$sub['nom']) ?></div>
          <div style="font-size:12px;color:#6b7280"><?= h($sub['email']) ?></div>
        </div>
        <div class="admin-card">
          <div style="font-size:12px;font-weight:700;color:#9ca3af;margin-bottom:10px">DEVOIR</div>
          <div style="font-size:15px;font-weight:700;color:#1C1917"><?= h($sub['assignment_titre']) ?></div>
          <div style="font-size:12px;color:#6b7280">
            Note max : <strong><?= $sub['note_max'] ?></strong>
            <?php if ($sub['note_min'] ?? 0): ?>
            · Note min : <strong style="color:#dc2626"><?= $sub['note_min'] ?></strong>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Contenu soumis -->
      <div class="admin-card" style="margin-bottom:20px">
        <div class="admin-card-title">
          Contenu soumis
          <span style="font-size:11px;font-weight:400;color:#9ca3af;margin-left:8px">
            le <?= date('d/m/Y à H:i', strtotime($sub['created_at'])) ?>
          </span>
        </div>
        <?php if ($sub['contenu']): ?>
        <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:16px;font-size:14px;line-height:1.7;white-space:pre-wrap;max-height:400px;overflow-y:auto">
          <?= h($sub['contenu']) ?>
        </div>
        <?php endif; ?>
        <?php if ($sub['fichier']): ?>
        <div style="margin-top:12px">
          <a href="<?= SITE_URL ?>/assets/uploads/<?= h($sub['fichier']) ?>" target="_blank" class="btn-outline" style="font-size:13px">
            <i class="ti ti-file-download"></i> Télécharger le fichier soumis
          </a>
        </div>
        <?php endif; ?>
        <?php if (!empty($sub['audio_path'])): ?>
        <div style="margin-top:12px">
          <div style="font-size:12px;color:#6b7280;margin-bottom:4px">Audio soumis par l'étudiant :</div>
          <audio controls src="<?= SITE_URL ?>/<?= h($sub['audio_path']) ?>" style="width:100%"></audio>
        </div>
        <?php endif; ?>
      </div>

      <!-- Historique soumissions -->
      <?php if (!empty($history)): ?>
      <div class="admin-card" style="margin-bottom:20px">
        <div class="admin-card-title"><i class="ti ti-history" style="color:var(--primary)"></i> Historique des soumissions</div>
        <table class="admin-table">
          <thead><tr><th>Version</th><th>Note</th><th>Feedback</th><th>Date</th></tr></thead>
          <tbody>
            <?php foreach ($history as $h2): ?>
            <tr>
              <td><span style="background:#FEF3C7;color:#D97706;padding:2px 8px;border-radius:12px;font-size:12px;font-weight:700">v<?= $h2['version'] ?></span></td>
              <td><?= $h2['note'] !== null ? $h2['note'].'/'.$sub['note_max'] : '—' ?></td>
              <td style="font-size:12px;color:#6b7280;max-width:200px"><?= h(mb_substr($h2['feedback']??'—',0,80)) ?></td>
              <td style="font-size:11px;color:#9ca3af"><?= date('d/m/Y H:i', strtotime($h2['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- Formulaire correction -->
    <div>
      <div class="admin-card" style="position:sticky;top:80px">
        <div class="admin-card-title"><i class="ti ti-pencil-check" style="color:var(--primary)"></i> Correction</div>

        <!-- Templates rapides -->
        <div style="margin-bottom:14px">
          <div style="font-size:11px;font-weight:700;color:#9ca3af;margin-bottom:6px">TEMPLATES RAPIDES</div>
          <div style="display:flex;gap:6px;flex-wrap:wrap">
            <button type="button" onclick="setTemplate('Excellent travail ! Votre analyse est pertinente et bien structurée. Continuez dans cette direction.')" class="btn-outline btn-sm">⭐ Excellent</button>
            <button type="button" onclick="setTemplate('Bon travail de manière générale. Quelques points à améliorer pour la prochaine soumission.')" class="btn-outline btn-sm">👍 Bon travail</button>
            <button type="button" onclick="setTemplate('Votre livrable nécessite des améliorations importantes. Veuillez revoir les points mentionnés et resoumettre.')" class="btn-outline btn-sm">⚠️ À améliorer</button>
            <button type="button" onclick="setTemplate('Votre livrable est incomplet. Merci de soumettre à nouveau avec tous les éléments requis.')" class="btn-outline btn-sm">📋 Incomplet</button>
          </div>
        </div>

        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

          <?php if (!empty($rubriqueCriteres)): ?>
          <div class="form-group">
            <label><i class="ti ti-table" style="color:var(--primary)"></i> Grille de rubrique
              <?php if ($sub['note_min'] ?? 0): ?>
              <small style="color:#dc2626">· Min total : <?= $sub['note_min'] ?></small>
              <?php endif; ?>
            </label>
            <table class="admin-table" style="font-size:13px">
              <thead><tr><th>Critère</th><th style="width:90px">Note</th><th style="width:50px">Max</th></tr></thead>
              <tbody>
                <?php foreach ($rubriqueCriteres as $i => $crit): ?>
                <?php $prevVal = $existingNotes[$i]['note'] ?? ''; ?>
                <tr>
                  <td><?= h($crit['nom'] ?? '') ?></td>
                  <td>
                    <input type="number" name="critere_note[<?= $i ?>]" min="0" max="<?= h($crit['points'] ?? 0) ?>" step="0.5"
                           value="<?= h($prevVal) ?>" required class="rubrique-note-input"
                           style="width:100%;padding:6px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px;text-align:center;box-sizing:border-box">
                  </td>
                  <td style="text-align:center;color:#6b7280"><?= h($crit['points'] ?? 0) ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <div style="text-align:right;margin-top:6px;font-size:13px;font-weight:700">
              Total : <span id="rubriqueTotalDisplay"><?= h($sub['note'] ?? 0) ?></span> / <?= $sub['note_max'] ?>
            </div>
          </div>
          <script>
          (function() {
            const inputs = document.querySelectorAll('.rubrique-note-input');
            const totalEl = document.getElementById('rubriqueTotalDisplay');
            function recompute() {
              let sum = 0;
              inputs.forEach(i => sum += parseFloat(i.value || 0));
              totalEl.textContent = sum;
            }
            inputs.forEach(i => i.addEventListener('input', recompute));
          })();
          </script>
          <?php else: ?>
          <div class="form-group">
            <label>Note (sur <?= $sub['note_max'] ?>)
              <?php if ($sub['note_min'] ?? 0): ?>
              <small style="color:#dc2626">· Min : <?= $sub['note_min'] ?></small>
              <?php endif; ?>
            </label>
            <input type="number" name="note" min="0" max="<?= $sub['note_max'] ?>" step="0.5"
                   value="<?= h($sub['note'] ?? '') ?>" required
                   style="width:100%;padding:10px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:15px;font-weight:700;text-align:center;box-sizing:border-box;font-family:inherit">
          </div>
          <?php endif; ?>

          <div class="form-group">
            <label>Décision</label>
            <select name="statut" style="width:100%;padding:10px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:14px;font-family:inherit">
              <option value="accepte" <?= ($sub['statut']==='accepte')?'selected':'' ?>>✅ Accepter</option>
              <option value="refuse" <?= ($sub['statut']==='refuse')?'selected':'' ?>>❌ Refuser (resoumission autorisée)</option>
            </select>
          </div>

          <div class="form-group">
            <label>Feedback à l'étudiant</label>
            <textarea name="feedback" id="feedbackArea" rows="6"
                      style="width:100%;padding:10px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;font-family:inherit;resize:vertical;box-sizing:border-box"
                      placeholder="Commentaires détaillés sur la soumission..."><?= h($sub['feedback'] ?? '') ?></textarea>
          </div>

          <?php if (!empty($sub['feedback_fichier'])): ?>
          <div style="margin-bottom:12px;padding:10px;background:#f0fdf4;border-radius:8px;font-size:12px;color:#15803d">
            <i class="ti ti-paperclip"></i> Pièce jointe actuelle :
            <a href="<?= SITE_URL ?>/assets/uploads/<?= h($sub['feedback_fichier']) ?>" target="_blank" style="color:#15803d">
              <?= h(basename($sub['feedback_fichier'])) ?>
            </a>
          </div>
          <?php endif; ?>

          <div class="form-group">
            <label>Pièce jointe (PDF, DOCX, image)</label>
            <input type="file" name="feedback_fichier" accept=".pdf,.docx,.jpg,.jpeg,.png"
                   style="width:100%;padding:8px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;box-sizing:border-box">
            <small style="color:#9ca3af;font-size:11px">Grille annotée, corrigé type, commentaires...</small>
          </div>

          <div class="form-group">
            <label>Feedback audio (optionnel)</label>
            <?php if (!empty($sub['audio_feedback_path'])): ?>
            <div style="margin-bottom:8px">
              <div style="font-size:11px;color:#6b7280;margin-bottom:4px">Feedback audio actuel :</div>
              <audio controls src="<?= SITE_URL ?>/<?= h($sub['audio_feedback_path']) ?>" style="width:100%"></audio>
            </div>
            <?php endif; ?>
            <div id="audioRecorder" data-target="feedback" data-id="<?= $id ?>" style="border:1px solid #e5e7eb;border-radius:8px;padding:12px">
              <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                <button type="button" class="btn-outline btn-sm" data-action="start"><i class="ti ti-microphone"></i> Démarrer</button>
                <button type="button" class="btn-outline btn-sm" data-action="stop" disabled><i class="ti ti-player-stop"></i> Arrêter</button>
                <span class="rec-status" style="font-size:12px;color:#6b7280"></span>
              </div>
              <div class="rec-preview" style="margin-top:8px"></div>
              <input type="hidden" name="audio_feedback_path" class="audio-path-input" value="">
            </div>
          </div>

          <button type="submit" class="btn-primary btn-full" style="padding:13px">
            <i class="ti ti-check"></i> Enregistrer la correction
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function setTemplate(text) {
  document.getElementById('feedbackArea').value = text;
  document.getElementById('feedbackArea').focus();
}

// Enregistreur audio réutilisable (MediaRecorder) — feedback coach
document.querySelectorAll('[id="audioRecorder"], .audioRecorder').forEach(function(box) {
  let mediaRecorder = null;
  let chunks = [];
  const startBtn  = box.querySelector('[data-action="start"]');
  const stopBtn   = box.querySelector('[data-action="stop"]');
  const statusEl  = box.querySelector('.rec-status');
  const previewEl = box.querySelector('.rec-preview');
  const pathInput = box.querySelector('.audio-path-input');
  const target    = box.dataset.target || 'feedback';

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

        try {
          const res = await fetch('<?= SITE_URL ?>/upload_audio.php', { method: 'POST', body: fd });
          const data = await res.json();
          if (data.path) {
            pathInput.value = data.path;
            statusEl.textContent = 'Audio prêt — sera enregistré à la validation.';
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
</body>
</html>
