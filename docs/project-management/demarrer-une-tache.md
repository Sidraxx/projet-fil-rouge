# Démarrer et terminer une tâche

Ce document décrit le parcours complet d'une tâche de développement ou de documentation.

## 1. Choisir une tâche prête

1. Choisir une Issue prévue dans le sprint.
2. Vérifier la Definition of Ready.
3. S'assigner l'Issue.
4. Placer la carte Trello dans `En cours`.

Une tâche non comprise, non estimée ou bloquée par une décision reste dans le Product Backlog.

## 2. Créer la branche

Mettre à jour `develop` :

```bash
git switch develop
git pull origin develop
```

Créer une branche portant le numéro de l'Issue :

```bash
git switch -c feature/12-pack-opening
```

Utiliser `fix/` pour une correction et `docs/` pour une modification uniquement documentaire.

## 3. Réaliser le travail

- Respecter uniquement le périmètre de l'Issue.
- Faire des commits courts et cohérents.
- Tester sur WAMP.
- Mettre à jour la documentation concernée.
- Ne jamais versionner un mot de passe ou un fichier de configuration local.

Exemple :

```bash
git add public/pages/pack.php src/modules/packs
git commit -m "feat: add pack opening"
```

## 4. Pousser la branche

```bash
git push -u origin feature/12-pack-opening
```

## 5. Ouvrir la Pull Request

- Cibler `develop`.
- Utiliser le modèle de Pull Request.
- Écrire `Refs #12` pour lier l'Issue.
- Décrire les vérifications effectuées.
- Ajouter une capture si l'interface change.
- Placer la carte Trello dans `Relecture et tests`.

## 6. Faire relire et fusionner

Le relecteur :

1. vérifie les critères d'acceptation ;
2. suit la procédure de test ;
3. vérifie la Definition of Done ;
4. demande des corrections ou approuve ;
5. fusionne dans `develop` lorsque tout est valide ;
6. supprime la branche distante ;
7. ferme manuellement l'Issue ;
8. déplace la carte Trello dans `Terminé`.

## 7. Resynchroniser le poste local

```bash
git switch develop
git pull origin develop
git branch -d feature/12-pack-opening
```

La tâche est terminée uniquement après sa fusion, sa vérification et la fermeture de l'Issue.
