<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/cards.php';
require_once __DIR__ . '/includes/packs.php';
require_once __DIR__ . '/includes/json.php';
require_once __DIR__ . '/includes/rate_limit.php';

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

$retry = rate_limit_api('open-pack', (string) $userId, 30);
if ($retry > 0) {
    header('Retry-After: ' . $retry);
    json_response(['success' => false, 'message' => 'Trop de tentatives d\'ouverture. Patientez un instant.'], 429);
}

try {
    $db->beginTransaction();
    $state = consume_pack($db, $userId);
    if ($state === null) {
        $db->rollBack();
        json_response(['success' => false, 'message' => 'Aucun paquet disponible pour le moment.', 'packsAvailable' => 0, 'nextIn' => refresh_pack_state($db, $userId)['next_in']], 409);
    }
    $cards = draw_pack_cards($db, 5);
    save_cards_to_collection($db, $userId, $cards);
    $db->commit();
} catch (Throwable $error) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    app_log_exception($error, 'Tirage de paquet impossible (joueur ' . $userId . ')');

    json_response([
        'success' => false,
        'message' => $error instanceof PDOException
            ? 'Impossible de tirer les cartes : ' . describe_database_error($error)
            : 'Impossible de tirer les cartes. Vérifiez que les raretés sont calculées.',
    ], 500);
}

json_response([
    'success' => true,
    'cards' => $cards,
    'packsAvailable' => $state['available'],
    'nextIn' => $state['next_in'],
]);
