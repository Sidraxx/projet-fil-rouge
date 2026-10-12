# Données des films

Ce document regroupe les décisions et les contrôles correspondant aux cartes Données #29 à #36.

## 1. Fichier source de référence

Le fichier utilisé par le projet est `cinema_mysql.sql`. Il crée la base MySQL `cinema` et importe exactement 1 000 films.

Son en-tête indique qu'il a été généré à partir de `imdb_top_1000.csv`. Les noms de colonnes et le contenu correspondent au jeu de données Kaggle [IMDb Dataset of Top 1000 Movies and TV Shows](https://www.kaggle.com/datasets/harshitshankhdhar/imdb-dataset-of-top-1000-movies-and-tv-shows). Le fichier CSV original et sa licence ne sont toutefois pas présents dans le dépôt.

IMDb autorise certains usages personnels et non commerciaux de ses jeux de données sous conditions. Le projet scolaire doit rester non commercial tant que les droits exacts du fichier Kaggle et des affiches distantes n'ont pas été validés. Les liens d'affiches pointent vers `m.media-amazon.com` ; le jeu de données ne prouve pas un droit de redistribution des images.

Références :

- [IMDb : utilisation des données dans un logiciel](https://help.imdb.com/article/imdb/general-information/can-i-use-imdb-data-in-my-software/G5JTRESSHJBBHTGX)
- [Jeux de données IMDb pour usage non commercial](https://www.imdb.com/interfaces/)

## 2. Colonnes disponibles

| Colonne | Type MySQL | Valeurs manquantes | Exemple | Utilisation |
|---|---|---:|---|---|
| `id` | `INT UNSIGNED` | 0 | `1` | Identifiant interne |
| `poster_link` | `VARCHAR(1000)` | 0 | URL de l'affiche | Affichage |
| `title` | `VARCHAR(255)` | 0 | `The Shawshank Redemption` | Carte et recherche |
| `released_year` | `YEAR` | 0 après correction | `1994` | Information du film |
| `certificate` | `VARCHAR(20)` | 101 | `A` | Information facultative |
| `runtime_minutes` | `SMALLINT UNSIGNED` | 0 | `142` | Information du film |
| `genre` | `VARCHAR(255)` | 0 | `Drama` | Affichage et gameplay |
| `imdb_rating` | `DECIMAL(3,1)` | 0 | `9.3` | Calcul de rareté |
| `overview` | `TEXT` | 0 | Synopsis en anglais | Fiche détaillée |
| `meta_score` | `DECIMAL(5,2)` | 157 | `80.00` | Calcul de rareté |
| `director` | `VARCHAR(255)` | 0 | `Frank Darabont` | Fiche détaillée |
| `star1` | `VARCHAR(255)` | 0 | `Tim Robbins` | Fiche détaillée |
| `star2` | `VARCHAR(255)` | 0 | `Morgan Freeman` | Fiche détaillée |
| `star3` | `VARCHAR(255)` | 0 | `Bob Gunton` | Fiche détaillée |
| `star4` | `VARCHAR(255)` | 0 | `William Sadler` | Fiche détaillée |
| `number_of_votes` | `INT UNSIGNED` | 0 | `2343110` | Calcul de rareté |
| `gross` | `BIGINT UNSIGNED` | 169 | `28341469` | Calcul de rareté |
| `rarity_score` | `DECIMAL(5,2)` | 0 après calcul | `90.64` | Classement interne |
| `rarity_level` | `ENUM` | 0 après calcul | `Légendaire` | Affichage et packs |
| `created_at` | `TIMESTAMP` | 0 | Date d'import | Suivi technique |

## 3. Valeurs manquantes

Le contrôle porte sur les 1 000 films.

| Champ | Valeurs manquantes | Traitement retenu |
|---|---:|---|
| `certificate` | 101 | Valeur facultative, affichée comme non renseignée |
| `meta_score` | 157 | Médiane 79 pendant le calcul de rareté |
| `gross` | 169 | Médiane 23 530 892 pendant le calcul de rareté |
| `released_year` | 1 avant nettoyage | `Apollo 13` corrigé à 1995 |
| Toutes les autres colonnes métier | 0 | Aucun remplacement nécessaire |

La correction de l'année d'`Apollo 13` est confirmée par sa [fiche IMDb](https://www.imdb.com/title/tt0112384/).

## 4. Films en double

La clé de contrôle est le titre en minuscules sans espaces extérieurs, associé à l'année de sortie :

```text
LOWER(TRIM(title)) + released_year
```

Aucun doublon n'existe avec cette clé. Le titre `Drishyam` apparaît deux fois, mais les films datent de 2013 et 2015. Les deux lignes sont donc conservées.

## 5. Nettoyage des titres

Le contrôle initial ne trouve aucun espace extérieur, espace multiple, caractère de contrôle ou caractère de remplacement. Les trois titres contenant `?` sont valides :

- `What Ever Happened to Baby Jane?`
- `Who's Afraid of Virginia Woolf?`
- `O Brother, Where Art Thou?`

Le script `database/clean_movies.sql` applique tout de même une normalisation reproductible des espaces et conserve la ponctuation.

## 6. Genres retenus

Les genres utilisent une majuscule initiale et le séparateur `, `. Les 21 valeurs officielles présentes dans la base sont :

| Genre | Films | Genre | Films | Genre | Films |
|---|---:|---|---:|---|---:|
| Action | 189 | Adventure | 196 | Animation | 82 |
| Biography | 109 | Comedy | 233 | Crime | 209 |
| Drama | 724 | Family | 56 | Fantasy | 66 |
| Film-Noir | 19 | History | 56 | Horror | 32 |
| Music | 35 | Musical | 17 | Mystery | 99 |
| Romance | 125 | Sci-Fi | 67 | Sport | 19 |
| Thriller | 137 | War | 51 | Western | 20 |

Aucune variante de casse ou d'orthographe n'a été détectée. Les films peuvent avoir plusieurs genres, donc le total des occurrences dépasse 1 000.

## 7. Revenus, notes et autres valeurs numériques

Les valeurs sont déjà enregistrées dans des colonnes numériques MySQL. Aucun symbole monétaire ni texte ne subsiste dans `gross`, `imdb_rating`, `meta_score` ou `number_of_votes`.

| Champ | Minimum | Maximum | Valeurs impossibles |
|---|---:|---:|---:|
| Année | 1920 | 2020 | 0 après correction |
| Durée en minutes | 45 | 321 | 0 |
| Note IMDb | 7,6 | 9,3 | 0 |
| Metascore | 28 | 100 | 0 |
| Nombre de votes | 25 088 | 2 343 110 | 0 |
| Revenus bruts | 1 305 | 936 662 225 | 0 parmi les valeurs présentes |

## 8. Échantillon de vingt films

Le fichier `data/echantillon_20_films.xlsx` contient un échantillon reproductible de vingt films. Il conserve les champs nécessaires aux contrôles de données et couvre les cinq niveaux de rareté : 9 communes, 6 peu communes, 3 rares, 1 épique et 1 légendaire.

## Ordre d'exécution

1. Exécuter `cinema_mysql.sql`.
2. Exécuter `database/clean_movies.sql`.
3. Exécuter `database/rarity.sql`.
4. Vérifier que les deux scripts affichent `OK`.
