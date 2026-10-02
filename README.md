# Nova Film

Page d'accès à l'espace personnel de Nova Film, avec inscription et connexion reliées à MySQL.

## Lancer en local avec WAMP

1. Démarrer Apache et MySQL dans WAMP.
2. Importer `cinema_mysql.sql` dans phpMyAdmin. Le fichier reste volontairement local et n'est pas suivi par Git.
3. Vérifier que la base s'appelle `cinema` et que l'utilisateur MySQL `root` n'a pas de mot de passe dans `config.php`.
4. Ouvrir [http://localhost/nova_film/projet-fil-rouge/](http://localhost/nova_film/projet-fil-rouge/).

Le formulaire d'inscription crée un utilisateur dans la table `users` avec un mot de passe haché. La connexion ouvre une session PHP.