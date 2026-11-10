<?php

declare(strict_types=1);

/**
 * Limitation de débit simple, sans dépendance : un fichier JSON par
 * (compartiment, clé) dans storage/ratelimit/. La clé est hachée : aucun
 * e-mail n'est stocké en clair.
 *
 * Si le stockage est inaccessible, la limite est ignorée et un avertissement
 * est journalisé, pour ne pas bloquer le site entier.
 */

const RATE_LIMIT_DIRECTORY = __DIR__ . '/../storage/ratelimit';

function client_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? 'inconnue');
}

function rate_limit_file(string $bucket, string $key): ?string
{
    if (!is_dir(RATE_LIMIT_DIRECTORY) && !@mkdir(RATE_LIMIT_DIRECTORY, 0700, true) && !is_dir(RATE_LIMIT_DIRECTORY)) {
        return null;
    }

    return RATE_LIMIT_DIRECTORY . '/' . hash('sha256', $bucket . '|' . strtolower($key)) . '.json';
}

/**
 * Lit puis, si $change renvoie un état, l'écrit, le tout verrouillé.
 *
 * @param callable(?array): ?array $change
 * @return ?array État après modification (ou état lu si rien n'a été écrit).
 */
function rate_limit_update(string $bucket, string $key, callable $change): ?array
{
    $path = rate_limit_file($bucket, $key);
    $handle = $path !== null ? @fopen($path, 'c+') : false;

    if ($handle === false) {
        app_log('warning', 'Limitation de débit ignorée : stockage inaccessible.', ['compartiment' => $bucket]);

        return null;
    }

    flock($handle, LOCK_EX);
    $raw = stream_get_contents($handle);
    $state = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
    $state = is_array($state) ? $state : null;

    $next = $change($state);
    if ($next !== null) {
        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, (string) json_encode($next));
        fflush($handle);
        $state = $next;
    }

    flock($handle, LOCK_UN);
    fclose($handle);

    return $state;
}

/** Secondes restantes avant d'être autorisé à réessayer (0 = autorisé). */
function rate_limit_retry_after(string $bucket, string $key, int $maxAttempts, int $windowSeconds): int
{
    $state = rate_limit_update($bucket, $key, static fn (?array $current): ?array => null);
    if ($state === null) {
        return 0;
    }

    $now = time();
    $first = (int) ($state['first'] ?? 0);
    $count = (int) ($state['count'] ?? 0);

    if ($now - $first >= $windowSeconds || $count < $maxAttempts) {
        return 0;
    }

    return $first + $windowSeconds - $now;
}

/** Comptabilise une tentative. La fenêtre démarre à la première tentative. */
function rate_limit_hit(string $bucket, string $key, int $windowSeconds): void
{
    rate_limit_update($bucket, $key, static function (?array $state) use ($windowSeconds): array {
        $now = time();

        if ($state === null || $now - (int) ($state['first'] ?? 0) >= $windowSeconds) {
            return ['count' => 1, 'first' => $now];
        }

        return ['count' => (int) $state['count'] + 1, 'first' => (int) $state['first']];
    });
}

/**
 * Comptabilise une requête d'API. Renvoie les secondes à attendre si la limite
 * est atteinte (0 = requête autorisée et comptée).
 */
function rate_limit_api(string $bucket, string $key, int $maxRequests, int $windowSeconds = 60): int
{
    $retry = rate_limit_retry_after($bucket, $key, $maxRequests, $windowSeconds);
    if ($retry > 0) {
        return $retry;
    }

    rate_limit_hit($bucket, $key, $windowSeconds);

    return 0;
}

/** Efface le compteur (après une connexion réussie, par exemple). */
function rate_limit_clear(string $bucket, string $key): void
{
    $path = rate_limit_file($bucket, $key);

    if ($path !== null && is_file($path)) {
        @unlink($path);
    }
}
