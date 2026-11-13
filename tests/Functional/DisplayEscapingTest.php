<?php

declare(strict_types=1);

require_once __DIR__ . '/../Support/IntegrationTestCase.php';

/**
 * Vérifie l'échappement des données réellement affichées par les pages PHP
 * (issue #104). Les pages sont rendues dans un processus PHP isolé, avec une
 * session simulée et la base `cinema_test` : on contrôle le HTML produit,
 * pas seulement la fonction e().
 */
final class DisplayEscapingTest extends IntegrationTestCase
{
    /** Charge utile classique : une balise exécutable et des guillemets. */
    private const PAYLOAD = '<script>alert("xss")</script>';

    public function testUneRechercheRefleteeEstEchappeeDansLeCatalogue(): void
    {
        $html = $this->renderPage('catalog', TestDatabase::createUser(), ['q' => self::PAYLOAD]);

        self::assertStringNotContainsString(self::PAYLOAD, $html, 'La recherche ne doit pas être renvoyée brute.');
        self::assertStringContainsString('value="&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;"', $html);
    }

    public function testUnNomDeDeckStockeEstEchappeDansLaListeEtLeDetail(): void
    {
        $userId = TestDatabase::createUser('escape_' . bin2hex(random_bytes(4)));
        $deckName = self::PAYLOAD . ' & co';

        $this->db()->prepare(
            'INSERT INTO user_decks (user_id, name, is_active) VALUES (:user_id, :name, 0)'
        )->execute(['user_id' => $userId, 'name' => $deckName]);

        $deckId = (int) $this->db()->lastInsertId();

        // Liste des decks : la vignette affiche le nom dans une balise <p>.
        $listHtml = $this->renderPage('deck', $userId);

        self::assertStringNotContainsString($deckName, $listHtml);
        self::assertStringNotContainsString('<script>alert', $listHtml);
        self::assertStringContainsString(
            '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt; &amp; co',
            $listHtml
        );

        // Vue détail : le nom apparaît aussi dans l'attribut data-name.
        $detailHtml = $this->renderPage('deck', $userId, ['deck' => $deckId]);

        self::assertStringNotContainsString($deckName, $detailHtml);
        self::assertStringNotContainsString('<script>alert', $detailHtml);
        self::assertStringContainsString(
            'data-name="&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt; &amp; co"',
            $detailHtml
        );
    }

    /**
     * Exécute tests/Support/render_page.php dans un processus séparé : les
     * pages déclarent des fonctions, elles ne peuvent donc pas toutes être
     * incluses dans le même processus PHPUnit.
     *
     * @param array<string, mixed> $query
     */
    private function renderPage(string $page, int $userId, array $query = []): string
    {
        $command = [
            PHP_BINARY,
            '-d', 'display_errors=stderr',
            '-d', 'error_reporting=E_ALL',
            __DIR__ . '/../Support/render_page.php',
            $page,
            (string) $userId,
            (string) json_encode($query, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];

        $environment = getenv();
        $environment['DB_HOST'] = TestDatabase::host();
        $environment['DB_PORT'] = TestDatabase::port();
        $environment['DB_USER'] = TestDatabase::user();
        $environment['DB_PASSWORD'] = TestDatabase::password();
        $environment['DB_NAME'] = TestDatabase::name();
        $environment['APP_DEBUG'] = '1';
        $environment['APP_LOG_SILENT'] = '1';

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes, dirname(__DIR__, 2), $environment);

        if (!is_resource($process)) {
            self::fail('Impossible de lancer le rendu de la page ' . $page . '.');
        }

        fclose($pipes[0]);
        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        self::assertSame(0, $exitCode, 'Le rendu de ' . $page . ' a échoué : ' . $stderr);
        self::assertNotSame('', $stdout, 'La page ' . $page . ' n’a rien produit.');

        return $stdout;
    }
}
