# Projet fil rouge - Jeu de cartes de films

## Présentation

Ce projet consiste à créer une application web de cartes à collectionner consacrée au cinéma. Chaque film devient une carte possédant une rareté, des informations culturelles et des caractéristiques utilisables dans un combat simplifié.

Dans le scénario pédagogique, le projet répond à une demande de l'Association Française du Festival International du Film. L'objectif est de rendre la découverte du cinéma plus attractive pour les jeunes grâce à la collection et au jeu.

## Équipe

- Hugo
- Antonio
- Siloe

## Stack technique

- HTML
- CSS
- JavaScript sans framework
- PHP sans framework
- MySQL
- WAMP pour l'environnement local
- PHP 8.2.29 et MySQL 8.4.7 comme versions de référence
- Git et GitHub pour le travail collaboratif

## Parcours principal du MVP

1. Créer un compte et se connecter.
2. Ouvrir un pack de cartes de films.
3. Retrouver les cartes obtenues dans sa collection.
4. Consulter les informations culturelles d'un film.
5. Construire un deck valide.
6. Terminer un combat simplifié.

Les échanges, les enchères, la marketplace, les guildes, les amis et l'abonnement sont prévus après le MVP.

## Architecture

```text
projet-fil-rouge/
|-- public/
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

Le rôle précis de chaque dossier est détaillé dans [l'architecture technique](docs/architecture/technical-architecture.md).

## Documentation

- [Vision et besoin](docs/product/vision-et-besoin.md)
- [Périmètre du MVP](docs/product/perimetre-mvp.md)
- [User stories](docs/product/user-stories.md)
- [Product Backlog](docs/product/product-backlog.md)
- [Organisation Scrum](docs/project-management/organisation-scrum.md)
- [Roadmap et sprints](docs/project-management/roadmap-sprints.md)
- [Definition of Ready et Definition of Done](docs/project-management/definition-ready-done.md)
- [Responsabilités](docs/project-management/responsabilites.md)
- [Registre des risques](docs/project-management/risques.md)
- [Démarrer et terminer une tâche](docs/project-management/demarrer-une-tache.md)
- [Glossaire](docs/project-management/glossaire.md)
- [Architecture technique](docs/architecture/technical-architecture.md)
- [Modèle de données](docs/database/data-model.md)
- [Installation MySQL avec WAMP](docs/database/mysql-wamp.md)
- [Décisions techniques](docs/decisions/technical-decisions.md)
- [Règles de contribution](CONTRIBUTING.md)

## Installation locale

1. Installer et démarrer WAMP.
2. Cloner le dépôt localement.
3. Configurer un VirtualHost WAMP dont le `DocumentRoot` pointe uniquement vers le dossier `public/`.
4. Vérifier que les services Apache et MySQL sont actifs.
5. Créer une base MySQL nommée `movie_cards` avec l'encodage `utf8mb4`.
6. Lorsque les fichiers seront disponibles, importer `database/schema.sql` puis `database/seed.sql`.
7. Créer la configuration locale de connexion sans la versionner.

La procédure détaillée se trouve dans [la documentation MySQL et WAMP](docs/database/mysql-wamp.md).

## Organisation Git

- `main` contient les versions stables et démontrables.
- `develop` contient la prochaine version en cours d'intégration.
- chaque tâche est développée sur une branche courte créée depuis `develop` ;
- chaque branche est liée à une Issue GitHub ;
- chaque intégration passe par une Pull Request relue par un autre membre.

Les conventions complètes sont décrites dans [CONTRIBUTING.md](CONTRIBUTING.md).

## Jalons

| Date | Résultat attendu |
|---|---|
| 9 octobre 2026 | Cadrage, organisation, architecture et premières maquettes |
| 6 novembre 2026 | Comptes, films, ouverture de pack et collection fonctionnels |
| 4 décembre 2026 | MVP complet en finalisation |
| 11 décembre 2026 | Soutenance et démonstration |

## État actuel

La fondation du projet est en cours de validation. Le développement fonctionnel n'a pas encore commencé.
