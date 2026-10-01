# Glossaire

## Product Backlog

Liste ordonnée de tout ce qui pourrait être développé. Les éléments les plus importants et les plus prêts sont placés en haut.

## Sprint

Période courte pendant laquelle l'équipe poursuit un objectif précis et produit un résultat vérifiable.

## Sprint Backlog

Ensemble des tâches choisies pour atteindre l'objectif du sprint en cours.

## User story

Besoin écrit du point de vue d'un utilisateur, sous la forme : « En tant que..., je veux..., afin de... ».

## Critère d'acceptation

Condition vérifiable permettant de décider si une user story répond au besoin.

## Definition of Ready

Liste des conditions qu'une tâche doit respecter avant d'entrer dans un sprint.

## Definition of Done

Liste des conditions qu'une tâche doit respecter avant d'être déclarée terminée.

## Points relatifs

Mesure utilisée pour comparer l'effort de plusieurs tâches. Les points ne représentent pas directement des heures.

## MCD

Modèle Conceptuel de Données. Il présente les entités, leurs informations et leurs relations sans dépendre directement de MySQL.

## MLD

Modèle Logique de Données. Il transforme le MCD en tables, colonnes, clés primaires et clés étrangères.

## Migration

Fichier SQL numéroté qui fait évoluer une base existante, par exemple en ajoutant une colonne.

## Transaction

Groupe de requêtes SQL qui réussissent ou échouent ensemble. Cela évite d'enregistrer seulement une partie d'une action.

## Opération atomique

Action appliquée entièrement ou pas du tout. Une transaction permet généralement de rendre une action atomique.

## PDO

Outil fourni par PHP pour se connecter à MySQL et exécuter des requêtes préparées.

## Requête préparée

Requête SQL dans laquelle les valeurs sont transmises séparément du texte SQL. Elle réduit notamment le risque d'injection SQL.

## VirtualHost

Configuration Apache qui associe une adresse locale à un dossier précis. Dans ce projet, il doit exposer uniquement `public/`.

## Burndown

Graphique montrant la quantité de travail restant dans un sprint. Il est facultatif pour cette équipe ; le suivi des points prévus et terminés suffit au départ.

## Incrément

Ensemble des éléments terminés et intégrés pendant un sprint selon la Definition of Done.
