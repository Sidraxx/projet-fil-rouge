<?php

declare(strict_types=1);

/**
 * Base de données de test : `cinema_test`.
 *
 * Reconstruite depuis les scripts SQL du projet (`cinema_mysql.sql`,
 * `clean_movies.sql`, `rarity.sql`, `user_cards.sql`, `user_pack_state.sql`)
 * à chaque exécution de la suite, pour rester déterministe.
 *
 * Les identifiants se règlent via les variables d'environnement TEST_DB_*
 * déclarées dans `phpunit.xml` ; une variable définie dans le shell
 * reste prioritaire sur la valeur du fichier XML.
 */
final class TestDatabase
{
    /** Chemins des scripts applicatifs, dans l'ordre d'exécution. */
    private const SCRIPTS = [
        __DIR__ . '/../../cinema_mysql.sql',
        __DIR__ . '/../../database/clean_movies.sql',
        __DIR__ . '/../../database/rarity.sql',
        __DIR__ . '/../../database/user_cards.sql',
        __DIR__ . '/../../database/user_pack_state.sql',
        __DIR__ . '/../../database/user_decks.sql',
    ];

    private static ?PDO $connection = null;
    private static bool $schemaReady = false;

    public static function host(): string
    {
        return (string) getenv('TEST_DB_HOST') ?: 'localhost';
    }

    public static function port(): string
    {
        return (string) getenv('TEST_DB_PORT') ?: '3306';
    }

    public static function user(): string
    {
        return (string) getenv('TEST_DB_USER') ?: 'root';
    }

    public static function password(): string
    {
        return (string) getenv('TEST_DB_PASSWORD');
    }

    public static function name(): string
    {
        return (string) getenv('TEST_DB_NAME') ?: 'cinema_test';
    }

    /**
     * Reconstruit la base de test (une seule fois par exécution),
     * sauf si TEST_DB_AUTO_CREATE vaut 0 (réutilisation d'une base déjà prête).
     */
    public static function ensureSchema(): void
    {
        if (self::$schemaReady) {
            return;
        }

        if ((string) getenv('TEST_DB_AUTO_CREATE') !== '0') {
            self::resetSchema();
        }

        self::$schemaReady = true;
    }

    /** Connexion PDO à `cinema_test`. */
    public static function connection(): PDO
    {
        if (self::$connection === null) {
            self::$connection = new PDO(
                'mysql:host=' . self::host()
                . ';port=' . self::port()
                . ';dbname=' . self::name()
                . ';charset=utf8mb4',
                self::user(),
                self::password(),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        }

        return self::$connection;
    }

    /** Drop + create de `cinema_test`, puis import des données et scripts du projet. */
    public static function resetSchema(): void
    {
        $server = self::serverConnection();

        $server->exec(
            'DROP DATABASE IF EXISTS `' . self::name() . '`;'
            . 'CREATE DATABASE `' . self::name() . '`'
            . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'
        );
        $server = null;
        self::$connection = null;

        foreach (self::SCRIPTS as $script) {
            if (!is_file($script)) {
                throw new RuntimeException('Script SQL introuvable : ' . $script);
            }
            self::runScript($script);
        }
    }

    /** Insère un utilisateur de test (mot de passe « test ») et retourne son id. */
    public static function createUser(?string $username = null, ?string $email = null): int
    {
        $username = $username ?? 'user_' . bin2hex(random_bytes(4));
        $email = $email ?? $username . '@test.local';

        $statement = self::connection()->prepare(
            'INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :hash)'
        );
        $statement->execute([
            'username' => $username,
            'email' => $email,
            'hash' => password_hash('test', PASSWORD_BCRYPT),
        ]);

        return (int) self::connection()->lastInsertId();
    }

    /** Vide les tables utilisateur (isolation entre les tests). */
    public static function resetUsers(): void
    {
        $db = self::connection();
        $db->exec('DELETE FROM deck_cards');
        $db->exec('DELETE FROM user_decks');
        $db->exec('DELETE FROM user_pack_state');
        $db->exec('DELETE FROM user_favorites');
        $db->exec('DELETE FROM user_cards');
        $db->exec('DELETE FROM users');
    }

    /** Connexion PDO au serveur, sans sélection de base. */
    private static function serverConnection(): PDO
    {
        return new PDO(
            'mysql:host=' . self::host() . ';port=' . self::port() . ';charset=utf8mb4',
            self::user(),
            self::password(),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }

    /**
     * Exécute un script SQL du projet après y avoir réécrit
     * `cinema` en la base de test (les scripts ciblent `cinema` en dur).
     *
     * Utilise une connexion dédiée : les scripts contiennent plusieurs
     * instructions, ce que PDO ne tolère que si l'émulation de préparations
     * reste activée (valeur par défaut), contrairement à la connexion
     * applicative de test.
     */
    private static function runScript(string $path): void
    {
        $sql = self::rewrite((string) file_get_contents($path));

        $loader = new PDO(
            'mysql:host=' . self::host()
            . ';port=' . self::port()
            . ';dbname=' . self::name()
            . ';charset=utf8mb4',
            self::user(),
            self::password(),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        $statement = $loader->query($sql);
        do {
            if ($statement->columnCount() > 0) {
                $statement->fetchAll();
            }
        } while ($statement->nextRowset());
        $statement->closeCursor();
        $loader = null;
    }

    private static function rewrite(string $sql): string
    {
        $name = self::name();
        $sql = preg_replace('/(CREATE DATABASE IF NOT EXISTS\s+)cinema\b/', '${1}`' . $name . '`', $sql) ?? $sql;
        $sql = preg_replace('/USE\s+cinema\s*;/', 'USE `' . $name . '`;', $sql) ?? $sql;

        return $sql;
    }
}
