<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

session_start();

function respond(bool $success, string $message, ?string $redirect = null): never
{
    http_response_code($success ? 200 : 400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
      'success' => $success,
      'message' => $message,
      'redirect' => $redirect,
    ], JSON_UNESCAPED_UNICODE);
    exit;
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
                time() - 42000,
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
            $statement = $db->prepare('SELECT id, username, password_hash FROM users WHERE email = :email LIMIT 1');
            $statement->execute(['email' => $email]);
            $user = $statement->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                respond(false, 'E-mail ou mot de passe incorrect.');
            }

            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['username'] = $user['username'];
            respond(true, 'Connexion réussie. Votre prochaine carte vous attend.', 'index.php');

        }

        respond(false, 'Action inconnue.');
    } catch (PDOException $exception) {
        respond(false, 'Impossible de joindre la base « ' . DB_NAME . ' ». Vérifiez que MySQL est démarré dans WAMP.');
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
  <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Manrope:wght@400;600;700&family=Playfair+Display:wght@500;600&display=swap" rel="stylesheet">
  <style>
    :root { --black: #070707; --panel: #11110f; --soft: #191816; --line: #2c2a25; --muted: #8c887d; --text: #f5f0e6; --gold: #c9a45c; --gold-light: #f1d38d; --danger: #df9081; }
    * { box-sizing: border-box; }
    body { margin: 0; min-height: 100vh; background: var(--black); color: var(--text); font-family: Manrope, sans-serif; }
    body::before { position: fixed; inset: 0; z-index: -1; background: radial-gradient(circle at 77% 14%, rgba(201, 164, 92, .1), transparent 27%), linear-gradient(128deg, #060606 10%, #11110f 58%, #080808); content: ""; }
    .shell { display: grid; grid-template-columns: minmax(320px, .92fr) minmax(430px, 1.08fr); min-height: 100vh; }
    .brand-side { position: relative; display: flex; flex-direction: column; justify-content: space-between; padding: clamp(28px, 5vw, 76px); border-right: 1px solid rgba(255,255,255,.08); overflow: hidden; }
    .brand-side::after { position: absolute; right: -120px; bottom: 11%; width: 360px; height: 360px; border: 1px solid rgba(201,164,92,.24); border-radius: 50%; box-shadow: 0 0 0 38px rgba(201,164,92,.025), 0 0 0 76px rgba(201,164,92,.018); content: ""; }
    .brand { position: relative; z-index: 1; display: inline-flex; align-items: center; gap: 12px; color: var(--text); font-size: 13px; font-weight: 700; letter-spacing: .2em; text-decoration: none; text-transform: uppercase; }
    .brand-mark { display: grid; width: 34px; height: 34px; place-items: center; border: 1px solid var(--gold); color: var(--gold-light); font-family: Georgia, serif; font-size: 17px; font-weight: 400; letter-spacing: 0; }
    .intro { position: relative; z-index: 1; max-width: 510px; padding: 70px 0 40px; }
    .eyebrow { display: flex; align-items: center; gap: 12px; color: var(--gold-light); font-family: "DM Mono", monospace; font-size: 10px; letter-spacing: .12em; text-transform: uppercase; }
    .eyebrow::before { width: 34px; height: 1px; background: var(--gold); content: ""; }
    h1 { max-width: 520px; margin: 22px 0 24px; font-family: "Playfair Display", Georgia, serif; font-size: clamp(43px, 5vw, 77px); font-weight: 500; line-height: 1.03; }
    .intro p { max-width: 410px; margin: 0; color: var(--muted); font-size: 14px; line-height: 1.9; }
    .side-note { position: relative; z-index: 1; display: flex; justify-content: space-between; max-width: 440px; padding-top: 18px; border-top: 1px solid var(--line); color: #66635b; font-family: "DM Mono", monospace; font-size: 10px; letter-spacing: .08em; text-transform: uppercase; }
    .form-side { display: grid; align-items: center; padding: clamp(30px, 8vw, 110px); }
    .auth-panel { width: min(100%, 480px); margin: auto; animation: rise .7s ease both; }
    .panel-header { margin-bottom: 34px; }
    .panel-header h2 { margin: 0 0 9px; font-size: 25px; font-weight: 600; }
    .panel-header p { margin: 0; color: var(--muted); font-size: 13px; }
    .tabs { display: grid; grid-template-columns: 1fr 1fr; margin-bottom: 30px; border-bottom: 1px solid var(--line); }
    .tab { position: relative; padding: 0 0 15px; border: 0; background: transparent; color: #68655e; cursor: pointer; font: inherit; font-size: 12px; text-align: left; }
    .tab::after { position: absolute; right: 0; bottom: -1px; left: 0; height: 1px; background: transparent; content: ""; }
    .tab.active { color: var(--gold-light); }
    .tab.active::after { background: var(--gold); }
    .form { display: none; }
    .form.active { display: block; animation: fade .25s ease both; }
    .field { margin-bottom: 19px; }
    label { display: block; margin-bottom: 8px; color: #b7b3a8; font-family: "DM Mono", monospace; font-size: 10px; letter-spacing: .08em; text-transform: uppercase; }
    input { width: 100%; padding: 15px 16px; border: 1px solid var(--line); border-radius: 0; outline: 0; background: var(--panel); color: var(--text); font: inherit; font-size: 13px; }
    input:focus { border-color: var(--gold); background: var(--soft); }
    .password-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .submit { display: flex; width: 100%; align-items: center; justify-content: center; gap: 12px; margin-top: 27px; padding: 16px 20px; border: 1px solid var(--gold); border-radius: 0; background: var(--gold); color: #15120c; cursor: pointer; font: inherit; font-size: 12px; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; transition: background .2s ease, transform .2s ease; }
    .submit:hover { background: var(--gold-light); transform: translateY(-2px); }
    .submit:disabled { cursor: wait; opacity: .65; transform: none; }
    .submit span { font-size: 17px; line-height: 0; }
    .message { min-height: 20px; margin: 19px 0 0; color: var(--muted); font-size: 12px; line-height: 1.5; }
    .message.error { color: var(--danger); }
    .message.success { color: var(--gold-light); }
    .legal { margin-top: 38px; color: #5e5b55; font-size: 10px; line-height: 1.7; text-align: center; }
    @keyframes rise { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes fade { from { opacity: 0; } to { opacity: 1; } }
    @media (max-width: 780px) { .shell { display: block; } .brand-side { min-height: 420px; padding: 26px; border-right: 0; border-bottom: 1px solid rgba(255,255,255,.08); } .intro { padding: 50px 0 25px; } h1 { font-size: clamp(43px, 12vw, 68px); } .form-side { padding: 52px 26px 64px; } }
    @media (max-width: 420px) { .password-row { grid-template-columns: 1fr; gap: 0; } }
  </style>
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
        message.textContent = result.message || 'Une erreur est survenue.'; message.className = `message ${result.success ? 'success' : 'error'}`;
        if (result.success && result.redirect) { window.location.assign(result.redirect); }
      } catch (error) { message.textContent = 'Le serveur est indisponible. Vérifiez que WAMP est démarré.'; message.className = 'message error'; } finally { submit.disabled = false; }
    }));
  </script>
</body>
</html>