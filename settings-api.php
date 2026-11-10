<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/json.php';

if (!isset($_SESSION['user_id'])) {
    json_response(['success' => false, 'message' => 'Connexion requise.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Méthode non autorisée.'], 405);
}

$token = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!hash_equals(ensure_csrf_token(), $token)) {
    json_response(['success' => false, 'message' => 'Session expirée. Rechargez la page.'], 403);
}

$payload = json_decode((string) file_get_contents('php://input'), true);
$action = is_array($payload) ? (string) ($payload['action'] ?? '') : '';

if ($action !== 'update_username') {
    json_response(['success' => false, 'message' => 'Action inconnue.'], 400);
}

$username = trim((string) ($payload['username'] ?? ''));
if (!preg_match('/^[\p{L}0-9_.-]{3,50}$/u', $username)) {
    json_response(['success' => false, 'message' => 'Choisissez un pseudo de 3 à 50 caractères, sans espace.'], 400);
}

try {
    $db = database();
    $check = $db->prepare('SELECT id FROM users WHERE username = :username AND id <> :id LIMIT 1');
    $check->execute(['username' => $username, 'id' => $_SESSION['user_id']]);

    if ($check->fetch()) {
        json_response(['success' => false, 'message' => 'Ce pseudo est déjà utilisé.'], 409);
    }

    $update = $db->prepare('UPDATE users SET username = :username WHERE id = :id');
    $update->execute(['username' => $username, 'id' => $_SESSION['user_id']]);
    $_SESSION['username'] = $username;

    json_response(['success' => true, 'message' => 'Pseudo mis à jour.', 'username' => $username]);
} catch (Throwable $error) {
    app_log_exception($error, 'Enregistrement du pseudo impossible (joueur ' . (int) $_SESSION['user_id'] . ')');
    json_response(['success' => false, 'message' => app_error_message($error)], 500);
}
