<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

/**
 * Règles mesurables du deck (issue [Deck] Définir les règles d'un deck) :
 *   - taille maximale : 10 cartes ;
 *   - limite de doublons : 2 exemplaires d'une même carte ;
 *   - validité : le deck est prêt au combat uniquement à 10 cartes exactement.
 * Le joueur peut gérer plusieurs decks nommés (issue #117), dans la limite
 * de 5 decks, avec un seul deck actif utilisé par le combat.
 */
const DECK_MAX_SIZE = 10;
const DECK_MAX_COPIES = 2;
const DECK_MAX_COUNT = 5;
const DECK_DEFAULT_NAME = 'Mon deck';

/**
 * Renvoie les règles du deck sous une forme directement affichable.
 *
 * @return array{size: int, copies: int, decks: int}
 */
function deck_rules(): array
{
    return [
        'size' => DECK_MAX_SIZE,
        'copies' => DECK_MAX_COPIES,
        'decks' => DECK_MAX_COUNT,
    ];
}

/** Identifiant du deck actif du joueur, ou null s'il n'en a aucun. */
function active_deck_id(PDO $db, int $userId): ?int
{
    $statement = $db->prepare(
        'SELECT id FROM user_decks
         WHERE user_id = :user_id AND is_active = 1
         FOR UPDATE'
    );
    $statement->execute(['user_id' => $userId]);
    $deckId = $statement->fetchColumn();

    return $deckId === false ? null : (int) $deckId;
}

/** Identifiant du plus ancien deck du joueur, ou null s'il n'en a aucun. */
function oldest_deck_id(PDO $db, int $userId): ?int
{
    $statement = $db->prepare(
        'SELECT id FROM user_decks
         WHERE user_id = :user_id
         ORDER BY id ASC
         LIMIT 1'
    );
    $statement->execute(['user_id' => $userId]);
    $deckId = $statement->fetchColumn();

    return $deckId === false ? null : (int) $deckId;
}

/**
 * Dernier recours quand la création d'un deck échoue : si la base a encore
 * l'ancien index UNIQUE (un seul deck par joueur), le deck existant est
 * réutilisé et activé, avec un avertissement expliquant comment migrer.
 */
function recover_existing_deck(PDO $db, int $userId, PDOException $exception): ?int
{
    if ((int) ($exception->errorInfo[1] ?? 0) !== 1062) {
        return null;
    }

    try {
        $deckId = oldest_deck_id($db, $userId);
        if ($deckId === null) {
            return null;
        }

        app_log(
            'warning',
            'user_decks a refusé un deck supplémentaire (index UNIQUE hérité) : réutilisation du deck existant. Exécutez database/user_decks.sql pour migrer la base.',
            ['user_id' => $userId]
        );

        switch_active_deck($db, $userId, $deckId);

        return $deckId;
    } catch (PDOException $recoveryFailure) {
        app_log_exception($recoveryFailure, 'Récupération du deck existant impossible');

        return null;
    }
}

/**
 * Charge le deck actif du joueur, en le créant s'il manque.
 * Un joueur peut posséder plusieurs decks : un seul est actif, et la
 * fonction garantit qu'il en existe toujours un à l'identifiant retourné.
 *
 * Aucune PDOException ne remonte : l'erreur est journalisée puis convertie
 * en AppDatabaseException avec un message lisible (affiché en bandeau).
 */
function get_or_create_deck(PDO $db, int $userId): int
{
    try {
        $active = active_deck_id($db, $userId);
        if ($active !== null) {
            return $active;
        }

        // Aucun actif : bascule le plus ancien deck existant, sinon crée le premier.
        $fallback = oldest_deck_id($db, $userId);
        if ($fallback !== null) {
            switch_active_deck($db, $userId, $fallback);

            return $fallback;
        }

        $db->prepare(
            'INSERT INTO user_decks (user_id, name, is_active)
             VALUES (:user_id, :name, 1)'
        )->execute(['user_id' => $userId, 'name' => DECK_DEFAULT_NAME]);

        return (int) $db->lastInsertId();
    } catch (PDOException $exception) {
        $deckId = recover_existing_deck($db, $userId, $exception);
        if ($deckId !== null) {
            return $deckId;
        }

        throw app_database_exception($exception, 'Création du deck du joueur ' . $userId . ' impossible');
    }
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
        $row['poster'] = movie_poster_url($row['poster']);
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
        $row['poster'] = movie_poster_url($row['poster']);
        $row['owned'] = (int) $row['owned'];
        $row['in_deck'] = (int) $row['in_deck'];
    }
    unset($row);

    return $rows;
}

/**
 * Charge les decks du joueur avec leur nombre de cartes (sélecteur de deck).
 * Le deck actif vient en premier.
 */
function load_user_decks(PDO $db, int $userId): array
{
    $statement = $db->prepare(
        'SELECT
            d.id,
            d.name,
            d.is_active,
            d.created_at,
            COALESCE(SUM(dc.quantity), 0) AS cards,
            (SELECT m.poster_link
             FROM deck_cards first_dc
             INNER JOIN movies m ON m.id = first_dc.movie_id
             WHERE first_dc.deck_id = d.id
             ORDER BY first_dc.created_at ASC, first_dc.movie_id ASC
             LIMIT 1) AS cover
         FROM user_decks d
         LEFT JOIN deck_cards dc ON dc.deck_id = d.id
         WHERE d.user_id = :user_id
         GROUP BY d.id, d.name, d.is_active, d.created_at
         ORDER BY d.is_active DESC, d.id ASC'
    );
    $statement->execute(['user_id' => $userId]);
    $rows = $statement->fetchAll();

    foreach ($rows as &$row) {
        $row['id'] = (int) $row['id'];
        $row['is_active'] = (bool) $row['is_active'];
        $row['cards'] = (int) $row['cards'];
        $row['cover'] = movie_poster_url($row['cover']);
    }
    unset($row);

    return $rows;
}

/**
 * Vérifie qu'un deck appartient bien au joueur connecté.
 */
function deck_belongs_to_user(PDO $db, int $userId, int $deckId): bool
{
    if ($deckId <= 0) {
        return false;
    }

    $statement = $db->prepare(
        'SELECT COUNT(*)
         FROM user_decks
         WHERE id = :deck_id
           AND user_id = :user_id'
    );
    $statement->execute(['deck_id' => $deckId, 'user_id' => $userId]);

    return (int) $statement->fetchColumn() > 0;
}

/** Lève une exception si le deck ne peut pas être édité par ce joueur. */
function require_user_deck(PDO $db, int $userId, int $deckId): int
{
    if (!deck_belongs_to_user($db, $userId, $deckId)) {
        throw new RuntimeException('Ce deck n\'existe pas.');
    }

    return $deckId;
}

/** Compte les decks du joueur (contrôle la limite de decks). */
function user_deck_count(PDO $db, int $userId): int
{
    $statement = $db->prepare(
        'SELECT COUNT(*) FROM user_decks WHERE user_id = :user_id'
    );
    $statement->execute(['user_id' => $userId]);

    return (int) $statement->fetchColumn();
}

/** Ramène un nom saisi à un nom utilisable : sans espaces superflus, 60 caractères. */
function deck_name(string $name): string
{
    $name = trim(mb_substr($name, 0, 60, 'UTF-8'));

    return $name === '' ? DECK_DEFAULT_NAME : $name;
}

/**
 * Crée un nouveau deck vide, nommé par le joueur. Le deck actif reste
 * inchangé : c'est le joueur qui bascule explicitement son deck actif.
 *
 * @return int L'identifiant du nouveau deck.
 */
function create_deck(PDO $db, int $userId, ?string $name): int
{
    if (user_deck_count($db, $userId) >= DECK_MAX_COUNT) {
        throw new RuntimeException('Limite de ' . DECK_MAX_COUNT . ' decks atteinte.');
    }

    $db->prepare(
        'INSERT INTO user_decks (user_id, name, is_active)
         VALUES (:user_id, :name, 0)'
    )->execute(['user_id' => $userId, 'name' => deck_name((string) $name)]);

    return (int) $db->lastInsertId();
}

/** Renomme un deck du joueur. */
function rename_deck(PDO $db, int $userId, int $deckId, string $name): void
{
    require_user_deck($db, $userId, $deckId);

    $db->prepare(
        'UPDATE user_decks
         SET name = :name
         WHERE id = :deck_id
           AND user_id = :user_id'
    )->execute(['name' => deck_name($name), 'deck_id' => $deckId, 'user_id' => $userId]);
}

/**
 * Bascule le deck actif du joueur : un seul deck actif à la fois,
 * garanti dans la même transaction.
 */
function switch_active_deck(PDO $db, int $userId, int $deckId): void
{
    require_user_deck($db, $userId, $deckId);

    $db->prepare(
        'UPDATE user_decks
         SET is_active = 0
         WHERE user_id = :user_id'
    )->execute(['user_id' => $userId]);

    $db->prepare(
        'UPDATE user_decks
         SET is_active = 1
         WHERE id = :deck_id
           AND user_id = :user_id'
    )->execute(['deck_id' => $deckId, 'user_id' => $userId]);
}

/**
 * Supprime un deck (et ses cartes). Le joueur conserve toujours au moins
 * un deck ; si le deck supprimé était actif, le plus ancien restant devient actif.
 */
function delete_deck(PDO $db, int $userId, int $deckId): void
{
    require_user_deck($db, $userId, $deckId);

    $statement = $db->prepare(
        'SELECT is_active
         FROM user_decks
         WHERE id = :deck_id
           AND user_id = :user_id
         FOR UPDATE'
    );
    $statement->execute(['deck_id' => $deckId, 'user_id' => $userId]);
    $wasActive = (bool) $statement->fetchColumn();

    $count = user_deck_count($db, $userId);
    if ($count <= 1) {
        throw new RuntimeException('Impossible de supprimer votre dernier deck.');
    }

    $db->prepare(
        'DELETE FROM user_decks
         WHERE id = :deck_id
           AND user_id = :user_id'
    )->execute(['deck_id' => $deckId, 'user_id' => $userId]);

    if ($wasActive) {
        $db->prepare(
            'UPDATE user_decks
             SET is_active = 1
             WHERE user_id = :user_id
             ORDER BY id ASC
             LIMIT 1'
        )->execute(['user_id' => $userId]);
    }
}
