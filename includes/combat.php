<?php

declare(strict_types=1);

const COMBAT_STARTING_ENERGY = 5;
const COMBAT_ENERGY_REGEN = 3;
const COMBAT_MAX_ENERGY = 12;
const COMBAT_FOCUS_GAIN = 2;

function combat_energy_after_regeneration(int $energy): int
{
    return min(COMBAT_MAX_ENERGY, $energy + COMBAT_ENERGY_REGEN);
}

function combat_profiles(): array
{
    return [
        'Action' => [
            'hp' => 100, 'atk' => 12, 'role' => 'Dégâts élevés',
            'attacks' => [
                ['name' => 'Frappe rapide', 'damage' => 8, 'cost' => 1],
                ['name' => 'Assaut', 'damage' => 18, 'cost' => 3],
                ['name' => 'Déchaînement', 'damage' => 28, 'cost' => 5],
            ],
            'passive' => ['name' => 'Adrénaline', 'description' => 'La prochaine attaque après être passé sous 50 % inflige +4 dégâts.', 'type' => 'adrenaline'],
            'effect' => ['name' => 'Boost', 'description' => '+3 ATK jusqu’à la fin du prochain tour.', 'cost' => 2, 'type' => 'boost'],
        ],
        'Adventure' => [
            'hp' => 110, 'atk' => 10, 'role' => 'Polyvalence et endurance',
            'attacks' => [
                ['name' => 'Coup d’explorateur', 'damage' => 7, 'cost' => 1],
                ['name' => 'Prise de risque', 'damage' => 15, 'cost' => 2],
                ['name' => 'Dernière épreuve', 'damage' => 22, 'cost' => 4],
            ],
            'passive' => ['name' => 'Persévérance', 'description' => 'Récupère 8 PV la première fois que la carte passe sous 50 %.', 'type' => 'perseverance'],
            'effect' => ['name' => 'Attaque renforcée', 'description' => 'La prochaine attaque inflige +5 dégâts.', 'cost' => 2, 'type' => 'empower'],
        ],
        'Biography' => [
            'hp' => 105, 'atk' => 9, 'role' => 'Résistance et soutien',
            'attacks' => [
                ['name' => 'Expérience', 'damage' => 7, 'cost' => 1],
                ['name' => 'Leçon de vie', 'damage' => 12, 'cost' => 2, 'shield' => 3],
                ['name' => 'Détermination', 'damage' => 19, 'cost' => 4],
            ],
            'passive' => ['name' => 'Résilience', 'description' => 'Après 20 dégâts reçus, gagne 2 DEF jusqu’à la fin du prochain tour adverse.', 'type' => 'resilience'],
            'effect' => ['name' => 'Bouclier', 'description' => 'Réduit les prochains dégâts reçus de 8.', 'cost' => 2, 'type' => 'shield', 'amount' => 8],
        ],
        'Comedy' => [
            'hp' => 90, 'atk' => 9, 'role' => 'Perturbation',
            'attacks' => [
                ['name' => 'Gag surprise', 'damage' => 7, 'cost' => 1],
                ['name' => 'Désorientation', 'damage' => 10, 'cost' => 2, 'weaken' => 3],
                ['name' => 'Chute comique', 'damage' => 17, 'cost' => 3],
            ],
            'passive' => ['name' => 'Détournement', 'description' => 'La première attaque spéciale reçue inflige 3 dégâts de moins.', 'type' => 'diversion'],
            'effect' => ['name' => 'Affaiblissement', 'description' => 'Réduit de 3 l’ATK adverse pour son prochain tour.', 'cost' => 2, 'type' => 'weaken', 'amount' => 3],
        ],
        'Crime' => [
            'hp' => 100, 'atk' => 11, 'role' => 'Attaques tactiques',
            'attacks' => [
                ['name' => 'Coup calculé', 'damage' => 8, 'cost' => 1],
                ['name' => 'Point faible', 'damage' => 12, 'cost' => 2, 'ignore_def' => 3],
                ['name' => 'Exécution', 'damage' => 20, 'cost' => 4],
            ],
            'passive' => ['name' => 'Repérage', 'description' => 'Toutes les attaques ignorent 2 DEF.', 'type' => 'scouting'],
            'effect' => ['name' => 'Attaque renforcée', 'description' => 'La prochaine attaque ignore 4 DEF supplémentaires.', 'cost' => 2, 'type' => 'precision'],
        ],
        'Drama' => [
            'hp' => 115, 'atk' => 9, 'role' => 'Soin et résistance',
            'attacks' => [
                ['name' => 'Confrontation', 'damage' => 7, 'cost' => 1],
                ['name' => 'Rebondissement', 'damage' => 13, 'cost' => 2],
                ['name' => 'Moment décisif', 'damage' => 18, 'cost' => 4, 'heal' => 5],
            ],
            'passive' => ['name' => 'Force intérieure', 'description' => 'Récupère 10 PV la première fois que la carte passe sous 40 %.', 'type' => 'inner_strength'],
            'effect' => ['name' => 'Soin', 'description' => 'Récupère 12 PV sans dépasser les PV maximum.', 'cost' => 2, 'type' => 'heal', 'amount' => 12],
        ],
        'Family' => [
            'hp' => 115, 'atk' => 8, 'role' => 'Protection et soutien',
            'attacks' => [
                ['name' => 'Coup solidaire', 'damage' => 6, 'cost' => 1],
                ['name' => 'Protection', 'damage' => 0, 'cost' => 2, 'shield' => 8],
                ['name' => 'Union', 'damage' => 12, 'cost' => 4, 'heal' => 8],
            ],
            'passive' => ['name' => 'Solidarité', 'description' => 'Récupère 8 PV lorsqu’une autre carte alliée est vaincue.', 'type' => 'solidarity'],
            'effect' => ['name' => 'Bouclier', 'description' => 'Réduit les prochains dégâts reçus de 8.', 'cost' => 2, 'type' => 'shield', 'amount' => 8],
        ],
        'Fantasy' => [
            'hp' => 100, 'atk' => 11, 'role' => 'Capacités spéciales',
            'attacks' => [
                ['name' => 'Projectile magique', 'damage' => 8, 'cost' => 1],
                ['name' => 'Sort protecteur', 'damage' => 12, 'cost' => 3, 'shield' => 5],
                ['name' => 'Explosion arcanique', 'damage' => 21, 'cost' => 4],
            ],
            'passive' => ['name' => 'Magie', 'description' => 'La première capacité spéciale coûte 1 énergie de moins.', 'type' => 'magic'],
            'effect' => ['name' => 'Bouclier', 'description' => 'Réduit les prochains dégâts reçus de 8.', 'cost' => 2, 'type' => 'shield', 'amount' => 8],
        ],
        'History' => [
            'hp' => 120, 'atk' => 9, 'role' => 'Défense',
            'attacks' => [
                ['name' => 'Frappe historique', 'damage' => 7, 'cost' => 1],
                ['name' => 'Formation défensive', 'damage' => 12, 'cost' => 2, 'defense' => 4],
                ['name' => 'Grande offensive', 'damage' => 19, 'cost' => 4],
            ],
            'passive' => ['name' => 'Héritage', 'description' => 'Réduit de 2 tous les dégâts reçus.', 'type' => 'heritage'],
            'effect' => ['name' => 'Bouclier', 'description' => 'Réduit les prochains dégâts reçus de 8.', 'cost' => 2, 'type' => 'shield', 'amount' => 8],
        ],
        'Horror' => [
            'hp' => 95, 'atk' => 12, 'role' => 'Dégâts persistants',
            'attacks' => [
                ['name' => 'Coup terrifiant', 'damage' => 8, 'cost' => 1],
                ['name' => 'Terreur', 'damage' => 12, 'cost' => 2, 'weaken' => 3],
                ['name' => 'Malédiction', 'damage' => 14, 'cost' => 4, 'poison' => 4, 'poison_turns' => 2],
            ],
            'passive' => ['name' => 'Peur', 'description' => 'La première attaque réduit de 2 la DEF adverse jusqu’à la fin du prochain tour.', 'type' => 'fear'],
            'effect' => ['name' => 'Poison', 'description' => 'Inflige 4 dégâts pendant 2 tours.', 'cost' => 3, 'type' => 'poison', 'amount' => 4, 'turns' => 2],
        ],
        'Mystery' => [
            'hp' => 95, 'atk' => 10, 'role' => 'Contrôle',
            'attacks' => [
                ['name' => 'Indice caché', 'damage' => 7, 'cost' => 1],
                ['name' => 'Déduction', 'damage' => 12, 'cost' => 2, 'ignore_def' => 4],
                ['name' => 'Énigme fatale', 'damage' => 19, 'cost' => 4],
            ],
            'passive' => ['name' => 'Anticipation', 'description' => 'La première attaque reçue inflige 4 dégâts de moins.', 'type' => 'anticipation'],
            'effect' => ['name' => 'Affaiblissement', 'description' => 'Réduit de 3 l’ATK adverse pour son prochain tour.', 'cost' => 2, 'type' => 'weaken', 'amount' => 3],
        ],
        'Romance' => [
            'hp' => 100, 'atk' => 9, 'role' => 'Soin et soutien',
            'attacks' => [
                ['name' => 'Élan du cœur', 'damage' => 7, 'cost' => 1],
                ['name' => 'Réconfort', 'damage' => 0, 'cost' => 2, 'heal' => 12],
                ['name' => 'Passion intense', 'damage' => 17, 'cost' => 3],
            ],
            'passive' => ['name' => 'Attachement', 'description' => 'Récupère 8 PV une fois en passant sous 50 %.', 'type' => 'attachment'],
            'effect' => ['name' => 'Soin', 'description' => 'Récupère 12 PV sans dépasser les PV maximum.', 'cost' => 2, 'type' => 'heal', 'amount' => 12],
        ],
        'Sci-Fi' => [
            'hp' => 95, 'atk' => 11, 'role' => 'Attaques techniques',
            'attacks' => [
                ['name' => 'Tir laser', 'damage' => 8, 'cost' => 1],
                ['name' => 'Surcharge', 'damage' => 15, 'cost' => 3],
                ['name' => 'Rayon expérimental', 'damage' => 20, 'cost' => 4, 'ignore_def' => 3],
            ],
            'passive' => ['name' => 'Optimisation', 'description' => 'La première attaque spéciale coûte 1 énergie de moins.', 'type' => 'optimization'],
            'effect' => ['name' => 'Boost', 'description' => '+3 ATK jusqu’à la fin du prochain tour.', 'cost' => 2, 'type' => 'boost'],
        ],
        'Thriller' => [
            'hp' => 95, 'atk' => 12, 'role' => 'Pression offensive',
            'attacks' => [
                ['name' => 'Frappe soudaine', 'damage' => 8, 'cost' => 1],
                ['name' => 'Suspense', 'damage' => 13, 'cost' => 2, 'weaken' => 3],
                ['name' => 'Point de rupture', 'damage' => 22, 'cost' => 4],
            ],
            'passive' => ['name' => 'Tension', 'description' => '+4 dégâts contre une cible sous 50 % de ses PV.', 'type' => 'tension'],
            'effect' => ['name' => 'Affaiblissement', 'description' => 'Réduit de 3 l’ATK adverse pour son prochain tour.', 'cost' => 2, 'type' => 'weaken', 'amount' => 3],
        ],
        'War' => [
            'hp' => 125, 'atk' => 11, 'role' => 'Puissance et résistance',
            'attacks' => [
                ['name' => 'Tir de couverture', 'damage' => 8, 'cost' => 1],
                ['name' => 'Offensive militaire', 'damage' => 16, 'cost' => 3],
                ['name' => 'Bombardement', 'damage' => 22, 'cost' => 5],
            ],
            'passive' => ['name' => 'Armure', 'description' => 'Réduit de 2 tous les dégâts reçus.', 'type' => 'armor'],
            'effect' => ['name' => 'Bouclier', 'description' => 'Réduit les prochains dégâts reçus de 8.', 'cost' => 2, 'type' => 'shield', 'amount' => 8],
        ],
        'Western' => [
            'hp' => 105, 'atk' => 11, 'role' => 'Précision et duel',
            'attacks' => [
                ['name' => 'Tir rapide', 'damage' => 8, 'cost' => 1],
                ['name' => 'Duel', 'damage' => 14, 'cost' => 3, 'ignore_def' => 3],
                ['name' => 'Tir décisif', 'damage' => 22, 'cost' => 5],
            ],
            'passive' => ['name' => 'Tireur d’élite', 'description' => 'La première attaque ignore 4 DEF.', 'type' => 'sniper'],
            'effect' => ['name' => 'Attaque renforcée', 'description' => 'La prochaine attaque inflige +5 dégâts.', 'cost' => 2, 'type' => 'empower'],
        ],
    ];
}

function combat_genre_cycle(): array
{
    return ['Action', 'Crime', 'Comedy', 'Horror', 'Drama', 'Romance', 'Family', 'Adventure', 'Fantasy', 'Sci-Fi', 'Mystery', 'Thriller', 'War', 'History', 'Biography', 'Western'];
}

function combat_genre_advantage(array $attackerGenres, array $defenderGenres): int
{
    $cycle = combat_genre_cycle();
    $advantages = [];
    foreach ($cycle as $index => $genre) {
        $advantages[$genre] = $cycle[($index + 1) % count($cycle)];
    }

    foreach ($attackerGenres as $attackerGenre) {
        foreach ($defenderGenres as $defenderGenre) {
            if (($advantages[$attackerGenre] ?? null) === $defenderGenre) {
                return 25;
            }
            if (($advantages[$defenderGenre] ?? null) === $attackerGenre) {
                return -25;
            }
        }
    }

    return 0;
}

function combat_card_from_movie(array $movie): array
{
    $profiles = combat_profiles();
    $genres = array_values(array_filter(array_map('trim', explode(',', (string) ($movie['genre'] ?? '')))));
    $genres = array_values(array_filter($genres, static fn (string $genre): bool => isset($profiles[$genre])));
    if ($genres === []) {
        $genres = ['Drama'];
    }

    $primary = $genres[0];
    $profile = $profiles[$primary];
    $maxHp = $profile['hp'];
    $attack = $profile['atk'];

    foreach (array_slice($genres, 1) as $secondary) {
        $maxHp += 5;
        $attack += 1;
    }
    $maxHp = min($maxHp, $profile['hp'] + 10);
    $attack = min($attack, $profile['atk'] + 2);

    $attacks = [];
    foreach ($genres as $genre) {
        foreach ($profiles[$genre]['attacks'] as $entry) {
            $entry['id'] = strtolower($genre . '-' . $entry['name']);
            if (!isset($attacks[$entry['id']])) {
                $attacks[$entry['id']] = $entry;
            }
        }
    }
    $attacks = array_values($attacks);
    $attacks = array_slice($attacks, 0, 3);
    $strengths = [];
    $weaknesses = [];
    foreach ($genres as $genre) {
        $cycle = combat_genre_cycle();
        $genreIndex = array_search($genre, $cycle, true);
        if ($genreIndex === false) {
            continue;
        }
        $strengths[] = $cycle[($genreIndex + 1) % count($cycle)];
        $weaknesses[] = $cycle[($genreIndex - 1 + count($cycle)) % count($cycle)];
    }

    return [
        'id' => (int) $movie['id'],
        'title' => (string) $movie['title'],
        'poster' => (string) ($movie['poster_link'] ?? ''),
        'genres' => $genres,
        'strengths' => array_values(array_unique($strengths)),
        'weaknesses' => array_values(array_unique($weaknesses)),
        'genre' => $primary,
        'max_hp' => $maxHp,
        'hp' => $maxHp,
        'atk' => $attack,
        'base_def' => 0,
        'def_bonus' => 0,
        'shield' => 0,
        'def_penalty' => 0,
        'poison' => 0,
        'poison_turns' => 0,
        'attack_boost' => 0,
        'weakened' => 0,
        'empowered' => false,
        'passive' => $profile['passive'],
        'passive_used' => false,
        'attacks' => $attacks,
        'effect' => $profile['effect'],
        'defeated' => false,
    ];
}

function combat_log(array &$state, string $message): void
{
    $state['log'][] = $message;
    if (count($state['log']) > 80) {
        array_shift($state['log']);
    }
}

function combat_alive_indices(array $team): array
{
    return array_values(array_keys(array_filter($team, static fn (array $card): bool => !$card['defeated'] && $card['hp'] > 0)));
}

function combat_begin_turn(array &$state, string $side): void
{
    $previousEnergy = $state[$side]['energy'];
    $state[$side]['energy'] = combat_energy_after_regeneration($previousEnergy);
    $state[$side]['focus_used'] = false;
    $regenerated = $state[$side]['energy'] - $previousEnergy;
    combat_log($state, ($side === 'player' ? 'Vous récupérez ' : 'Le système récupère ') . $regenerated . ' énergie (' . $state[$side]['energy'] . '/' . COMBAT_MAX_ENERGY . ').');
    foreach (['player', 'enemy'] as $affectedSide) {
        foreach ($state[$affectedSide]['team'] as &$affectedCard) {
            if ($affectedCard['def_penalty'] > 0) {
                $affectedCard['def_penalty']--;
            }
        }
        unset($affectedCard);
    }
    $activeKey = $side . '_active';
    if (!isset($state[$side]['team'][$state[$activeKey]])) {
        return;
    }
    $active = &$state[$side]['team'][$state[$activeKey]];
    if ($active['poison_turns'] > 0) {
        $damage = min($active['hp'], $active['poison']);
        $active['hp'] -= $damage;
        $active['poison_turns']--;
        combat_log($state, $active['title'] . ' subit ' . $damage . ' dégâts de poison.');
        if ($active['hp'] <= 0) {
            $active['hp'] = 0;
            $active['defeated'] = true;
            combat_log($state, $active['title'] . ' est vaincu par le poison.');
            combat_select_next_active($state, $side);
        }
    }
    $active['def_bonus'] = 0;
    $active['attack_boost'] = 0;
}

function combat_select_next_active(array &$state, string $side, ?int $index = null): bool
{
    $alive = combat_alive_indices($state[$side]['team']);
    if ($alive === []) {
        return false;
    }
    if ($index !== null && in_array($index, $alive, true)) {
        $state[$side . '_active'] = $index;
        return true;
    }
    $state[$side . '_active'] = $alive[0];
    return true;
}

function combat_receive_damage(array &$state, string $defenderSide, int $damage, int $ignoreDef = 0, bool $special = false): int
{
    $card = &$state[$defenderSide]['team'][$state[$defenderSide . '_active']];
    $defense = max(0, $card['base_def'] + $card['def_bonus'] - $card['def_penalty'] - $ignoreDef);
    if (!$card['passive_used'] && $card['passive']['type'] === 'anticipation') {
        $damage = max(0, $damage - 4);
        $card['passive_used'] = true;
    }
    if (!$card['passive_used'] && $card['passive']['type'] === 'diversion' && $special) {
        $damage = max(0, $damage - 3);
        $card['passive_used'] = true;
    }
    if ($card['passive']['type'] === 'heritage') {
        $damage = max(0, $damage - 2);
    }
    if ($card['passive']['type'] === 'armor') {
        $damage = max(0, $damage - 2);
    }
    $damage = max(0, $damage - $defense);
    if ($card['shield'] > 0) {
        $absorbed = min($damage, $card['shield']);
        $card['shield'] -= $absorbed;
        $damage -= $absorbed;
    }
    $card['hp'] = max(0, $card['hp'] - $damage);
    return $damage;
}

function combat_check_passive(array &$state, string $side, int $beforeHp): void
{
    $card = &$state[$side]['team'][$state[$side . '_active']];
    if ($card['hp'] <= 0) {
        $card['defeated'] = true;
        return;
    }
    $ratio = $card['hp'] / $card['max_hp'];
    if (!$card['passive_used'] && $beforeHp > $card['max_hp'] * .5 && $ratio <= .5) {
        switch ($card['passive']['type']) {
            case 'perseverance':
                $card['hp'] = min($card['max_hp'], $card['hp'] + 8);
                $card['passive_used'] = true;
                combat_log($state, $card['title'] . ' récupère 8 PV grâce à Persévérance.');
                break;
            case 'attachment':
                $card['hp'] = min($card['max_hp'], $card['hp'] + 8);
                $card['passive_used'] = true;
                combat_log($state, $card['title'] . ' récupère 8 PV grâce à Attachement.');
                break;
            case 'adrenaline':
                $card['empowered'] = true;
                $card['passive_used'] = true;
                combat_log($state, $card['title'] . ' entre en Adrénaline.');
                break;
        }
    }
    if (!$card['passive_used'] && $beforeHp > $card['max_hp'] * .4 && $ratio <= .4 && $card['passive']['type'] === 'inner_strength') {
        $card['hp'] = min($card['max_hp'], $card['hp'] + 10);
        $card['passive_used'] = true;
        combat_log($state, $card['title'] . ' récupère 10 PV grâce à sa Force intérieure.');
    }
}

function combat_apply_action(array &$state, string $attackerSide, int $attackIndex, string $defenderSide): array
{
    return combat_apply_attack(
        $state,
        $attackerSide,
        $state[$attackerSide . '_active'],
        $attackIndex,
        $defenderSide,
        $state[$defenderSide . '_active']
    );
}

function combat_apply_attack(
    array &$state,
    string $attackerSide,
    int $attackerIndex,
    int $attackIndex,
    string $defenderSide,
    int $defenderIndex
): array {
    if (!isset($state[$attackerSide]['team'][$attackerIndex], $state[$defenderSide]['team'][$defenderIndex])) {
        return ['success' => false, 'message' => 'Carte ou cible invalide.'];
    }
    if ($state[$attackerSide]['team'][$attackerIndex]['defeated'] || $state[$defenderSide]['team'][$defenderIndex]['defeated']) {
        return ['success' => false, 'message' => 'Cette carte ne peut plus combattre.'];
    }
    $state[$attackerSide . '_active'] = $attackerIndex;
    $state[$defenderSide . '_active'] = $defenderIndex;
    $attacker = &$state[$attackerSide]['team'][$attackerIndex];
    $attack = $attacker['attacks'][$attackIndex] ?? null;
    if (!$attack) {
        return ['success' => false, 'message' => 'Attaque invalide.'];
    }
    $cost = (int) $attack['cost'];
    if (in_array($attacker['passive']['type'], ['magic', 'optimization'], true) && !$attacker['passive_used'] && $cost > 1 && $attackIndex > 0) {
        $cost = max(1, $cost - 1);
        $attacker['passive_used'] = true;
    }
    if ($state[$attackerSide]['energy'] < $cost) {
        return ['success' => false, 'message' => 'Énergie insuffisante.'];
    }
    $state[$attackerSide]['energy'] -= $cost;
    $beforeHp = $state[$defenderSide]['team'][$defenderIndex]['hp'];
    $damage = (int) ($attack['damage'] ?? 0) + $attacker['atk'] + $attacker['attack_boost'] - $attacker['weakened'];
    if ($attacker['empowered']) {
        $damage += 4;
        $attacker['empowered'] = false;
    }
    if ($attacker['passive']['type'] === 'tension' && $beforeHp < $state[$defenderSide]['team'][$defenderIndex]['max_hp'] * .5) {
        $damage += 4;
    }
    if ($attacker['passive']['type'] === 'scouting') {
        $attack['ignore_def'] = (int) ($attack['ignore_def'] ?? 0) + 2;
    }
    if ($attacker['passive']['type'] === 'sniper' && !$attacker['passive_used']) {
        $attack['ignore_def'] = (int) ($attack['ignore_def'] ?? 0) + 4;
        $attacker['passive_used'] = true;
    }
    if ($attacker['passive']['type'] === 'fear' && !$attacker['passive_used']) {
        $defenderCard = &$state[$defenderSide]['team'][$defenderIndex];
        $defenderCard['def_penalty'] = min(2, $defenderCard['def_penalty'] + 2);
        $attacker['passive_used'] = true;
        unset($defenderCard);
    }
    $advantage = combat_genre_advantage($attacker['genres'], $state[$defenderSide]['team'][$defenderIndex]['genres']);
    $damage = (int) round($damage * (1 + ($advantage / 100)));
    $actualDamage = combat_receive_damage($state, $defenderSide, $damage, (int) ($attack['ignore_def'] ?? 0), $cost > 1);
    $defender = &$state[$defenderSide]['team'][$defenderIndex];
    if (!empty($attack['weaken'])) {
        $defender['weakened'] = (int) $attack['weaken'];
    }
    if (!empty($attack['shield'])) {
        $attacker['shield'] += (int) $attack['shield'];
    }
    if (!empty($attack['defense'])) {
        $attacker['def_bonus'] += (int) $attack['defense'];
    }
    if (!empty($attack['heal'])) {
        $attacker['hp'] = min($attacker['max_hp'], $attacker['hp'] + (int) $attack['heal']);
    }
    if (!empty($attack['poison']) && !$defender['defeated']) {
        $defender['poison'] = (int) $attack['poison'];
        $defender['poison_turns'] = (int) $attack['poison_turns'];
    }
    combat_check_passive($state, $defenderSide, $beforeHp);
    if (!$defender['defeated'] && $defender['passive']['type'] === 'resilience' && $actualDamage >= 20) {
        $defender['def_bonus'] += 2;
    }
    $advantageMessage = $advantage > 0 ? ' (faiblesse de la cible : +25 %)' : ($advantage < 0 ? ' (résistance de la cible : -25 %)' : '');
    combat_log($state, $attacker['title'] . ' utilise ' . $attack['name'] . ' et inflige ' . $actualDamage . ' dégâts' . $advantageMessage . '.');
    if ($defender['defeated']) {
        combat_log($state, $defender['title'] . ' est vaincu.');
        foreach ($state[$defenderSide]['team'] as $index => &$ally) {
            if ($ally['passive']['type'] === 'solidarity' && !$ally['passive_used'] && $index !== $state[$defenderSide . '_active'] && !$ally['defeated']) {
                $ally['hp'] = min($ally['max_hp'], $ally['hp'] + 8);
                $ally['passive_used'] = true;
            }
        }
        unset($ally);
        combat_select_next_active($state, $defenderSide);
    }
    return ['success' => true, 'message' => $actualDamage . ' dégâts infligés.', 'advantage' => $advantage];
}

function combat_apply_effect(array &$state, string $side, string $defenderSide, ?int $targetIndex = null, ?int $sourceIndex = null): array
{
    $sourceIndex = $sourceIndex ?? $state[$side . '_active'];
    if (!isset($state[$side]['team'][$sourceIndex]) || $state[$side]['team'][$sourceIndex]['defeated']) {
        return ['success' => false, 'message' => 'Carte active invalide.'];
    }
    $state[$side . '_active'] = $sourceIndex;
    $card = &$state[$side]['team'][$sourceIndex];
    $effect = $card['effect'];
    $cost = (int) $effect['cost'];
    if (in_array($card['passive']['type'], ['magic'], true) && !$card['passive_used']) {
        $cost = max(1, $cost - 1);
        $card['passive_used'] = true;
    }
    if ($state[$side]['energy'] < $cost) {
        return ['success' => false, 'message' => 'Énergie insuffisante.'];
    }
    $state[$side]['energy'] -= $cost;
    $targetIndex = $targetIndex ?? $state[$defenderSide . '_active'];
    if (!isset($state[$defenderSide]['team'][$targetIndex]) || $state[$defenderSide]['team'][$targetIndex]['defeated']) {
        return ['success' => false, 'message' => 'Cible invalide.'];
    }
    $target = &$state[$defenderSide]['team'][$targetIndex];
    switch ($effect['type']) {
        case 'heal':
            $card['hp'] = min($card['max_hp'], $card['hp'] + (int) $effect['amount']);
            break;
        case 'shield':
            $card['shield'] += (int) $effect['amount'];
            break;
        case 'boost':
            $card['attack_boost'] = 3;
            break;
        case 'empower':
            $card['empowered'] = true;
            break;
        case 'precision':
            $card['empowered'] = true;
            break;
        case 'weaken':
            $target['weakened'] = (int) $effect['amount'];
            break;
        case 'poison':
            $target['poison'] = (int) $effect['amount'];
            $target['poison_turns'] = (int) $effect['turns'];
            break;
    }
    combat_log($state, $card['title'] . ' active ' . $effect['name'] . '.');
    return ['success' => true, 'message' => $effect['name'] . ' activé.'];
}

function combat_apply_focus(array &$state, string $side): array
{
    if ($state[$side]['focus_used']) {
        return ['success' => false, 'message' => 'La concentration a déjà été utilisée ce tour.'];
    }
    $before = $state[$side]['energy'];
    $state[$side]['energy'] = min(COMBAT_MAX_ENERGY, $before + COMBAT_FOCUS_GAIN);
    $state[$side]['focus_used'] = true;
    $gained = $state[$side]['energy'] - $before;
    combat_log($state, ($side === 'player' ? 'Vous' : 'Le système') . ' récupère ' . $gained . ' énergie grâce à la concentration.');
    return ['success' => true, 'message' => '+' . $gained . ' énergie.'];
}

function combat_status(array &$state): string
{
    if (combat_alive_indices($state['player']['team']) === []) {
        return 'defeat';
    }
    if (combat_alive_indices($state['enemy']['team']) === []) {
        return 'victory';
    }
    return 'playing';
}

function combat_ai_turn(array &$state): void
{
    combat_begin_turn($state, 'enemy');
    $actions = 0;
    while (combat_status($state) === 'playing' && $state['enemy']['energy'] > 0 && $actions < 8) {
        $enemy = &$state['enemy']['team'][$state['enemy_active']];
        $chosen = 0;
        foreach ($enemy['attacks'] as $index => $attack) {
            if ((int) $attack['cost'] <= $state['enemy']['energy']) {
                $chosen = $index;
            }
        }
        $result = combat_apply_attack($state, 'enemy', $state['enemy_active'], $chosen, 'player', $state['player_active']);
        if (!$result['success']) {
            break;
        }
        $actions++;
    }
    if (combat_status($state) === 'playing') {
        combat_begin_turn($state, 'player');
    }
    $state['turn'] = 'player';
    $state['round']++;
    if (combat_status($state) === 'playing') {
        combat_log($state, 'À vous de jouer. Votre énergie sera récupérée au début du prochain tour.');
    }
}

function combat_public_state(array $state): array
{
    $publicState = $state;
    foreach (['player', 'enemy'] as $side) {
        foreach ($publicState[$side]['team'] as &$card) {
            unset($card['passive_used'], $card['poison'], $card['poison_turns'], $card['attack_boost'], $card['weakened'], $card['empowered'], $card['def_penalty']);
        }
        unset($card);
    }
    return $publicState;
}
