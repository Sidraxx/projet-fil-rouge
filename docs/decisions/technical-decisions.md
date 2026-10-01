# Décisions techniques

Ce document conserve les choix importants du projet. Une décision peut être modifiée, mais sa nouvelle version doit être expliquée dans une Pull Request.

## 1. Technologies sans framework

Décision : utiliser HTML, CSS, JavaScript, PHP et MySQL sans framework.

Raison : ces technologies sont maîtrisées par l'équipe et respectent la règle pédagogique de 80 % de connaissances acquises et 20 % de découverte.

Conséquence : l'équipe doit définir elle-même une organisation claire des fichiers et des fonctions communes.

## 2. Environnement WAMP

Décision : utiliser WAMP comme environnement local commun.

Raison : WAMP regroupe Apache, PHP, MySQL et phpMyAdmin dans l'environnement déjà utilisé par l'équipe.

Conséquence : chaque membre doit vérifier sa version de PHP et importer les mêmes fichiers SQL.

## 3. Application PHP unique

Décision : construire une seule application PHP, sans séparer le frontend et le backend en deux projets.

Raison : PHP génère les pages HTML et traite les actions côté serveur. Une séparation en deux applications ajouterait une API et des concepts inutiles au MVP.

Conséquence : `public/` contient les pages accessibles et `src/` contient le code interne.

## 4. Accès MySQL avec PDO

Décision : utiliser PDO et des requêtes préparées.

Raison : PDO est inclus avec PHP, centralise la connexion et protège les requêtes contre les injections SQL lorsqu'il est correctement utilisé.

Conséquence : aucune saisie utilisateur ne doit être concaténée dans une requête SQL.

## 5. Configuration locale non versionnée

Décision : conserver les identifiants MySQL dans `src/config/database.local.php`, ignoré par Git.

Raison : les paramètres varient entre les machines et peuvent contenir un mot de passe.

Conséquence : un exemple sans secret devra être fourni avec le code de connexion.

## 6. Modèle de données séparant films et cartes

Décision : stocker les informations culturelles dans `movies` et les propriétés de jeu dans `cards`.

Raison : la note ou les revenus décrivent un film, tandis que la rareté, l'attaque et la défense décrivent sa carte.

Conséquence : les règles de jeu peuvent évoluer sans dupliquer les informations du film.

## 7. Données sources conservées séparément

Décision : séparer les données brutes, les données traitées et les petits échantillons.

Raison : les données originales restent vérifiables et les traitements peuvent être reproduits.

Conséquence : un fichier brut n'est jamais corrigé manuellement ; une nouvelle version traitée est générée.

## 8. Workflow Git simplifié inspiré de GitFlow

Décision : conserver `main` et `develop` comme branches permanentes, avec des branches courtes `feature/`, `fix/` et `docs/`.

Raison : l'équipe travaille à trois et doit pouvoir intégrer, relire et présenter une version stable sans utiliser un processus trop lourd.

Conséquence : les développements partent de `develop` et reviennent dans `develop` par Pull Request. `main` reçoit uniquement les versions démontrables.

## 9. Contrôle automatique limité à la syntaxe PHP au départ

Décision : vérifier automatiquement la syntaxe de tous les fichiers PHP sur GitHub.

Raison : le projet ne possède pas encore de dépendances ni de suite de tests automatisés.

Conséquence : le workflow pourra être complété plus tard par des tests, sans bloquer la fondation du projet.

## 10. Versions et navigateurs de référence

Décision : utiliser PHP 8.2.29 et MySQL 8.4.7 sous WAMP. Le parcours principal sera vérifié sur les versions de bureau de Google Chrome et Microsoft Edge disponibles sur les postes de l'équipe.

Raison : des versions communes réduisent les différences de comportement entre les postes et les navigateurs retenus couvrent l'environnement de développement et de soutenance.

Conséquence : une incompatibilité observée sur un autre environnement est enregistrée, mais ne bloque pas le MVP tant qu'elle ne touche pas les navigateurs de référence.
