<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/errors.php';

$localConfigFile = __DIR__ . '/includes/database.local.php';
$localConfig = is_file($localConfigFile) ? require $localConfigFile : [];

if (!is_array($localConfig)) {
    $localConfig = [];
}

$databaseConfig = [
    'host' => getenv('DB_HOST') ?: ($localConfig['host'] ?? 'localhost'),
    'name' => getenv('DB_NAME') ?: ($localConfig['name'] ?? 'cinema'),
    'user' => getenv('DB_USER') ?: ($localConfig['user'] ?? 'root'),
    'password' => getenv('DB_PASSWORD') ?: ($localConfig['password'] ?? ''),
    'port' => getenv('DB_PORT') ?: ($localConfig['port'] ?? '3306'),
];

/**
 * Connexion PDO partagée.
 *
 * Une panne de connexion est journalisée puis traduite en
 * AppDatabaseException (message lisible) : le code appelant n'a jamais à
 * gérer une PDOException brute.
 */
function database(): PDO
{
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    try {
        $connection = new PDO(
            'mysql:host=' . $databaseConfig['host'] . ';port=' . $databaseConfig['port'] . ';dbname=' . $databaseConfig['name'] . ';charset=utf8mb4',
            $databaseConfig['user'],
            $databaseConfig['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (PDOException $exception) {
        $connection = null;

        throw app_database_exception(
            $exception,
            'Connexion à la base de données impossible'
        );
    }

    app_log('info', 'Connexion MySQL établie.');

    return $connection;
}
