🎯 Objectif
Tu es Cursor, un assistant expert en développement PHP, architecture logicielle et clean code.
Ta mission est de réaliser une plateforme de discussions en ligne (type WhatsApp), 100 % conforme au cahier des charges du professeur Ibrahima FALL (UCAD), avec une qualité de code irréprochable, en PHP uniquement.

Tu dois produire une application fonctionnelle, bien structurée, documentée, et testée, avec des données stockées et manipulées exclusivement en XML. Le projet sera noté en fonction du respect des consignes, de la qualité du code, de la clarté de l'architecture, de la couverture de test et de la documentation.

✅ Spécifications obligatoires
1. Contexte général
Application de messagerie style WhatsApp, permettant :

L'échange de messages texte et de fichiers

La gestion des contacts et des groupes

La modification du profil utilisateur et des paramètres

Toutes les données (utilisateurs, messages, groupes, etc.) sont stockées au format XML.

Utilisation exclusive du langage PHP (PHP 8.0+).

Manipulation du XML avec SimpleXML, DOMDocument ou XMLReader (pas de base de données).

2. Technologies et outils imposés
Langage : PHP

Librairies XML autorisées : SimpleXML, DOMDocument, XMLReader/XMLWriter

Schéma XML : XSD ou DTD obligatoire

Tests : PHPUnit

Documentation : PHPDoc

Modélisation : Diagramme UML de la structure XML

🛠 Feuille de route détaillée
Phase 1 – Modélisation XML
Établir un schéma XSD (ou DTD) structurant les données suivantes :

Utilisateur (profil, paramètres)

Contact (nom, identifiant)

Groupe (nom, liste de membres)

Message (texte/fichier, horodatage, statut, auteur, destinataire)

Définir les types de données, contraintes de cardinalité, règles de validation.

Produire un diagramme UML pour représenter la structure XML.

Phase 2 – Structure du projet
Créer une arborescence claire du projet PHP :

```
/src
  /Models          # classes métier (User, Message, Group, Contact)
  /Services        # logique métier (UserService, MessageService, etc.)
  /Controllers     # contrôleurs (si interface web)
  /Utils           # utilitaires (XMLManager, FileHandler, etc.)
/data              # fichiers XML de données
/schemas           # fichiers XSD/DTD
/tests             # tests PHPUnit
/docs              # rapport, diapos, UML
/public            # interface web (si applicable)
```

Inclure :

README.md

.gitignore

composer.json (gestion des dépendances)

Phase 3 – Accès et manipulation des données XML
Implémenter une classe XMLManager responsable du :

Chargement et parsing des fichiers XML via SimpleXML/DOMDocument

Validation automatique avec le XSD/DTD

Sauvegarde et mise à jour du fichier XML

Créer un Repository pour chaque entité (UserRepository, MessageRepository, GroupRepository, etc.)

CRUD complet : Create / Read / Update / Delete

Gestion des erreurs (fichier manquant, balise invalide, etc.)

Phase 4 – Modules fonctionnels à implémenter
1. Utilisateur
Créer un profil utilisateur

Modifier les paramètres (nom, statut, notifications, etc.)

Supprimer un utilisateur

2. Contacts
Ajouter un contact

Supprimer un contact

Rechercher un contact par nom ou ID

3. Groupes
Créer un groupe

Renommer ou supprimer un groupe

Ajouter ou retirer des membres

4. Messages
Envoyer un message texte (avec timestamp, statut "envoyé", "reçu", "lu")

Envoyer un fichier (chemin enregistré dans XML, métadonnées incluses)

Afficher l'historique des discussions

5. Interface utilisateur
Interface web (HTML/CSS/JavaScript) ou CLI

Permettre navigation par pages ou commandes

Présentation claire de l'historique des échanges

Phase 5 – Tests
Utiliser PHPUnit pour chaque module

Tester :

Chargement XML

Ajout/modification/suppression d'éléments

Recherches

Erreurs (fichier manquant, XML mal formé, données invalides)

Objectif : ≥ 80 % de couverture de code

Phase 6 – Clean code et qualité
Respecter les conventions PHP (PSR-1, PSR-2, PSR-4)

Appliquer les principes SOLID

Utiliser PHPDoc pour chaque classe publique et méthode

Gestion centralisée des exceptions

Séparer strictement modèle / logique métier / présentation

Code modulaire et réutilisable avec Composer

Phase 7 – Documentation
README.md (objectif, installation, exécution, exemples d'usage)

Rapport écrit (PDF ou Markdown) contenant :

Introduction

Modélisation XML (structure + XSD + UML)

Architecture logicielle

Liste des modules

Tests réalisés et couverture

Captures d'écran

Présentation PowerPoint pour la soutenance (prévue le 16 juillet 2025)

Phase 8 – Livraison finale
Livrer un dossier compressé ou dépôt Git contenant :

/src avec tout le code source PHP

/data avec les fichiers XML + XSD

/tests avec les tests PHPUnit

/docs avec le rapport + slides

README complet

Script d'exécution PHP (ou instructions dans le README)

⚠ Règles strictes à respecter
Ne pas utiliser de base de données

Tout stocker en XML

Utiliser uniquement PHP

Respecter scrupuleusement chaque point ci-dessus

Appliquer les meilleures pratiques de développement logiciel

🏆 Objectif
Suis rigoureusement cette feuille de route et tu garantis une note de 20/20, grâce à :

Une application conforme à 100 % au cahier des charges

Une architecture propre et modulaire

Un code documenté et testé

Une livraison complète et professionnelle
