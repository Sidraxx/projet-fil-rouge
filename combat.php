<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/combat.php';

header('Content-Type: application/json; charset=utf-8');

function combat_json(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    combat_json(401, ['success' => false, 'message' => 'Connexion requise.']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    combat_json(405, ['success' => false, 'message' => 'Méthode non autorisée.']);
}

if (!hash_equals(ensure_csrf_token(), (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) {
    combat_json(403, ['success' => false, 'message' => 'Session expirée. Rechargez la page.']);
}

$payload = json_decode((string) file_get_contents('php://input'), true);
$action = is_array($payload) ? (string) ($payload['action'] ?? '') : '';
$db = database();

try {
    if ($action === 'start') {
        $ids = is_array($payload['movie_ids'] ?? null) ? array_values(array_unique(array_map('intval', $payload['movie_ids']))) : [];
        if (count($ids) !== 4 || in_array(0, $ids, true)) {
            combat_json(422, ['success' => false, 'message' => 'Sélectionnez exactement 4 cartes différentes.']);
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $statement = $db->prepare(
            'SELECT m.id, m.title, m.poster_link, m.genre
             FROM user_cards uc
             INNER JOIN movies m ON m.id = uc.movie_id
             WHERE uc.user_id = ? AND m.id IN (' . $placeholders . ')'
        );
        $statement->execute(array_merge([(int) $_SESSION['user_id']], $ids));
        $movies = $statement->fetchAll();
        if (count($movies) !== 4) {
            combat_json(422, ['success' => false, 'message' => 'Une ou plusieurs cartes ne font pas partie de votre collection.']);
        }

        $playerTeam = array_map('combat_card_from_movie', $movies);
        $enemyStatement = $db->query(
            'SELECT id, title, poster_link, genre
             FROM movies
             WHERE genre IS NOT NULL
             ORDER BY RAND()
             LIMIT 4'
        );
        $enemyTeam = array_map('combat_card_from_movie', $enemyStatement->fetchAll());
        if (count($enemyTeam) !== 4) {
            combat_json(500, ['success' => false, 'message' => 'Impossible de préparer l’adversaire.']);
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
        combat_json(200, ['success' => true, 'state' => combat_public_state($_SESSION['combat'])]);
    }

    if (!isset($_SESSION['combat']) || !is_array($_SESSION['combat'])) {
        combat_json(409, ['success' => false, 'message' => 'Aucun combat en cours.']);
    }

    $state = &$_SESSION['combat'];
    if (combat_status($state) !== 'playing') {
        combat_json(409, ['success' => false, 'message' => 'Ce combat est terminé.', 'state' => combat_public_state($state)]);
    }

    if ($action === 'attack') {
        if ($state['turn'] !== 'player') {
            combat_json(409, ['success' => false, 'message' => 'Ce n’est pas votre tour.']);
        }
        $attackIndex = (int) ($payload['attack_index'] ?? -1);
        $attackerIndex = (int) ($payload['attacker_index'] ?? $state['player_active']);
        $targetIndex = (int) ($payload['target_index'] ?? $state['enemy_active']);
        $result = combat_apply_attack($state, 'player', $attackerIndex, $attackIndex, 'enemy', $targetIndex);
        if (!$result['success']) {
            combat_json(422, $result);
        }
        combat_log($state, 'Il vous reste ' . $state['player']['energy'] . ' énergie.');
        combat_json(200, ['success' => true, 'result' => $result, 'state' => combat_public_state($state)]);
    }

    if ($action === 'effect') {
        if ($state['turn'] !== 'player') {
            combat_json(409, ['success' => false, 'message' => 'Ce n’est pas votre tour.']);
        }
        $targetSide = (string) ($payload['target_side'] ?? 'player');
        if (!in_array($targetSide, ['player', 'enemy'], true)) {
            combat_json(422, ['success' => false, 'message' => 'Cible invalide.']);
        }
        $targetIndex = (int) ($payload['target_index'] ?? $state[$targetSide . '_active']);
        $sourceIndex = (int) ($payload['source_index'] ?? $state['player_active']);
        $result = combat_apply_effect($state, 'player', $targetSide, $targetIndex, $sourceIndex);
        if (!$result['success']) {
            combat_json(422, $result);
        }
        combat_json(200, ['success' => true, 'result' => $result, 'state' => combat_public_state($state)]);
    }

    if ($action === 'focus') {
        if ($state['turn'] !== 'player') {
            combat_json(409, ['success' => false, 'message' => 'Ce n’est pas votre tour.']);
        }
        $result = combat_apply_focus($state, 'player');
        if (!$result['success']) {
            combat_json(422, $result);
        }
        combat_json(200, ['success' => true, 'result' => $result, 'state' => combat_public_state($state)]);
    }

    if ($action === 'switch') {
        if ($state['turn'] !== 'player') {
            combat_json(409, ['success' => false, 'message' => 'Ce n’est pas votre tour.']);
        }
        $index = (int) ($payload['index'] ?? -1);
        if (!combat_select_next_active($state, 'player', $index)) {
            combat_json(422, ['success' => false, 'message' => 'Cette carte est indisponible.']);
        }
        combat_log($state, $state['player']['team'][$index]['title'] . ' entre en jeu.');
        combat_json(200, ['success' => true, 'state' => combat_public_state($state)]);
    }

    if ($action === 'end_turn') {
        if ($state['turn'] !== 'player') {
            combat_json(409, ['success' => false, 'message' => 'Ce n’est pas votre tour.']);
        }
        $state['turn'] = 'enemy';
        combat_ai_turn($state);
        combat_json(200, ['success' => true, 'state' => combat_public_state($state)]);
    }

    if ($action === 'cancel') {
        unset($_SESSION['combat']);
        combat_json(200, ['success' => true]);
    }

    combat_json(422, ['success' => false, 'message' => 'Action inconnue.']);
} catch (Throwable $exception) {
    combat_json(500, ['success' => false, 'message' => 'Impossible de traiter cette action de combat.']);
}
