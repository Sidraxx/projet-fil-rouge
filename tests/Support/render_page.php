<?php

declare(strict_types=1);

/**
 * Rend une page PHP de l'application hors contexte HTTP, pour les tests
 * fonctionnels : la session est simulée puis la page est incluse dans un
 * processus isolé, ce qui évite les redéclarations de fonctions entre pages.
 *
 * Usage : php tests/Support/render_page.php <page> <user_id> [query_json]
 * Exemple : php tests/Support/render_page.php catalog 1 '{"q":"<script>"}'
 *
 * Le processus hérite des variables DB_* fournies par le test : la base
 * utilisée est `cinema_test`, jamais la base applicative.
 */

$page = (string) ($argv[1] ?? '');
$userId = (int) ($argv[2] ?? 0);
$query = json_decode((string) ($argv[3] ?? '{}'), true);
$query = is_array($query) ? $query : [];

if (preg_match('/^[a-z0-9-]+$/', $page) !== 1) {
    fwrite(STDERR, 'Nom de page invalide.' . PHP_EOL);
    exit(2);
}

require_once __DIR__ . '/../../includes/layout.php';

// Session simulée : layout.php a démarré la session, on renseigne donc les
// valeurs juste après. Le jeton CSRF est factice mais bien formé.
$_SESSION['user_id'] = $userId;
$_SESSION['username'] = 'Joueur de test';
$_SESSION['csrf_token'] = str_repeat('a', 64);
$_GET = $query;

$pagesDirectory = realpath(__DIR__ . '/../../pages');
$pageFile = $pagesDirectory . '/' . $page . '.php';

if ($pagesDirectory === false || !is_file($pageFile)) {
    fwrite(STDERR, 'Page introuvable : ' . $page . PHP_EOL);
    exit(2);
}

chdir($pagesDirectory);
require $pageFile;
