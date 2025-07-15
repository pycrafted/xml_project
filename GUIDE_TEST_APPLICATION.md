# 🧪 GUIDE DE TEST COMPLET - WhatsApp Clone

## 🎯 OBJECTIF
Vérifier que **toutes les fonctionnalités** de l'application WhatsApp fonctionnent correctement.

---

## 🚀 ÉTAPE 1 : LANCEMENT DE L'APPLICATION

### 1.1 Démarrage du serveur
```bash
# Dans le terminal (PowerShell)
cd C:\Users\ASUS TUF\OneDrive\Bureau\xml_project
php -S localhost:8000 -t public
```

### 1.2 Accès à l'application
- Ouvrez votre navigateur (Chrome, Firefox, Edge)
- Tapez : `http://localhost:8000`
- ✅ **RÉSULTAT ATTENDU :** Page d'accueil WhatsApp Web s'affiche

---

## 🔐 ÉTAPE 2 : TEST DE CONNEXION/INSCRIPTION

### 2.1 Création du premier utilisateur
- **Action :** Remplissez le formulaire
  - Nom : `Alice Dupont`
  - Email : `alice@test.com`
- **Clic :** Bouton "Se connecter / S'inscrire"
- ✅ **RÉSULTAT ATTENDU :** 
  - Message "Nouveau compte créé avec succès"
  - Redirection vers le dashboard

### 2.2 Vérification du dashboard
- ✅ **VÉRIFIEZ :** 
  - Votre nom "Alice Dupont" apparaît dans la sidebar
  - Statistiques affichées (0 contacts, 0 groupes, etc.)
  - Navigation fonctionnelle (Dashboard, Contacts, Groupes, etc.)

---

## 👥 ÉTAPE 3 : TEST DE GESTION DES UTILISATEURS

### 3.1 Création d'utilisateurs supplémentaires
**Ouvrez 2 nouveaux onglets :** `http://localhost:8000`

**Utilisateur 2 :**
- Nom : `Bob Martin`
- Email : `bob@test.com`

**Utilisateur 3 :**
- Nom : `Carol Smith`  
- Email : `carol@test.com`

✅ **RÉSULTAT ATTENDU :** 3 utilisateurs créés et connectés

### 3.2 Test de déconnexion/reconnexion
- **Dans l'onglet Alice :** Clic sur "🚪 Déconnexion"
- ✅ **VÉRIFIEZ :** Retour à la page d'accueil
- **Reconnectez-vous :** Alice Dupont / alice@test.com
- ✅ **VÉRIFIEZ :** Connexion automatique (utilisateur existant)

---

## 👥 ÉTAPE 4 : TEST DE GESTION DES CONTACTS

### 4.1 Ajout de contacts (Onglet Alice)
- **Navigation :** Clic sur "👥 Mes Contacts"
- **Action :** Clic sur "➕ Ajouter un contact"
- **Formulaire :**
  - Nom du contact : `Bob`
  - Utilisateur : Sélectionner `Bob Martin (bob@test.com)`
- **Clic :** "➕ Ajouter le contact"
- ✅ **RÉSULTAT ATTENDU :** Message de succès, Bob ajouté à la liste

### 4.2 Ajout du second contact
- **Action :** Ajouter Carol Smith de la même manière
- **Nom :** `Carol`
- ✅ **RÉSULTAT ATTENDU :** 2 contacts dans la liste

### 4.3 Test de recherche de contacts
- **Action :** Tapez "Bob" dans la barre de recherche
- ✅ **VÉRIFIEZ :** Seul Bob apparaît dans les résultats
- **Action :** Effacez la recherche
- ✅ **VÉRIFIEZ :** Tous les contacts réapparaissent

### 4.4 Test de suppression de contact
- **Action :** Clic sur l'icône "🗑️" à côté d'un contact
- **Confirmation :** Confirmez la suppression
- ✅ **VÉRIFIEZ :** Contact supprimé de la liste

---

## 👫 ÉTAPE 5 : TEST DE GESTION DES GROUPES

### 5.1 Création d'un groupe (Onglet Alice)
- **Navigation :** Clic sur "👫 Mes Groupes"
- **Action :** Clic sur "➕ Créer un groupe"
- **Formulaire :**
  - Nom : `Équipe Projet`
  - Description : `Groupe pour notre projet universitaire`
- **Clic :** "➕ Créer le groupe"
- ✅ **RÉSULTAT ATTENDU :** Groupe créé, Alice = admin

### 5.2 Ajout de membres au groupe
- **Action :** Clic sur "⚙️" (gérer) à côté du groupe
- **Action :** Clic sur "➕ Ajouter un membre"
- **Sélection :** Choisir Bob (contact)
- **Rôle :** Membre
- **Clic :** "Ajouter"
- ✅ **RÉSULTAT ATTENDU :** Bob ajouté comme membre

### 5.3 Test de promotion en administrateur
- **Répétez l'ajout :** Ajoutez Carol comme "👑 Administrateur"
- ✅ **VÉRIFIEZ :** Carol apparaît avec l'icône admin

### 5.4 Vérification côté membres (Onglet Bob)
- **Dans l'onglet Bob :** Allez sur "👫 Mes Groupes"
- ✅ **VÉRIFIEZ :** "Équipe Projet" apparaît dans sa liste
- ✅ **VÉRIFIEZ :** Bob est marqué comme "Membre"

---

## 💬 ÉTAPE 6 : TEST DE MESSAGERIE

### 6.1 Messages privés
**Dans l'onglet Alice :**
- **Navigation :** Clic sur "💬 Messages"
- **Action :** Clic sur contact "Bob" dans la sidebar
- **Message :** Tapez "Salut Bob ! Comment ça va ?"
- **Envoi :** Appuyez sur Entrée ou clic "➤"
- ✅ **RÉSULTAT ATTENDU :** Message apparaît côté droit (envoyé)

**Vérification côté Bob :**
- **Onglet Bob :** Allez sur "💬 Messages"
- **Action :** Clic sur "Alice" dans la sidebar
- ✅ **VÉRIFIEZ :** Message d'Alice apparaît côté gauche (reçu)

### 6.2 Réponse de Bob
**Dans l'onglet Bob :**
- **Message :** "Salut Alice ! Ça va bien, merci !"
- **Envoi :** Entrée
- ✅ **VÉRIFIEZ :** Message envoyé par Bob

**Vérification côté Alice :**
- **Rafraîchissez la page Alice**
- ✅ **VÉRIFIEZ :** Réponse de Bob visible

### 6.3 Messages de groupe
**Dans l'onglet Alice :**
- **Action :** Clic sur groupe "Équipe Projet" dans la sidebar
- **Message :** "Bonjour l'équipe ! Prêts pour le projet ?"
- **Envoi :** Entrée
- ✅ **VÉRIFIEZ :** Message apparaît avec nom "Alice"

**Vérification côté Bob :**
- **Onglet Bob :** Messages → Groupe "Équipe Projet"
- ✅ **VÉRIFIEZ :** Message d'Alice visible dans le groupe

### 6.4 Réponse dans le groupe
**Bob répond :** "Oui, je suis prêt !"
**Carol répond :** "Moi aussi ! On commence quand ?"
- ✅ **VÉRIFIEZ :** Tous les messages s'affichent avec les bons noms

---

## ⚙️ ÉTAPE 7 : TEST DE PROFIL UTILISATEUR

### 7.1 Modification du profil (Onglet Alice)
- **Navigation :** Clic sur "⚙️ Mon Profil"
- **Modification :**
  - Nom : `Alice Dupont-Martin`
  - Statut : `Disponible pour le projet`
- **Sauvegarde :** Clic "💾 Sauvegarder"
- ✅ **RÉSULTAT ATTENDU :** Message de succès

### 7.2 Vérification des changements
- **Action :** Retournez au Dashboard
- ✅ **VÉRIFIEZ :** Nouveau nom visible dans la sidebar
- **Onglet Bob :** Allez voir les contacts
- ✅ **VÉRIFIEZ :** Statut d'Alice mis à jour

---

## 📊 ÉTAPE 8 : TEST DES STATISTIQUES

### 8.1 Vérification du dashboard
**Dans chaque onglet utilisateur :**
- ✅ **Alice :** 1-2 contacts, 1 groupe, X messages envoyés
- ✅ **Bob :** 0 contacts, 1 groupe, X messages
- ✅ **Carol :** 0 contacts, 1 groupe, X messages

### 8.2 Test de mise à jour en temps réel
- **Action :** Ajoutez un nouveau contact
- **Vérifiez :** Compteur mis à jour automatiquement

---

## 🌐 ÉTAPE 9 : TEST D'INTERFACE RESPONSIVE

### 9.1 Test sur mobile
- **Action :** Appuyez F12 (Outils développeur)
- **Mode :** Clic sur l'icône téléphone (responsive)
- ✅ **VÉRIFIEZ :** Interface s'adapte à la taille mobile

### 9.2 Test de navigation mobile
- ✅ **VÉRIFIEZ :** Sidebar accessible, boutons cliquables
- ✅ **VÉRIFIEZ :** Chat utilisable sur petit écran

---

## ⚡ ÉTAPE 10 : TEST DES FONCTIONNALITÉS AJAX

### 10.1 Test de l'API (Optionnel - Technique)
Ouvrez la console développeur (F12) et testez :
```javascript
// Test ping
fetch('ajax.php?action=ping').then(r => r.json()).then(console.log)

// Test statistiques
fetch('ajax.php?action=get_stats').then(r => r.json()).then(console.log)
```
✅ **VÉRIFIEZ :** Réponses JSON valides

---

## 🗃️ ÉTAPE 11 : TEST DE PERSISTANCE DES DONNÉES

### 11.1 Test de redémarrage
- **Action :** Fermez tous les onglets
- **Action :** Arrêtez le serveur (Ctrl+C dans le terminal)
- **Action :** Redémarrez : `php -S localhost:8000 -t public`
- **Action :** Reconnectez-vous avec Alice

### 11.2 Vérification de persistance
- ✅ **VÉRIFIEZ :** Contacts toujours présents
- ✅ **VÉRIFIEZ :** Groupes et membres conservés
- ✅ **VÉRIFIEZ :** Historique des messages intact
- ✅ **VÉRIFIEZ :** Profil utilisateur sauvegardé

---

## 🎯 CHECKLIST FINALE

### ✅ Fonctionnalités testées :
- [ ] Connexion/Inscription automatique
- [ ] Création de 3+ utilisateurs
- [ ] Ajout/suppression de contacts
- [ ] Recherche de contacts
- [ ] Création de groupes
- [ ] Gestion des membres et rôles
- [ ] Messages privés (aller-retour)
- [ ] Messages de groupe (multiple utilisateurs)
- [ ] Modification de profil
- [ ] Statistiques temps réel
- [ ] Interface responsive
- [ ] Persistance des données
- [ ] Navigation fluide

### 🎉 RÉSULTAT FINAL
Si **TOUS** les tests sont ✅, votre application WhatsApp Clone est **100% fonctionnelle** !

---

## 🆘 EN CAS DE PROBLÈME

### Erreur de connexion ?
- Vérifiez l'URL : `http://localhost:8000`
- Relancez le serveur : `php -S localhost:8000 -t public`

### Page blanche ?
- Vérifiez la console (F12) pour les erreurs
- Regardez les erreurs PHP dans le terminal

### Données perdues ?
- Vérifiez le fichier `data/sample_data.xml`
- Relancez `php app.php` pour recréer des données test

### Problème de performance ?
- Fermez les onglets inutiles
- Rafraîchissez la page (F5)

---

## 🎓 POUR LA PRÉSENTATION

**Scénario de démonstration recommandé :**
1. **Connexion** - Créer un utilisateur
2. **Dashboard** - Montrer les statistiques
3. **Contacts** - Ajouter quelques contacts
4. **Groupes** - Créer un groupe de travail
5. **Chat** - Conversation temps réel
6. **Profil** - Personnalisation
7. **Technique** - Montrer le XML généré

**Durée estimée :** 10-15 minutes de démonstration fluide

---

**🏆 FÉLICITATIONS ! Vous maîtrisez maintenant votre application WhatsApp Clone !** 