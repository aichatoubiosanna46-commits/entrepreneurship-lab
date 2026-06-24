<?php
require_once __DIR__ . '/../config/database.php';

$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    if ($titre) {
        $pdo->prepare('INSERT INTO quizzes (titre, score_min, actif) VALUES (?,60,1)')
            ->execute([$titre]);
        echo 'Quiz créé ! ID: ' . $pdo->lastInsertId();
    } else {
        echo 'Titre vide';
    }
    exit;
}
?>
<form method="POST">
  <input type="text" name="titre" placeholder="Titre du quiz">
  <button type="submit">Créer</button>
</form>