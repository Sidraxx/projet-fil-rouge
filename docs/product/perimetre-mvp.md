# Périmètre du MVP

## 1. Objectif du MVP

Le MVP doit démontrer la valeur principale du projet avec un parcours complet :

> Créer un compte, ouvrir un pack de films, retrouver les cartes dans sa collection, construire un deck et terminer un combat simplifié.

Le MVP n'a pas pour objectif de reproduire immédiatement toutes les fonctions d'un jeu de cartes complet.

## 2. Fonctionnalités incluses

### Compte utilisateur

- Inscription avec un pseudonyme, une adresse électronique et un mot de passe.
- Connexion et déconnexion.
- Mots de passe stockés de manière sécurisée avec les fonctions prévues par PHP.
- Session utilisateur conservée pendant la navigation.

### Base de films et cartes

- Import de la base disponible d'environ 1 000 films dans MySQL.
- Utilisation d'un petit échantillon uniquement pour accélérer les tests d'import pendant le développement.
- Association d'un film à une carte.
- Affichage au minimum du titre, de l'affiche, du genre, de l'année, de la note, des revenus et de la rareté lorsque ces données sont disponibles.
- Calcul documenté d'une rareté à partir des données disponibles.
- Gestion explicite des données manquantes.

### Packs

- Ouverture d'un pack contenant un nombre fixe de cartes.
- Tirage fondé sur des probabilités définies par rareté.
- Affichage des cartes obtenues.
- Enregistrement immédiat des cartes dans la collection du joueur.
- Prévention d'une seconde validation involontaire du même pack.

### Collection

- Affichage des cartes possédées.
- Affichage du nombre d'exemplaires de chaque carte.
- Filtre simple par titre, genre ou rareté.
- Consultation de la fiche détaillée d'une carte et de son film.

### Deck

- Création d'un deck à partir des cartes possédées.
- Taille du deck et règles minimales clairement indiquées.
- Refus d'un deck invalide.
- Enregistrement du deck.

### Combat simplifié

- Lancement d'un combat contre un adversaire contrôlé par l'application ou un scénario fixe.
- Utilisation d'un deck valide.
- Règles courtes, déterministes et documentées.
- Affichage du déroulement et du résultat.
- Possibilité de recommencer un combat.

### Qualité et démonstration

- Parcours principal utilisable sur ordinateur.
- Messages d'erreur compréhensibles.
- Base de démonstration reproductible.
- Procédure d'installation locale sous WAMP.
- Jeu de comptes et de données permettant la soutenance.

## 3. Hors périmètre du MVP

Les éléments suivants restent dans le backlog mais ne doivent pas retarder le parcours principal :

- marketplace entre joueurs ;
- enchères en temps réel ;
- vente de cartes ;
- échanges directs de cartes ;
- monnaie réelle ou paiement ;
- abonnement payant ;
- guildes ;
- liste d'amis et messagerie ;
- classement compétitif complet ;
- succès avancés ;
- combat multijoueur en temps réel ;
- matchmaking ;
- application mobile native ;
- notifications ;
- système anti-triche complet ;
- administration avancée ;
- import automatique depuis des services externes.

## 4. Version minimale du combat

Les règles détaillées seront précisées par le membre chargé du système de combat. Pour rester dans le MVP, le combat doit respecter les limites suivantes :

- un adversaire local ou un scénario fixe ;
- un nombre limité de cartes par deck ;
- une seule condition de victoire ;
- peu de caractéristiques par carte ;
- un tableau d'avantages entre genres limité et lisible ;
- aucun temps réel nécessaire.

Si les règles complètes ne sont pas prêtes au début du Sprint 3, une règle de secours sera utilisée : chaque carte possède une valeur calculée, un modificateur de genre simple est appliqué, puis la meilleure valeur gagne la manche.

## 5. Hypothèses à valider

- Le dataset contient les champs nécessaires au calcul de rareté.
- Les droits d'utilisation des affiches et données sont compatibles avec une démonstration scolaire.
- L'équipe peut utiliser MySQL avec WAMP sur les trois postes.
- La taille du pack, la taille du deck et les probabilités de rareté seront fixées avant le développement correspondant.
- Les règles de combat seront figées au plus tard au début du Sprint 3.

## 6. Règles de contrôle du périmètre

Une nouvelle fonctionnalité n'entre dans le MVP que si :

1. elle sert directement le parcours principal ;
2. ses critères d'acceptation sont écrits ;
3. ses dépendances sont connues ;
4. l'équipe estime pouvoir la terminer sans mettre en danger les éléments de priorité supérieure.

Toute autre idée est ajoutée au Product Backlog sans être développée immédiatement.
