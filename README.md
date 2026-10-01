# Projet fil rouge - Jeu de cartes de films

Application web de cartes à collectionner autour du cinéma. Les joueurs ouvrent des packs, complètent leur collection et utilisent leurs cartes dans des combats simples.

Le projet est réalisé par Hugo, Antonio et Siloe dans le cadre de leur formation.

## Stack

- HTML
- CSS
- JavaScript
- PHP
- MySQL avec WAMP

Aucun framework n'est utilisé.

## Structure

```text
projet-fil-rouge/
|-- assets/
|   |-- css/
|   |-- js/
|   `-- images/
|-- includes/
|-- pages/
|-- database/
|-- data/
|-- README.md
`-- DOCUMENTATION.md
```

- `assets` : styles, scripts et images.
- `includes` : connexion MySQL et éléments PHP réutilisés.
- `pages` : pages du site.
- `database` : fichiers SQL.
- `data` : données sources des films.

La page d'accueil `index.php` sera placée à la racine du projet.

## Démarrage

1. Placer le dossier dans `C:\wamp64\www` ou créer un VirtualHost WAMP.
2. Démarrer Apache et MySQL.
3. Créer la base de données du projet dans phpMyAdmin.
4. Ouvrir le projet depuis `localhost`.

La vision, le MVP, les user stories et les sprints sont regroupés dans [DOCUMENTATION.md](DOCUMENTATION.md).

## Git

- `main` : version stable.
- `develop` : version en cours.
- une branche par fonctionnalité, par exemple `feature/pack-opening`.
- une Pull Request vers `develop` quand la fonctionnalité est terminée.
- une Pull Request de `develop` vers `main` quand une version complète est prête.
