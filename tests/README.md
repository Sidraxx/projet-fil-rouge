# Tests

La suite utilise [PHPUnit](https://phpunit.de) et une base MySQL dédiée, `cinema_test`, reconstruite automatiquement à chaque exécution. La base `cinema` de l'application n'est jamais modifiée.

## Installation (une seule fois)

Prérequis : PHP 8.2+ avec `pdo_mysql`, Composer, MySQL démarré sur `localhost:3306`.

### Sur Windows (WAMP)

```powershell
cd C:\wamp64\www\projet-fil-rouge
composer install
```

### Sur macOS

```bash
brew install php composer mysql
brew services start mysql
cd /chemin/vers/projet-fil-rouge
composer install
```

## Lancer la suite

### Sur Windows

```powershell
php vendor\phpunit\phpunit\phpunit                        # toutes les suites
php vendor\phpunit\phpunit\phpunit --testsuite unit
php vendor\phpunit\phpunit\phpunit --testsuite integration
php vendor\phpunit\phpunit\phpunit --testsuite functional
```

### Sur macOS

```bash
vendor/bin/phpunit                                        # toutes les suites
vendor/bin/phpunit --testsuite unit
vendor/bin/phpunit --testsuite integration
vendor/bin/phpunit --testsuite functional
```

Une exécution réussie affiche `OK (N tests, M assertions)`.

## Suites

| Suite | Dossier | Contenu |
|-------|---------|---------|
| `unit` | `tests/Unit/` | environnement (version PHP, `pdo_mysql`, données de référence) |
| `integration` | `tests/Integration/` | logique avec MySQL : `DeckTest.php` (règles du deck, ajout/retrait, limites, plusieurs decks nommés, bascule et suppression) |
| `functional` | `tests/Functional/` | rendu réel des pages : `DisplayEscapingTest.php` vérifie, avec des caractères spéciaux, que les données dynamiques (recherche, nom de deck) sont échappées |

## Base de données de test

- Le bootstrap (`tests/bootstrap.php`) **recrée `cinema_test` à chaque exécution** : import de `cinema_mysql.sql`, puis `clean_movies.sql`, `rarity.sql`, `user_cards.sql`, `user_pack_state.sql` et `user_decks.sql`, dans cet ordre (liste dans `tests/Support/TestDatabase.php`).
- Chaque test d'intégration hérite de `tests/Support/IntegrationTestCase.php`, qui vide les tables utilisateurs avant chaque test (isolation).
- Les scripts SQL du projet ciblent `USE cinema;` en dur : `TestDatabase.php` réécrit cette référence vers `cinema_test` à l'import.

## Configuration

Les identifiants sont déclarés dans `phpunit.xml` (variables `TEST_DB_*`, valeurs WAMP par défaut : `root` sans mot de passe). Pour les modifier sans toucher au fichier :

- **macOS / Linux** : `TEST_DB_PORT=8889 TEST_DB_USER=moi TEST_DB_PASSWORD='secret' vendor/bin/phpunit`
- **Windows (PowerShell)** : `$env:TEST_DB_USER = "moi"; $env:TEST_DB_PASSWORD = "secret"; php vendor\phpunit\phpunit\phpunit`
- **Windows (CMD)** : `set TEST_DB_USER=moi` puis `set TEST_DB_PASSWORD=secret` sur deux lignes, puis `php vendor\phpunit\phpunit\phpunit`

Pour réutiliser une base de test déjà prête (débogage manuel, sans reconstruction) :

- **macOS** : `TEST_DB_AUTO_CREATE=0 vendor/bin/phpunit`
- **Windows (PowerShell)** : `$env:TEST_DB_AUTO_CREATE = "0"; php vendor\phpunit\phpunit\phpunit`

## Organisation

```text
tests/
|-- bootstrap.php                # chargé par phpunit.xml avant tout test
|-- Support/
|   |-- TestDatabase.php         # création de cinema_test, helpers (createUser, resetUsers)
|   |-- IntegrationTestCase.php  # classe de base des tests avec MySQL
|   `-- render_page.php          # rendu d'une page dans un processus isolé (tests fonctionnels)
|-- Unit/                        # fonctions PHP, sans effet de bord applicatif
|-- Integration/                 # logique avec MySQL (packs, collections, decks)
`-- Functional/                  # rendu des pages et échappement des données affichées
```

## Dépannage

- `SQLSTATE[HY000] [2002]` : MySQL n'est pas démarré (WAMP ou `brew services start mysql`).
- `Access denied for user 'root'` : renseigner `TEST_DB_USER` et `TEST_DB_PASSWORD` (voir Configuration).
- `Script SQL introuvable` : vérifier que le dépôt est complet (le dossier `database/` doit contenir les scripts listés plus haut).
