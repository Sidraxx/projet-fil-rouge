<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/decks.php';

header('Content-Type: application/json; charset=utf-8');

function deck_json(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    deck_json(401, ['success' => false, 'message' => 'Connexion requise.']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    deck_json(405, ['success' => false, 'message' => 'Méthode non autorisée.']);
}

$token = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!hash_equals(ensure_csrf_token(), $token)) {
    deck_json(403, ['success' => false, 'message' => 'Session expirée. Rechargez la page.']);
}

$db = database();
$userId = (int) $_SESSION['user_id'];

$data = json_decode((string) file_get_contents('php://input'), true) ?: [];
$movieId = (int) ($data['movieId'] ?? 0);
$action = (string) ($data['action'] ?? '');

if ($action !== 'add' && $action !== 'remove') {
    deck_json(400, ['success' => false, 'message' => 'Action inconnue.']);
}

if ($movieId <= 0) {
    deck_json(400, ['success' => false, 'message' => 'Carte invalide.']);
}

try {
    $db->beginTransaction();
    $deckId = get_or_create_deck($db, $userId);

    if ($action === 'add') {
        add_card_to_deck($db, $deckId, $userId, $movieId);
    } else {
        remove_card_from_deck($db, $deckId, $movieId);
    }

    $status = deck_status($db, $deckId);
    $cards = load_deck_cards($db, $deckId);
    $db->commit();
} catch (RuntimeException $error) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    deck_json(400, ['success' => false, 'message' => $error->getMessage()]);
} catch (Throwable) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    deck_json(500, ['success' => false, 'message' => 'Impossible de mettre à jour le deck.']);
}

deck_json(200, [
    'success' => true,
    'deckId' => $deckId,
    'count' => $status['count'],
    'size' => $status['size'],
    'valid' => $status['valid'],
    'cards' => $cards,
]);
