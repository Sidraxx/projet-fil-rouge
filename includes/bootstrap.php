<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
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
