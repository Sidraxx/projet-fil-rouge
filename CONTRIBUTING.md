# Contribuer au projet

## Principes

- Une tâche claire correspond à une Issue GitHub.
- Une branche correspond à une seule Issue.
- Une Pull Request doit rester courte et compréhensible.
- Aucun push direct ne doit être effectué sur `main`.
- Toute modification importante doit être relue par un autre membre.
- Le code fusionné ne doit pas casser le parcours principal du projet.

## Branches permanentes

### `main`

Contient uniquement les versions stables et démontrables.

### `develop`

Contient la prochaine version en cours d'intégration.

## Branches temporaires

Chaque branche est créée depuis `develop`.

| Type | Usage | Exemple |
|---|---|---|
| `feature/` | nouvelle fonctionnalité | `feature/12-pack-opening` |
| `fix/` | correction d'un problème | `fix/27-duplicate-card` |
| `docs/` | documentation uniquement | `docs/8-data-model` |

Le nombre correspond à l'Issue GitHub associée. Le nom utilise des mots courts en anglais, en minuscules, séparés par des tirets.

## Commencer une tâche

1. Choisir une Issue prête dans le sprint.
2. S'assigner l'Issue.
3. Mettre la carte Trello dans `En cours`.
4. Mettre à jour `develop` localement.
5. Créer la branche depuis `develop`.

```bash
git switch develop
git pull origin develop
git switch -c feature/12-pack-opening
```

Après le premier commit :

```bash
git push -u origin feature/12-pack-opening
```

La Pull Request cible `develop`. La carte Trello passe alors dans `Relecture et tests`.

## Commits

Format :

```text
type: description courte
```

Types utilisés :

| Type | Usage |
|---|---|
| `feat` | ajout d'une fonctionnalité |
| `fix` | correction d'un problème |
| `docs` | documentation uniquement |
| `test` | ajout ou modification de tests |
| `refactor` | réorganisation sans changement fonctionnel |
| `chore` | configuration ou maintenance |

Exemples :

```text
feat: add pack opening page
fix: prevent negative card quantity
docs: describe MySQL setup
chore: add pull request template
```

Un commit doit représenter une modification cohérente. Il ne faut pas mélanger une fonctionnalité, une correction indépendante et une réorganisation générale dans le même commit.

## Pull Request

Une Pull Request de fonctionnalité ou de correction cible `develop`.

Avant de l'ouvrir :

- relire les fichiers modifiés ;
- tester le scénario concerné ;
- vérifier la syntaxe PHP ;
- vérifier qu'aucun mot de passe ni fichier local n'est inclus ;
- mettre la branche à jour si `develop` a évolué.

La Pull Request doit contenir :

- un titre explicite ;
- le lien vers l'Issue avec `Refs #numero` ;
- un résumé des modifications ;
- une procédure de test ;
- une capture d'écran si l'interface change ;
- les limites ou travaux restants.

Une autre personne relit la Pull Request avant la fusion. La branche est supprimée après fusion.

Comme la Pull Request cible `develop` et non la branche par défaut `main`, l'Issue n'est pas fermée automatiquement. Après validation et fusion, le relecteur ferme manuellement l'Issue, déplace la carte Trello dans `Terminé`, puis l'auteur resynchronise son dépôt :

```bash
git switch develop
git pull origin develop
```

## Passage vers `main`

Quand `develop` contient une version complète et démontrable :

1. ouvrir une Pull Request de `develop` vers `main` ;
2. exécuter le parcours principal complet ;
3. vérifier la documentation d'installation ;
4. faire relire la Pull Request ;
5. fusionner puis créer un tag de version.

Exemple :

```text
v0.1.0
```

## Correction urgente

Avant la première mise en production réelle, les corrections passent également par `develop`. Une branche `hotfix/` ne sera ajoutée que si une version déployée sur `main` doit être corrigée immédiatement.

## Gestion des conflits

- Prévenir les autres membres avant de modifier un fichier déjà pris en charge.
- Mettre régulièrement sa branche à jour avec `develop`.
- Résoudre les conflits sur la branche de travail, jamais directement sur `main`.
- Demander une relecture si la résolution modifie le travail d'un autre membre.

## Definition of Done technique

Une tâche technique est terminée lorsque :

- les critères d'acceptation de l'Issue sont respectés ;
- le code fonctionne sur WAMP ;
- les entrées utilisateur sont validées ;
- les requêtes SQL utilisent PDO et des paramètres ;
- la syntaxe PHP est valide ;
- les tests manuels ou automatisés nécessaires passent ;
- la documentation concernée est à jour ;
- la Pull Request est relue et fusionnée.
