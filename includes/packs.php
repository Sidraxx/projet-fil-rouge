<?php

declare(strict_types=1);

const PACK_LIMIT = 10;
const PACK_REFILL_SECONDS = 600;

/**
 * Recalcule les paquets disponibles (recharge à la demande, sans cron).
 * Doit être appelé dans une transaction si l'on va consommer un paquet ensuite.
 *
 * @return array{available: int, next_in: ?int}
 */
function refresh_pack_state(PDO $db, int $userId): array
{
    $now = time();

    $db->prepare(
        'INSERT IGNORE INTO user_pack_state (user_id, packs_available, last_refill_ts)
         VALUES (:user_id, :limit, :now)'
    )->execute(['user_id' => $userId, 'limit' => PACK_LIMIT, 'now' => $now]);

    $select = $db->prepare(
        'SELECT packs_available, last_refill_ts
         FROM user_pack_state
         WHERE user_id = :user_id
         FOR UPDATE'
    );
    $select->execute(['user_id' => $userId]);
    $row = $select->fetch();

    // Ligne absente (joueur supprimé, session obsolète, INSERT IGNORE avalé
    // par une clé étrangère) : on repart d'un état vide sans faire planter la page.
    if (!$row) {
        app_log('warning', 'user_pack_state introuvable pour le joueur ' . $userId . ' : état des paquets réinitialisé à zéro.', ['user_id' => $userId]);

        return ['available' => 0, 'next_in' => null];
    }

    $available = (int) $row['packs_available'];
    $last = (int) $row['last_refill_ts'];

    if ($available >= PACK_LIMIT) {
        $last = $now;
    } else {
        $elapsed = intdiv(max(0, $now - $last), PACK_REFILL_SECONDS);
        $added = min($elapsed, PACK_LIMIT - $available);
        if ($added > 0) {
            $available += $added;
            $last += $added * PACK_REFILL_SECONDS;
            if ($available >= PACK_LIMIT) {
                $last = $now;
            }
        }
    }

    $db->prepare(
        'UPDATE user_pack_state
         SET packs_available = :available, last_refill_ts = :last
         WHERE user_id = :user_id'
    )->execute(['available' => $available, 'last' => $last, 'user_id' => $userId]);

    return [
        'available' => $available,
        'next_in' => $available < PACK_LIMIT ? max(0, $last + PACK_REFILL_SECONDS - $now) : null,
    ];
}

/**
 * Consomme un paquet. Retourne l'état après consommation, ou null si aucun paquet n'est disponible.
 * Doit être appelé dans une transaction.
 *
 * @return array{available: int, next_in: ?int}|null
 */
function consume_pack(PDO $db, int $userId): ?array
{
    $state = refresh_pack_state($db, $userId);
    if ($state['available'] <= 0) {
        return null;
    }

    $wasFull = $state['available'] === PACK_LIMIT;
    $available = $state['available'] - 1;

    if ($wasFull) {
        // Le compte à rebours démarre au moment où l'on passe sous la limite.
        $db->prepare('UPDATE user_pack_state SET last_refill_ts = :now WHERE user_id = :user_id')
            ->execute(['now' => time(), 'user_id' => $userId]);
    }

    $db->prepare('UPDATE user_pack_state SET packs_available = :available WHERE user_id = :user_id')
        ->execute(['available' => $available, 'user_id' => $userId]);

    return refresh_pack_state($db, $userId);
}
