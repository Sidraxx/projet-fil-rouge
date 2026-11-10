# Cahier des Charges Fonctionnel et Technique

## Projet Fil Rouge — Jeu de cartes de films

**Nom de l'équipe / Groupe :** Groupe Projet Fil Rouge

**Membres du projet :** Hugo (rôle à définir), Antonio (rôle à définir), Siloe (rôle à définir)

**Tuteur :** ALEXANDRE Sylvain

**Client fictif :** Association Française du Festival International du Film (AFFIF)

**État du projet :** phase de cadrage et de fondation. L'arborescence est créée, mais l'application n'est pas encore développée.

---

## 1. Présentation du Projet & Contexte

### 1.1 Contexte & Problématique

L'Association Française du Festival International du Film souhaite proposer une expérience numérique capable de rapprocher les jeunes du cinéma.

Les catalogues et bases de données de films permettent de consulter beaucoup d'informations, mais leur utilisation reste principalement passive. Le projet cherche donc à transformer la découverte des films en une activité plus ludique grâce à des mécanismes connus des jeux de cartes à collectionner : ouverture de packs, niveaux de rareté, collection, création de decks et combats.

Le projet s'appuie sur une base d'environ mille films. Chaque film devient une carte dont la rareté est calculée à partir des données disponibles, notamment les revenus du film et sa note. La carte conserve également une fonction culturelle en donnant accès aux informations principales du film. Avant l'import, l'équipe devra confirmer l'origine et le droit d'utilisation des données et des images, les colonnes réellement disponibles, l'identifiant unique des films et le traitement des valeurs manquantes.

La problématique retenue est la suivante :

> Comment utiliser la collection et le jeu pour donner envie à un public jeune de découvrir davantage de films et de s'intéresser au cinéma ?

### 1.2 Objectifs du projet

L'objectif principal est de créer une application web simple dans laquelle un utilisateur peut découvrir des films en collectionnant et en utilisant leurs cartes.

L'application doit permettre de :

- transformer les films de la base de données en cartes à collectionner ;
- calculer une rareté cohérente pour chaque carte ;
- ouvrir des packs contenant plusieurs cartes aléatoires ;
- sauvegarder la collection de chaque joueur ;
- consulter les informations culturelles associées à un film ;
- créer un deck à partir des cartes possédées ;
- utiliser ce deck dans un combat simple contre le système ;
- proposer un parcours compréhensible et démontrable lors de la soutenance.

### 1.3 Périmètre du MVP

Le MVP correspond à la version minimale complète qui sera présentée lors de la soutenance.

Le parcours principal du MVP est le suivant :

1. créer un compte ;
2. se connecter ;
3. ouvrir un pack ;
4. retrouver les cartes obtenues dans sa collection ;
5. consulter la fiche d'un film ;
6. construire un deck valide ;
7. lancer et terminer un combat contre un adversaire contrôlé par le système.

Le MVP comprend :

- l'inscription, la connexion et la déconnexion ;
- la gestion de la session utilisateur ;
- l'importation de la base de films dans MySQL ;
- le calcul et l'enregistrement de la rareté des films ;
- l'ouverture de packs selon des probabilités définies ;
- l'enregistrement des cartes et des doublons ;
- l'affichage de la collection ;
- la consultation de la fiche détaillée d'un film ;
- la création et la validation d'un deck ;
- un combat simple contre le système ;
- les tests du parcours complet.

Les fonctionnalités suivantes sont hors du MVP :

- échanges de cartes entre joueurs ;
- vente de cartes et marketplace ;
- enchères ;
- guildes ;
- liste d'amis ;
- abonnement ;
- paiement en ligne ;
- combat entre deux joueurs ;
- classement général et succès avancés ;
- interface complète d'administration.

---

## 2. Analyse des Utilisateurs (Cibles)

### Utilisateur principal : joueur

L'utilisateur principal est un adolescent ou un jeune adulte intéressé par les jeux de collection, avec ou sans connaissances particulières en cinéma. La tranche d'âge définitive sera confirmée avec le tuteur.

**Besoins principaux :**

- comprendre rapidement le fonctionnement de l'application ;
- obtenir des cartes en ouvrant des packs ;
- visualiser facilement sa collection ;
- découvrir les informations principales des films ;
- construire un deck sans connaître les règles techniques internes ;
- terminer un combat simple et compréhensible.

**Niveau d'aisance numérique :**

Le parcours est conçu pour un utilisateur habitué aux sites web et aux jeux grand public. Aucune compétence technique n'est nécessaire.

**Objectif sur la plateforme :**

Compléter sa collection, découvrir de nouveaux films et utiliser ses cartes dans un combat.

### Utilisateur administrateur / support

Pendant le MVP, le rôle d'administrateur est assuré par l'équipe projet. Il n'existe pas de back-office dédié.

**Besoins principaux :**

- importer et vérifier les films ;
- contrôler les niveaux de rareté ;
- vérifier les données des utilisateurs et des collections en cas de problème ;
- préparer un compte et des données de démonstration ;
- diagnostiquer une erreur depuis MySQL ou phpMyAdmin.

Ces opérations sont réalisées directement avec MySQL, phpMyAdmin et les fichiers du projet. Une interface d'administration pourra être étudiée après le MVP.

---

## 3. Spécifications Fonctionnelles (Backlog Produit)

Les fonctionnalités sont classées avec la méthode MoSCoW.

### Matrice de Priorisation des Fonctionnalités (MoSCoW)

| Priorité | User Story / Fonctionnalité | Description & Critères d'acceptation |
|---|---|---|
| Must Have | Authentification | En tant que joueur, je peux créer un compte, me connecter et me déconnecter. Le mot de passe est haché, les erreurs sont compréhensibles et les pages privées sont protégées par une session PHP. |
| Must Have | Base de films | La base disponible est contrôlée puis importée dans MySQL sans doublons bloquants. L'origine des données, leur droit d'utilisation, l'identifiant unique, les champs obligatoires et le traitement des valeurs manquantes sont documentés. |
| Must Have | Rareté des cartes | Chaque film reçoit un score et un niveau de rareté calculés avec une formule définie par l'équipe. La répartition finale est vérifiée sur l'ensemble des films. |
| Must Have | Ouverture de packs | En tant que joueur connecté, je peux ouvrir un pack contenant le nombre prévu de cartes. Toutes les cartes obtenues sont enregistrées dans une même transaction. La distribution des raretés est contrôlée par un test sur un grand nombre de tirages avec une tolérance définie par l'équipe. |
| Must Have | Gestion des doublons | Lorsqu'une carte déjà possédée est obtenue, sa quantité augmente conformément à la règle choisie. Aucune carte du pack n'est perdue en cas d'enregistrement réussi. |
| Must Have | Collection | En tant que joueur, je peux consulter toutes les cartes que je possède, leur rareté et leur quantité. Un message adapté apparaît lorsque la collection est vide. |
| Must Have | Fiche d'un film | En tant que joueur, je peux ouvrir une carte et consulter les informations principales du film associé. Les données manquantes n'endommagent pas l'affichage. |
| Must Have | Identification visuelle des raretés | Chaque niveau de rareté possède un rendu distinct, lisible et cohérent sur les écrans de pack, de collection, de film et de deck. |
| Must Have | Création d'un deck | En tant que joueur, je peux ajouter et retirer des cartes possédées. Le nombre total de cartes et la limite de doublons sont contrôlés côté serveur. |
| Must Have | Combat contre le système | En tant que joueur disposant d'un deck valide, je peux lancer un combat, jouer mes cartes, voir les actions du système et atteindre un résultat de victoire ou de défaite. |
| Must Have | Parcours complet testable | Le parcours inscription → pack → collection → deck → combat peut être effectué sans blocage sur Chrome et Edge. |
| Should Have | Tri de la collection | Le joueur peut trier sa collection par titre ou par rareté. |
| Should Have | Révélation progressive du pack | Les cartes du pack sont révélées progressivement avec une interaction JavaScript simple. |
| Could Have | Recherche de films | Le joueur peut rechercher un film par son titre dans le catalogue ou sa collection. |
| Could Have | Filtres supplémentaires | Le joueur peut filtrer les cartes par genre ou par rareté. |
| Could Have | Animations supplémentaires | Des transitions peuvent améliorer l'ouverture des packs et le combat sans modifier les règles. |
| Won't Have | Échanges, ventes et enchères | Ces fonctionnalités sont reportées après la soutenance car elles nécessitent davantage de règles, de sécurité et de gestion des transactions. |
| Won't Have | Guildes, amis et abonnement | Les fonctionnalités sociales et l'abonnement ne font pas partie du parcours principal du MVP. |
| Won't Have | Combat entre joueurs | Le MVP utilise uniquement un adversaire contrôlé par le système. |
| Won't Have | Paiement en ligne | Aucun paiement réel n'est intégré au MVP. |

### 3.1 Règles de rareté et de packs — version 1

Cette version repose sur les 1 000 films présents dans `cinema_mysql.sql`. Elle doit servir de règle de travail pour le MVP et pourra être ajustée après les premiers tests de jeu.

#### Informations visibles sur une carte

La face principale d'une carte affiche uniquement :

- l'affiche du film ;
- le titre ;
- l'année de sortie ;
- le genre principal ;
- la note IMDb ;
- le nom et la couleur de la rareté.

Le score numérique de rareté n'est pas affiché au joueur. Le synopsis, le réalisateur, les acteurs, la durée, le Metascore, le nombre de votes et les revenus restent accessibles sur la fiche détaillée du film. Cette séparation évite de surcharger la carte tout en conservant les informations culturelles dans l'application.

#### Niveaux de rareté

Les cinq niveaux sont, du plus fréquent au plus rare :

1. Commune ;
2. Peu commune ;
3. Rare ;
4. Épique ;
5. Légendaire.

Chaque niveau devra posséder une couleur et un rendu visuel distincts. La couleur ne devra pas être le seul moyen utilisé pour reconnaître une rareté : son nom restera toujours visible.

#### Données utilisées dans le calcul

| Critère | Rôle | Complétude | Poids |
|---|---|---:|---:|
| Note IMDb | Qualité perçue par le public | 1 000/1 000 | 35 % |
| Nombre de votes IMDb | Popularité et notoriété | 1 000/1 000 | 30 % |
| Revenus bruts | Succès commercial | 831/1 000 | 20 % |
| Metascore | Réception critique | 843/1 000 | 15 % |

Les autres colonnes ne déterminent pas la rareté. Le genre sert au gameplay, tandis que le titre, l'année, l'affiche, le réalisateur, les acteurs, la durée et le synopsis servent à l'affichage.

Le nombre de votes et les revenus sont conservés, mais utilisent une échelle logarithmique. Sans cette correction, quelques valeurs très élevées écraseraient presque tous les autres films. Une valeur manquante de revenus ou de Metascore est remplacée par la médiane de la colonne : 23 530 892 pour les revenus et 79 pour le Metascore. Une donnée absente n'accorde donc ni bonus ni pénalité extrême.

#### Normalisation des critères

Tous les critères sont ramenés entre 0 et 1.

```text
imdb_normalise = (imdb_rating - 7,6) / (9,3 - 7,6)

votes_normalises =
    (log10(number_of_votes) - log10(25 088))
    / (log10(2 343 110) - log10(25 088))

revenus_normalises =
    (log10(gross_corrige) - log10(1 305))
    / (log10(936 662 225) - log10(1 305))

metascore_normalise = (meta_score_corrige - 28) / (100 - 28)
```

Les valeurs finales sont limitées à l'intervalle compris entre 0 et 1 afin de protéger le calcul si une valeur sort des bornes observées.

#### Formule du score

```text
score_rarete = 100 × (
    0,35 × imdb_normalise
  + 0,30 × votes_normalises
  + 0,20 × revenus_normalises
  + 0,15 × metascore_normalise
)
```

Le score final est compris entre 0 et 100 et est arrondi à deux décimales pour l'enregistrement.

Trois contrôles calculés sur la base actuelle :

| Film | Score | Rareté |
|---|---:|---|
| The Godfather | 92,63 | Légendaire |
| Titanic | 58,06 | Rare |
| Tropa de Elite | 20,99 | Commune |

#### Seuils et répartition des 1 000 films

Les seuils proviennent des quantiles de la base actuelle. Ils assurent une collection équilibrée sans modifier artificiellement les données d'un film.

| Rareté | Intervalle du score | Nombre de films | Part du catalogue |
|---|---:|---:|---:|
| Commune | score inférieur à 39,79 | 450 | 45 % |
| Peu commune | de 39,79 à moins de 50,56 | 300 | 30 % |
| Rare | de 50,56 à moins de 60,38 | 150 | 15 % |
| Épique | de 60,38 à moins de 74,76 | 80 | 8 % |
| Légendaire | 74,76 ou plus | 20 | 2 % |

Les seuils devront être recalculés si la base de films change fortement. Pour le MVP, ils restent fixes après l'import afin qu'un même film conserve toujours la même rareté.

#### Test sur un échantillon

Un échantillon reproductible de 20 films, tiré avec la graine aléatoire `40`, donne :

- 9 cartes communes ;
- 6 cartes peu communes ;
- 3 cartes rares ;
- 1 carte épique ;
- 1 carte légendaire.

Cette répartition correspond exactement aux proportions cibles arrondies sur 20 films. Le contrôle complet sur les 1 000 films donne 450 cartes communes, 300 peu communes, 150 rares, 80 épiques et 20 légendaires.

#### Taille et probabilités d'un pack

Un pack du MVP contient cinq cartes. Chaque emplacement effectue un tirage indépendant selon les probabilités suivantes :

| Rareté tirée | Probabilité par carte |
|---|---:|
| Commune | 55 % |
| Peu commune | 27 % |
| Rare | 12 % |
| Épique | 5 % |
| Légendaire | 1 % |
| Total | 100 % |

Avec cinq tirages, la probabilité d'obtenir au moins une carte rare ou supérieure est d'environ 63 %, celle d'obtenir au moins une carte épique ou légendaire est d'environ 27 %, et celle d'obtenir au moins une carte légendaire est d'environ 5 %.

Le test automatisé des packs portera sur au moins 1 000 cartes tirées. Les résultats seront acceptés si chaque fréquence reste dans la tolérance suivante : ±5 points pour Commune et Peu commune, ±3 points pour Rare, ±2 points pour Épique et ±1 point pour Légendaire.

Le détail opérationnel est suivi dans la colonne `Backlog MVP` du tableau Trello. Chaque carte représente une tâche estimée entre une et deux heures et possède sa propre checklist.

---

## 4. Spécifications Techniques & Architecture

### 4.1 Stack Technique Retenue

- **Front-End :** HTML5, CSS3 et JavaScript sans framework.
- **Back-End :** PHP 8.2 sans framework.
- **Base de données :** MySQL 8 avec accès via PDO.
- **Environnement local :** WAMP sous Windows.
- **Gestion de la base :** phpMyAdmin et scripts SQL.
- **Gestion de version :** Git et GitHub.
- **Workflow Git :** branches permanentes `main` et `develop`, puis une branche courte par fonctionnalité.
- **Navigateurs ciblés :** Chrome et Edge sur ordinateur.
- **Hébergement :** solution à définir selon les contraintes de la soutenance ; la version locale sous WAMP reste la référence de développement.

Le projet utilise une architecture PHP simple. Il n'y a ni framework, ni service séparé, ni API indépendante. Les pages PHP réalisent l'affichage, appellent les fonctions nécessaires et communiquent avec MySQL.

### 4.2 Organisation cible des dossiers

```text
projet-fil-rouge/
|-- assets/
|   |-- css/
|   |-- js/
|   `-- images/
|-- includes/
|-- pages/
|-- database/
|-- data/
|-- index.php
|-- README.md
`-- DOCUMENTATION.md
```

- `assets` contient les styles, les scripts JavaScript et les images ;
- `includes` contient la connexion MySQL et les éléments PHP réutilisables ;
- `pages` contient les différentes pages de l'application ;
- `database` contient les scripts SQL ;
- `data` contient les données sources et préparées des films ;
- `index.php` sera le point d'entrée de l'application lorsqu'il sera créé.

### 4.3 Contraintes Techniques & Sécurité

- Les mots de passe sont enregistrés avec `password_hash()` et vérifiés avec `password_verify()`.
- Les requêtes contenant des données dynamiques utilisent PDO et des paramètres préparés.
- Les données affichées dans le HTML sont échappées afin de limiter les injections de contenu.
- Les formulaires sont validés côté serveur, même si une validation JavaScript existe aussi.
- Les pages privées vérifient la présence d'une session utilisateur valide.
- L'identifiant de session est régénéré après une connexion réussie. Les cookies de session sont `HttpOnly`, `SameSite=Lax`, `Secure` lorsque le site est servi en HTTPS, et le mode strict de session est activé.
- Les actions qui modifient des données sont protégées contre les requêtes intersites avec un jeton CSRF.
- Le serveur vérifie que l'utilisateur possède bien une collection, une carte ou un deck avant d'autoriser sa consultation ou sa modification.
- Les identifiants MySQL locaux sont placés dans un fichier ignoré par Git.
- Aucun mot de passe, secret ou fichier de configuration local ne doit être envoyé sur GitHub.
- Les identifiants transmis dans les URL ou formulaires sont contrôlés avant toute requête.
- L'ouverture d'un pack utilise une transaction afin d'éviter un enregistrement partiel.
- Si l'application est déployée publiquement, elle doit être servie en HTTPS.

### 4.4 Contraintes de réalisation

- La stack doit rester limitée aux technologies maîtrisées pendant la formation.
- Aucun framework front-end ou back-end n'est utilisé.
- L'application doit fonctionner dans l'environnement WAMP de l'équipe.
- Le code doit rester compréhensible par les trois membres du groupe.
- Les fonctionnalités hors MVP ne doivent pas retarder le parcours principal.
- Une fonctionnalité est développée sur une branche dédiée puis intégrée dans `develop` après vérification.

---

## 5. Conception UX/UI & Maquettes

Le parcours utilisateur principal comporte les étapes suivantes :

### 1. Inscription / Connexion

Le joueur crée un compte ou saisit ses identifiants. En cas d'erreur, un message indique clairement le champ ou l'information à corriger. Après la connexion, il accède à l'application et voit comment obtenir son premier pack.

### 2. Ouverture d'un pack

Le joueur ouvre un pack depuis l'écran dédié. Les cartes sont générées selon les probabilités définies, enregistrées dans sa collection puis révélées à l'écran.

### 3. Consultation de la collection

Le joueur retrouve les cartes qu'il possède, leur rareté et leur quantité. Une carte permet d'accéder à la fiche du film correspondant.

### 4. Découverte d'un film

La fiche présente les informations principales disponibles : titre, genre, note, revenus, image et niveau de rareté. Une valeur de remplacement est utilisée lorsqu'une donnée manque.

### 5. Création d'un deck

Le joueur choisit des cartes de sa collection. L'interface indique le nombre de cartes présentes et précise si le deck respecte les règles.

### 6. Combat

Le joueur lance un combat avec un deck valide, choisit une carte à chaque tour et voit la carte sélectionnée par le système. L'application calcule le résultat selon les statistiques et les avantages de genre.

### 7. Confirmation & Résultat

Après chaque action importante, l'application affiche un retour visuel. À la fin du combat, un écran indique la victoire ou la défaite et permet de recommencer.

### Principes d'interface

- navigation simple entre les écrans principaux ;
- cartes lisibles et cohérentes ;
- raretés facilement reconnaissables ;
- messages d'erreur proches de l'action concernée ;
- états vides prévus pour les nouveaux comptes ;
- indicateurs simples pendant les actions nécessitant un chargement ;
- navigation utilisable au clavier, contrastes lisibles et textes alternatifs sur les images utiles ;
- interface adaptée aux écrans d'ordinateur utilisés lors de la soutenance.

Les maquettes doivent au minimum couvrir : connexion, inscription, accueil, packs, résultat d'un pack, collection, fiche de film, deck, combat et résultat du combat.

---

## 6. Planning, Jalons & Organisation

Le projet suit une organisation agile inspirée de Scrum, adaptée à la taille de l'équipe. Le backlog reste continu : lorsqu'une tâche est terminée, le membre prend la prochaine carte prioritaire disponible. Un point de suivi est réalisé toutes les deux semaines afin de mesurer l'avancement, montrer ce qui fonctionne et ajuster les priorités. Ces périodes de deux semaines servent de jalons de contrôle et ne bloquent pas l'équipe lorsqu'elle termine les tâches prévues plus tôt.

### Phase 1 : Cadrage & Conception — début octobre 2026

- finalisation du cahier des charges ;
- validation du périmètre du MVP ;
- définition des règles de rareté, de packs, de decks et de combat ;
- analyse de la base de films ;
- réalisation des maquettes ;
- conception des tables MySQL.

### Phase 2 : Données & Cœur de l'application — octobre à début novembre 2026

- nettoyage et importation des films ;
- installation de la base MySQL ;
- inscription, connexion et sessions ;
- affichage des films ;
- calcul des raretés ;
- ouverture des packs ;
- enregistrement et affichage de la collection.

### Phase 3 : Deck, Combat & Intégration — novembre 2026

- création et validation des decks ;
- développement de l'adversaire contrôlé par le système ;
- développement du déroulement du combat ;
- création de l'interface de combat ;
- liaison complète entre compte, pack, collection, deck et combat.

### Phase 4 : Tests & Finalisation — fin novembre au 4 décembre 2026

- tests fonctionnels du parcours complet ;
- contrôle des requêtes et de l'affichage des données ;
- tests sur Chrome et Edge ;
- correction des anomalies bloquantes ;
- préparation des données de démonstration.

### Phase 5 : Préparation & Soutenance — du 5 au 11 décembre 2026

- préparation du support de présentation ;
- rédaction du script de démonstration ;
- répétition du parcours complet ;
- vérification finale du dépôt et de la base ;
- présentation devant le jury.

### Organisation du tableau Trello

Le tableau utilise les colonnes suivantes :

- `Backlog MVP` : toutes les tâches découpées et classées par priorité ;
- `À faire` : tâches prêtes à être commencées ;
- `En cours` : tâches actuellement prises en charge ;
- `Terminé` : tâches développées et vérifiées.

Le backlog contient quatre ordres de réalisation :

1. préparation indispensable ;
2. cœur du MVP ;
3. deck et combat ;
4. validation finale.

Ces niveaux indiquent l'ordre conseillé des tâches. Ils ne remplacent pas MoSCoW, qui indique si une fonctionnalité est indispensable ou optionnelle pour le MVP.

Une carte ne doit normalement pas dépasser deux heures de travail. Si elle devient trop importante, elle doit être divisée en plusieurs cartes.

---

## 7. Livrables Attendus pour la Soutenance

### Dépôt Git

- code source complet sur GitHub ;
- historique des branches et des contributions ;
- fichier `README.md` expliquant l'installation sous WAMP ;
- fichier SQL permettant de créer la base ;
- absence de secrets ou d'identifiants locaux dans le dépôt.

### Application fonctionnelle

- MVP utilisable de l'inscription jusqu'à la fin d'un combat ;
- version de référence fonctionnant sous WAMP ;
- lien public ajouté si une solution d'hébergement compatible est retenue ;
- fonctionnement vérifié sur Chrome et Edge.

### Support de présentation

- diaporama exportable en PDF ;
- présentation du problème, de la solution et du MVP ;
- démonstration du parcours utilisateur principal ;
- présentation de la répartition du travail et des choix techniques.

### Jeux de données

- base MySQL contenant les films nécessaires à la démonstration ;
- niveaux de rareté déjà calculés ;
- compte de démonstration prêt à l'emploi ;
- collection et deck de démonstration si cela facilite la soutenance.

### Documentation projet

- cahier des charges fonctionnel et technique ;
- schéma de la base de données ;
- règles de rareté, de packs, de decks et de combat ;
- tableau Trello à jour ;
- procédure courte permettant de reproduire la démonstration.

---

## Décisions restant à valider

Les éléments ci-dessous ne sont pas inventés dans ce document. Ils doivent être décidés avec l'équipe et le tuteur. Les règles concernant la rareté, les packs, les decks et le combat doivent être validées avant le développement des fonctionnalités correspondantes.

- nom définitif du produit ;
- nom officiel de l'équipe ;
- nom du tuteur ;
- rôle principal de chaque membre ;
- validation par l'équipe et le tuteur de la formule, des seuils et de la répartition de rareté proposés en version 1 ;
- validation par l'équipe et le tuteur du pack de cinq cartes et des probabilités proposés en version 1 ;
- taille d'un deck, limite de doublons et conditions de validité ;
- statistiques utilisées par les cartes, avantages entre genres, déroulement d'un tour et condition de victoire ;
- origine, droit d'utilisation, colonnes et règles de nettoyage de la base de films et des images ;
- solution d'hébergement retenue, si un accès public est obligatoire.
