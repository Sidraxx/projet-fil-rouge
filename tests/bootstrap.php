<?php

declare(strict_types=1);

// Bootstrap de la suite de tests : charge la configuration applicative
// puis prépare la base de données de test avant l'exécution des suites.

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/Support/TestDatabase.php';

TestDatabase::ensureSchema();
