<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/json.php';
require_once __DIR__ . '/includes/rate_limit.php';

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

$retry = rate_limit_api('settings', (string) $_SESSION['user_id'], 60);
if ($retry > 0) {
    header('Retry-After: ' . $retry);
    json_response(['success' => false, 'message' => 'Trop de requêtes. Patientez un instant.'], 429);
}

$rawBody = app_read_body();
if ($rawBody === null) {
    json_response(['success' => false, 'message' => 'Requête trop volumineuse.'], 413);
}
$payload = json_decode($rawBody, true);
$action = is_array($payload) ? (string) ($payload['action'] ?? '') : '';

if (!in_array($action, ['update_username', 'delete_account'], true)) {
    json_response(['success' => false, 'message' => 'Action inconnue.'], 400);
}

if ($action === 'delete_account' && ($payload['confirmation'] ?? '') !== 'SUPPRIMER') {
    json_response(['success' => false, 'message' => 'Saisissez SUPPRIMER pour confirmer la suppression.'], 400);
}

try {
    $db = database();

    if ($action === 'delete_account') {
        $db->beginTransaction();
        $delete = $db->prepare('DELETE FROM users WHERE id = :id');
        $delete->execute(['id' => $_SESSION['user_id']]);

        if ($delete->rowCount() !== 1) {
            $db->rollBack();
            json_response(['success' => false, 'message' => 'Compte introuvable.'], 404);
        }

        $db->commit();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => (bool) $params['secure'],
                'httponly' => (bool) $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }

        session_destroy();
        json_response(['success' => true, 'message' => 'Votre compte a été supprimé.', 'redirect' => '../auth.php']);
    }

    $username = trim((string) ($payload['username'] ?? ''));
    if (!preg_match('/^[\p{L}0-9_.-]{3,50}$/u', $username)) {
        json_response(['success' => false, 'message' => 'Choisissez un pseudo de 3 à 50 caractères, sans espace.'], 400);
    }

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
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    app_log_exception($error, 'Enregistrement du pseudo impossible (joueur ' . (int) $_SESSION['user_id'] . ')');
    json_response(['success' => false, 'message' => app_error_message($error)], 500);
}
