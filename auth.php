<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/json.php';

session_start();

function ensureLoginAttemptsTable(PDO $db): void
{
    $db->exec(
        'CREATE TABLE IF NOT EXISTS login_attempts (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            email VARCHAR(255) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            attempted_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            INDEX idx_login_attempts_email_time (email, attempted_at),
            INDEX idx_login_attempts_ip_time (ip_address, attempted_at)
        ) ENGINE=InnoDB'
    );
}

function clientIpAddress(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'session') {
    json_response([
      'authenticated' => isset($_SESSION['user_id']),
      'redirect' => 'auth.php',
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['action']) && isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'logout') {
    $token = (string) ($_POST['csrf_token'] ?? '');
    $expected = (string) ($_SESSION['csrf_token'] ?? '');

    if ($expected !== '' && hash_equals($expected, $token)) {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 5000,
                $params['path'],
                $params['domain'],
                (bool) $params['secure'],
                (bool) $params['httponly']
            );
        }

        session_destroy();
        header('Location: auth.php');
        exit;
    }

    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                json_response(['success' => false, 'message' => 'Choisissez un pseudo de 3 à 50 caractères, sans espace.'], 400);
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                json_response(['success' => false, 'message' => 'Saisissez une adresse e-mail valide.'], 400);
            }
            if (strlen($password) < 8) {
                json_response(['success' => false, 'message' => 'Le mot de passe doit contenir au moins 8 caractères.'], 400);
            }
            if ($password !== $confirmation) {
                json_response(['success' => false, 'message' => 'Les mots de passe ne correspondent pas.'], 400);
            }

            $existing = $db->prepare('SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1');
            $existing->execute(['username' => $username, 'email' => $email]);
            if ($existing->fetch()) {
                json_response(['success' => false, 'message' => 'Ce pseudo ou cette adresse e-mail est déjà utilisé.'], 400);
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
            json_response(['success' => true, 'message' => 'Votre collection est prête. Bienvenue dans Absolute Cinema.', 'redirect' => 'index.php']);
        }

        if ($action === 'login') {
            $email = strtolower(trim((string) ($payload['email'] ?? '')));
            $password = (string) ($payload['password'] ?? '');
            $ipAddress = clientIpAddress();
            $maxLoginAttempts = 5;

            ensureLoginAttemptsTable($db);
            $attempts = $db->prepare(
                'SELECT COUNT(*) FROM login_attempts
                 WHERE attempted_at >= (NOW() - INTERVAL 15 MINUTE)
                 AND (email = :email OR ip_address = :ip_address)'
            );
            $attempts->execute([
                'email' => $email,
                'ip_address' => $ipAddress,
            ]);

            if ((int) $attempts->fetchColumn() >= $maxLoginAttempts) {
                json_response(['success' => false, 'message' => 'Trop de tentatives de connexion. Réessayez dans 15 minutes.'], 429);
            }

            $statement = $db->prepare('SELECT id, username, password_hash FROM users WHERE email = :email LIMIT 1');
            $statement->execute(['email' => $email]);
            $user = $statement->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $failedAttempt = $db->prepare(
                    'INSERT INTO login_attempts (email, ip_address, attempted_at)
                     VALUES (:email, :ip_address, NOW())'
                );
                $failedAttempt->execute([
                    'email' => $email,
                    'ip_address' => $ipAddress,
                ]);
                json_response(['success' => false, 'message' => 'E-mail ou mot de passe incorrect.'], 400);
            }

            $clearAttempts = $db->prepare(
                'DELETE FROM login_attempts WHERE email = :email AND ip_address = :ip_address'
            );
            $clearAttempts->execute([
                'email' => $email,
                'ip_address' => $ipAddress,
            ]);
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['username'] = $user['username'];
            json_response(['success' => true, 'message' => 'Connexion réussie. Votre prochaine carte vous attend.', 'redirect' => 'index.php']);

        }

        json_response(['success' => false, 'message' => 'Action inconnue.'], 400);
    } catch (PDOException $exception) {
        app_log_exception($exception, 'Authentification impossible');
        json_response(['success' => false, 'message' => 'Connexion impossible : ' . describe_database_error($exception)], 500);
    } catch (AppDatabaseException $exception) {
        // Message déjà traduit par config.php / app_database_exception().
        json_response(['success' => false, 'message' => $exception->getMessage()], 500);
    } catch (Throwable $exception) {
        app_log_exception($exception, 'Authentification impossible');
        json_response(['success' => false, 'message' => 'Impossible de finaliser la connexion : une erreur interne est survenue.'], 500);
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Rejoignez Absolute Cinema, le TCG de cartes de films.">
  <title>Absolute Cinema | TCG de cartes de films</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
  <main class="shell">
    <section class="brand-side" aria-labelledby="welcome-title">
      <a class="brand" href="auth.php" aria-label="Absolute Cinema, accueil"><span class="brand-mark">A</span><span>Absolute Cinema</span></a>
      <div class="intro">
        <div class="eyebrow">Le TCG des films cultes</div>
        <h1 id="welcome-title">Collectionnez le cinéma.</h1>
        <p>Construisez votre deck de cartes de films, révélez les classiques et affrontez les autres cinéphiles dans l'univers d'Absolute Cinema.</p>
      </div>
      <div class="side-note"><span>Film Trading Card Game</span><span>AC / 001</span></div>
    </section>
    <section class="form-side" aria-label="Accès à votre collection">
      <div class="auth-panel">
        <header class="panel-header"><h2 id="panel-title">Ravi de vous revoir</h2><p id="panel-subtitle">Connectez-vous pour retrouver votre deck.</p></header>
        <div class="tabs" role="tablist" aria-label="Choix d'accès">
          <button class="tab active" id="login-tab" type="button" role="tab" aria-selected="true" aria-controls="login-form">Se connecter</button>
          <button class="tab" id="register-tab" type="button" role="tab" aria-selected="false" aria-controls="register-form">Créer un compte</button>
        </div>
        <form class="form active" id="login-form" data-action="login" novalidate>
          <div class="field"><label for="login-email">Adresse e-mail</label><input id="login-email" name="email" type="email" autocomplete="email" placeholder="vous@exemple.fr" required></div>
          <div class="field"><label for="login-password">Mot de passe</label><input id="login-password" name="password" type="password" autocomplete="current-password" placeholder="Votre mot de passe" required></div>
          <button class="submit" type="submit">Ouvrir ma collection <span aria-hidden="true">↗</span></button>
        </form>
        <form class="form" id="register-form" data-action="register" novalidate>
          <div class="field"><label for="register-username">Pseudo de joueur</label><input id="register-username" name="username" type="text" autocomplete="username" minlength="3" maxlength="50" placeholder="Votre pseudo" required></div>
          <div class="field"><label for="register-email">Adresse e-mail</label><input id="register-email" name="email" type="email" autocomplete="email" placeholder="vous@exemple.fr" required></div>
          <div class="password-row"><div class="field"><label for="register-password">Mot de passe</label><input id="register-password" name="password" type="password" autocomplete="new-password" minlength="8" placeholder="8 caractères min." required></div><div class="field"><label for="register-password-confirmation">Confirmation</label><input id="register-password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" placeholder="Répétez-le" required></div></div>
          <button class="submit" type="submit">Créer mon deck <span aria-hidden="true">↗</span></button>
        </form>
        <p class="message" id="message" role="status" aria-live="polite"></p>
        <p class="legal">Chaque carte raconte une histoire. Chaque deck révèle votre regard.</p>
      </div>
    </section>
  </main>
  <script src="assets/js/auth.js"></script>
<?= app_console_script() ?>
</body>
</html>