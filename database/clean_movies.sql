-- Nettoyage et contrôle des 1 000 films.
-- À exécuter après cinema_mysql.sql et avant rarity.sql.

USE cinema;
SET NAMES utf8mb4;

START TRANSACTION;

-- Suppression des espaces inutiles sans modifier la ponctuation des titres.
UPDATE movies
SET title = REGEXP_REPLACE(TRIM(title), '[[:space:]]+', ' ')
WHERE title <> REGEXP_REPLACE(TRIM(title), '[[:space:]]+', ' ');

-- Format unique pour les listes de genres : une virgule suivie d'un espace.
UPDATE movies
SET genre = REGEXP_REPLACE(TRIM(genre), '[[:space:]]*,[[:space:]]*', ', ')
WHERE genre IS NOT NULL
  AND genre <> REGEXP_REPLACE(TRIM(genre), '[[:space:]]*,[[:space:]]*', ', ');

-- La source contient une année absente pour Apollo 13. La sortie officielle est 1995.
UPDATE movies
SET released_year = 1995
WHERE title = 'Apollo 13'
  AND director = 'Ron Howard'
  AND released_year IS NULL;

COMMIT;

-- Contrôle final. Drishyam 2013 et Drishyam 2015 sont deux films différents.
SELECT
    COUNT(*) AS total_films,
    SUM(title IS NULL OR TRIM(title) = '') AS titres_manquants,
    SUM(title <> TRIM(title)) AS titres_avec_espaces_exterieurs,
    SUM(title REGEXP '[[:space:]]{2,}') AS titres_avec_espaces_multiples,
    SUM(released_year IS NULL) AS annees_manquantes,
    SUM(released_year < 1888 OR released_year > YEAR(CURRENT_DATE)) AS annees_invalides,
    SUM(imdb_rating < 0 OR imdb_rating > 10) AS notes_imdb_invalides,
    SUM(meta_score < 0 OR meta_score > 100) AS metascores_invalides,
    SUM(number_of_votes IS NULL OR number_of_votes = 0) AS votes_manquants_ou_nuls,
    SUM(gross = 0) AS revenus_nuls,
    CASE
        WHEN COUNT(*) = 1000
         AND SUM(title IS NULL OR TRIM(title) = '') = 0
         AND SUM(title <> TRIM(title)) = 0
         AND SUM(title REGEXP '[[:space:]]{2,}') = 0
         AND SUM(released_year IS NULL) = 0
         AND SUM(released_year < 1888 OR released_year > YEAR(CURRENT_DATE)) = 0
         AND SUM(imdb_rating < 0 OR imdb_rating > 10) = 0
         AND SUM(meta_score < 0 OR meta_score > 100) = 0
         AND SUM(number_of_votes IS NULL OR number_of_votes = 0) = 0
         AND SUM(gross = 0) = 0
        THEN 'OK'
        ELSE 'À VÉRIFIER'
    END AS validation_donnees
FROM movies;

SELECT
    LOWER(TRIM(title)) AS titre_normalise,
    released_year,
    COUNT(*) AS occurrences
FROM movies
GROUP BY LOWER(TRIM(title)), released_year
HAVING COUNT(*) > 1;
