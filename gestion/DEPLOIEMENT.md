# Mise en ligne sur Hostinger — erp.dunerooftop.shop

Durée : environ 15 minutes. Tout se fait depuis **hPanel** (panneau Hostinger).

---

## Étape 1 — Créer le sous-domaine `erp`

1. hPanel → **Sites web** → choisir `dunerooftop.shop` → **Tableau de bord**.
2. Menu **Domaines** → **Sous-domaines**.
3. Nom du sous-domaine : `erp` → **Créer**.
4. Notez le **dossier** affiché pour ce sous-domaine (en général `public_html/erp`).

> Si le domaine `dunerooftop.shop` n'utilise pas les serveurs DNS de Hostinger, ajoutez chez votre registrar
> un enregistrement **A** `erp` → l'adresse IP de votre hébergement (visible dans hPanel → *Détails du plan*).

## Étape 2 — Créer la base de données MySQL

1. hPanel → **Bases de données** → **Bases de données MySQL**.
2. Remplir :
   - Nom de la base : `erp` → devient par exemple `u123456789_erp`
   - Utilisateur : `erp` → devient par exemple `u123456789_erp`
   - Mot de passe : un mot de passe fort (notez-le)
3. **Créer**. Notez le **nom complet** de la base et de l'utilisateur (avec le préfixe `u…_`).

## Étape 3 — Vérifier la version de PHP

hPanel → **Avancé** → **Configuration PHP** → choisir **PHP 8.1 ou plus récent** (8.2 / 8.3 recommandé).

## Étape 4 — Envoyer les fichiers

1. hPanel → **Fichiers** → **Gestionnaire de fichiers**.
2. Ouvrir le dossier du sous-domaine (ex. `public_html/erp`). S'il contient un `default.php`, supprimez-le.
3. **Téléverser** le fichier `dunerooftop-erp.zip`, clic droit → **Extraire** dans ce même dossier.
4. Vérifier que `index.php`, `login.php`, `.htaccess`… sont **directement** dans `public_html/erp`
   (et pas dans un sous-dossier). Supprimez ensuite le fichier `.zip`.

> Les fichiers commençant par un point (`.htaccess`) peuvent être masqués : activez
> « Afficher les fichiers cachés » dans le gestionnaire de fichiers.

## Étape 5 — Configurer la connexion à la base

1. Dans le gestionnaire de fichiers, clic droit sur `config.example.php` → **Copier** → nommer la copie `config.php`.
2. Ouvrir `config.php` et remplacer par vos informations de l'étape 2 :

```php
const DB_HOST = 'localhost';
const DB_NAME = 'u123456789_erp';
const DB_USER = 'u123456789_erp';
const DB_PASS = 'votre_mot_de_passe';
```

3. **Enregistrer**.

## Étape 6 — Activer le HTTPS (SSL)

hPanel → **Sécurité** → **SSL** → vérifier que `erp.dunerooftop.shop` a un certificat **actif**
(Hostinger l'installe gratuitement ; sinon cliquez sur **Installer SSL**). Cela peut prendre quelques minutes.

## Étape 7 — Premier démarrage

1. Ouvrir **https://erp.dunerooftop.shop**
2. L'application crée automatiquement toutes les tables, puis affiche **« Installation — compte administrateur »**.
3. Choisir votre identifiant et votre mot de passe → **Créer le compte**. C'est prêt !

> ⚠️ Faites cette étape **juste après** l'envoi des fichiers : tant qu'aucun compte n'existe, la page
> d'installation est ouverte à quiconque connaît l'adresse. Dès que le compte administrateur est créé,
> elle est définitivement désactivée.
4. Pour vos employés (caissier, gérant…) : menu **Utilisateurs** → *Nouvel utilisateur* (rôle « Utilisateur »).

---

## Logo officiel

Téléversez votre logo dans `public_html/erp/assets/` sous le nom **`logo.png`** : il remplace automatiquement le logo par défaut.

## Mettre à jour l'application plus tard

Téléversez le nouveau `.zip` et extrayez-le en écrasant les fichiers.
**Ne supprimez pas `config.php`** (il n'est pas dans le zip, il ne sera donc pas écrasé). Les données restent dans MySQL.

## Sauvegardes

hPanel → **Fichiers** → **Sauvegardes** → *Sauvegardes de bases de données* : téléchargez régulièrement la base `u…_erp`.

## En cas de problème

| Message | Solution |
|---|---|
| « Configuration manquante » | `config.php` n'existe pas : refaire l'étape 5. |
| « Connexion à la base de données impossible » | Vérifier nom de base / utilisateur (avec le préfixe `u…_`) et mot de passe dans `config.php`. |
| Page blanche / erreur 500 | Vérifier la version PHP (étape 3). Voir hPanel → *Avancé* → *Journaux d'erreurs*. |
| « Ce site n'est pas sécurisé » | Le certificat SSL n'est pas encore actif : étape 6, patienter quelques minutes. |
| Mot de passe administrateur oublié | Dans phpMyAdmin, vider la table `g_users` puis rouvrir le site pour recréer le compte. |
