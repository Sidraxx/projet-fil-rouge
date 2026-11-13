<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../includes/escaping.php';

/**
 * Vérifie l'échappement HTML des données affichées (issue #104) : les
 * caractères spéciaux d'une saisie utilisateur ou d'une colonne de base
 * doivent ressortir sous forme de texte, jamais sous forme de balise.
 */
final class EscapingTest extends TestCase
{
    public function testLesBalisesEtGuillemetsSontEchappes(): void
    {
        self::assertSame(
            '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;',
            e('<script>alert("xss")</script>')
        );
    }

    public function testLesApostrophesEtEsperluettesSontEchappees(): void
    {
        self::assertSame(
            'L&#039;Épée &amp; Cœur',
            e('L\'Épée & Cœur')
        );
    }

    public function testLesCaracteresAccentuesEtEmojisRestentIntacts(): void
    {
        self::assertSame('Amélie · 🎬', e('Amélie · 🎬'));
    }

    public function testUneValeurNulleDevientUneChaineVide(): void
    {
        self::assertSame('', e(null));
    }

    public function testUneChaineUtf8InvalideNEstPasPerdue(): void
    {
        $escaped = e("Titre \xC3 invalide");

        self::assertStringContainsString('Titre ', $escaped);
        self::assertStringContainsString("\u{FFFD}", $escaped);
    }

    public function testUneDoubleEchappementNEstJamaisAppliqueeParLaFonction(): void
    {
        // e() est idempotente uniquement sur du texte déjà échappé : elle
        // échappe à nouveau le « & » d'une entité, ce qui prouve qu'elle ne
        // tente pas de deviner ce qui est déjà sûr. Les appelants ne doivent
        // donc pas stocker de HTML échappé en base.
        self::assertSame('&amp;lt;', e('&lt;'));
    }
}
