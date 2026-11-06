# Absolute Cinema

Application web de cartes à collectionner autour du cinéma. Les joueurs créent un compte, ouvrent des packs, complètent leur collection et utilisent leurs cartes dans des combats simples.

Le projet est réalisé par Hugo, Antonio et Siloe dans le cadre de leur formation.

## État actuel

L'authentification, la base initiale de 1 000 films, le nettoyage des données, le calcul des raretés, l'ouverture de paquets, la collection et le constructeur de deck (10 cartes, 2 exemplaires maximum par carte) sont en place. Les combats restent à développer.

## Stack

- HTML
- CSS
- JavaScript
- PHP
- MySQL avec WAMP

Aucun framework n'est utilisé.

## Lancer le projet avec WAMP

1. Placer le dépôt dans `C:\wamp64\www`.
2. Démarrer Apache et MySQL dans WAMP.
3. Importer `cinema_mysql.sql` dans phpMyAdmin.
4. Exécuter `database/clean_movies.sql`.
5. Exécuter `database/rarity.sql`.
6. Exécuter `database/user_pack_state.sql` (table des paquets disponibles par utilisateur).
7. Exécuter `database/user_decks.sql` (tables du deck actif par joueur et de ses cartes).
8. Vérifier que `validation_donnees` et `validation_rarete` valent `OK`.
9. Ouvrir `http://localhost/projet-fil-rouge/auth.php`.

Le projet utilise la base MySQL `cinema`. Avec la configuration WAMP par défaut, l'utilisateur est `root` sans mot de passe. Ces valeurs sont définies dans `config.php` pour le développement local.

Le formulaire d'inscription crée un utilisateur avec un mot de passe haché. La connexion ouvre ensuite une session PHP.

## Lancer les tests

Les tests utilisent PHPUnit et une base dédiée `cinema_test`, reconstruite automatiquement à chaque exécution : la base `cinema` de l'application n'est jamais touchée.

### Sur Windows (WAMP)

```bash
composer install       # une seule fois ; composer s'installe depuis https://getcomposer.org
vendor/bin/phpunit     # toutes les suites ; sous CMD/PowerShell : php vendor\phpunit\phpunit\phpunit
```

### Sur macOS

```bash
# Prérequis (une seule fois)
brew install php composer mysql
brew services start mysql      # MySQL doit tourner sur localhost:3306

# Dépendances des tests (une seule fois)
composer install

# Exécution, depuis la racine du projet
vendor/bin/phpunit             # toutes les suites
vendor/bin/phpunit --testsuite unit   # suite rapide de fumée
```

Si MySQL local utilise un autre port ou des identifiants différents de `root` sans mot de passe :

```bash
TEST_DB_PORT=8889 TEST_DB_USER=moi TEST_DB_PASSWORD='secret' vendor/bin/phpunit
```

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
