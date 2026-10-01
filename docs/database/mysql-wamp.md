# MySQL avec WAMP

## Environnement retenu

Le projet utilise :

- Apache fourni par WAMP pour servir l'application ;
- PHP fourni par WAMP pour exécuter le code serveur ;
- MySQL fourni par WAMP pour stocker les données ;
- phpMyAdmin pour administrer la base pendant le développement ;
- PDO pour communiquer entre PHP et MySQL.

Versions de référence pour le projet :

- PHP 8.2.29 ;
- MySQL 8.4.7.

Chaque membre doit sélectionner ces versions dans WAMP. Toute différence doit être signalée avant de commencer une Issue de développement.

## Création de la base locale

1. Démarrer WAMP et vérifier que les services Apache et MySQL sont actifs.
2. Ouvrir phpMyAdmin.
3. Créer une base nommée `movie_cards` avec l'encodage `utf8mb4`.
4. Importer `database/schema.sql`.
5. Importer `database/seed.sql` si des données de démonstration sont nécessaires.

## VirtualHost obligatoire

Le dépôt peut être placé dans le dossier `www` de WAMP, mais Apache doit exposer uniquement le dossier `public/`. Il ne faut pas rendre `src/`, `database/`, `data/` ou `docs/` accessibles depuis le navigateur.

Chaque membre crée donc un VirtualHost WAMP dont le `DocumentRoot` pointe vers :

```text
chemin-vers-le-projet/projet-fil-rouge/public
```

Le nom local recommandé est `projet-fil-rouge.local`. La procédure exacte dépend de la version de WAMP et sera testée sur les trois postes pendant le Sprint 0.

Commande SQL équivalente :

```sql
CREATE DATABASE movie_cards
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
```

## Configuration locale

Chaque membre possède ses propres paramètres de connexion. Ils ne doivent pas être versionnés.

Fichier local prévu :

```text
src/config/database.local.php
```

Exemple de contenu :

```php
<?php

return [
    'host' => '127.0.0.1',
    'port' => 3306,
    'database' => 'movie_cards',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
];
```

Le mot de passe vide correspond à une configuration WAMP locale fréquente. Chaque membre doit utiliser la valeur réelle de son installation.

## Connexion avec PDO

La connexion doit être centralisée dans un seul fichier PHP. Le principe est le suivant :

```php
<?php

$config = require __DIR__ . '/../config/database.local.php';

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $config['host'],
    $config['port'],
    $config['database'],
    $config['charset']
);

$pdo = new PDO($dsn, $config['username'], $config['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
```

Ce code sert de référence. Son emplacement définitif sera créé avec le premier développement de la base.

## Requêtes préparées

Toute donnée fournie par un utilisateur doit être envoyée à MySQL avec une requête préparée.

```php
$statement = $pdo->prepare(
    'SELECT id, title FROM movies WHERE title LIKE :title'
);
$statement->execute([
    'title' => '%' . $search . '%',
]);
```

Il ne faut jamais construire une requête SQL en concaténant directement une saisie utilisateur.

## Synchronisation entre les membres

La base MySQL locale elle-même n'est pas partagée dans Git. Seuls les fichiers nécessaires pour la reconstruire sont versionnés :

- `database/schema.sql` pour la structure complète ;
- `database/seed.sql` pour les données minimales ;
- `database/migrations/` pour les évolutions successives.

Lorsqu'une table change :

1. créer un fichier de migration numéroté ;
2. mettre à jour `schema.sql` pour les nouvelles installations ;
3. tester la migration sur une copie locale ;
4. expliquer le changement dans la Pull Request.

Exemple :

```text
database/migrations/001_add_rarity_score_to_cards.sql
```

## Sauvegardes

Les exports personnels et sauvegardes de base ne doivent pas être ajoutés au dépôt. Le dossier `database/backups/` est ignoré par Git.

Avant une modification importante de la structure, chaque membre peut exporter sa base depuis phpMyAdmin. La source commune reste toutefois `schema.sql` et les migrations versionnées.
