<?php

declare(strict_types=1);

/**
 * Règles mesurables du deck (issue [Deck] Définir les règles d'un deck) :
 *   - taille maximale : 10 cartes ;
 *   - limite de doublons : 2 exemplaires d'une même carte ;
 *   - validité : le deck est prêt au combat uniquement à 10 cartes exactement.
 */
const DECK_MAX_SIZE = 10;
const DECK_MAX_COPIES = 2;

/**
 * Renvoie les règles du deck sous une forme directement affichable.
 *
 * @return array{size: int, copies: int}
 */
function deck_rules(): array
{
    return [
        'size' => DECK_MAX_SIZE,
        'copies' => DECK_MAX_COPIES,
    ];
}

/**
 * Charge le deck actif du joueur, en le créant s'il manque.
 * Retourne l'identifiant du deck : c'est la base persistante du constructeur.
 */
function get_or_create_deck(PDO $db, int $userId): int
{
    $db->prepare(
        'INSERT IGNORE INTO user_decks (user_id, is_active) VALUES (:user_id, 1)'
    )->execute(['user_id' => $userId]);

    $statement = $db->prepare(
        'SELECT id
         FROM user_decks
         WHERE user_id = :user_id
         FOR UPDATE'
    );
    $statement->execute(['user_id' => $userId]);
    $deckId = $statement->fetchColumn();

    if ($deckId === false) {
        throw new RuntimeException('Deck introuvable.');
    }

    return (int) $deckId;
}

/**
 * Compte le nombre total de cartes placées dans un deck.
 */
function deck_card_count(PDO $db, int $deckId): int
{
    $statement = $db->prepare(
        'SELECT COALESCE(SUM(quantity), 0)
         FROM deck_cards
         WHERE deck_id = :deck_id'
    );
    $statement->execute(['deck_id' => $deckId]);

    return (int) $statement->fetchColumn();
}

/**
 * État du deck : taille actuelle, taille maximale et validité.
 *
 * @return array{count: int, size: int, valid: bool}
 */
function deck_status(PDO $db, int $deckId): array
{
    $count = deck_card_count($db, $deckId);

    return [
        'count' => $count,
        'size' => DECK_MAX_SIZE,
        'valid' => $count === DECK_MAX_SIZE,
    ];
}

/**
 * Charge les cartes d'un deck avec les détails du film (affichage de la zone deck).
 */
function load_deck_cards(PDO $db, int $deckId): array
{
    $statement = $db->prepare(
        'SELECT
            dc.movie_id AS id,
            dc.quantity,
            m.title,
            m.poster_link AS poster,
            m.released_year AS year,
            m.genre,
            m.rarity_level AS rarity
         FROM deck_cards dc
         INNER JOIN movies m ON m.id = dc.movie_id
         WHERE dc.deck_id = :deck_id
         ORDER BY dc.created_at ASC, dc.movie_id ASC'
    );
    $statement->execute(['deck_id' => $deckId]);
    $rows = $statement->fetchAll();

    foreach ($rows as &$row) {
        $row['quantity'] = (int) $row['quantity'];
        $row['id'] = (int) $row['id'];
    }
    unset($row);

    return $rows;
}

/**
 * Ajoute une carte au deck, après validation complète :
 *   - la carte doit être possédée (protection contre le formulaire, cf. issue dédiée) ;
 *   - le nombre d'exemplaires déjà placés reste sous la limite de doublons ;
 *   - le deck reste sous sa taille maximale.
 *
 * À appeler dans une transaction. Le fil d'exécution du joueur est
 * verrouillé par les SELECT ... FOR UPDATE pour éviter les dépassements
 * dus à deux onglets ouverts simultanément.
 */
function add_card_to_deck(PDO $db, int $deckId, int $userId, int $movieId): void
{
    // La carte doit exister chez le joueur : c'est la source de vérité, pas la page.
    $statement = $db->prepare(
        'SELECT quantity
         FROM user_cards
         WHERE user_id = :user_id
           AND movie_id = :movie_id
         FOR UPDATE'
    );
    $statement->execute(['user_id' => $userId, 'movie_id' => $movieId]);
    $row = $statement->fetch();
    if (!$row) {
        throw new RuntimeException('Vous ne possédez pas cette carte.');
    }
    $owned = (int) $row['quantity'];

    $statement = $db->prepare(
        'SELECT quantity
         FROM deck_cards
         WHERE deck_id = :deck_id
           AND movie_id = :movie_id
         FOR UPDATE'
    );
    $statement->execute(['deck_id' => $deckId, 'movie_id' => $movieId]);
    $current = $statement->fetchColumn();
    $current = $current === false ? 0 : (int) $current;

    if ($current >= DECK_MAX_COPIES) {
        throw new RuntimeException(
            'Limite de ' . DECK_MAX_COPIES . ' exemplaires par carte atteinte pour ce deck.'
        );
    }

    // Ses propres exemplaires dans la collection limitent aussi le deck :
    // on ne place pas plus d'exemplaires qu'on n'en possède réellement.
    if ($current + 1 > $owned) {
        throw new RuntimeException('Exemplaire supplémentaire impossible : vous ne possédez pas assez de copies de cette carte.');
    }

    if (deck_card_count($db, $deckId) + 1 > DECK_MAX_SIZE) {
        throw new RuntimeException('Deck plein : ' . DECK_MAX_SIZE . ' cartes maximum.');
    }

    $db->prepare(
        'INSERT INTO deck_cards (deck_id, movie_id, quantity)
         VALUES (:deck_id, :movie_id, 1)
         ON DUPLICATE KEY UPDATE quantity = quantity + 1'
    )->execute(['deck_id' => $deckId, 'movie_id' => $movieId]);
}

/**
 * Retire un exemplaire d'une carte du deck : décrémente la quantité et
 * supprime la ligne quand elle devient nulle.
 *
 * À appeler dans une transaction.
 */
function remove_card_from_deck(PDO $db, int $deckId, int $movieId): void
{
    $statement = $db->prepare(
        'SELECT quantity
         FROM deck_cards
         WHERE deck_id = :deck_id
           AND movie_id = :movie_id
         FOR UPDATE'
    );
    $statement->execute(['deck_id' => $deckId, 'movie_id' => $movieId]);
    $row = $statement->fetch();

    if (!$row) {
        throw new RuntimeException('Cette carte n\'est pas dans le deck.');
    }

    $quantity = (int) $row['quantity'];
    if ($quantity <= 1) {
        $db->prepare(
            'DELETE FROM deck_cards WHERE deck_id = :deck_id AND movie_id = :movie_id'
        )->execute(['deck_id' => $deckId, 'movie_id' => $movieId]);

        return;
    }

    $db->prepare(
        'UPDATE deck_cards
         SET quantity = quantity - 1
         WHERE deck_id = :deck_id
           AND movie_id = :movie_id'
    )->execute(['deck_id' => $deckId, 'movie_id' => $movieId]);
}

/**
 * Vue des cartes disponibles pour le deck : la collection réelle du joueur,
 * enrichie du nombre de cartes déjà placées dans le deck.
 */
function load_deck_available_cards(PDO $db, int $userId, int $deckId): array
{
    $statement = $db->prepare(
        'SELECT
            m.id,
            m.title,
            m.poster_link AS poster,
            m.released_year AS year,
            m.genre,
            m.rarity_level AS rarity,
            uc.quantity AS owned,
            COALESCE(dc.quantity, 0) AS in_deck
         FROM user_cards uc
         INNER JOIN movies m ON m.id = uc.movie_id
         LEFT JOIN deck_cards dc
            ON dc.deck_id = :deck_id
            AND dc.movie_id = uc.movie_id
         WHERE uc.user_id = :user_id
         ORDER BY uc.quantity DESC, m.title ASC'
    );
    $statement->execute(['deck_id' => $deckId, 'user_id' => $userId]);
    $rows = $statement->fetchAll();

    foreach ($rows as &$row) {
        $row['id'] = (int) $row['id'];
        $row['owned'] = (int) $row['owned'];
        $row['in_deck'] = (int) $row['in_deck'];
    }
    unset($row);

    return $rows;
}
