# MCD et MLD - Absolute Cinema

Ce document est derive du schema local `bdd_abs_cin.sql`. Le fichier SQL et ses donnees restent locaux et ne sont pas necessaires pour lire ces modeles.

## MCD

Le modele conceptuel represente les objets metier et leurs associations principales.

```mermaid
erDiagram
    UTILISATEUR ||--o{ CARTE_UTILISATEUR : possede
    FILM ||--o{ CARTE_UTILISATEUR : represente
    UTILISATEUR ||--o{ DECK : cree
    DECK ||--o{ COMPOSITION_DECK : contient
    CARTE_UTILISATEUR ||--o{ COMPOSITION_DECK : est_selectionnee
    UTILISATEUR ||--o{ OUVERTURE_PACK : effectue
    TYPE_PACK ||--o{ OUVERTURE_PACK : definit
    OUVERTURE_PACK ||--o{ CARTE_OUVERTE : produit
    CARTE_UTILISATEUR ||--o| CARTE_OUVERTE : provient_de
    UTILISATEUR ||--o{ TRANSACTION_MONNAIE : genere
    COMBAT ||--o{ PARTICIPATION_COMBAT : accueille
    UTILISATEUR o|--o{ PARTICIPATION_COMBAT : joue
    DECK o|--o{ PARTICIPATION_COMBAT : utilise
    UTILISATEUR o|--o{ COMBAT : gagne
    COMBAT ||--o{ ACTION_COMBAT : contient
    UTILISATEUR o|--o{ ACTION_COMBAT : realise
    CARTE_UTILISATEUR o|--o{ ACTION_COMBAT : joue

    UTILISATEUR {
        int id
        string username
        string email
        int currency_balance
        boolean tutorial_completed
    }
    FILM {
        int id
        string title
        int released_year
        decimal imdb_rating
    }
    CARTE_UTILISATEUR {
        bigint id
        enum rarity
        smallint attack
        smallint defense
    }
    DECK {
        int id
        string name
        boolean is_active
    }
    TYPE_PACK {
        int id
        string name
        int price
        int cards_count
    }
    OUVERTURE_PACK {
        bigint id
        int price_paid
        datetime opened_at
    }
    COMBAT {
        bigint id
        enum mode
        enum status
        datetime started_at
        datetime ended_at
    }
    PARTICIPATION_COMBAT {
        enum side
        boolean is_ai
        int starting_hp
        int remaining_hp
        enum result
    }
    ACTION_COMBAT {
        bigint id
        int turn_number
        enum action_type
        json action_details
    }
    TRANSACTION_MONNAIE {
        bigint id
        int amount
        enum transaction_type
    }
```

### Regles metier principales

- Un utilisateur peut posseder plusieurs exemplaires d'un meme film.
- Un deck appartient a un seul utilisateur et reference des exemplaires precis de cartes.
- Une carte ouverte provient d'au plus une ouverture de pack.
- Un combat peut opposer un utilisateur a un autre joueur ou a l'IA.
- Un combat possede deux participants identifies par `player1` et `player2`.
- Une transaction de monnaie appartient toujours a un utilisateur.

## MLD

Le modele logique relationnel reprend les tables, les cles et les cardinalites implementees en MySQL.

```mermaid
erDiagram
    users ||--o{ user_cards : user_id
    movies ||--o{ user_cards : movie_id
    users ||--o{ decks : user_id
    decks ||--o{ deck_cards : deck_id
    user_cards ||--o{ deck_cards : user_card_id
    users ||--o{ pack_openings : user_id
    pack_types ||--o{ pack_openings : pack_type_id
    pack_openings ||--o{ pack_opening_cards : pack_opening_id
    user_cards ||--o| pack_opening_cards : user_card_id
    users ||--o{ currency_transactions : user_id
    users ||--o{ battles : winner_user_id
    battles ||--o{ battle_players : battle_id
    users ||--o{ battle_players : user_id
    decks ||--o{ battle_players : deck_id
    battles ||--o{ battle_actions : battle_id
    users ||--o{ battle_actions : user_id
    user_cards ||--o{ battle_actions : user_card_id

    users {
        INT id PK
        VARCHAR username UK
        VARCHAR email UK
        VARCHAR password_hash
        ENUM role
        INT currency_balance
        BOOLEAN tutorial_completed
        TIMESTAMP created_at
    }
    movies {
        INT id PK
        VARCHAR title
        YEAR released_year
        VARCHAR genre
        DECIMAL imdb_rating
        VARCHAR director
    }
    user_cards {
        BIGINT id PK
        INT user_id FK
        INT movie_id FK
        ENUM rarity
        SMALLINT attack
        SMALLINT defense
        ENUM obtained_from
    }
    decks {
        INT id PK
        INT user_id FK
        VARCHAR name
        BOOLEAN is_active
    }
    deck_cards {
        INT deck_id PK, FK
        BIGINT user_card_id PK, FK
        TINYINT position UK
    }
    pack_types {
        INT id PK
        VARCHAR name UK
        INT price
        TINYINT cards_count
        BOOLEAN is_active
    }
    pack_openings {
        BIGINT id PK
        INT user_id FK
        INT pack_type_id FK
        INT price_paid
        TIMESTAMP opened_at
    }
    pack_opening_cards {
        BIGINT pack_opening_id PK, FK
        BIGINT user_card_id PK, FK
    }
    currency_transactions {
        BIGINT id PK
        INT user_id FK
        INT amount
        ENUM transaction_type
        VARCHAR description
    }
    battles {
        BIGINT id PK
        ENUM mode
        ENUM status
        INT winner_user_id FK
        TIMESTAMP started_at
        TIMESTAMP ended_at
    }
    battle_players {
        BIGINT battle_id PK, FK
        INT user_id FK
        INT deck_id FK
        ENUM side PK
        BOOLEAN is_ai
        SMALLINT remaining_hp
        ENUM result
    }
    battle_actions {
        BIGINT id PK
        BIGINT battle_id FK
        INT turn_number
        INT user_id FK
        BIGINT user_card_id FK
        ENUM action_type
        JSON action_details
    }
```

### Tables

| Table | Cle primaire | Cles et contraintes principales |
| --- | --- | --- |
| `movies` | `id` | Index sur titre, annee et note IMDb |
| `users` | `id` | `username` et `email` uniques |
| `user_cards` | `id` | FK vers `users` et `movies`; doublons de cartes autorises |
| `decks` | `id` | FK vers `users` |
| `deck_cards` | `deck_id`, `user_card_id` | FK vers `decks` et `user_cards`; position unique dans un deck |
| `pack_types` | `id` | `name` unique |
| `pack_openings` | `id` | FK vers `users` et `pack_types` |
| `pack_opening_cards` | `pack_opening_id`, `user_card_id` | Une carte ne peut provenir que d'une ouverture |
| `currency_transactions` | `id` | FK vers `users`; montant signe pour gains et depenses |
| `battles` | `id` | FK optionnelle vers le gagnant `users.id` |
| `battle_players` | `battle_id`, `side` | FK vers `battles`, `users` et `decks`; deux camps par combat |
| `battle_actions` | `id` | FK vers `battles`, `users` et `user_cards` |

## Correspondance des cardinalites

- `users 1,n user_cards`, `movies 1,n user_cards`
- `users 1,n decks`, `decks n,n user_cards` via `deck_cards`
- `users 1,n pack_openings`, `pack_types 1,n pack_openings`
- `pack_openings n,n user_cards` via `pack_opening_cards`, avec une carte liee a une seule ouverture au maximum
- `users 1,n currency_transactions`
- `battles 1,n battle_players` et `battles 1,n battle_actions`