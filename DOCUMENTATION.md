# Documentation du projet

## Vision

Le projet est un jeu web de cartes à collectionner consacré au cinéma. Chaque carte représente un film provenant de notre base de données.

La rareté d'une carte sera calculée à partir d'informations comme les revenus du film et sa note. L'objectif est de rendre la découverte du cinéma plus ludique pour un public jeune.

Dans le scénario du projet, l'Association Française du Festival International du Film souhaite utiliser ce jeu pour donner envie aux jeunes de découvrir des films et de s'intéresser davantage au cinéma.

## MVP

La première version doit permettre de :

1. créer un compte et se connecter ;
2. ouvrir un pack de cartes ;
3. consulter sa collection ;
4. afficher la fiche d'un film ;
5. créer un deck ;
6. terminer un combat simple.

Le combat du MVP opposera le joueur à un adversaire contrôlé par le système. Les statistiques utilisées et la condition de victoire seront définies par l'équipe avant le Sprint 3.

Les échanges, les enchères, la marketplace, les guildes, les amis et l'abonnement seront développés uniquement si le MVP est terminé.

## User stories principales

- En tant que joueur, je veux créer un compte afin de sauvegarder ma progression.
- En tant que joueur, je veux ouvrir un pack afin d'obtenir des cartes.
- En tant que joueur, je veux consulter ma collection afin de voir les cartes que je possède.
- En tant que joueur, je veux ouvrir la fiche d'une carte afin de découvrir le film associé.
- En tant que joueur, je veux composer un deck afin de préparer un combat.
- En tant que joueur, je veux lancer un combat simple afin d'utiliser mes cartes.

Les critères précis de chaque fonctionnalité seront ajoutés dans les cartes Trello ou les Issues GitHub au moment de leur développement.

## Backlog du MVP

| Priorité | Fonctionnalité |
|---|---|
| 1 | Préparer la base de films et définir les raretés |
| 2 | Créer l'inscription et la connexion |
| 3 | Afficher les films et leurs informations |
| 4 | Créer l'ouverture des packs |
| 5 | Créer la collection du joueur |
| 6 | Permettre la création d'un deck |
| 7 | Développer le combat simple |
| 8 | Tester et corriger le parcours complet |

## Sprints

Les sprints durent environ deux semaines.

### Sprint 0 - Préparation

- Valider le MVP.
- Préparer les maquettes.
- Étudier les données des films.
- Préparer la base MySQL.

### Sprint 1 - Comptes et films

- Inscription et connexion.
- Import des films.
- Affichage des fiches de films.

### Sprint 2 - Packs et collection

- Calcul des raretés.
- Ouverture des packs.
- Affichage de la collection.

### Sprint 3 - Deck et combat

- Création d'un deck.
- Règles du combat simple.
- Écran de combat.

### Sprint 4 - Finalisation

- Tests du parcours complet.
- Corrections.
- Préparation de la démonstration.

## Organisation Scrum

- Le backlog et les tâches sont suivis sur Trello.
- L'équipe choisit les tâches du sprint au début de chaque sprint.
- Un point court permet de suivre ce qui est fait, ce qui reste à faire et les blocages.
- Une démonstration est réalisée à la fin du sprint.
- Chaque fonctionnalité est développée sur une branche Git dédiée.

Les rôles précis et la répartition des fonctionnalités seront décidés avec toute l'équipe.
