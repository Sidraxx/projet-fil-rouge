# Absolute Cinema

Application web de cartes à collectionner autour du cinéma. Les joueurs créent un compte, ouvrent des packs, complètent leur collection et utilisent leurs cartes dans des combats simples.

Le projet est réalisé par Hugo, Antonio et Siloe dans le cadre de leur formation.

## État actuel

L'authentification, la base initiale de 1 000 films, le nettoyage des données, le calcul des raretés et le combat contre le système sont en place.

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
7. Vérifier que `validation_donnees` et `validation_rarete` valent `OK`.
8. Ouvrir `http://localhost/projet-fil-rouge/auth.php`.

Le projet utilise la base MySQL `cinema`. Avec la configuration WAMP par défaut, l'utilisateur est `root` sans mot de passe. Ces valeurs sont définies dans `config.php` pour le développement local.

Le formulaire d'inscription crée un utilisateur avec un mot de passe haché. La connexion ouvre ensuite une session PHP.

## Combat

La page `pages/combat.php` permet de sélectionner quatre cartes possédées et de lancer un combat contre une équipe générée par le système. L'état du combat est conservé dans la session PHP pendant la partie.

Le moteur `includes/combat.php` applique les règles de `regle.txt` : énergie commune de 5 points au départ, récupération passive de 3 points au début de chaque tour avec un maximum de 12, concentration (+2 énergie une fois par tour), attaques de toutes les cartes vivantes, sélection explicite des cibles, profils des 16 genres avec résistances et faiblesses, bonus de genre de +/- 25 %, passifs, soins plafonnés, poison, boucliers, affaiblissements et victoire après élimination des quatre cartes adverses.

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
|   |-- combat.php
|-- pages/
|-- combat.php
|-- auth.php
|-- config.php
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
