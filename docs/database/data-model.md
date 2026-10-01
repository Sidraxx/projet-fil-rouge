# Modèle de données initial

## Statut du document

Ce modèle couvre le MVP. Il sert de base au MCD et au MLD, mais les attributs liés au combat restent provisoires tant que les règles ne sont pas validées.

## Entités principales

### Utilisateur

Représente un joueur inscrit.

- identifiant ;
- nom d'utilisateur ;
- adresse e-mail ;
- mot de passe haché ;
- date de création.

### Film

Contient les informations culturelles importées depuis le jeu de données.

- identifiant ;
- titre ;
- année de sortie ;
- résumé ;
- affiche ;
- revenus ;
- note ;
- nombre de votes.

### Genre

Décrit un genre cinématographique. Un film peut appartenir à plusieurs genres et un genre peut concerner plusieurs films.

### Rareté

Définit un niveau de rareté et ses paramètres de tirage.

- identifiant ;
- nom ;
- score minimal ;
- score maximal ;
- poids de tirage.

### Carte

Représente la version jouable d'un film.

- identifiant ;
- film associé ;
- rareté ;
- score de rareté calculé ;
- statistiques de jeu à définir.

Un film possède une seule carte dans le MVP.

### Carte possédée

Associe un utilisateur à une carte et indique le nombre d'exemplaires qu'il possède.

### Pack ouvert

Conserve la trace d'une ouverture de pack par un utilisateur.

### Carte de pack

Associe chaque carte tirée au pack qui l'a produite et conserve sa position dans le résultat.

### Deck

Représente une sélection de cartes créée par un utilisateur.

### Carte de deck

Associe les cartes à un deck et conserve leur position.

### Combat

Conserve le résultat minimal d'un combat : joueur, type d'adversaire, éventuel second joueur, état, vainqueur et dates. Le détail des tours sera ajouté lorsque les règles de combat seront validées.

## Relations

```text
users 1-----N user_cards N-----1 cards
movies 1-----1 cards N-----1 rarities
movies N-----N genres, via movie_genres
users 1-----N packs 1-----N pack_cards N-----1 cards
users 1-----N decks 1-----N deck_cards N-----1 cards
users 1-----N battles, comme joueur 1 ou joueur 2
```

## Tables proposées

### `users`

| Colonne | Type MySQL | Contraintes |
|---|---|---|
| `id` | `INT UNSIGNED` | clé primaire, auto-incrément |
| `username` | `VARCHAR(50)` | unique, non nul |
| `email` | `VARCHAR(255)` | unique, non nul |
| `password_hash` | `VARCHAR(255)` | non nul |
| `created_at` | `DATETIME` | non nul |

### `movies`

| Colonne | Type MySQL | Contraintes |
|---|---|---|
| `id` | `INT UNSIGNED` | clé primaire, auto-incrément |
| `title` | `VARCHAR(255)` | non nul |
| `release_year` | `SMALLINT UNSIGNED` | nullable |
| `synopsis` | `TEXT` | nullable |
| `poster_path` | `VARCHAR(500)` | nullable |
| `revenue` | `BIGINT UNSIGNED` | nullable |
| `rating` | `DECIMAL(3,1)` | nullable |
| `vote_count` | `INT UNSIGNED` | nullable |

### `genres`

| Colonne | Type MySQL | Contraintes |
|---|---|---|
| `id` | `SMALLINT UNSIGNED` | clé primaire, auto-incrément |
| `name` | `VARCHAR(80)` | unique, non nul |

### `movie_genres`

| Colonne | Type MySQL | Contraintes |
|---|---|---|
| `movie_id` | `INT UNSIGNED` | clé étrangère vers `movies` |
| `genre_id` | `SMALLINT UNSIGNED` | clé étrangère vers `genres` |

La clé primaire est composée de `movie_id` et `genre_id`.

### `rarities`

| Colonne | Type MySQL | Contraintes |
|---|---|---|
| `id` | `TINYINT UNSIGNED` | clé primaire, auto-incrément |
| `name` | `VARCHAR(30)` | unique, non nul |
| `min_score` | `DECIMAL(8,3)` | non nul |
| `max_score` | `DECIMAL(8,3)` | non nul |
| `drop_weight` | `INT UNSIGNED` | non nul |

### `cards`

| Colonne | Type MySQL | Contraintes |
|---|---|---|
| `id` | `INT UNSIGNED` | clé primaire, auto-incrément |
| `movie_id` | `INT UNSIGNED` | unique, clé étrangère vers `movies` |
| `rarity_id` | `TINYINT UNSIGNED` | clé étrangère vers `rarities` |
| `rarity_score` | `DECIMAL(8,3)` | non nul |
| `attack` | `SMALLINT UNSIGNED` | nullable tant que les règles évoluent |
| `defense` | `SMALLINT UNSIGNED` | nullable tant que les règles évoluent |

### `user_cards`

| Colonne | Type MySQL | Contraintes |
|---|---|---|
| `user_id` | `INT UNSIGNED` | clé étrangère vers `users` |
| `card_id` | `INT UNSIGNED` | clé étrangère vers `cards` |
| `quantity` | `INT UNSIGNED` | non nul, valeur minimale 1 |
| `first_obtained_at` | `DATETIME` | non nul |

La clé primaire est composée de `user_id` et `card_id`.

### `packs`

| Colonne | Type MySQL | Contraintes |
|---|---|---|
| `id` | `BIGINT UNSIGNED` | clé primaire, auto-incrément |
| `user_id` | `INT UNSIGNED` | clé étrangère vers `users` |
| `opened_at` | `DATETIME` | non nul |

### `pack_cards`

| Colonne | Type MySQL | Contraintes |
|---|---|---|
| `pack_id` | `BIGINT UNSIGNED` | clé étrangère vers `packs` |
| `card_id` | `INT UNSIGNED` | clé étrangère vers `cards` |
| `slot_position` | `TINYINT UNSIGNED` | non nul |

La clé primaire est composée de `pack_id` et `slot_position`.

### `decks`

| Colonne | Type MySQL | Contraintes |
|---|---|---|
| `id` | `INT UNSIGNED` | clé primaire, auto-incrément |
| `user_id` | `INT UNSIGNED` | clé étrangère vers `users` |
| `name` | `VARCHAR(80)` | non nul |
| `created_at` | `DATETIME` | non nul |

### `deck_cards`

| Colonne | Type MySQL | Contraintes |
|---|---|---|
| `deck_id` | `INT UNSIGNED` | clé étrangère vers `decks` |
| `card_id` | `INT UNSIGNED` | clé étrangère vers `cards` |
| `position` | `TINYINT UNSIGNED` | non nul |

La clé primaire est composée de `deck_id` et `position`.

### `battles`

| Colonne | Type MySQL | Contraintes |
|---|---|---|
| `id` | `BIGINT UNSIGNED` | clé primaire, auto-incrément |
| `player_one_id` | `INT UNSIGNED` | clé étrangère vers `users` |
| `opponent_type` | `ENUM('system','player')` | non nul |
| `player_two_id` | `INT UNSIGNED` | nullable, clé étrangère vers `users` |
| `winner_id` | `INT UNSIGNED` | nullable, clé étrangère vers `users` |
| `status` | `VARCHAR(20)` | non nul |
| `started_at` | `DATETIME` | non nul |
| `ended_at` | `DATETIME` | nullable |

`player_two_id` reste nul pour un combat contre l'application et devient obligatoire lorsque `opponent_type` vaut `player`.

## Règles à confirmer avant le schéma SQL

- nombre de cartes dans un pack ;
- nombre de cartes dans un deck ;
- liste et seuils des raretés ;
- formule exacte du score de rareté ;
- statistiques nécessaires au combat ;
- combat contre un joueur, contre le système ou les deux ;
- conservation ou non de l'historique détaillé des tours.
