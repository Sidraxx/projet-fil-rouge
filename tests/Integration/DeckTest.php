<?php

declare(strict_types=1);

require_once __DIR__ . '/../Support/IntegrationTestCase.php';
require_once __DIR__ . '/../../includes/decks.php';

/**
 * Couvre les règles du deck ([Deck] Définir les règles), les ajouts retraits
 * avec validation serveur, et le cycle complet du deck (créer, ajouter,
 * retirer, recharger).
 */
final class DeckTest extends IntegrationTestCase
{
    private int $userId = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userId = TestDatabase::createUser();
    }

    /** Donne un film au joueur de test et retourne l'identifiant du film. */
    private function giveMovie(int $copies = 1): int
    {
        $movieId = $this->someMovieId();
        $this->db()->prepare(
            'INSERT INTO user_cards (user_id, movie_id, quantity) VALUES (:user_id, :movie_id, :quantity)'
        )->execute([
            'user_id' => $this->userId,
            'movie_id' => $movieId,
            'quantity' => $copies,
        ]);

        return $movieId;
    }

    /** Récupère un identifiant de film jamais utilisé dans ce test. */
    private function someMovieId(): int
    {
        static $used = [];

        $statement = $this->db()->prepare(
            'SELECT id FROM movies WHERE rarity_level IS NOT NULL ORDER BY id LIMIT :offset, 1'
        );
        $statement->bindValue('offset', count($used), PDO::PARAM_INT);
        $statement->execute();
        $movieId = (int) $statement->fetchColumn();
        $used[] = $movieId;

        return $movieId;
    }

    public function testLeRenvoiDesReglesEstMesurable(): void
    {
        self::assertSame(['size' => 10, 'copies' => 2], deck_rules());
        self::assertSame(10, DECK_MAX_SIZE);
        self::assertSame(2, DECK_MAX_COPIES);
    }

    public function testLeDeckManquantEstCreeAuPremierAcces(): void
    {
        $deckId = get_or_create_deck($this->db(), $this->userId);

        self::assertGreaterThan(0, $deckId);

        $state = $this->db()->prepare(
            'SELECT user_id, is_active FROM user_decks WHERE id = :id'
        );
        $state->execute(['id' => $deckId]);

        self::assertSame([
            'user_id' => $this->userId,
            'is_active' => 1,
        ], $state->fetch());
    }

    public function testLeDeckExistantEstRechargeSansCreerUnDoublon(): void
    {
        $db = $this->db();

        $db->beginTransaction();
        $firstId = get_or_create_deck($db, $this->userId);
        $db->commit();

        $db->beginTransaction();
        $secondId = get_or_create_deck($db, $this->userId);
        $db->commit();

        self::assertSame($firstId, $secondId);
        self::assertSame(1, (int) $db->query('SELECT COUNT(*) FROM user_decks')->fetchColumn());
    }

    public function testAjouterUneCartePossedeeCreeDEckCards(): void
    {
        $movieId = $this->giveMovie();
        $db = $this->db();

        $db->beginTransaction();
        $deckId = get_or_create_deck($db, $this->userId);
        add_card_to_deck($db, $deckId, $this->userId, $movieId);
        $db->commit();

        $row = $db->prepare('SELECT quantity FROM deck_cards WHERE deck_id = :deck_id AND movie_id = :movie_id');
        $row->execute(['deck_id' => $deckId, 'movie_id' => $movieId]);

        self::assertSame(1, (int) $row->fetchColumn());
        self::assertSame(1, deck_card_count($db, $deckId));
    }

    public function testAjouterDeuxFoisIncrementLaQuantite(): void
    {
        $movieId = $this->giveMovie(2);
        $db = $this->db();

        $db->beginTransaction();
        $deckId = get_or_create_deck($db, $this->userId);
        add_card_to_deck($db, $deckId, $this->userId, $movieId);
        add_card_to_deck($db, $deckId, $this->userId, $movieId);
        $db->commit();

        $row = $db->prepare('SELECT quantity FROM deck_cards WHERE deck_id = :deck_id AND movie_id = :movie_id');
        $row->execute(['deck_id' => $deckId, 'movie_id' => $movieId]);

        self::assertSame(2, (int) $row->fetchColumn());
    }

    public function testLaCarteNonPossedeeEstRefusee(): void
    {
        $movieId = $this->someMovieId(); // Film existant mais jamais obtenu par le joueur.
        $db = $this->db();

        $db->beginTransaction();
        $deckId = get_or_create_deck($db, $this->userId);

        try {
            add_card_to_deck($db, $deckId, $this->userId, $movieId);
            self::fail('L\'ajout d\'une carte non possédée doit être refusé.');
        } catch (RuntimeException $error) {
            self::assertStringContainsString('possédez pas', $error->getMessage());
            self::assertSame(0, deck_card_count($db, $deckId));
        } finally {
            $db->rollBack();
        }
    }

    public function testLaLimiteDeDernierExemplaireSapplique(): void
    {
        // Le joueur possède 2 exemplaires mais ne peut en placer que 2.
        // On lui en ajoute deux, le troisième appel est refusé même s'il possède déjà 2.
        // Vérifie aussi la possession : posséder 2 exemplaires limite l'ajout à 2 cartes.
        $movieId = $this->giveMovie(2);
        $db = $this->db();

        $db->beginTransaction();
        $deckId = get_or_create_deck($db, $this->userId);
        add_card_to_deck($db, $deckId, $this->userId, $movieId);
        add_card_to_deck($db, $deckId, $this->userId, $movieId);

        try {
            add_card_to_deck($db, $deckId, $this->userId, $movieId);
            self::fail('La limite de doublons doit refuser le troisième exemplaire.');
        } catch (RuntimeException $error) {
            self::assertStringContainsString('exemplaires', $error->getMessage());
        } finally {
            $db->rollBack();
        }
    }

    public function testLeDeckPleinRefuseLAjoutSuivant(): void
    {
        // Douze films différents, chacun possédé en au moins 2 exemplaires
        // pour isoler la règle de taille de la règle de doublons.
        $movieIds = [];
        for ($i = 0; $i < 12; $i++) {
            $movieIds[] = $this->giveMovie(2);
        }

        $db = $this->db();
        $db->beginTransaction();
        $deckId = get_or_create_deck($db, $this->userId);

        foreach (array_slice($movieIds, 0, 10) as $movieId) {
            add_card_to_deck($db, $deckId, $this->userId, $movieId);
        }

        self::assertSame(10, deck_card_count($db, $deckId));

        try {
            add_card_to_deck($db, $deckId, $this->userId, $movieIds[10]);
            self::fail('Le onzième film doit refuser l\'ajout : deck plein.');
        } catch (RuntimeException $error) {
            self::assertStringContainsString('Deck plein', $error->getMessage());
            self::assertSame(10, deck_card_count($db, $deckId));
        } finally {
            $db->rollBack();
        }
    }

    public function testRetirerUnExemplaireDecrementAlorsQueLaDerniereCarteSupprimeLaLigne(): void
    {
        $movieId = $this->giveMovie(2);
        $db = $this->db();

        $db->beginTransaction();
        $deckId = get_or_create_deck($db, $this->userId);
        add_card_to_deck($db, $deckId, $this->userId, $movieId);
        add_card_to_deck($db, $deckId, $this->userId, $movieId);

        remove_card_from_deck($db, $deckId, $movieId);

        $before = $db->prepare('SELECT quantity FROM deck_cards WHERE deck_id = :deck_id AND movie_id = :movie_id');
        $before->execute(['deck_id' => $deckId, 'movie_id' => $movieId]);
        self::assertSame(1, (int) $before->fetchColumn());

        remove_card_from_deck($db, $deckId, $movieId);

        $after = $db->prepare('SELECT COUNT(*) FROM deck_cards WHERE deck_id = :deck_id AND movie_id = :movie_id');
        $after->execute(['deck_id' => $deckId, 'movie_id' => $movieId]);
        self::assertSame(0, (int) $after->fetchColumn());
        self::assertSame(0, deck_card_count($db, $deckId));

        $db->commit();
    }

    public function testLeRetraitDUneCarteAbsenteEchoue(): void
    {
        $movieId = $this->giveMovie();
        $db = $this->db();

        $db->beginTransaction();
        $deckId = get_or_create_deck($db, $this->userId);

        try {
            remove_card_from_deck($db, $deckId, $movieId);
            self::fail('Retirer une carte non présente doit échouer.');
        } catch (RuntimeException $error) {
            self::assertStringContainsString('pas dans le deck', $error->getMessage());
        } finally {
            $db->rollBack();
        }
    }

    public function testLaValiditeNEstAtteinteQuALaTailleMaximale(): void
    {
        $db = $this->db();
        $movieIds = [];
        for ($i = 0; $i < DECK_MAX_SIZE + 1; $i++) {
            $movieIds[] = $this->giveMovie();
        }

        $db->beginTransaction();
        try {
            $deckId = get_or_create_deck($db, $this->userId);

            self::assertSame(['count' => 0, 'size' => 10, 'valid' => false], deck_status($db, $deckId));

            foreach (array_slice($movieIds, 0, DECK_MAX_SIZE - 1) as $movieId) {
                add_card_to_deck($db, $deckId, $this->userId, $movieId);
            }

            self::assertSame(9, deck_card_count($db, $deckId));
            self::assertFalse(deck_status($db, $deckId)['valid']);

            // La dixième carte rend le deck valide, prêt au combat.
            add_card_to_deck($db, $deckId, $this->userId, $movieIds[9]);
            $status = deck_status($db, $deckId);

            self::assertSame(10, $status['count']);
            self::assertTrue($status['valid']);

            // Un onzième film est refusé : la taille maximale est protégée.
            try {
                add_card_to_deck($db, $deckId, $this->userId, $movieIds[10]);
                self::fail('Le onzième film doit refuser l\'ajout : deck plein.');
            } catch (RuntimeException $error) {
                self::assertStringContainsString('Deck plein', $error->getMessage());
            }
        } finally {
            $db->rollBack();
        }
    }

    public function testOnNePlacePasPlusDExemplairesQuOnEnPossede(): void
    {
        // Le joueur possède un seul exemplaire : il ne peut le placer qu'une fois
        // dans le deck, même si la limite de doublons autorise 2.
        $movieId = $this->giveMovie(1);
        $db = $this->db();

        $db->beginTransaction();
        try {
            $deckId = get_or_create_deck($db, $this->userId);
            add_card_to_deck($db, $deckId, $this->userId, $movieId);

            try {
                add_card_to_deck($db, $deckId, $this->userId, $movieId);
                self::fail('Placer deux exemplaires d\'une carte possédée une seule fois doit échouer.');
            } catch (RuntimeException $error) {
                self::assertStringContainsString('possédez pas assez', $error->getMessage());
            }

            self::assertSame(1, deck_card_count($db, $deckId));
        } finally {
            $db->rollBack();
        }
    }

    public function testLeCycleCompletSurvitAUneReouverture(): void
    {
        // Scenario de l'issue [Deck] Tester le cycle complet : créer le deck,
        // ajouter et retirer des cartes, recharger puis vérifier les données.
        $removed = $this->giveMovie();
        $kept = $this->giveMovie(2);
        $db = $this->db();

        $db->beginTransaction();
        $deckId = get_or_create_deck($db, $this->userId);
        add_card_to_deck($db, $deckId, $this->userId, $removed);
        add_card_to_deck($db, $deckId, $this->userId, $kept);
        add_card_to_deck($db, $deckId, $this->userId, $kept);
        remove_card_from_deck($db, $deckId, $removed);
        $db->commit();

        // "Rechargement de la page" : nouvelle lecture depuis la base.
        $db->beginTransaction();
        $reloadedDeckId = get_or_create_deck($db, $this->userId);
        $cards = load_deck_cards($db, $reloadedDeckId);
        $status = deck_status($db, $reloadedDeckId);
        $available = load_deck_available_cards($db, $this->userId, $reloadedDeckId);
        $db->commit();

        self::assertSame($deckId, $reloadedDeckId);
        self::assertSame([
            ['id' => $kept, 'quantity' => 2],
        ], array_map(
            static fn (array $card): array => ['id' => $card['id'], 'quantity' => $card['quantity']],
            $cards
        ));
        self::assertSame(2, $status['count']);
        self::assertFalse($status['valid']);

        $availableById = [];
        foreach ($available as $card) {
            $availableById[$card['id']] = $card;
        }
        self::assertSame(2, $availableById[$kept]['owned']);
        self::assertSame(2, $availableById[$kept]['in_deck']);
        self::assertSame(1, $availableById[$removed]['owned']);
        self::assertSame(0, $availableById[$removed]['in_deck']);
    }
}
