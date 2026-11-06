# Tests

La suite utilise [PHPUnit](https://phpunit.de) et une base MySQL dédiée, `cinema_test`, reconstruite automatiquement à chaque exécution.

## Installation (une seule fois)

Les tests tournent avec le même serveur MySQL que l'application (WAMP sous Windows, MySQL local sous macOS/Linux), mais **jamais sur la base `cinema` de l'application**.

```bash
composer install
```

## Lancer la suite

```bash
vendor/bin/phpunit                # toutes les suites
vendor/bin/phpunit --testsuite unit
vendor/bin/phpunit --testsuite integration
vendor/bin/phpunit --testsuite functional
```

## Base de données de test

- Le bootstrap (`tests/bootstrap.php`) **recrée `cinema_test` à chaque exécution** : import de `cinema_mysql.sql`, puis `clean_movies.sql`, `rarity.sql`, `user_cards.sql` et `user_pack_state.sql`.
- Chaque test d'intégration hérite de `tests/Support/IntegrationTestCase.php`, qui vide les tables utilisateurs avant chaque test (isolation).
- Les scripts SQL du projet ciblent `USE cinema;` en dur : le support `tests/Support/TestDatabase.php` réécrit cette référence vers `cinema_test` à l'import.

## Configuration

Les identifiants sont déclarés dans `phpunit.xml` (variables `TEST_DB_*`) et restent sur les valeurs WAMP (`root` sans mot de passe). Pour les surcharger sans modifier le fichier :

```bash
TEST_DB_HOST=localhost TEST_DB_USER=mon_user TEST_DB_PASSWORD='secret' vendor/bin/phpunit
```

Pour réutiliser une base de test déjà prête (débogage manuel) :

```bash
TEST_DB_AUTO_CREATE=0 vendor/bin/phpunit
```

## Organisation

```text
tests/
|-- bootstrap.php            # chargé par phpunit.xml avant tout test
|-- Support/
|   |-- TestDatabase.php     # création de cinema_test, helpers (createUser, resetUsers)
|   `-- IntegrationTestCase.php  # classe de base des tests avec MySQL
|-- Unit/                    # fonctions PHP, sans effet de bord applicatif
|-- Integration/             # logique avec MySQL (packs, tirage, collections)
`-- Functional/              # endpoints HTTP et parcours complet
```

Une base `cinema` locale **n'est jamais modifiée** par la suite.
