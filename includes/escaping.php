<?php

declare(strict_types=1);

/**
 * Échappement des données affichées dans les pages.
 *
 * Toute valeur dynamique (saisie utilisateur, colonne de base de données,
 * message d'erreur…) doit passer par e() avant d'être écrite dans le HTML.
 * Les guillemets sont échappés : la fonction convient donc aussi bien au
 * contenu texte qu'aux valeurs d'attributs.
 *
 * ENT_SUBSTITUTE évite qu'une chaîne UTF-8 invalide rende une valeur vide :
 * les octets incorrects sont remplacés par U+FFFD.
 */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
