# Vision et besoin du projet

## 1. Contexte

Le projet consiste à créer une application web de cartes à collectionner consacrée au cinéma. Chaque film présent dans la base de données devient une carte avec une rareté, des caractéristiques et des informations culturelles.

Dans le scénario pédagogique du projet, l'application est réalisée pour l'Association Française du Festival International du Film, liée au Festival de Cannes. Le produit doit utiliser le jeu pour donner aux jeunes l'envie de découvrir des films, des genres et des œuvres qu'ils ne connaissent pas encore.

## 2. Problème identifié

Les jeunes ont accès à un très grand nombre de films, mais cette abondance ne facilite pas toujours la découverte. Les catalogues classiques présentent surtout des affiches, des notes et des recommandations. Ils donnent peu de raisons de revenir régulièrement, de comparer les œuvres ou d'explorer des films en dehors de ses habitudes.

Le projet répond donc au besoin suivant : rendre la découverte du cinéma plus attractive, régulière et interactive grâce aux mécanismes d'un jeu de cartes.

## 3. Utilisateurs visés

### Utilisateur principal

Un jeune amateur de jeux, de collection ou de cinéma qui souhaite :

- ouvrir des packs de cartes ;
- découvrir de nouveaux films ;
- compléter une collection ;
- construire un deck ;
- utiliser ses cartes dans des combats simples.

### Utilisateur secondaire

Un amateur de cinéma qui souhaite parcourir une base de films et consulter des informations sur les œuvres sous une forme plus ludique qu'un catalogue traditionnel.

### Partie prenante

Le commanditaire pédagogique souhaite proposer une expérience capable de valoriser les films et de susciter la curiosité pour la culture cinématographique.

## 4. Proposition de valeur

L'application transforme une base de films en expérience de collection et de jeu :

1. le joueur ouvre un pack ;
2. il obtient des cartes de raretés différentes ;
3. il consulte les films découverts ;
4. il complète sa collection ;
5. il compose un deck ;
6. il utilise ce deck dans un combat simplifié.

La rareté d'une carte est calculée à partir de données du film, notamment ses revenus et sa note. La formule exacte doit être documentée et testée avant son utilisation définitive.

## 5. Objectifs du produit

- Donner envie de découvrir des films par le jeu.
- Valoriser une base d'environ 1 000 films.
- Proposer une boucle de jeu courte et compréhensible.
- Faire ressentir une progression grâce à la collection.
- Créer une base technique évolutive pour les échanges, le classement et la marketplace.
- Livrer au semestre 3 un parcours utilisateur complet et démontrable.

## 6. Indicateurs de réussite du MVP

Le MVP est considéré comme pertinent si, pendant une démonstration :

- un nouvel utilisateur peut créer un compte et se connecter ;
- il peut ouvrir un pack sans intervention technique ;
- les cartes obtenues sont enregistrées dans sa collection ;
- il peut consulter la fiche d'un film obtenu ;
- il peut créer un deck valide ;
- il peut terminer un combat simplifié ;
- le parcours principal ne présente pas d'erreur bloquante.

Des tests utilisateurs simples pourront aussi mesurer :

- la compréhension du parcours sans explication ;
- le nombre de films inconnus découverts ;
- l'envie déclarée d'en savoir plus sur au moins un film.

## 7. Contraintes connues

- Application web réalisée sans framework.
- Frontend en HTML, CSS et JavaScript.
- Backend en PHP.
- Base de données MySQL exécutée sur un serveur WAMP.
- Équipe de trois étudiants.
- Le projet doit reposer majoritairement sur des compétences déjà maîtrisées.
- Le MVP doit être stable et présentable le 11 décembre 2026.

## 8. Principes de décision

En cas de manque de temps, l'équipe protège dans cet ordre :

1. la stabilité du parcours principal ;
2. l'enregistrement correct des cartes et de la progression ;
3. la consultation des informations sur les films ;
4. le combat simplifié ;
5. les améliorations visuelles ;
6. les fonctionnalités secondaires.
