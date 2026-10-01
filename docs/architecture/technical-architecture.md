# Architecture technique

## Objectif

Le projet est une application web de collection et de combat de cartes basées sur des films. L'architecture reste volontairement simple afin d'utiliser uniquement les technologies maîtrisées par l'équipe : HTML, CSS, JavaScript, PHP et MySQL.

L'application fonctionne comme un monolithe PHP : les pages, la logique métier et l'accès à la base de données appartiennent au même projet. Il n'y a ni framework, ni API séparée, ni système de microservices.

## Vue générale

```text
Navigateur
    |
    | Requête HTTP
    v
Apache sous WAMP
    |
    v
Fichier PHP dans public/
    |
    +--> composant d'affichage dans src/components/
    +--> fonction ou module métier dans src/
    +--> requête préparée avec PDO
              |
              v
          Base MySQL
```

## Arborescence cible

```text
projet-fil-rouge/
|-- public/
|   |-- index.php
|   |-- pages/
|   `-- assets/
|       |-- css/
|       |-- js/
|       `-- images/
|-- src/
|   |-- config/
|   |-- database/
|   |-- functions/
|   |-- components/
|   `-- modules/
|       |-- auth/
|       |-- movies/
|       |-- packs/
|       |-- collection/
|       `-- battles/
|-- database/
|   |-- schema.sql
|   |-- seed.sql
|   `-- migrations/
|-- data/
|   |-- raw/
|   |-- processed/
|   `-- samples/
|-- scripts/
|-- tests/
|-- docs/
`-- .github/
```

## Rôle des dossiers

### `public/`

Contient uniquement les fichiers accessibles depuis le navigateur.

- `index.php` : point d'entrée principal.
- `pages/` : pages de l'application, par exemple la collection ou l'ouverture d'un pack.
- `assets/css/` : feuilles de style.
- `assets/js/` : scripts exécutés dans le navigateur.
- `assets/images/` : images propres au projet.

Les mots de passe, les paramètres de connexion et les requêtes SQL internes ne doivent jamais être placés dans ce dossier.

### `src/`

Contient le code PHP interne qui ne doit pas être appelé directement par le navigateur.

- `config/` : configuration générale et connexion locale à la base.
- `database/` : fonctions PHP qui exécutent les requêtes SQL.
- `functions/` : fonctions réutilisables dans plusieurs fonctionnalités.
- `components/` : éléments d'interface réutilisables comme l'en-tête, la navigation, le pied de page ou l'affichage d'une carte.
- `modules/` : logique organisée par fonctionnalité métier.

Un module peut contenir plusieurs fichiers PHP simples. Il n'est pas nécessaire de créer des classes si des fonctions bien nommées suffisent.

### `database/`

Contient les fichiers servant à créer et faire évoluer la base MySQL.

- `schema.sql` : structure complète des tables pour installer une base vide.
- `seed.sql` : petit jeu de données initial pour le développement et la démonstration.
- `migrations/` : modifications SQL successives lorsque la structure évolue.

### `data/`

Contient les données sur les films avant leur insertion dans MySQL.

- `raw/` : données originales non modifiées.
- `processed/` : données nettoyées et enrichies, notamment avec le score de rareté.
- `samples/` : petits échantillons utilisés pour tester rapidement un import.

Le passage normal est : `raw` vers `processed`, puis import dans MySQL. Les échantillons servent aux essais et aux tests.

### `scripts/`

Contient les scripts ponctuels d'import, de nettoyage ou de calcul. Ils ne correspondent pas à des pages de l'application.

### `tests/`

Contient les scénarios de test du projet. Au départ, une checklist de tests manuels suffit. Des tests automatisés peuvent être ajoutés lorsque le code métier existe.

### `docs/`

Contient les documents produit, l'architecture, le modèle de données, les décisions techniques et la gestion du projet.

### `.github/`

Contient les modèles d'Issues, le modèle de Pull Request et les vérifications automatiques exécutées sur GitHub.

## Flux d'une fonctionnalité

Exemple pour l'ouverture d'un pack :

1. L'utilisateur ouvre une page dans `public/pages/`.
2. La page vérifie que l'utilisateur est connecté.
3. Le module `src/modules/packs/` applique les règles de tirage.
4. Les fonctions de `src/database/` lisent les cartes disponibles et enregistrent le résultat.
5. La page affiche les cartes tirées à l'aide des composants communs.
6. La collection de l'utilisateur est mise à jour dans la même transaction MySQL.

## Règles techniques

- Utiliser `PDO` pour toutes les connexions MySQL.
- Utiliser des requêtes préparées pour toutes les valeurs provenant d'un utilisateur.
- Ne jamais enregistrer un mot de passe en clair ; utiliser `password_hash()` et `password_verify()`.
- Échapper les valeurs affichées dans le HTML avec `htmlspecialchars()`.
- Conserver les paramètres locaux de connexion hors de Git.
- Placer le moins de logique possible directement dans les pages publiques.
- Nommer les fichiers et les variables en anglais de manière cohérente.
- Une fonctionnalité ne doit accéder aux données qu'à travers les fonctions prévues dans `src/database/`.

## Installation locale sous WAMP

Le dépôt peut être placé dans le dossier `www` de WAMP, mais un VirtualHost doit obligatoirement utiliser `public/` comme `DocumentRoot`. Les dossiers internes ne doivent pas être exposés par Apache. PHP se connecte à MySQL sur la machine locale.

La procédure détaillée se trouve dans `docs/database/mysql-wamp.md`.
