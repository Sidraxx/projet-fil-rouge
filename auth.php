<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

session_start();
header('Content-Type: application/json; charset=utf-8');

function respond(bool $success, string $message): never
{
    http_response_code($success ? 200 : 400);
    echo json_encode([
        'success' => $success,
        'message' => $message,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Méthode non autorisée.');
}

$payload = json_decode(file_get_contents('php://input'), true);
$action = is_array($payload) ? ($payload['action'] ?? '') : '';

try {
    $db = database();

    if ($action === 'register') {
        $username = trim((string) ($payload['username'] ?? ''));
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $password = (string) ($payload['password'] ?? '');
        $confirmation = (string) ($payload['password_confirmation'] ?? '');

        if (!preg_match('/^[\p{L}0-9_.-]{3,50}$/u', $username)) {
            respond(false, 'Choisissez un nom de 3 à 50 caractères, sans espace.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            respond(false, 'Saisissez une adresse e-mail valide.');
        }
        if (strlen($password) < 8) {
            respond(false, 'Le mot de passe doit contenir au moins 8 caractères.');
        }
        if ($password !== $confirmation) {
            respond(false, 'Les mots de passe ne correspondent pas.');
        }

        $existing = $db->prepare('SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1');
        $existing->execute(['username' => $username, 'email' => $email]);
        if ($existing->fetch()) {
            respond(false, 'Ce nom d’utilisateur ou cette adresse e-mail est déjà utilisé.');
        }

        $statement = $db->prepare('INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)');
        $statement->execute([
            'username' => $username,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $db->lastInsertId();
        $_SESSION['username'] = $username;
        respond(true, 'Votre espace est prêt. Bienvenue dans Nova Film.');
    }

    if ($action === 'login') {
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $password = (string) ($payload['password'] ?? '');
        $statement = $db->prepare('SELECT id, username, password_hash FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            respond(false, 'E-mail ou mot de passe incorrect.');
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['username'] = $user['username'];
        respond(true, 'Connexion réussie. Bonne séance.');
    }

    respond(false, 'Action inconnue.');
} catch (PDOException $exception) {
    respond(false, 'Impossible de joindre la base de données. Vérifiez que la base « cinema » existe dans WAMP.');
}