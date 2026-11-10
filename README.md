# Absolute Cinema

Application web de cartes à collectionner autour du cinéma. Les joueurs créent un compte, ouvrent des packs, complètent leur collection et utilisent leurs cartes dans des combats simples.

Le projet est réalisé par Hugo, Antonio et Siloe dans le cadre de leur formation.

## État actuel

L'authentification, la base initiale de 1 000 films, le nettoyage des données, le calcul des raretés, l'ouverture de paquets, la collection et le constructeur de deck (plusieurs decks nommés par joueur, 10 cartes et 2 exemplaires maximum par carte, un seul deck actif au combat) sont en place. Les combats restent à développer.

## Stack

- HTML
- CSS
- JavaScript
- PHP
- MySQL (WAMP sous Windows, MySQL via Homebrew sous macOS)

Aucun framework n'est utilisé.

## Base de données SQL du projet

La base s'appelle `cinema` (déclarée dans `config.php`). Cinq scripts s'exécutent **dans cet ordre** après l'import du dump, tous idempotents (réexécutable sans erreur) :

| # | Script | Rôle |
|---|--------|------|
| 1 | `cinema_mysql.sql` | dump principal : 1 000 films |
| 2 | `database/clean_movies.sql` | nettoyage des données |
| 3 | `database/rarity.sql` | calcul des raretés |
| 4 | `database/user_cards.sql` | cartes obtenues par les joueurs |
| 5 | `database/user_pack_state.sql` | paquets disponibles par joueur |
| 6 | `database/user_decks.sql` | decks nommés par joueur et leurs cartes |

## Lancer le projet sous Windows (WAMP)

1. Placer le dépôt dans `C:\wamp64\www`.
2. Démarrer Apache et MySQL dans WAMP.
3. Ouvrir phpMyAdmin et importer `cinema_mysql.sql` (bouton *Import*).
4. Exécuter dans l'onglet *SQL* de la base `cinema`, un par un, les cinq scripts de la table ci-dessus, depuis `clean_movies.sql` jusqu'à `user_decks.sql`.
5. Vérifier que les colonnes `validation_donnees` (fin de `clean_movies.sql`) et `validation_rarete` (fin de `rarity.sql`) valent `OK` dans les résultats affichés.
6. Ouvrir `http://localhost/projet-fil-rouge/auth.php`.

Avec la configuration WAMP par défaut, l'utilisateur est `root` sans mot de passe. Ces valeurs sont définies dans `config.php` pour le développement local.

Pour afficher les détails techniques (console du navigateur, page d'erreur avec le fichier et la ligne), définir la variable d'environnement `APP_DEBUG=1` : décommenter `SetEnv APP_DEBUG 1` dans `.htaccess` ou la définir dans l'environnement de WAMP. Sans cette variable, les visiteurs ne voient que des messages génériques ; les logs PHP restent complets.

Sécurité du serveur (WAMP) : dans `httpd.conf`, ajouter `TraceEnable off` pour désactiver la méthode HTTP TRACE, puis redémarrer Apache. Le fichier `.htaccess` interdit l'accès web à `.git`, `vendor/`, `tests/`, `database/`, `storage/` et aux fichiers `*.sql`.

La connexion est limitée à 5 échecs par compte et 20 par adresse IP sur 15 minutes ; l'inscription à 5 tentatives par heure et par adresse IP. Les compteurs sont stockés dans `storage/ratelimit/` (ignoré par git).

## Lancer le projet sous macOS

```bash
# Prérequis (une seule fois)
brew install php mysql
brew services start mysql        # MySQL doit tourner sur localhost:3306

# Import depuis la racine du dépôt (le dump crée lui-même la base `cinema`)
mysql -u root < cinema_mysql.sql
mysql -u root cinema < database/clean_movies.sql
mysql -u root cinema < database/rarity.sql
mysql -u root cinema < database/user_cards.sql
mysql -u root cinema < database/user_pack_state.sql
mysql -u root cinema < database/user_decks.sql

# Serveur web de développement
php -S localhost:8000
```

Ouvrir ensuite `http://localhost:8000/auth.php`.

Le formulaire d'inscription crée un utilisateur avec un mot de passe haché. La connexion ouvre ensuite une session PHP. Pour repartir de zéro, supprimer la base (`mysql -u root -e "DROP DATABASE cinema"`) puis réexécuter ces commandes.

## Erreurs et diagnostics

L'application n'affiche jamais de trace brute : une erreur de base est traduite en
message lisible (bandeau dans la page) et détaillée à deux endroits :

- la **console du navigateur** (F12 → Console), sous la forme
  `Absolute Cinema — N message(s) applicatif(s)` : code MySQL (SQLSTATE), fichier
  et ligne concernés ;
- les **logs PHP** (`error_log`) : les mêmes informations côté serveur.

La traduction des erreurs est centralisée dans `includes/errors.php`
(`describe_database_error()`), utilisée par `config.php`, les pages et les API.

Points de vigilance après un partage de base :

- importer le dump **puis** rejouer les scripts de `database/` dans l'ordre du
  README : un dump ancien ne contient pas les tables `user_decks` / `deck_cards` ;
- se **déconnecter puis se reconnecter** après avoir importé la base d'un autre
  joueur : la session contient encore un `user_id` qui n'existe plus (l'appli
  redirige désormais vers la page de connexion au lieu de planter).

## Lancer les tests

Les tests utilisent PHPUnit et une base dédiée `cinema_test`, reconstruite automatiquement à chaque exécution : la base `cinema` de l'application n'est jamais touchée.

Prérequis communs : PHP 8.2 ou plus récent avec les extensions `pdo_mysql`, Composer, et un serveur MySQL démarré sur `localhost:3306`.

### Sur Windows (WAMP)

Dans un terminal ouvert à la racine du dépôt (`C:\wamp64\www\projet-fil-rouge`) :

```powershell
composer install                          # une seule fois ; Composer : https://getcomposer.org
php vendor\phpunit\phpunit\phpunit        # toutes les suites
php vendor\phpunit\phpunit\phpunit --testsuite unit
```

Si la base de test n'utilise pas `root` sans mot de passe (WAMP par défaut), définir les variables avant de lancer la suite :

```powershell
$env:TEST_DB_USER = "moi"; $env:TEST_DB_PASSWORD = "secret"; php vendor\phpunit\phpunit\phpunit
```

(Sous l'invite de commandes CMD : `set TEST_DB_USER=moi` puis `set TEST_DB_PASSWORD=secret` sur deux lignes.)

### Sur macOS

```bash
# Prérequis (une seule fois)
brew install php composer mysql
brew services start mysql              # MySQL doit tourner sur localhost:3306

# Dépendances des tests (une seule fois), depuis la racine du projet
composer install

# Exécution
vendor/bin/phpunit                     # toutes les suites
vendor/bin/phpunit --testsuite unit    # suite rapide de fumée
```

Si MySQL local utilise un autre port ou d'autres identifiants :

```bash
TEST_DB_PORT=8889 TEST_DB_USER=moi TEST_DB_PASSWORD='secret' vendor/bin/phpunit
```

### Résultat attendu

Une ligne `OK (N tests, M assertions)` en fin d'exécution. La suite reconstruit `cinema_test` à chaque lancement, sans toucher à `cinema`.

Les détails (overrides d'identifiants, organisation des suites) sont dans [tests/README.md](tests/README.md).

## Résultats attendus pour les données

- 1 000 films importés ;
- aucune anomalie bloquante après le nettoyage ;
- un score et une rareté pour chaque film ;
- 450 cartes communes ;
- 300 cartes peu communes ;
- 150 cartes rares ;
- 80 cartes épiques ;
- 20 cartes légendaires.

Le contrôle détaillé est disponible dans [data/DONNEES_FILMS.md](data/DONNEES_FILMS.md). L'échantillon reproductible de vingt films se trouve dans [data/echantillon_20_films.xlsx](data/echantillon_20_films.xlsx).

## Structure

```text
projet-fil-rouge/
|-- assets/
|   |-- css/
|   |-- js/
|   `-- images/
|-- data/
|-- database/
|-- documents/
|-- includes/
|-- pages/
|-- tests/
|-- auth.php
|-- deck-api.php
|-- open-pack.php
|-- composer.json
|-- config.php
|-- phpunit.xml
|-- cinema_mysql.sql
|-- README.md
`-- DOCUMENTATION.md
```

- `assets` contient les styles, les scripts JavaScript et les images ;
- `data` contient l'analyse et les échantillons de données ;
- `database` contient les scripts SQL de nettoyage et de calcul ;
- `documents` contient les livrables partageables ;
- `includes` contient les éléments PHP réutilisables ;
- `pages` contient les pages supplémentaires de l'application.

Le cahier des charges fonctionnel et technique est disponible dans [DOCUMENTATION.md](DOCUMENTATION.md).

## Git

- `main` contient la version stable ;
- `develop` contient la version intégrée en cours ;
- chaque fonctionnalité est développée sur une branche courte, par exemple `feature/movie-data-rarity` ;
- une Pull Request est ouverte vers `develop` lorsque la fonctionnalité est terminée et vérifiée ;
- `develop` est fusionnée dans `main` lorsqu'une version complète est prête.
