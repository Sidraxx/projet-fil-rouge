# Absolute Cinema

Page d'accès à l'espace joueur d'Absolute Cinema, le TCG de cartes de films, avec inscription et connexion reliées à MySQL.

## Lancer en local avec WAMP

1. Démarrer Apache et MySQL dans WAMP.
2. Importer `cinema_mysql.sql` dans phpMyAdmin. Le fichier reste volontairement local et n'est pas suivi par Git.
3. Vérifier que la base s'appelle `bdd_abs_cin` et que l'utilisateur MySQL `root` n'a pas de mot de passe dans `config.php`.
4. Ouvrir [http://localhost/nova_film/projet-fil-rouge/auth.php](http://localhost/nova_film/projet-fil-rouge/auth.php).

Le formulaire d'inscription crée un utilisateur dans la table `users` avec un mot de passe haché. La connexion ouvre une session PHP.