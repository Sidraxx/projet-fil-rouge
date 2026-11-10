<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

session_start();

function respond(bool $success, string $message, ?string $redirect = null, int $statusCode = 200): never
{
    http_response_code($success ? $statusCode : ($statusCode === 200 ? 400 : $statusCode));
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
      'success' => $success,
      'message' => $message,
      'redirect' => $redirect,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

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
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
      'authenticated' => isset($_SESSION['user_id']),
      'redirect' => 'auth.php',
    ], JSON_UNESCAPED_UNICODE);
    exit;
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
                respond(false, 'Choisissez un pseudo de 3 à 50 caractères, sans espace.');
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
                respond(false, 'Ce pseudo ou cette adresse e-mail est déjà utilisé.');
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
            respond(true, 'Votre collection est prête. Bienvenue dans Absolute Cinema.', 'index.php');
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
                respond(false, 'Trop de tentatives de connexion. Réessayez dans 15 minutes.', null, 429);
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
                respond(false, 'E-mail ou mot de passe incorrect.');
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
            respond(true, 'Connexion réussie. Votre prochaine carte vous attend.', 'index.php');

        }

        respond(false, 'Action inconnue.');
    } catch (PDOException $exception) {
        app_log_exception($exception, 'Authentification impossible');
        respond(false, 'Connexion à la base « ' . DB_NAME . ' » impossible : ' . describe_database_error($exception));
    } catch (AppDatabaseException $exception) {
        // Message déjà traduit par config.php / app_database_exception().
        respond(false, $exception->getMessage());
    } catch (Throwable $exception) {
        app_log_exception($exception, 'Authentification impossible');
        respond(false, 'Impossible de finaliser la connexion : une erreur interne est survenue.');
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
  <script>
    const tabs = document.querySelectorAll('.tab');
    const forms = document.querySelectorAll('.form');
    const message = document.querySelector('#message');
    const panelTitle = document.querySelector('#panel-title');
    const panelSubtitle = document.querySelector('#panel-subtitle');
    function setMode(mode) {
      const isLogin = mode === 'login';
      tabs.forEach((tab) => { const active = tab.id === `${mode}-tab`; tab.classList.toggle('active', active); tab.setAttribute('aria-selected', active); });
      forms.forEach((form) => form.classList.toggle('active', form.dataset.action === mode));
      panelTitle.textContent = isLogin ? 'Ravi de vous revoir' : 'Entrez dans la partie';
      panelSubtitle.textContent = isLogin ? 'Connectez-vous pour retrouver votre deck.' : 'Créez votre compte et commencez votre collection.';
      message.textContent = ''; message.className = 'message';
    }
    tabs.forEach((tab) => tab.addEventListener('click', () => setMode(tab.id.replace('-tab', ''))));
    forms.forEach((form) => form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const submit = form.querySelector('.submit');
      const data = Object.fromEntries(new FormData(form));
      if (!form.checkValidity()) { form.reportValidity(); return; }
      if (form.dataset.action === 'register' && data.password !== data.password_confirmation) { message.textContent = 'Les mots de passe ne correspondent pas.'; message.className = 'message error'; return; }
      submit.disabled = true; message.textContent = 'Chargement de votre collection...'; message.className = 'message';
      try {
        const response = await fetch('auth.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: form.dataset.action, ...data }) });
        const result = await response.json();
        if (!result.success) { console.error('[auth] action refusée :', { status: response.status, message: result.message }); }
        message.textContent = result.message || 'Une erreur est survenue.'; message.className = `message ${result.success ? 'success' : 'error'}`;
        if (result.success && result.redirect) { window.location.assign(result.redirect); }
      } catch (error) { console.error('[auth] requête impossible (serveur PHP arrêté ?) :', error); message.textContent = 'Le serveur est indisponible. Vérifiez que WAMP est démarré.'; message.className = 'message error'; } finally { submit.disabled = false; }
    }));
  </script>
<?= app_console_script() ?>
</body>
</html>