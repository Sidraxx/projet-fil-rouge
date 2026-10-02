# Absolute Cinema

Page d'accès à l'espace joueur d'Absolute Cinema, le TCG de cartes de films, avec inscription et connexion reliées à MySQL.

## Lancer en local avec WAMP

1. Démarrer Apache et MySQL dans WAMP.
2. Importer `cinema_mysql.sql` dans phpMyAdmin. Le fichier reste volontairement local et n'est pas suivi par Git.
3. Vérifier que la base s'appelle `bdd_abs_cin` et que l'utilisateur MySQL `root` n'a pas de mot de passe dans `config.php`.
4. Ouvrir [http://localhost/nova_film/projet-fil-rouge/auth.php](http://localhost/nova_film/projet-fil-rouge/auth.php).

Le formulaire d'inscription crée un utilisateur dans la table `users` avec un mot de passe haché. La connexion ouvre une session PHP.
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
