<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
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

$payload = json_decode((string) file_get_contents('php://input'), true);
$action = is_array($payload) ? (string) ($payload['action'] ?? '') : '';
$slot = is_array($payload) ? (int) ($payload['slot'] ?? 0) : 0;

if ($slot < 1 || $slot > 4) {
    json_response(['success' => false, 'message' => 'Emplacement de vitrine invalide.'], 400);
}

$db = database();

try {
    if ($action === 'set_showcase_card') {
        $movieId = (int) ($payload['movie_id'] ?? 0);
        if ($movieId <= 0) {
            json_response(['success' => false, 'message' => 'Carte invalide.'], 400);
        }

        $db->beginTransaction();
        $ownedCard = $db->prepare(
            'SELECT 1 FROM user_cards
             WHERE user_id = :user_id AND movie_id = :movie_id
             LIMIT 1'
        );
        $ownedCard->execute(['user_id' => $_SESSION['user_id'], 'movie_id' => $movieId]);

        if (!$ownedCard->fetchColumn()) {
            $db->rollBack();
            json_response(['success' => false, 'message' => 'Cette carte ne fait pas partie de votre collection.'], 403);
        }

        $clearPreviousPosition = $db->prepare(
            'DELETE FROM profile_showcase_cards
             WHERE user_id = :user_id
               AND (slot_position = :slot OR movie_id = :movie_id)'
        );
        $clearPreviousPosition->execute([
            'user_id' => $_SESSION['user_id'],
            'slot' => $slot,
            'movie_id' => $movieId,
        ]);

        $insert = $db->prepare(
            'INSERT INTO profile_showcase_cards (user_id, slot_position, movie_id)
             VALUES (:user_id, :slot, :movie_id)'
        );
        $insert->execute([
            'user_id' => $_SESSION['user_id'],
            'slot' => $slot,
            'movie_id' => $movieId,
        ]);
        $db->commit();
        json_response(['success' => true, 'message' => 'Vitrine mise à jour.']);
    }

    if ($action === 'remove_showcase_card') {
        $remove = $db->prepare(
            'DELETE FROM profile_showcase_cards
             WHERE user_id = :user_id AND slot_position = :slot'
        );
        $remove->execute(['user_id' => $_SESSION['user_id'], 'slot' => $slot]);
        json_response(['success' => true, 'message' => 'Carte retirée de la vitrine.']);
    }

    json_response(['success' => false, 'message' => 'Action inconnue.'], 400);
} catch (PDOException $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    app_log_exception($exception, 'Modification de la vitrine impossible (joueur ' . (int) $_SESSION['user_id'] . ')');
    json_response(['success' => false, 'message' => 'Impossible de modifier la vitrine : ' . describe_database_error($exception)], 500);
}
