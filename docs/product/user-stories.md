# User stories priorisées

## 1. Convention

- `P0` : indispensable au parcours principal du MVP.
- `P1` : important pour rendre le MVP clair et fiable.
- `P2` : amélioration utile après sécurisation du MVP.
- `P3` : évolution envisagée après le semestre 3.

Une user story suit la forme : « En tant que..., je veux..., afin de... ».

## 2. User stories du MVP

### US-01 - Créer un compte

**Priorité : P0**

En tant que nouveau joueur, je veux créer un compte afin de conserver ma collection et ma progression.

Critères d'acceptation :

- les champs obligatoires sont indiqués ;
- une adresse déjà utilisée est refusée ;
- le mot de passe n'est jamais enregistré en clair ;
- le compte est créé lorsque les données sont valides ;
- un message explique toute erreur de saisie.

### US-02 - Se connecter

**Priorité : P0**

En tant que joueur inscrit, je veux me connecter afin d'accéder à mes cartes.

Critères d'acceptation :

- des identifiants valides ouvrent une session ;
- des identifiants invalides affichent un message générique ;
- les pages personnelles ne sont pas accessibles sans session ;
- le joueur peut se déconnecter.

### US-03 - Ouvrir un pack

**Priorité : P0**

En tant que joueur connecté, je veux ouvrir un pack afin d'obtenir de nouvelles cartes de films.

Critères d'acceptation :

- un pack contient le nombre de cartes défini par l'équipe ;
- chaque carte respecte les probabilités de rareté configurées ;
- les cartes obtenues sont affichées ;
- le même pack ne peut pas être enregistré deux fois ;
- les cartes obtenues sont ajoutées à la collection.

### US-04 - Voir ma collection

**Priorité : P0**

En tant que joueur, je veux voir toutes mes cartes afin de suivre l'avancement de ma collection.

Critères d'acceptation :

- seules les cartes du joueur connecté sont affichées ;
- chaque carte montre son titre, son visuel et sa rareté ;
- les doublons affichent une quantité ;
- une collection vide affiche une indication utile.

### US-05 - Filtrer ma collection

**Priorité : P1**

En tant que joueur, je veux filtrer ma collection afin de retrouver rapidement une carte.

Critères d'acceptation :

- une recherche par titre est disponible ;
- un filtre par genre ou rareté est disponible ;
- les filtres peuvent être réinitialisés ;
- aucun résultat produit un message clair.

### US-06 - Consulter une fiche de film

**Priorité : P0**

En tant que joueur, je veux consulter les informations d'une carte afin de découvrir le film qu'elle représente.

Critères d'acceptation :

- le titre, l'année, le genre, la note et la rareté sont affichés lorsqu'ils existent ;
- la fiche affiche une description courte ou les informations culturelles disponibles ;
- les données absentes sont gérées sans casser la page ;
- le joueur peut revenir à sa collection.

### US-07 - Comprendre la rareté

**Priorité : P1**

En tant que joueur, je veux identifier facilement la rareté d'une carte afin de comprendre sa valeur dans la collection.

Critères d'acceptation :

- chaque niveau possède un nom et une présentation cohérente ;
- la rareté affichée correspond à la valeur enregistrée en base ;
- les probabilités de tirage sont documentées ;
- la formule de classement donne toujours le même résultat pour les mêmes données.

### US-08 - Créer un deck

**Priorité : P0**

En tant que joueur, je veux sélectionner des cartes de ma collection afin de préparer un combat.

Critères d'acceptation :

- seules les cartes possédées peuvent être ajoutées ;
- l'interface indique le nombre de cartes sélectionnées ;
- un deck incomplet ou invalide ne peut pas être enregistré ;
- un deck valide est conservé en base ;
- le joueur peut modifier son deck.

### US-09 - Lancer un combat simplifié

**Priorité : P0**

En tant que joueur possédant un deck valide, je veux lancer un combat afin d'utiliser mes cartes.

Critères d'acceptation :

- le lancement est refusé sans deck valide ;
- l'adversaire utilise des cartes valides ;
- les règles appliquées correspondent au document de règles ;
- chaque étape utile du calcul est visible ou compréhensible ;
- une victoire ou une défaite est affichée ;
- le joueur peut terminer ou recommencer le combat.

### US-10 - Reprendre ma progression

**Priorité : P0**

En tant que joueur, je veux retrouver mes cartes et mon deck après reconnexion afin de continuer ma partie.

Critères d'acceptation :

- la collection est persistée dans MySQL ;
- le deck enregistré est restauré ;
- les données d'un joueur ne sont pas visibles par un autre joueur ;
- aucune carte n'est perdue lors d'une navigation normale.

### US-11 - Comprendre les erreurs

**Priorité : P1**

En tant que joueur, je veux recevoir des messages compréhensibles afin de corriger une action impossible.

Critères d'acceptation :

- les erreurs de formulaire sont affichées près de l'action concernée ;
- aucune erreur technique sensible n'est présentée au joueur ;
- les actions critiques échouent sans créer de données partielles ;
- un retour vers une page utilisable est proposé.

### US-12 - Utiliser le parcours sur ordinateur

**Priorité : P1**

En tant que joueur, je veux naviguer facilement entre les fonctions afin de terminer le parcours sans aide.

Critères d'acceptation :

- la navigation principale est présente sur les pages du jeu ;
- la page active est identifiable ;
- les boutons ont des libellés compréhensibles ;
- le parcours principal est utilisable sur les navigateurs retenus pour la démonstration.

## 3. User stories après le MVP

### US-13 - Échanger des cartes

**Priorité : P3**

En tant que joueur, je veux proposer un échange afin d'obtenir une carte qui me manque.

Critères d'acceptation :

- seules les cartes possédées peuvent être proposées ;
- l'autre joueur peut accepter ou refuser ;
- les deux collections ne sont modifiées qu'après validation ;
- une carte engagée dans un échange ne peut pas être transférée ailleurs.

### US-14 - Vendre une carte aux enchères

**Priorité : P3**

En tant que joueur, je veux créer une enchère avec une durée et un prix de départ afin de vendre une carte.

Critères d'acceptation :

- la carte appartient au vendeur ;
- le prix de départ et la date de fin sont obligatoires ;
- l'annonce reste visible jusqu'à sa clôture ;
- la carte ne peut pas être vendue deux fois.

### US-15 - Enchérir sur une carte

**Priorité : P3**

En tant que joueur, je veux placer une enchère afin de tenter d'acheter une carte proposée sur la marketplace.

Critères d'acceptation :

- une offre doit dépasser l'offre courante ;
- une offre arrivée après la date de fin est refusée ;
- le gagnant et le vendeur sont identifiés à la clôture ;
- le transfert de la carte et de la monnaie est atomique.

### US-16 - Consulter un classement

**Priorité : P2**

En tant que joueur, je veux consulter un classement afin de comparer ma progression à celle des autres joueurs.

Critères d'acceptation :

- la règle de calcul du score est documentée ;
- le classement affiche le pseudonyme, le rang et le score ;
- deux joueurs à égalité suivent une règle connue ;
- la mise à jour ne modifie pas l'historique des combats.

### US-17 - Débloquer des succès

**Priorité : P2**

En tant que joueur, je veux débloquer des succès afin d'être récompensé pour ma progression.

Critères d'acceptation :

- chaque succès possède une condition vérifiable ;
- un succès déjà obtenu n'est pas attribué une seconde fois ;
- les succès obtenus sont visibles sur le profil ;
- la récompense éventuelle est enregistrée une seule fois.

### US-18 - Ajouter un ami

**Priorité : P3**

En tant que joueur, je veux ajouter un autre joueur à mes amis afin d'interagir avec lui.

Critères d'acceptation :

- une demande peut être acceptée ou refusée ;
- un joueur ne peut pas s'ajouter lui-même ;
- les deux joueurs voient la relation après acceptation ;
- un ami peut être retiré.

### US-19 - Rejoindre une guilde

**Priorité : P3**

En tant que joueur, je veux rejoindre une guilde afin de progresser avec un groupe.

Critères d'acceptation :

- le joueur peut consulter les guildes disponibles ;
- les règles d'adhésion sont affichées ;
- un joueur ne peut appartenir qu'au nombre de guildes autorisé ;
- quitter une guilde met à jour les droits associés.

### US-20 - Souscrire un abonnement

**Priorité : P3**

En tant que joueur, je veux souscrire une offre facultative afin d'accéder à des avantages clairement décrits.

Critères d'acceptation :

- le prix, la durée et les avantages sont affichés avant validation ;
- les fonctions gratuites restent identifiables ;
- l'état de l'abonnement est visible ;
- l'annulation et la date de fin sont gérées selon les règles définies.
