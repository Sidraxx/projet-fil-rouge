<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/cards.php';
require_once __DIR__ . '/includes/packs.php';

header('Content-Type: application/json; charset=utf-8');

function pack_json(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    pack_json(401, ['success' => false, 'message' => 'Connexion requise.']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    pack_json(405, ['success' => false, 'message' => 'Méthode non autorisée.']);
}

$token = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!hash_equals(ensure_csrf_token(), $token)) {
    pack_json(403, ['success' => false, 'message' => 'Session expirée. Rechargez la page.']);
}

$db = database();
$userId = (int) $_SESSION['user_id'];

try {
    $db->beginTransaction();
    $state = consume_pack($db, $userId);
    if ($state === null) {
        $db->rollBack();
        pack_json(409, ['success' => false, 'message' => 'Aucun paquet disponible pour le moment.', 'packsAvailable' => 0, 'nextIn' => refresh_pack_state($db, $userId)['next_in']]);
    }
    $cards = draw_pack_cards($db, 5);
    save_cards_to_collection($db, $userId, $cards);
    $db->commit();
} catch (Throwable) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    pack_json(500, ['success' => false, 'message' => 'Impossible de tirer les cartes. Vérifiez que les raretés sont calculées.']);
}

pack_json(200, [
    'success' => true,
    'cards' => $cards,
    'packsAvailable' => $state['available'],
    'nextIn' => $state['next_in'],
]);
