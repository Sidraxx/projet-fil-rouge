<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Test de validation de l'environnement : vérifie que la base de test est
 * reconstruite correctement à partir des scripts SQL du projet.
 *
 * Si ces tests échouent, la configuration des tests (et non l'application)
 * est en cause : ils servent de fumée avant les vraies suites.
 */
final class EnvironmentTest extends TestCase
{
    public function testLaVersionDePhpEstSupportee(): void
    {
        self::assertTrue(
            version_compare(PHP_VERSION, '8.2.0', '>='),
            'PHP 8.2 minimum est requis (version actuelle : ' . PHP_VERSION . ').'
        );
    }

    public function testLExtensionPdoMysqlEstChargee(): void
    {
        self::assertTrue(
            extension_loaded('pdo_mysql'),
            'pdo_mysql est requis pour exécuter la suite.'
        );
    }

    public function testLaBaseDeTestExposeLesMilleFilms(): void
    {
        $count = (int) TestDatabase::connection()
            ->query('SELECT COUNT(*) FROM movies')
            ->fetchColumn();

        self::assertSame(1000, $count);
    }

    public function testLesRaritesSontCalculeesPourChaqueFilm(): void
    {
        $missing = (int) TestDatabase::connection()
            ->query('SELECT COUNT(*) FROM movies WHERE rarity_level IS NULL OR rarity_score IS NULL')
            ->fetchColumn();

        self::assertSame(0, $missing);
    }

    public function testLaDistributionDesRaritesCorrespondAuResultatAttendu(): void
    {
        $counts = TestDatabase::connection()->query(
            "SELECT rarity_level, COUNT(*) AS total
             FROM movies
             WHERE rarity_level IS NOT NULL
             GROUP BY rarity_level"
        )->fetchAll(PDO::FETCH_KEY_PAIR);

        // Comparaison dans l'ordre du catalogue, indépendamment du tri SQL.
        foreach ([
            'Commune' => 450,
            'Peu commune' => 300,
            'Rare' => 150,
            'Épique' => 80,
            'Légendaire' => 20,
        ] as $level => $expected) {
            self::assertSame($expected, (int) ($counts[$level] ?? 0), "Niveau « $level »");
        }
    }

    public function testLaBaseDeTestCommenceSansUtilisateur(): void
    {
        $count = (int) TestDatabase::connection()
            ->query('SELECT COUNT(*) FROM users')
            ->fetchColumn();

        self::assertSame(0, $count);
    }

    public function testLesTablesUtilisateurSontVidablesEntreDeuxTests(): void
    {
        TestDatabase::createUser('isolation_test');
        $before = (int) TestDatabase::connection()->query('SELECT COUNT(*) FROM users')->fetchColumn();

        TestDatabase::resetUsers();
        $after = (int) TestDatabase::connection()->query('SELECT COUNT(*) FROM users')->fetchColumn();

        self::assertSame(1, $before);
        self::assertSame(0, $after);
    }
}
