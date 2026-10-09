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