<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Classe de base des tests d'intégration : garantit que le schéma de
 * `cinema_test` est prêt et isole chaque test en vidant les tables
 * utilisateurs avant de démarrer.
 */
abstract class IntegrationTestCase extends TestCase
{
    protected function setUp(): void
    {
        TestDatabase::ensureSchema();
        TestDatabase::resetUsers();
    }

    /** Connexion PDO à la base de test. */
    protected function db(): PDO
    {
        return TestDatabase::connection();
    }
}
