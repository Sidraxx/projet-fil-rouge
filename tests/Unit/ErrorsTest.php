<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../includes/errors.php';

/**
 * Vérifie la traduction des erreurs de base et l'exposition des messages
 * à la console du navigateur : l'application ne doit jamais afficher de
 * trace brute, mais toujours une explication actionnable.
 */
final class ErrorsTest extends TestCase
{
    /** @param array<int, mixed> $errorInfo */
    private function pdoException(array $errorInfo): PDOException
    {
        $exception = new PDOException('erreur simulée');
        $exception->errorInfo = $errorInfo;

        return $exception;
    }

    public function testChaqueCodeMysqlConnuDonneUneExplicationActionnable(): void
    {
        self::assertStringContainsString(
            'database/',
            describe_database_error($this->pdoException(['42S02', 1146, ''])),
            'Table absent : le message doit renvoyer vers les scripts SQL.'
        );
        self::assertStringContainsString(
            'user_decks.sql',
            describe_database_error($this->pdoException(['23000', 1062, ''])),
            'Doublon : le message doit renvoyer vers la migration du deck.'
        );
        self::assertStringContainsString(
            'MySQL',
            describe_database_error($this->pdoException(['HY000', 2002, ''])),
            'Serveur injoignable : le message doit parler de MySQL.'
        );
        self::assertStringContainsString(
            'reconnectez-vous',
            describe_database_error($this->pdoException(['23000', 1452, ''])),
            'Session obsolète : le message doit proposer de se reconnecter.'
        );
    }

    public function testSansAppDebugLesDetailsTechniquesRestentDansLesLogs(): void
    {
        putenv('APP_DEBUG=0');

        try {
            $message = describe_database_error($this->pdoException(['42S02', 1146, '']));
        } finally {
            putenv('APP_DEBUG=1');
        }

        self::assertStringNotContainsString('database/', $message);
        self::assertStringNotContainsString('SQLSTATE', $message);
        self::assertStringNotContainsString('1146', $message);
    }

    public function testUnCodeInconnuEstSignaleSansTraceBrute(): void
    {
        $message = describe_database_error($this->pdoException(['XX000', 9999, '']));

        self::assertStringContainsString('SQLSTATE XX000', $message);
        self::assertStringNotContainsString('PDOException', $message);
    }

    public function testLesMessagesSontJournalisesEtExposesALaConsole(): void
    {
        $before = count(app_errors());
        app_log('warning', 'Message de test unique', ['user_id' => 42]);

        $errors = app_errors();
        self::assertCount($before + 1, $errors);

        $last = $errors[count($errors) - 1];
        self::assertSame('warning', $last['level']);
        self::assertSame('Message de test unique', $last['message']);
        self::assertSame(42, $last['context']['user_id']);

        $script = app_console_script();
        self::assertStringContainsString('window.appErrors', $script);
        self::assertStringContainsString('Message de test unique', $script);
    }

    public function testAppDatabaseExceptionPorteUnMessageLisible(): void
    {
        $exception = app_database_exception($this->pdoException(['42S02', 1146, '']), 'Chargement impossible');

        self::assertInstanceOf(AppDatabaseException::class, $exception);
        self::assertStringContainsString('Chargement impossible', $exception->getMessage());
        self::assertStringContainsString('database/', $exception->getMessage());
        self::assertInstanceOf(PDOException::class, $exception->getPrevious());
    }
}
