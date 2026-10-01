# Registre des risques

## 1. Échelle

- Probabilité : faible, moyenne ou forte.
- Impact : faible, moyen ou fort.
- Un risque fort ou bloquant est examiné à chaque Sprint Planning et Sprint Review.

## 2. Risques identifiés

| ID | Risque | Probabilité | Impact | Prévention | Réponse si le risque arrive | Responsable de suivi |
|---|---|---|---|---|---|---|
| R-01 | Périmètre trop large pour le semestre 3 | Forte | Fort | Protéger le MVP et placer les idées secondaires après le MVP | Retirer les fonctions P2/P3 et simplifier le combat | Product Owner, à attribuer |
| R-02 | Règles de combat définies trop tard | Moyenne | Fort | Fixer une date limite au début du Sprint 3 et préparer une règle de secours | Utiliser le combat simplifié de secours | Responsable combat, à attribuer |
| R-03 | Dataset incomplet, incohérent ou difficile à importer | Forte | Fort | Auditer un échantillon dès le Sprint 0 et documenter les champs manquants | Réduire le dataset et utiliser des valeurs neutres documentées | Antonio proposé |
| R-04 | Formule de rareté déséquilibrée | Moyenne | Moyen | Tester la distribution des cartes avant le tirage des packs | Ajuster les seuils sans modifier les données sources | Antonio proposé |
| R-05 | Environnements WAMP différents entre les membres | Moyenne | Fort | Documenter les versions de PHP et MySQL et tester l'installation sur plusieurs postes | Aligner les versions et fournir une procédure de correction | Hugo |
| R-06 | Conflits Git ou travail perdu | Moyenne | Fort | Branches courtes, Pull Requests et mises à jour régulières depuis `develop` | Résoudre le conflit à deux et restaurer depuis Git si nécessaire | Hugo |
| R-07 | Accès non sécurisé aux comptes ou données | Moyenne | Fort | Sessions contrôlées, mots de passe hachés, requêtes préparées et validation serveur | Bloquer la livraison concernée et corriger avant démonstration | Toute l'équipe |
| R-08 | Une fonctionnalité marche seulement sur le poste de son auteur | Moyenne | Fort | Relecture et test croisé avant fusion | Reproduire sur un second poste et corriger la configuration | Auteur et relecteur |
| R-09 | Mauvaise répartition du travail dans l'équipe | Moyenne | Fort | Responsables clairs, tâches petites et limite d'une tâche principale en cours | Réattribuer pendant le Daily Scrum | Scrum Master, à attribuer |
| R-10 | Retard non visible jusqu'au jalon | Moyenne | Fort | Daily Scrum, burndown simple et démonstration à chaque Review | Replanifier selon le chemin critique | Scrum Master, à attribuer |
| R-11 | Absence d'un membre proche d'un jalon | Faible à moyenne | Fort | Documentation continue et au moins une personne de soutien par domaine | Réduire le périmètre et transférer la tâche prioritaire | Toute l'équipe |
| R-12 | Images ou données de films non utilisables pour la soutenance | Moyenne | Moyen | Vérifier la source et prévoir des visuels autorisés ou de remplacement | Utiliser un échantillon dont la provenance est documentée | Product Owner, à attribuer |
| R-13 | Perte ou corruption de la base de démonstration | Faible | Fort | Script de création et de données de démonstration versionné | Recréer la base depuis les scripts | Responsable base, proposé Antonio |
| R-14 | Démonstration impossible le jour de la soutenance | Faible | Fort | Répétition, sauvegarde locale, captures et scénario de secours | Présenter sur un second poste ou utiliser la sauvegarde prévue | Toute l'équipe |
| R-15 | Ajout de fonctionnalités après le gel du MVP | Moyenne | Moyen | Gel fonctionnel le 4 décembre | Annuler ou reporter la modification | Product Owner, à attribuer |

## 3. Suivi des risques

À chaque Sprint Review, l'équipe doit :

1. vérifier si un risque a changé de probabilité ou d'impact ;
2. fermer les risques qui ne sont plus possibles ;
3. ajouter les nouveaux risques observés ;
4. transformer en tâche toute action de prévention nécessaire au sprint suivant.

Un risque devenu réel n'est plus seulement un risque : il devient un problème ou un blocage et doit être ajouté au Sprint Backlog avec un responsable.
