# Definition of Ready et Definition of Done

## 1. Definition of Ready

Une user story peut entrer dans un sprint si toutes les conditions suivantes sont remplies :

- le besoin utilisateur est formulé clairement ;
- la priorité est connue ;
- les critères d'acceptation sont écrits et vérifiables ;
- les maquettes ou règles nécessaires sont disponibles ;
- les dépendances sont identifiées ;
- les données nécessaires sont accessibles ;
- l'équipe comprend le résultat attendu ;
- l'élément a été estimé ;
- l'élément est assez petit pour être terminé dans le sprint ;
- aucun choix majeur non résolu n'empêche de commencer.

Si une condition manque, l'élément reste dans le Product Backlog et doit être précisé pendant le refinement.

## 2. Definition of Done générale

Un élément est terminé si toutes les conditions suivantes sont remplies :

- les critères d'acceptation sont respectés ;
- le code fonctionne sur l'environnement WAMP retenu ;
- le code ne contient pas d'identifiant ou de mot de passe personnel ;
- les entrées utilisateur sont validées côté serveur ;
- les requêtes MySQL utilisant des valeurs externes sont préparées ;
- aucune erreur PHP visible ne bloque le parcours ;
- les cas normaux et les principales erreurs ont été testés ;
- le code a été relu par un autre membre ;
- la Pull Request est fusionnée dans la branche prévue ;
- la documentation utile est mise à jour ;
- aucune régression connue ne touche le parcours principal ;
- une démonstration rapide est possible.

## 3. Conditions supplémentaires pour la base de données

- le schéma ou la migration est versionné ;
- la modification est reproductible sur une base vide ;
- les clés primaires, étrangères et contraintes utiles sont définies ;
- aucune donnée personnelle réelle n'est nécessaire aux tests ;
- une procédure de retour ou de correction est connue.

## 4. Conditions supplémentaires pour une interface

- les états vide, normal et erreur sont prévus ;
- les libellés sont compréhensibles ;
- la navigation reste cohérente ;
- l'affichage est vérifié sur le navigateur utilisé pour la soutenance ;
- les images manquantes n'empêchent pas l'utilisation de la page.

## 5. Conditions supplémentaires pour une livraison de jalon

- la branche de livraison est stable ;
- l'installation a été testée à partir de la documentation ;
- la base de démonstration peut être recréée ;
- les comptes de démonstration sont prêts ;
- le scénario de présentation a été répété ;
- les défauts connus sont écrits et ne bloquent pas le parcours présenté.

## 6. Ce qui n'est pas considéré comme terminé

Un élément n'est pas terminé si :

- il fonctionne uniquement sur le poste de son auteur ;
- il est développé mais non testé ;
- il attend encore une relecture ;
- il contient une partie essentielle simulée sans que cela soit prévu ;
- ses critères d'acceptation ont changé sans mise à jour de la carte ;
- il crée une régression connue dans le MVP.
