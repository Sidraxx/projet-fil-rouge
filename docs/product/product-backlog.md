# Product Backlog

## 1. Règles du backlog

Le Product Backlog est ordonné par valeur et par dépendances. Il peut évoluer après chaque Sprint Review. Une tâche ne peut entrer dans un sprint que si elle respecte la Definition of Ready.

Les estimations utilisent des points relatifs : 1, 2, 3, 5, 8 ou 13. Elles devront être confirmées collectivement par l'équipe.

## 2. Backlog priorisé

| ID | Élément | Type | Priorité | Estimation | Dépend de | Cible proposée |
|---|---|---|---|---:|---|---|
| PB-01 | Valider la vision, les utilisateurs et le périmètre MVP | Produit | P0 | 2 | Aucune | Sprint 0 |
| PB-02 | Auditer et nettoyer la base d'environ 1 000 films | Données | P0 | 5 | PB-01 | Sprint 0 |
| PB-03 | Définir les niveaux et la formule de rareté | Produit/Données | P0 | 5 | PB-02 | Sprint 1 |
| PB-04 | Définir les probabilités et le contenu d'un pack | Produit | P0 | 3 | PB-03 | Sprint 1 |
| PB-05 | Écrire et figer les règles du combat simplifié | Produit | P0 | 5 | PB-01 | Sprint 2 au plus tard |
| PB-06 | Créer l'architecture du projet PHP | Technique | P0 | 3 | PB-01 | Sprint 0 |
| PB-07 | Mettre en place le workflow Git simplifié et les règles de contribution | Technique | P0 | 3 | PB-06 | Sprint 0 |
| PB-08 | Concevoir le MCD puis le MLD | Base de données | P0 | 5 | PB-01, PB-02 | Sprint 0 |
| PB-09 | Créer le schéma MySQL initial | Base de données | P0 | 5 | PB-08 | Sprint 1 |
| PB-10 | Préparer un script reproductible de données de démonstration | Base de données | P1 | 3 | PB-09 | Sprint 1 |
| PB-11 | Concevoir les wireframes du parcours principal | UX/UI | P0 | 5 | PB-01 | Sprint 0 |
| PB-12 | Définir la navigation et les composants visuels communs | UX/UI | P1 | 3 | PB-11 | Sprint 1 |
| PB-13 | Développer l'inscription sécurisée | Fonctionnalité | P0 | 5 | PB-09, PB-12 | Sprint 1 |
| PB-14 | Développer la connexion, la session et la déconnexion | Fonctionnalité | P0 | 5 | PB-13 | Sprint 1 |
| PB-15 | Importer les films nettoyés dans MySQL | Données | P0 | 5 | PB-03, PB-09 | Sprint 1 |
| PB-16 | Afficher une carte et sa fiche de film | Fonctionnalité | P0 | 5 | PB-12, PB-15 | Sprint 1 |
| PB-17 | Développer le tirage d'un pack | Fonctionnalité | P0 | 8 | PB-04, PB-15 | Sprint 2 |
| PB-18 | Enregistrer le résultat d'un pack sans doublon d'action | Fonctionnalité | P0 | 5 | PB-14, PB-17 | Sprint 2 |
| PB-19 | Afficher la collection du joueur | Fonctionnalité | P0 | 5 | PB-18 | Sprint 2 |
| PB-20 | Ajouter la recherche et les filtres de collection | Fonctionnalité | P1 | 3 | PB-19 | Sprint 2 |
| PB-21 | Créer et modifier un deck | Fonctionnalité | P0 | 8 | PB-19, PB-05 | Sprint 3 |
| PB-22 | Valider les règles d'un deck | Fonctionnalité | P0 | 3 | PB-21 | Sprint 3 |
| PB-23 | Développer l'adversaire ou le scénario de combat | Fonctionnalité | P0 | 5 | PB-05, PB-15 | Sprint 3 |
| PB-24 | Développer le moteur du combat simplifié | Fonctionnalité | P0 | 8 | PB-05, PB-22, PB-23 | Sprint 3 |
| PB-25 | Afficher le déroulement et le résultat du combat | Fonctionnalité | P0 | 5 | PB-24 | Sprint 4 |
| PB-26 | Tester les droits d'accès et l'isolation des joueurs | Test | P0 | 3 | PB-14, PB-19, PB-21 | Sprint 4 |
| PB-27 | Tester le parcours complet de bout en bout | Test | P0 | 5 | PB-25 | Sprint 4 |
| PB-28 | Corriger les défauts bloquants du MVP | Qualité | P0 | 8 | PB-27 | Sprint 4 et 5 |
| PB-29 | Rédiger la procédure d'installation WAMP | Documentation | P0 | 3 | PB-09, PB-14 | Sprint 4 |
| PB-30 | Préparer les données et comptes de démonstration | Livraison | P0 | 3 | PB-10, PB-27 | Sprint 5 |
| PB-31 | Préparer le scénario de soutenance | Livraison | P0 | 3 | PB-27 | Sprint 5 |
| PB-32 | Améliorer l'accessibilité et l'affichage responsive | Qualité | P1 | 5 | PB-12 | Sprint 4 si capacité |
| PB-33 | Ajouter un classement simple | Fonctionnalité | P2 | 5 | PB-25 | Après MVP |
| PB-34 | Ajouter des succès | Fonctionnalité | P2 | 5 | PB-19, PB-25 | Après MVP |
| PB-35 | Ajouter les échanges de cartes | Fonctionnalité | P3 | 13 | PB-19 | Semestre 4 |
| PB-36 | Ajouter la marketplace et les enchères | Fonctionnalité | P3 | 13 | PB-19 | Semestre 4 |
| PB-37 | Ajouter les amis et les guildes | Fonctionnalité | P3 | 13 | PB-14 | Semestre 4 |
| PB-38 | Étudier un abonnement facultatif | Produit | P3 | 8 | Mesures d'usage | Semestre 4 |

## 3. Issues GitHub déjà créées

| Élément du backlog | Issue GitHub |
|---|---|
| PB-01, PB-06 et PB-07 | #1 |
| PB-02 | #2 |
| PB-08 | #3 |
| PB-11 | #4 |
| PB-03 | #5 |
| PB-05 | #6 |
| PB-04 | #9 |

Les autres Issues seront créées pendant le refinement, une fois leur Definition of Ready vérifiée. L'Issue #7 couvre l'attribution des rôles Scrum et l'Issue #8 la vérification de l'environnement WAMP commun.

## 4. Ordre de réalisation du parcours principal

Le chemin critique du MVP est :

`Données films -> schéma MySQL -> compte utilisateur -> pack -> collection -> deck -> combat -> tests -> démonstration`

Tout retard sur un élément de ce chemin doit être signalé pendant le Daily Scrum et traité avant une fonctionnalité de priorité inférieure.

## 5. Règle de découpage

Une carte estimée à 8 points ou plus doit être vérifiée avant le Sprint Planning. Si elle ne peut pas être terminée dans un sprint, elle doit être découpée en éléments plus petits donnant chacun un résultat vérifiable.
