# Organisation Scrum

## 1. Méthode retenue

Le projet utilise Scrum dans une version adaptée à une équipe de trois étudiants. Le but n'est pas de multiplier les réunions, mais de travailler par cycles courts, de montrer régulièrement un résultat fonctionnel et de corriger rapidement les écarts.

Le travail est organisé en sprints d'environ deux semaines. Le dernier sprint est plus court afin de préparer la soutenance.

## 2. Rôles

### Product Owner

Responsabilités :

- maintenir la vision et le périmètre ;
- ordonner le Product Backlog ;
- clarifier les critères d'acceptation ;
- accepter ou refuser les éléments présentés en Sprint Review ;
- représenter le besoin du commanditaire pédagogique.

Dans une équipe de trois personnes, le Product Owner participe aussi au développement. Le titulaire doit être validé par l'équipe lors du premier Sprint Planning.

### Scrum Master

Responsabilités :

- faire respecter le rythme Scrum ;
- préparer les cérémonies ;
- rendre visibles les blocages ;
- vérifier que les décisions et actions sont suivies ;
- aider l'équipe à améliorer sa manière de travailler.

Le Scrum Master n'est pas un chef hiérarchique. Ce rôle peut être tenu par un développeur et peut tourner entre les sprints si l'équipe le souhaite.

### Developers

Les trois membres sont Developers. Ils sont collectivement responsables de :

- estimer le travail ;
- choisir ce qui entre dans le sprint ;
- concevoir, développer, tester et documenter ;
- relire les Pull Requests ;
- livrer un incrément conforme à la Definition of Done.

## 3. Cérémonies

### Sprint Planning

Durée adaptée : 45 à 60 minutes au début de chaque sprint.

Ordre du jour :

1. rappeler l'objectif du sprint ;
2. vérifier la capacité réelle de chaque membre ;
3. sélectionner les éléments prêts du backlog ;
4. découper les éléments en tâches ;
5. identifier les dépendances et responsables ;
6. confirmer que le Sprint Backlog est réaliste.

Sortie attendue : un objectif de sprint écrit et une liste de tâches prêtes.

### Daily Scrum

Durée adaptée : 10 minutes les jours de travail commun, en présentiel ou par message structuré.

Chaque membre répond à trois points :

- ce qui a été terminé depuis le dernier point ;
- ce qui sera fait ensuite ;
- le blocage éventuel.

Le Daily Scrum sert à ajuster le plan. Les discussions techniques longues sont traitées après avec les personnes concernées.

### Backlog Refinement

Durée adaptée : 30 minutes par semaine.

Objectifs :

- clarifier les prochaines user stories ;
- ajouter ou corriger les critères d'acceptation ;
- découper les éléments trop gros ;
- vérifier les dépendances ;
- estimer les prochains éléments.

### Sprint Review

Durée adaptée : 30 à 45 minutes en fin de sprint.

L'équipe montre uniquement ce qui fonctionne réellement. Chaque élément est accepté ou replacé dans le backlog. Les retours peuvent modifier les priorités du sprint suivant.

Sortie attendue : liste des éléments acceptés, retours et backlog réordonné.

### Sprint Retrospective

Durée adaptée : 20 à 30 minutes après la Review.

Questions :

- qu'est-ce qui a bien fonctionné ?
- qu'est-ce qui a ralenti l'équipe ?
- quelle amélioration concrète teste-t-on au prochain sprint ?

Une seule action d'amélioration prioritaire est assignée avec un responsable.

## 4. Artefacts

### Product Backlog

Liste ordonnée de tout ce qui pourrait être développé. Il est maintenu dans la documentation produit et décliné en cartes de suivi.

### Sprint Backlog

Sous-ensemble choisi pour atteindre l'objectif du sprint. Les colonnes recommandées dans Trello sont :

- Références et décisions ;
- Product Backlog ;
- Sprint en cours ;
- En cours ;
- Relecture et tests ;
- Bloqué ;
- Terminé.

### Incrément

Ensemble des éléments terminés et intégrés pendant le sprint. Un élément ne fait partie de l'incrément que s'il respecte la Definition of Done.

## 5. Règles de fonctionnement

- Une personne ne garde qu'une tâche principale en cours à la fois.
- Toute tâche de code part d'une Issue et d'une branche dédiée.
- Toute intégration passe par une Pull Request relue par au moins un autre membre.
- Un blocage de plus d'une journée est signalé dans la colonne `Bloqué` et au Daily Scrum.
- Une tâche presque terminée mais non testée reste non terminée.
- Une nouvelle idée est ajoutée au Product Backlog ; elle n'interrompt pas automatiquement le sprint.
- L'objectif du sprint est plus important que l'ajout de tâches secondaires.

## 6. Suivi simple

À la fin de chaque sprint, l'équipe conserve :

- le nombre de points prévus ;
- le nombre de points terminés ;
- les éléments reportés ;
- les défauts importants découverts ;
- l'action décidée en rétrospective.

Ces mesures servent à mieux planifier. Elles ne servent pas à comparer les membres entre eux.
