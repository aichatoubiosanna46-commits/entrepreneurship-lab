<?php
// api/index.php — API REST publique Ariziki
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getPDO();

// Authentification par token
function apiAuth(PDO $pdo): ?array {
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!$auth || !str_starts_with($auth, 'Bearer ')) return null;
    $token = substr($auth, 7);
    $stmt = $pdo->prepare(
        'SELECT at.*, u.id as uid, u.prenom, u.nom, u.email, u.role
         FROM api_tokens at JOIN users u ON u.id = at.user_id
         WHERE at.token = ? AND at.actif = 1
         AND (at.expires_at IS NULL OR at.expires_at > NOW())
         LIMIT 1'
    );
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    if ($row) {
        $pdo->prepare('UPDATE api_tokens SET last_used = NOW() WHERE id = ?')->execute([$row['id']]);
        return $row;
    }
    return null;
}

function apiError(int $code, string $msg): void {
    http_response_code($code);
    echo json_encode(['error' => $msg, 'code' => $code]);
    exit;
}

function apiSuccess(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

$path   = trim($_GET['endpoint'] ?? '', '/');
$method = $_SERVER['REQUEST_METHOD'];
$user   = apiAuth($pdo);

// Routes publiques (sans auth)
match(true) {

    // GET /courses — liste des cours publiés
    $path === 'courses' && $method === 'GET' => (function() use ($pdo) {
        $cat   = $_GET['cat'] ?? '';
        $where = ["c.actif = 1", "c.statut = 'publie'"];
        $params = [];
        if ($cat) { $where[] = 'cat.nom = ?'; $params[] = $cat; }
        $rows = $pdo->prepare(
            'SELECT c.id, c.titre, c.slug, c.type, c.prix, c.description,
             cat.nom as categorie, c.created_at
             FROM courses c LEFT JOIN categories cat ON cat.id = c.category_id
             WHERE ' . implode(' AND ', $where) . ' ORDER BY c.created_at DESC'
        );
        $rows->execute($params);
        apiSuccess(['courses' => $rows->fetchAll(PDO::FETCH_ASSOC)]);
    })(),

    // GET /courses/{slug} — détail d'un cours
    preg_match('#^courses/([a-z0-9-]+)$#', $path, $m) && $method === 'GET' => (function() use ($pdo, $m) {
        $stmt = $pdo->prepare("SELECT c.*, cat.nom as categorie FROM courses c LEFT JOIN categories cat ON cat.id=c.category_id WHERE c.slug=? AND c.actif=1 LIMIT 1");
        $stmt->execute([$m[1]]);
        $c = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$c) apiError(404, 'Cours introuvable');
        apiSuccess(['course' => $c]);
    })(),

    // GET /categories
    $path === 'categories' && $method === 'GET' => (function() use ($pdo) {
        apiSuccess(['categories' => $pdo->query('SELECT * FROM categories ORDER BY nom')->fetchAll(PDO::FETCH_ASSOC)]);
    })(),

    // Routes authentifiées
    // GET /me — profil utilisateur
    $path === 'me' && $method === 'GET' => (function() use ($pdo, $user) {
        if (!$user) apiError(401, 'Token requis');
        $progress = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE user_id=? AND statut='actif'")->execute([$user['uid']]);
        apiSuccess(['user' => [
            'id'     => $user['uid'],
            'prenom' => $user['prenom'],
            'nom'    => $user['nom'],
            'email'  => $user['email'],
            'role'   => $user['role'],
            'xp'     => (int)$pdo->query("SELECT xp_total FROM users WHERE id=".(int)$user['uid'])->fetchColumn(),
        ]]);
    })(),

    // GET /me/enrollments
    $path === 'me/enrollments' && $method === 'GET' => (function() use ($pdo, $user) {
        if (!$user) apiError(401, 'Token requis');
        $stmt = $pdo->prepare("SELECT c.titre, c.slug, e.statut, e.created_at FROM enrollments e JOIN courses c ON c.id=e.course_id WHERE e.user_id=? AND e.statut='actif'");
        $stmt->execute([$user['uid']]);
        apiSuccess(['enrollments' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    })(),

    // GET /me/certificates
    $path === 'me/certificates' && $method === 'GET' => (function() use ($pdo, $user) {
        if (!$user) apiError(401, 'Token requis');
        $stmt = $pdo->prepare("SELECT cert.code_unique, c.titre, cert.delivre_le FROM certificates cert JOIN courses c ON c.id=cert.course_id WHERE cert.user_id=?");
        $stmt->execute([$user['uid']]);
        apiSuccess(['certificates' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    })(),

    // POST /tokens — créer un token API (authentifié via session)
    $path === 'tokens' && $method === 'POST' => (function() use ($pdo) {
        if (!isset($_SESSION['user_id'])) apiError(401, 'Connexion requise');
        $nom   = trim($_POST['nom'] ?? 'Mon app');
        $token = bin2hex(random_bytes(32));
        $pdo->prepare('INSERT INTO api_tokens (user_id, token, nom) VALUES (?,?,?)')->execute([$_SESSION['user_id'], $token, $nom]);
        apiSuccess(['token' => $token, 'nom' => $nom], 201);
    })(),

    default => apiError(404, 'Endpoint introuvable. Consultez /api/docs.php')
};
