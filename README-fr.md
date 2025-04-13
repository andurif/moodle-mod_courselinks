Mod Courselinks
==================================
Ce module permet de faire des liens entre plusieurs cours. Ces liens seront disponibles tel une activité étiquette.

But
------------
Le but principal de ce module est de faire afficher sur un cours des liens vers d'autres cours pour faciliter la navigation entre ceux-ci.

Pré-requis
------------
- Moodle en version 3.7 à 4.5.<br/>
  -> Tests toujours en cours.<br/>
- Utilisation du thème Boost ou d'un thème qui étend le thème Boost (qui utilise bootstrap).

Installation
------------
1. Installation du plugin

- Avec git:
> git clone https://github.com/andurif/moodle-mod_courselinks.git mod/courselinks

- Téléchargement:
> Télécharger le zip depuis https://github.com/andurif/moodle-mod_courselinks/archive/refs/heads/main.zip, dézipper l'archive dans le dossier mod/ et renommez-le "courselinks" si besoin ou installez-le depuis la page d'installation des plugins si vous possédez les bons droits.

2. Aller sur la page de notifications pour finaliser l'installation du plugin.

Présentation / Fonctionnalités
------------
Afficher sur un cours des liens vers d'autres cours selon trois types d'affichage possible pour l'instant:
- Vignette: les liens seront affichés sous forme de vignette avec l\'image de cours propre à chaque cours.</li>
- Liste : les liens seront listés les uns en dessous des autres.</li>
- Menu de navigation: les liens seront affichés sous forme d\'un menu où chaque cours sera représenté par un item du menu.
<p>Attention, un lien vers un cours ne sera visible qu'aux utilisateurs ayant des droits d'accès à ce cours (sauf si vous avez paramétré le forçage de l'affichage dans le formulaire) !<br/>
De même, à l'ajout de la ressource, seuls les cours où vous avez des droits de gestion vous seront proposés au niveau du formulaire 
(filtre par rapport à la capacité moodle/role:assign "Attribuer des rôles aux utilisateurs").</p>
<p>Vous pouvez aussi choisir la façon dont afficher le cours lié: ouvrir dans un nouvel onglet, une fenêtre surgissante, etc.</p>

<img alt="Capture activité cours lié" src="https://i15.servimg.com/u/f15/17/05/22/27/course10.png" />

Pistes d'améliorations
-----
- Ajouter d'autres types d'affichage.
- Améliorer le côté responsive du plugin.
- Être moins dépendant d'un thème qui étend le thème Boost et tester avec d'autres types de thèmes.
- Ajouter un paramètre de configuration pour indiquer si d'autres cours peuvent être pris en compte (pas seulement les cours que l'on gère) ?

A propos
------
<a href="https://www.uca.fr" target="_blank">Université Clermont Auvergne</a> -
<a href="https://github.com/UCA-Squad" target="_blank">UCA Squad</a>

© UCA - 2025
