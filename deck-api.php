<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/decks.php';
require_once __DIR__ . '/includes/json.php';

if (!isset($_SESSION['user_id'])) {
    json_response(['success' => false, 'message' => 'Connexion requise.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Méthode non autorisée.'], 405);
}

$token = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!hash_equals(ensure_csrf_token(), $token)) {
    json_response(['success' => false, 'message' => 'Session expirée. Rechargez la page.'], 403);
}

$db = database();
$userId = (int) $_SESSION['user_id'];

$data = json_decode((string) file_get_contents('php://input'), true) ?: [];
$movieId = (int) ($data['movieId'] ?? 0);
$deckId = (int) ($data['deckId'] ?? 0);
$name = (string) ($data['name'] ?? '');
$action = (string) ($data['action'] ?? '');

$deckActions = ['add', 'remove', 'create', 'rename', 'switch', 'delete'];
if (!in_array($action, $deckActions, true)) {
    json_response(['success' => false, 'message' => 'Action inconnue.'], 400);
}

try {
    $db->beginTransaction();

    // Gestion des decks : création, renommage, bascule et suppression.
    if ($action === 'create') {
        $createdId = create_deck($db, $userId, $name);
        $decks = load_user_decks($db, $userId);
        $db->commit();

        json_response([
            'success' => true,
            'deckId' => $createdId,
            'decks' => $decks,
        ]);
    }

    if ($action === 'rename') {
        rename_deck($db, $userId, $deckId, $name);
        $decks = load_user_decks($db, $userId);
        $db->commit();

        json_response([
            'success' => true,
            'deckId' => $deckId,
            'decks' => $decks,
        ]);
    }

    if ($action === 'switch') {
        switch_active_deck($db, $userId, $deckId);
        $db->commit();

        json_response([
            'success' => true,
            'deckId' => $deckId,
        ]);
    }

    if ($action === 'delete') {
        delete_deck($db, $userId, $deckId);
        $activeId = get_or_create_deck($db, $userId);
        $decks = load_user_decks($db, $userId);
        $db->commit();

        json_response([
            'success' => true,
            'deckId' => $activeId,
            'decks' => $decks,
        ]);
    }

    // Ajout / retrait : le deck visé est celui transmis, sinon le deck actif.
    if ($deckId > 0) {
        require_user_deck($db, $userId, $deckId);
    } else {
        $deckId = get_or_create_deck($db, $userId);
    }

    if ($action === 'add') {
        if ($movieId <= 0) {
            throw new RuntimeException('Carte invalide.');
        }
        add_card_to_deck($db, $deckId, $userId, $movieId);
    } else {
        if ($movieId <= 0) {
            throw new RuntimeException('Carte invalide.');
        }
        remove_card_from_deck($db, $deckId, $movieId);
    }

    $status = deck_status($db, $deckId);
    $cards = load_deck_cards($db, $deckId);
    $db->commit();
} catch (AppDatabaseException $error) {
    // Panne de base déjà journalisée et traduite par app_database_exception().
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    json_response(['success' => false, 'message' => $error->getMessage()], 500);
} catch (RuntimeException $error) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    // Refus métier (deck plein, carte non possédée...) : ce n'est pas une panne.
    app_log('warning', 'Deck refusé (' . $action . ') : ' . $error->getMessage(), ['user_id' => $userId, 'deck_id' => $deckId]);

    json_response(['success' => false, 'message' => $error->getMessage()], 400);
} catch (Throwable $error) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    app_log_exception($error, 'Mise à jour du deck impossible (action ' . $action . ')');

    json_response([
        'success' => false,
        'message' => $error instanceof PDOException
            ? 'Impossible de mettre à jour le deck : ' . describe_database_error($error)
            : 'Impossible de mettre à jour le deck : une erreur interne est survenue.',
    ], 500);
}

json_response([
    'success' => true,
    'deckId' => $deckId,
    'count' => $status['count'],
    'size' => $status['size'],
    'valid' => $status['valid'],
    'cards' => $cards,
]);
