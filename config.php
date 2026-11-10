<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/errors.php';
require_once __DIR__ . '/includes/security.php';

const DB_HOST = 'localhost';
const DB_NAME = 'cinema';
const DB_USER = 'root';
const DB_PASSWORD = '';

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
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASSWORD,
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
            'Connexion à la base « ' . DB_NAME . ' » sur ' . DB_HOST . ' impossible'
        );
    }

    app_log('info', 'Connexion MySQL établie sur « ' . DB_NAME . ' » (' . DB_HOST . ').');

    return $connection;
}

if (PHP_SAPI !== 'cli') {
    app_configure_session();
    app_send_security_headers();
}
