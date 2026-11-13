<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/escaping.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const APP_MAX_BODY_BYTES = 16384;

/** Corps brut d'une requête JSON, ou null s'il dépasse APP_MAX_BODY_BYTES. */
function app_read_body(): ?string
{
    $body = file_get_contents('php://input', false, null, 0, APP_MAX_BODY_BYTES + 1);

    if ($body === false) {
        return '';
    }

    return strlen($body) > APP_MAX_BODY_BYTES ? null : $body;
}

function movie_poster_url(?string $url): string
{
    $source = trim((string) $url);

    if (!preg_match('#^https?://#i', $source)) {
        return '';
    }

    $highResolution = preg_replace(
        '/\._V1_[^?#]*\.(?:jpe?g|webp)(?=([?#]|$))/i',
        '._V1_FMjpg_UX600_.jpg',
        $source
    );

    return $highResolution ?: $source;
}

function display_text(mixed $value, string $fallback = '—'): string
{
    $text = trim((string) $value);

    return $text === '' ? $fallback : $text;
}

function movie_meta(mixed $genre, mixed $year): string
{
    $parts = array_values(array_filter(
        [trim((string) $genre), trim((string) $year)],
        static fn (string $part): bool => $part !== ''
    ));

    return $parts ? implode(' · ', $parts) : '—';
}

function ensure_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['csrf_token'];
}

function require_login(string $root = ''): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . $root . 'auth.php');
        exit;
    }

    ensure_csrf_token();
}

function current_user(string $root = ''): array
{
    $statement = database()->prepare('SELECT id, username, email, created_at FROM users WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $_SESSION['user_id']]);
    $user = $statement->fetch();

    if (!$user) {
        $_SESSION = [];
        session_destroy();
        header('Location: ' . $root . 'auth.php');
        exit;
    }

    return $user;
}

function french_date(string $datetime): string
{
    $months = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    $timestamp = strtotime($datetime);

    return date('j', $timestamp) . ' ' . $months[(int) date('n', $timestamp) - 1] . ' ' . date('Y', $timestamp);
}
