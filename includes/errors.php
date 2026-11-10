<?php

declare(strict_types=1);

/**
 * Gestion centralisée des erreurs applicatives.
 *
 * Principes :
 *   - une erreur de base de données ne doit jamais interrompre brutalement
 *     l'affichage : elle est traduite en message lisible pour l'utilisateur ;
 *   - chaque erreur est écrite dans les logs PHP (error_log) avec son contexte ;
 *   - les erreurs sont aussi envoyées à la console du navigateur
 *     (voir app_console_script()), pour diagnostiquer sans lire les logs serveur.
 */

const APP_ERROR_PREFIX = '[AbsoluteCinema]';

/**
 * Détails techniques (console, page d'erreur, messages de base) visibles
 * seulement en développement : définir APP_DEBUG=1. En production, les
 * visiteurs ne voient que des messages génériques ; les logs restent complets.
 */
function app_debug_enabled(): bool
{
    return (string) getenv('APP_DEBUG') === '1';
}

/** Exception « base de données » portant déjà un message compréhensible. */
final class AppDatabaseException extends RuntimeException
{
}

/** @return list<array{level: string, message: string, context: array<string, mixed>}> */
function app_errors(): array
{
    $errors = $GLOBALS['app_errors'] ?? [];

    return is_array($errors) ? $errors : [];
}

/** Journalise un message : log PHP + futur affichage dans la console du navigateur. */
function app_log(string $level, string $message, array $context = []): void
{
    $GLOBALS['app_errors'][] = [
        'level' => $level,
        'message' => $message,
        'context' => $context,
    ];

    $line = APP_ERROR_PREFIX . ' ' . strtoupper($level) . ' : ' . $message;

    foreach ($context as $key => $value) {
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } elseif (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }

        $line .= ' | ' . $key . '=' . (string) $value;
    }

    if ((string) getenv('APP_LOG_SILENT') !== '1') {
        error_log($line);
    }
}

/**
 * Contexte technique d'une exception (fichier, ligne, code SQL).
 *
 * @return array<string, mixed>
 */
function app_exception_context(Throwable $exception): array
{
    $context = [
        'exception' => $exception::class,
        'fichier' => $exception->getFile() . ':' . $exception->getLine(),
    ];

    if ($exception instanceof PDOException) {
        $context['sqlstate'] = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        $context['code_mysql'] = (int) ($exception->errorInfo[1] ?? $exception->getCode());
    } else {
        $context['code'] = $exception->getCode();
    }

    $previous = $exception->getPrevious();
    if ($previous !== null) {
        $context['cause'] = $previous->getMessage();
    }

    return $context;
}

/** Journalise une exception sans interrompre le flux. */
function app_log_exception(Throwable $exception, string $message): void
{
    app_log('error', $message, app_exception_context($exception));
}

/**
 * Traduit une erreur MySQL en explication actionnable pour l'utilisateur.
 * Le code SQL complet reste disponible dans les logs et la console.
 */
function describe_database_error(PDOException $exception): string
{
    // Sans APP_DEBUG, le détail (noms de tables, conseils de migration) reste dans les logs.
    if (!app_debug_enabled()) {
        return 'la base de données est momentanément indisponible. Réessayez dans un instant.';
    }

    $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
    $code = (int) ($exception->errorInfo[1] ?? $exception->getCode());
    $database = defined('DB_NAME') ? DB_NAME : 'cinema';
    $host = defined('DB_HOST') ? DB_HOST : 'localhost';

    return match (true) {
        $code === 1045 => 'MySQL refuse l’utilisateur configuré : vérifiez DB_USER et DB_PASSWORD dans config.php.',
        $code === 1049 => 'La base de données « ' . $database . ' » n’existe pas encore : importez cinema_mysql.sql.',
        $code === 1146 => 'Une table attendue par l’application est absente : exécutez les scripts de database/ dans l’ordre décrit dans le README.',
        $code === 1054 => 'Une colonne attendue par l’application manque : le schéma de la base est plus ancien que le code. Réexécutez le script database/ concerné (par exemple database/user_decks.sql).',
        $code === 1062 => 'La base contient encore une contrainte d’unicité ancienne (un seul deck par joueur). Réexécutez database/user_decks.sql pour la migrer.',
        $code === 1364 => 'Une colonne obligatoire n’a pas de valeur par défaut : le schéma de la base ne correspond pas au code.',
        $code === 1451 => 'Suppression refusée : d’autres enregistrements dépendent encore de cette ligne.',
        $code === 1452 => 'Une référence est absente de la base (joueur ou carte). Si vous venez d’importer un autre dump, déconnectez-vous puis reconnectez-vous : votre session pointait vers un ancien joueur.',
        $code === 1205, $code === 1213 => 'Deux requêtes se sont bloquées mutuellement (verrou ou deadlock) : réessayez dans un instant.',
        $code === 2002 => 'Le serveur MySQL est injoignable sur ' . $host . '. Démarrez MySQL (WAMP ou « brew services start mysql »).',
        $code === 2005 => 'L’hôte MySQL est introuvable. Vérifiez DB_HOST dans config.php.',
        default => 'Erreur MySQL inattendue (SQLSTATE ' . $sqlState . ($code > 0 ? ', code ' . $code : '') . ').',
    };
}

/** Construit (et journalise) l'exception applicative d'une panne de base. */
function app_database_exception(PDOException $exception, string $action): AppDatabaseException
{
    $message = $action . ' : ' . describe_database_error($exception);
    app_log('error', $message, app_exception_context($exception));

    return new AppDatabaseException($message, 0, $exception);
}

/**
 * Message à afficher pour une erreur attrapée par une page : jamais de trace
 * brute, toujours une explication actionnable.
 */
function app_error_message(Throwable $error): string
{
    return match (true) {
        $error instanceof PDOException => describe_database_error($error),
        $error instanceof RuntimeException => $error->getMessage(),
        default => 'Une erreur interne est survenue. Le détail est dans la console et les logs PHP.',
    };
}

/**
 * Script à injecter dans la page : expose les messages applicatifs et les
 * erreurs JavaScript dans la console du navigateur.
 */
function app_console_script(): string
{
    $payload = json_encode(
        app_debug_enabled() ? app_errors() : [],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ) ?: '[]';

    return <<<HTML
<script>
  window.appErrors = {$payload};
  (function () {
    var errors = Array.isArray(window.appErrors) ? window.appErrors : [];
    if (errors.length > 0) {
      console.groupCollapsed(
        '%cAbsolute Cinema — ' + errors.length + ' message(s) applicatif(s)',
        'color:#3ddc97;font-weight:600'
      );
      errors.forEach(function (entry) {
        var method = entry.level === 'error' || entry.level === 'fatal'
          ? 'error'
          : (entry.level === 'warning' ? 'warn' : 'info');
        console[method]('[' + String(entry.level).toUpperCase() + '] ' + entry.message, entry.context || {});
      });
      console.groupEnd();
    }
    // Erreurs JavaScript non interceptées : toujours visibles (F12 → Console).
    window.addEventListener('error', function (event) {
      console.error('[JS] erreur non interceptée :', event.message, event.filename + ':' + event.lineno + ':' + event.colno, event.error || '');
    });
    window.addEventListener('unhandledrejection', function (event) {
      console.error('[JS] promesse rejetée sans capture :', event.reason);
    });
  })();
</script>
HTML;
}

/** Vrai quand la requête attend du JSON (API) plutôt qu'une page HTML. */
function app_wants_json(): bool
{
    $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
    $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
    $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '');

    return str_contains($accept, 'application/json')
        || str_contains($contentType, 'application/json')
        || preg_match('/(deck-api|open-pack)\.php$/', $script) === 1;
}

/** Page d'erreur minimale : aucune trace brute affichée, détails dans la console. */
function app_render_fatal_page(string $message, ?Throwable $exception = null): void
{
    $safeMessage = htmlspecialchars($message !== '' ? $message : 'Une erreur interne est survenue.', ENT_QUOTES, 'UTF-8');

    // Page déjà en cours d'envoi : on ajoute un encart au lieu de casser le HTML.
    if (headers_sent()) {
        echo '<div style="margin:1rem;padding:1rem;border:1px solid rgba(251,191,36,.4);'
            . 'border-radius:.75rem;background:rgba(251,191,36,.1);color:#fcd34d;font-family:system-ui,sans-serif">'
            . '<strong>Une erreur est survenue.</strong> ' . $safeMessage
            . '</div>' . app_console_script();

        return;
    }

    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');

    $safeDetails = '';

    if ($exception !== null && app_debug_enabled()) {
        $details = $exception::class . ' : ' . $exception->getMessage()
            . ' (' . $exception->getFile() . ':' . $exception->getLine() . ')';
        $safeDetails = htmlspecialchars($details, ENT_QUOTES, 'UTF-8');
    }

    $console = app_console_script();

    // Les pages de pages/ doivent revenir à la racine du projet.
    $root = str_contains((string) ($_SERVER['SCRIPT_NAME'] ?? ''), '/pages/') ? '../' : '';
    $detailsBlock = $safeDetails !== '' ? '<code>' . $safeDetails . '</code>' : '';

    echo <<<HTML
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Erreur | Absolute Cinema</title>
  <style>
    :root { color-scheme: dark; }
    * { box-sizing: border-box; }
    body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 2rem;
           background: #0a0a0a; color: #e7e5e4; font-family: Manrope, system-ui, sans-serif; }
    main { width: min(100%, 40rem); border: 1px solid rgba(255, 255, 255, .1); border-radius: 1rem;
           background: #141414; padding: 2rem; }
    .eyebrow { margin: 0 0 .75rem; font-size: .7rem; letter-spacing: .22em; text-transform: uppercase; color: #3ddc97; }
    h1 { margin: 0 0 1rem; font-size: 1.6rem; }
    .message { margin: 0 0 1rem; line-height: 1.6; color: #fcd34d; }
    .hint { margin: 0 0 1.5rem; font-size: .85rem; line-height: 1.5; color: #a8a29e; }
    code { display: block; padding: .75rem; border-radius: .5rem; background: #0a0a0a;
           font-family: "DM Mono", ui-monospace, monospace; font-size: .75rem; color: #a8a29e; overflow-wrap: anywhere; }
    a { display: inline-block; margin-top: 1.5rem; padding: .65rem 1.1rem; border-radius: 999px;
        background: #3ddc97; color: #000; font-size: .85rem; font-weight: 600; text-decoration: none; }
  </style>
</head>
<body>
  <main>
    <p class="eyebrow">Absolute Cinema</p>
    <h1>Une erreur est survenue</h1>
    <p class="message">{$safeMessage}</p>
    <p class="hint">Le détail technique est écrit dans les logs PHP et dans la console du navigateur (F12 → Console).</p>
    {$detailsBlock}
    <a href="{$root}index.php">Retour à l’accueil</a>
  </main>
  {$console}
</body>
</html>
HTML;

    exit;
}

/** Dernier filet : exception non interceptée (aucun throw brut affiché). */
function app_handle_uncaught(Throwable $exception): void
{
    if ($exception instanceof AppDatabaseException) {
        $message = $exception->getMessage();
    } else {
        app_log_exception($exception, 'Exception non gérée : ' . $exception->getMessage());
        $message = 'Une erreur interne empêche l’affichage de cette page.';
    }

    if (app_wants_json()) {
        if (headers_sent()) {
            return;
        }

        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(
            ['success' => false, 'message' => $message],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        exit;
    }

    app_render_fatal_page($message, $exception);
}

/**
 * Les avertissements PHP sont journalisés (logs + console) puis étouffés :
 * l'utilisateur ne voit pas de warning brut au milieu de la page.
 */
function app_handle_php_error(int $severity, string $message, string $file = '', int $line = 0): bool
{
    if ((error_reporting() & $severity) === 0) {
        return false;
    }

    app_log('warning', $message, ['fichier' => $file . ':' . $line, 'gravite' => $severity]);

    return true;
}

/** Les erreurs fatales (parse, mémoire...) n'ont pas de contexte : on les logue. */
function app_handle_shutdown(): void
{
    $error = error_get_last();

    if ($error === null || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }

    app_log('fatal', $error['message'], ['fichier' => $error['file'] . ':' . $error['line']]);
}

// En CLI (PHPUnit), PHP gère ses propres erreurs : on n'installe rien.
if (PHP_SAPI !== 'cli') {
    set_exception_handler('app_handle_uncaught');
    set_error_handler('app_handle_php_error');
    register_shutdown_function('app_handle_shutdown');
}
