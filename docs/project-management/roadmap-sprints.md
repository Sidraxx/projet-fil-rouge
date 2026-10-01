# Roadmap et sprints

## 1. Jalons scolaires

| Date | Jalon | Résultat attendu |
|---|---|---|
| 9 octobre 2026 | Cadrage initial | Périmètre, organisation, architecture, workflow Git, backlog et premières maquettes validés |
| 6 novembre 2026 | Premier incrément | Base MySQL, comptes, films, ouverture de pack et collection fonctionnels |
| 4 décembre 2026 | MVP complet | Parcours pack, collection, deck et combat démontrable |
| 11 décembre 2026 | Soutenance | Version stable, documentation et démonstration préparées |

## 2. Découpage des sprints

### Sprint 0 - Fondation

**Période : 1 au 8 octobre 2026**
**Jalon : 9 octobre 2026**

Objectif : mettre l'équipe en état de développer sur une base commune.

Livrables visés :

- vision et besoin ;
- périmètre MVP et hors périmètre ;
- user stories et Product Backlog ;
- méthode Scrum et responsabilités ;
- architecture des dossiers PHP ;
- workflow Git simplifié et modèles GitHub ;
- audit du dataset ;
- première version du MCD et du MLD ;
- wireframes du parcours principal ;
- règles générales du système de rareté et du combat à préciser.

Critère de réussite : chaque membre peut prendre une tâche prête, créer sa branche et travailler avec une compréhension commune du MVP.

### Sprint 1 - Socle utilisateur et données

**Période : 9 au 22 octobre 2026**

Objectif : rendre disponibles les films et l'accès utilisateur.

Livrables visés :

- schéma MySQL initial ;
- données de démonstration ;
- formule de rareté version 1 ;
- import des films ;
- inscription, connexion, session et déconnexion ;
- navigation et composants visuels communs ;
- première fiche de film.

Critère de réussite : un utilisateur peut se connecter et consulter un film provenant de MySQL.

### Sprint 2 - Packs et collection

**Période : 23 octobre au 5 novembre 2026**
**Jalon : 6 novembre 2026**

Objectif : rendre la boucle de collection fonctionnelle.

Livrables visés :

- probabilités de pack validées ;
- tirage d'un pack ;
- enregistrement fiable des cartes obtenues ;
- affichage de la collection ;
- quantité des doublons ;
- recherche ou filtre simple ;
- règles du combat simplifié validées pour préparer le sprint suivant ;
- première série de tests du parcours.

Critère de réussite : un compte neuf peut ouvrir un pack et retrouver les cartes après reconnexion.

### Sprint 3 - Deck et moteur de combat

**Période : 6 au 19 novembre 2026**

Objectif : permettre au joueur de préparer et lancer un combat.

Livrables visés :

- règles de combat appliquées dans le développement ;
- création et modification d'un deck ;
- validation d'un deck ;
- adversaire ou scénario local ;
- moteur de combat simplifié ;
- tests unitaires ou scénarios de test sur les règles principales.

Critère de réussite : un deck valide peut être utilisé dans un combat qui produit toujours un résultat cohérent.

### Sprint 4 - Parcours MVP et qualité

**Période : 20 novembre au 3 décembre 2026**
**Jalon : 4 décembre 2026**

Objectif : relier et stabiliser tout le parcours du MVP.

Livrables visés :

- interface du déroulement du combat ;
- résultat de victoire ou défaite ;
- tests du parcours complet ;
- vérification de l'isolation entre utilisateurs ;
- correction des défauts bloquants ;
- documentation d'installation ;
- amélioration de l'affichage si la capacité le permet.

Critère de réussite : le parcours complet peut être démontré sans manipulation manuelle de la base.

### Sprint 5 - Livraison et soutenance

**Période : 4 au 10 décembre 2026**
**Soutenance : 11 décembre 2026**

Objectif : livrer une version stable et présenter clairement le travail réalisé.

Livrables visés :

- gel des nouvelles fonctionnalités ;
- corrections critiques uniquement ;
- données et comptes de démonstration ;
- dossier technique à jour ;
- scénario de démonstration ;
- support de soutenance ;
- répétition chronométrée ;
- solution de secours en cas de problème de poste ou de serveur.

Critère de réussite : l'équipe peut installer, lancer, expliquer et démontrer le projet dans le temps prévu.

## 3. Règles de planification

- Les objectifs ci-dessus sont des propositions à confirmer au Sprint Planning.
- La capacité réelle doit tenir compte des cours, absences et autres rendus.
- Les points non terminés reviennent dans le Product Backlog ; ils ne sont pas déclarés terminés partiellement.
- À partir du 4 décembre, aucune fonctionnalité secondaire ne doit être ajoutée.
- Si le combat prend du retard, l'équipe utilise la règle de secours définie dans le périmètre MVP.

## 4. Priorités en cas de retard

1. Conserver compte, pack et collection.
2. Conserver la fiche culturelle des films.
3. Simplifier le deck et le combat.
4. Retirer les filtres secondaires ou animations.
5. Reporter classement, succès et toute fonction sociale.
