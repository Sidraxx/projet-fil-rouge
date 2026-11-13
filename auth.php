<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/json.php';
require_once __DIR__ . '/includes/rate_limit.php';

const LOGIN_MAX_ATTEMPTS_PER_ACCOUNT = 5;
const LOGIN_MAX_ATTEMPTS_PER_IP = 20;
const LOGIN_WINDOW_SECONDS = 900;
const REGISTER_MAX_ATTEMPTS = 5;
const REGISTER_WINDOW_SECONDS = 3600;
const DATABASE_UNAVAILABLE_MESSAGE = 'Connexion indisponible pour le moment. Réessayez plus tard.';

function respond_rate_limited(int $seconds): never
{
    header('Retry-After: ' . $seconds);
    $minutes = (int) ceil($seconds / 60);

    json_response(['success' => false, 'message' => 'Trop de tentatives. Réessayez dans ' . $minutes . ' minute' . ($minutes > 1 ? 's' : '') . '.'], 429);
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
    $rawBody = app_read_body();
    if ($rawBody === null) {
        json_response(['success' => false, 'message' => 'Requête trop volumineuse.'], 413);
    }
    $payload = json_decode($rawBody, true);
    $action = is_array($payload) ? ($payload['action'] ?? '') : '';

    $csrfToken = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals(ensure_csrf_token(), $csrfToken)) {
        json_response(['success' => false, 'message' => 'Session expirée. Rechargez la page.'], 403);
    }

    try {
        $db = database();

        if ($action === 'register') {
            $ip = client_ip();
            $retry = rate_limit_retry_after('register-ip', $ip, REGISTER_MAX_ATTEMPTS, REGISTER_WINDOW_SECONDS);
            if ($retry > 0) {
                app_log('warning', 'Inscription refusée : limite de tentatives atteinte.');
                respond_rate_limited($retry);
            }
            rate_limit_hit('register-ip', $ip, REGISTER_WINDOW_SECONDS);

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
            // bcrypt ignore tout au-delà de 72 octets : on refuse plutôt que de tronquer en silence.
            if (strlen($password) > 72) {
                json_response(['success' => false, 'message' => 'Le mot de passe ne doit pas dépasser 72 caractères.'], 400);
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
            unset($_SESSION['csrf_token']);
            $_SESSION['user_id'] = (int) $db->lastInsertId();
            $_SESSION['username'] = $username;
            json_response(['success' => true, 'message' => 'Votre collection est prête. Bienvenue dans Absolute Cinema.', 'redirect' => 'index.php']);
        }

        if ($action === 'login') {
            $email = strtolower(trim((string) ($payload['email'] ?? '')));
            $password = (string) ($payload['password'] ?? '');
            $ip = client_ip();

            $retry = max(
                rate_limit_retry_after('login-account', $email, LOGIN_MAX_ATTEMPTS_PER_ACCOUNT, LOGIN_WINDOW_SECONDS),
                rate_limit_retry_after('login-ip', $ip, LOGIN_MAX_ATTEMPTS_PER_IP, LOGIN_WINDOW_SECONDS)
            );
            if ($retry > 0) {
                app_log('warning', 'Connexion refusée : limite de tentatives atteinte.');
                respond_rate_limited($retry);
            }

            $statement = $db->prepare('SELECT id, username, password_hash FROM users WHERE email = :email LIMIT 1');
            $statement->execute(['email' => $email]);
            $user = $statement->fetch();

            // Un hachage est vérifié même si le compte n'existe pas : le temps de réponse
            // ne révèle donc pas si l'adresse e-mail est inscrite.
            $hash = $user ? $user['password_hash'] : password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
            $passwordValid = password_verify($password, $hash);

            if (!$user || !$passwordValid) {
                rate_limit_hit('login-account', $email, LOGIN_WINDOW_SECONDS);
                rate_limit_hit('login-ip', $ip, LOGIN_WINDOW_SECONDS);
                json_response(['success' => false, 'message' => 'E-mail ou mot de passe incorrect.'], 400);
            }

            rate_limit_clear('login-account', $email);
            session_regenerate_id(true);
            unset($_SESSION['csrf_token']);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['username'] = $user['username'];
            json_response(['success' => true, 'message' => 'Connexion réussie. Votre prochaine carte vous attend.', 'redirect' => 'index.php']);

        }

        json_response(['success' => false, 'message' => 'Action inconnue.'], 400);
    } catch (PDOException $exception) {
        app_log_exception($exception, 'Authentification impossible');
        json_response(['success' => false, 'message' => app_debug_enabled()
            ? 'Connexion à la base « ' . DB_NAME . ' » impossible : ' . describe_database_error($exception)
            : DATABASE_UNAVAILABLE_MESSAGE], 500);
    } catch (AppDatabaseException $exception) {
        // Message déjà traduit par config.php / app_database_exception().
        json_response(['success' => false, 'message' => app_debug_enabled() ? $exception->getMessage() : DATABASE_UNAVAILABLE_MESSAGE], 500);
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
  <meta name="csrf-token" content="<?= e(ensure_csrf_token()) ?>">
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
  <script src="assets/js/auth.js?v=2"></script>
<?= app_console_script() ?>
</body>
</html>