-- Calcul de la rareté des cartes à partir des données des films.
-- À exécuter après cinema_mysql.sql dans MySQL 8.

USE cinema;
SET NAMES utf8mb4;

-- Ajout des colonnes si la base a été importée avec une ancienne version.
SET @add_score_sql = IF(
    EXISTS(
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'movies'
          AND COLUMN_NAME = 'rarity_score'
    ),
    'DO 0',
    'ALTER TABLE movies ADD COLUMN rarity_score DECIMAL(5,2) NULL AFTER gross'
);
PREPARE add_score_statement FROM @add_score_sql;
EXECUTE add_score_statement;
DEALLOCATE PREPARE add_score_statement;

SET @add_level_sql = IF(
    EXISTS(
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'movies'
          AND COLUMN_NAME = 'rarity_level'
    ),
    'DO 0',
    'ALTER TABLE movies ADD COLUMN rarity_level ENUM(''Commune'', ''Peu commune'', ''Rare'', ''Épique'', ''Légendaire'') NULL AFTER rarity_score'
);
PREPARE add_level_statement FROM @add_level_sql;
EXECUTE add_level_statement;
DEALLOCATE PREPARE add_level_statement;

-- Contrôle des quatre colonnes utilisées par la formule.
SELECT
    COUNT(*) AS total_films,
    SUM(imdb_rating IS NULL) AS imdb_manquants,
    SUM(meta_score IS NULL) AS metascores_manquants,
    SUM(number_of_votes IS NULL OR number_of_votes = 0) AS votes_manquants_ou_nuls,
    SUM(gross IS NULL OR gross = 0) AS revenus_manquants_ou_nuls
FROM movies;

-- Médianes : elles remplacent uniquement les valeurs absentes pendant le calcul.
SET @imdb_median = (
    SELECT AVG(value)
    FROM (
        SELECT
            imdb_rating AS value,
            ROW_NUMBER() OVER (ORDER BY imdb_rating) AS row_number_value,
            COUNT(*) OVER () AS row_count
        FROM movies
        WHERE imdb_rating IS NOT NULL
    ) AS ordered_values
    WHERE row_number_value IN (
        FLOOR((row_count + 1) / 2),
        FLOOR((row_count + 2) / 2)
    )
);

SET @metascore_median = (
    SELECT AVG(value)
    FROM (
        SELECT
            meta_score AS value,
            ROW_NUMBER() OVER (ORDER BY meta_score) AS row_number_value,
            COUNT(*) OVER () AS row_count
        FROM movies
        WHERE meta_score IS NOT NULL
    ) AS ordered_values
    WHERE row_number_value IN (
        FLOOR((row_count + 1) / 2),
        FLOOR((row_count + 2) / 2)
    )
);

SET @votes_median = (
    SELECT AVG(value)
    FROM (
        SELECT
            number_of_votes AS value,
            ROW_NUMBER() OVER (ORDER BY number_of_votes) AS row_number_value,
            COUNT(*) OVER () AS row_count
        FROM movies
        WHERE number_of_votes IS NOT NULL
          AND number_of_votes > 0
    ) AS ordered_values
    WHERE row_number_value IN (
        FLOOR((row_count + 1) / 2),
        FLOOR((row_count + 2) / 2)
    )
);

SET @gross_median = (
    SELECT AVG(value)
    FROM (
        SELECT
            gross AS value,
            ROW_NUMBER() OVER (ORDER BY gross) AS row_number_value,
            COUNT(*) OVER () AS row_count
        FROM movies
        WHERE gross IS NOT NULL
          AND gross > 0
    ) AS ordered_values
    WHERE row_number_value IN (
        FLOOR((row_count + 1) / 2),
        FLOOR((row_count + 2) / 2)
    )
);

-- Bornes utilisées pour ramener chaque critère entre 0 et 1.
SELECT
    MIN(imdb_rating), MAX(imdb_rating),
    MIN(meta_score), MAX(meta_score),
    MIN(NULLIF(number_of_votes, 0)), MAX(number_of_votes),
    MIN(NULLIF(gross, 0)), MAX(gross)
INTO
    @imdb_min, @imdb_max,
    @metascore_min, @metascore_max,
    @votes_min, @votes_max,
    @gross_min, @gross_max
FROM movies;

START TRANSACTION;

-- Le score complet est conservé temporairement pour attribuer la rareté avant
-- l'arrondi à deux décimales enregistré dans la table movies.
DROP TEMPORARY TABLE IF EXISTS movie_rarity_calculations;
CREATE TEMPORARY TABLE movie_rarity_calculations AS
SELECT
    id AS movie_id,
    100 * (
        0.35 * LEAST(1, GREATEST(0,
            (COALESCE(imdb_rating, @imdb_median) - @imdb_min)
            / NULLIF(@imdb_max - @imdb_min, 0)
        ))
        + 0.30 * LEAST(1, GREATEST(0,
            (LOG10(COALESCE(NULLIF(number_of_votes, 0), @votes_median)) - LOG10(@votes_min))
            / NULLIF(LOG10(@votes_max) - LOG10(@votes_min), 0)
        ))
        + 0.20 * LEAST(1, GREATEST(0,
            (LOG10(COALESCE(NULLIF(gross, 0), @gross_median)) - LOG10(@gross_min))
            / NULLIF(LOG10(@gross_max) - LOG10(@gross_min), 0)
        ))
        + 0.15 * LEAST(1, GREATEST(0,
            (COALESCE(meta_score, @metascore_median) - @metascore_min)
            / NULLIF(@metascore_max - @metascore_min, 0)
        ))
    ) AS raw_rarity_score
FROM movies;

-- Enregistrement du score et attribution avec les seuils fixes de la version 1.
UPDATE movies AS movie
INNER JOIN movie_rarity_calculations AS calculation
    ON calculation.movie_id = movie.id
SET
    movie.rarity_score = ROUND(calculation.raw_rarity_score, 2),
    movie.rarity_level = CASE
    WHEN calculation.raw_rarity_score < 39.79 THEN 'Commune'
    WHEN calculation.raw_rarity_score < 50.56 THEN 'Peu commune'
    -- 60.3842 est la borne interne ; elle s'affiche 60,38 dans la documentation.
    WHEN calculation.raw_rarity_score < 60.3842 THEN 'Rare'
    WHEN calculation.raw_rarity_score < 74.76 THEN 'Épique'
    ELSE 'Légendaire'
END;

DROP TEMPORARY TABLE movie_rarity_calculations;

COMMIT;

-- Valeurs de référence attendues pour la base actuelle.
SELECT
    ROUND(@imdb_median, 2) AS mediane_imdb,
    ROUND(@metascore_median, 2) AS mediane_metascore,
    ROUND(@votes_median, 2) AS mediane_votes,
    ROUND(@gross_median, 2) AS mediane_revenus;

SELECT
    rarity_level,
    COUNT(*) AS nombre_de_films,
    ROUND(COUNT(*) * 100 / (SELECT COUNT(*) FROM movies), 2) AS pourcentage
FROM movies
GROUP BY rarity_level
ORDER BY FIELD(rarity_level, 'Commune', 'Peu commune', 'Rare', 'Épique', 'Légendaire');

SELECT
    COUNT(*) AS total_films,
    SUM(rarity_score IS NOT NULL) AS films_avec_score,
    SUM(rarity_level IS NOT NULL) AS films_avec_rarete,
    CASE
        WHEN COUNT(*) = 1000
         AND SUM(rarity_score IS NOT NULL) = 1000
         AND SUM(rarity_level = 'Commune') = 450
         AND SUM(rarity_level = 'Peu commune') = 300
         AND SUM(rarity_level = 'Rare') = 150
         AND SUM(rarity_level = 'Épique') = 80
         AND SUM(rarity_level = 'Légendaire') = 20
        THEN 'OK'
        ELSE 'À VÉRIFIER'
    END AS validation_rarete
FROM movies;
