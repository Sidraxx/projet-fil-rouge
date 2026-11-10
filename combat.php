<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/combat.php';
require_once __DIR__ . '/includes/decks.php';
require_once __DIR__ . '/includes/json.php';

if (!isset($_SESSION['user_id'])) {
    json_response(['success' => false, 'message' => 'Connexion requise.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Méthode non autorisée.'], 405);
}

if (!hash_equals(ensure_csrf_token(), (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) {
    json_response(['success' => false, 'message' => 'Session expirée. Rechargez la page.'], 403);
}

$payload = json_decode((string) file_get_contents('php://input'), true);
$action = is_array($payload) ? (string) ($payload['action'] ?? '') : '';
$db = database();

try {
    if ($action === 'start') {
        $ids = is_array($payload['movie_ids'] ?? null) ? array_values(array_unique(array_map('intval', $payload['movie_ids']))) : [];
        if (count($ids) !== 4 || in_array(0, $ids, true)) {
            json_response(['success' => false, 'message' => 'Sélectionnez exactement 4 cartes différentes.'], 422);
        }

        $activeDeckId = get_or_create_deck($db, (int) $_SESSION['user_id']);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $statement = $db->prepare(
            'SELECT m.id, m.title, m.poster_link, m.genre
             FROM deck_cards dc
             INNER JOIN user_decks d ON d.id = dc.deck_id
             INNER JOIN movies m ON m.id = dc.movie_id
             WHERE d.id = ? AND d.user_id = ? AND d.is_active = 1
               AND m.id IN (' . $placeholders . ')'
        );
        $statement->execute(array_merge([$activeDeckId, (int) $_SESSION['user_id']], $ids));
        $movies = $statement->fetchAll();
        if (count($movies) !== 4) {
            json_response(['success' => false, 'message' => 'Une ou plusieurs cartes ne font pas partie de votre deck actif.'], 422);
        }

        $playerTeam = array_map('combat_card_from_movie', $movies);
        $previewIds = array_values(array_unique(array_map(
            'intval',
            $_SESSION['combat_preview_enemy_ids'] ?? []
        )));
        if (count($previewIds) < 4) {
            json_response(['success' => false, 'message' => 'L’aperçu adverse est indisponible. Rechargez le lobby.'], 409);
        }

        shuffle($previewIds);
        $enemyIds = array_slice($previewIds, 0, 4);
        $enemyPlaceholders = implode(',', array_fill(0, count($enemyIds), '?'));
        $enemyStatement = $db->prepare(
            'SELECT id, title, poster_link, genre
             FROM movies
             WHERE genre IS NOT NULL
               AND id IN (' . $enemyPlaceholders . ')
             ORDER BY RAND()'
        );
        $enemyStatement->execute($enemyIds);
        $enemyTeam = array_map('combat_card_from_movie', $enemyStatement->fetchAll());
        if (count($enemyTeam) !== 4) {
            json_response(['success' => false, 'message' => 'Impossible de préparer l’adversaire.'], 500);
        }

        $_SESSION['combat'] = [
            'round' => 1,
            'turn' => 'player',
            'player_active' => 0,
            'enemy_active' => 0,
            'player' => ['energy' => COMBAT_STARTING_ENERGY, 'focus_used' => false, 'team' => $playerTeam],
            'enemy' => ['energy' => COMBAT_STARTING_ENERGY, 'focus_used' => false, 'team' => $enemyTeam],
            'log' => ['Le combat commence. Vous avez ' . COMBAT_STARTING_ENERGY . ' points d’énergie. Vous récupérez ' . COMBAT_ENERGY_REGEN . ' énergie au début de chaque tour.'],
        ];
        unset($_SESSION['combat_preview_enemy_ids']);
        json_response(['success' => true, 'state' => combat_public_state($_SESSION['combat'])]);
    }

    if (!isset($_SESSION['combat']) || !is_array($_SESSION['combat'])) {
        json_response(['success' => false, 'message' => 'Aucun combat en cours.'], 409);
    }

    $state = &$_SESSION['combat'];
    if (combat_status($state) !== 'playing') {
        json_response(['success' => false, 'message' => 'Ce combat est terminé.', 'state' => combat_public_state($state)], 409);
    }

    if ($action === 'attack') {
        if ($state['turn'] !== 'player') {
            json_response(['success' => false, 'message' => 'Ce n’est pas votre tour.'], 409);
        }
        $attackIndex = (int) ($payload['attack_index'] ?? -1);
        $attackerIndex = (int) ($payload['attacker_index'] ?? $state['player_active']);
        $targetIndex = (int) ($payload['target_index'] ?? $state['enemy_active']);
        $result = combat_apply_attack($state, 'player', $attackerIndex, $attackIndex, 'enemy', $targetIndex);
        if (!$result['success']) {
            json_response($result, 422);
        }
        combat_log($state, 'Il vous reste ' . $state['player']['energy'] . ' énergie.');
        json_response(['success' => true, 'result' => $result, 'state' => combat_public_state($state)]);
    }

    if ($action === 'effect') {
        if ($state['turn'] !== 'player') {
            json_response(['success' => false, 'message' => 'Ce n’est pas votre tour.'], 409);
        }
        $targetSide = (string) ($payload['target_side'] ?? 'player');
        if (!in_array($targetSide, ['player', 'enemy'], true)) {
            json_response(['success' => false, 'message' => 'Cible invalide.'], 422);
        }
        $targetIndex = (int) ($payload['target_index'] ?? $state[$targetSide . '_active']);
        $sourceIndex = (int) ($payload['source_index'] ?? $state['player_active']);
        $result = combat_apply_effect($state, 'player', $targetSide, $targetIndex, $sourceIndex);
        if (!$result['success']) {
            json_response($result, 422);
        }
        json_response(['success' => true, 'result' => $result, 'state' => combat_public_state($state)]);
    }

    if ($action === 'focus') {
        if ($state['turn'] !== 'player') {
            json_response(['success' => false, 'message' => 'Ce n’est pas votre tour.'], 409);
        }
        $result = combat_apply_focus($state, 'player');
        if (!$result['success']) {
            json_response($result, 422);
        }
        json_response(['success' => true, 'result' => $result, 'state' => combat_public_state($state)]);
    }

    if ($action === 'switch') {
        if ($state['turn'] !== 'player') {
            json_response(['success' => false, 'message' => 'Ce n’est pas votre tour.'], 409);
        }
        $index = (int) ($payload['index'] ?? -1);
        if (!combat_select_next_active($state, 'player', $index)) {
            json_response(['success' => false, 'message' => 'Cette carte est indisponible.'], 422);
        }
        combat_log($state, $state['player']['team'][$index]['title'] . ' entre en jeu.');
        json_response(['success' => true, 'state' => combat_public_state($state)]);
    }

    if ($action === 'end_turn') {
        if ($state['turn'] !== 'player') {
            json_response(['success' => false, 'message' => 'Ce n’est pas votre tour.'], 409);
        }
        $state['turn'] = 'enemy';
        combat_ai_turn($state);
        json_response(['success' => true, 'state' => combat_public_state($state)]);
    }

    if ($action === 'cancel') {
        unset($_SESSION['combat']);
        json_response(['success' => true]);
    }

    json_response(['success' => false, 'message' => 'Action inconnue.'], 422);
} catch (Throwable $exception) {
    json_response(['success' => false, 'message' => 'Impossible de traiter cette action de combat.'], 500);
}
