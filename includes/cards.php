<?php

declare(strict_types=1);

function rarity_catalog(): array
{
    return [
        'Commune' => ['key' => 'commune', 'color' => '#6B7280', 'weight' => 55],
        'Peu commune' => ['key' => 'peu-commune', 'color' => '#16A34A', 'weight' => 27],
        'Rare' => ['key' => 'rare', 'color' => '#2563EB', 'weight' => 12],
        'Épique' => ['key' => 'epique', 'color' => '#7C3AED', 'weight' => 5],
        'Légendaire' => ['key' => 'legendaire', 'color' => '#D97706', 'weight' => 1],
    ];
}

function roll_rarity_level(): string
{
    $roll = random_int(1, 100);
    $cursor = 0;

    foreach (rarity_catalog() as $level => $meta) {
        $cursor += $meta['weight'];
        if ($roll <= $cursor) {
            return $level;
        }
    }

    return 'Commune';
}

function primary_genre(?string $genre): string
{
    $genre = trim((string) $genre);
    if ($genre === '') {
        return '';
    }

    $parts = preg_split('/\s*,\s*/', $genre) ?: [];

    return (string) ($parts[0] ?? '');
}

function format_imdb(mixed $rating): string
{
    if ($rating === null || $rating === '') {
        return '';
    }

    return number_format((float) $rating, 1, ',', '');
}

function draw_pack_cards(PDO $db, int $count = 5): array
{
    $available = (int) $db->query('SELECT COUNT(*) FROM movies WHERE rarity_level IS NOT NULL')->fetchColumn();
    if ($available === 0) {
        throw new RuntimeException('Aucune rareté calculée.');
    }

    $statement = $db->prepare(
        'SELECT id, poster_link, title, released_year, genre, imdb_rating, rarity_level
         FROM movies
         WHERE rarity_level = :level
         ORDER BY RAND()
         LIMIT 1'
    );
    $catalog = rarity_catalog();
    $cards = [];

    for ($slot = 0; $slot < $count; $slot++) {
        $level = roll_rarity_level();
        $statement->execute(['level' => $level]);
        $movie = $statement->fetch();

        if (!$movie) {
            throw new RuntimeException('Tirage impossible pour ' . $level . '.');
        }

        $poster = (string) ($movie['poster_link'] ?? '');
        if (!preg_match('#^https?://#i', $poster)) {
            $poster = '';
        }

        $cards[] = [
            'id' => (int) $movie['id'],
            'title' => (string) $movie['title'],
            'year' => $movie['released_year'] !== null ? (int) $movie['released_year'] : null,
            'genre' => primary_genre($movie['genre'] ?? null),
            'imdb' => format_imdb($movie['imdb_rating']),
            'poster' => $poster,
            'rarity' => $level,
            'rarityKey' => $catalog[$level]['key'],
            'color' => $catalog[$level]['color'],
            'slot' => $slot,
        ];
    }

    $order = array_flip(array_keys($catalog));
    usort($cards, static function (array $left, array $right) use ($order): int {
        return [$order[$left['rarity']], $left['slot']] <=> [$order[$right['rarity']], $right['slot']];
    });

    foreach ($cards as &$card) {
        unset($card['slot']);
    }
    unset($card);

    return $cards;
}
