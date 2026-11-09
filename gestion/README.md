# Dune Rooftop — gestion du restaurant

Application web (PHP / MySQL) en français pour le restaurant **Dune Rooftop**.

## Modules

| Page | Rôle |
|---|---|
| **Tableau de bord** (`index.php`) | CA espèces/carte, couverts et ticket moyen, ratios restaurant (food cost, coût boissons, masse salariale, prime cost), achats, salaires, charges, résultat estimé, flux de trésorerie, graphiques, dettes fournisseurs, rappels (salaires non payés, charges fixes non saisies, CA d'hier manquant) |
| **Chiffre d'affaires** (`ventes.php`) | Saisie quotidienne des encaissements **en espèces** et **par carte**, nombre de couverts |
| **Fournisseurs** (`fournisseurs.php`) | Fiches fournisseurs, solde dû, relevé de compte imprimable |
| **Achats** (`achats.php`) | Factures / BL d'achat par fournisseur et par famille (nourriture, boissons, consommables, autre) |
| **Paiements fournisseurs** (`paiements.php`) | Règlements (espèces, carte, chèque, virement) avec référence |
| **Employés** (`employes.php`) | Personnel, salaire de base, actif/inactif |
| **Avances** (`avances.php`) | Avances sur salaire avec le mois où elles seront déduites |
| **Salaires** (`salaires.php`) | Paie mensuelle : base + primes − retenues − avances = net à payer |
| **Charges** (`charges.php`) | Charges **fixes** (loyer, assurance, internet…) et **variables** (électricité, eau, gaz…), catégories modifiables |

## Installation

Application **autonome** : elle a sa propre connexion (comptes dans la table `g_users`) et sa propre configuration.

1. Copier `config.example.php` en `config.php` et y mettre les identifiants MySQL.
2. Ouvrir le site : les tables (préfixe `g_`) sont créées automatiquement, puis l'écran
   **« Installation — compte administrateur »** permet de créer le premier compte.
3. Les autres comptes se créent dans **Utilisateurs** (rôle Administrateur ou Utilisateur).

Mise en ligne sur Hostinger (`erp.dunerooftop.shop`) : voir **[DEPLOIEMENT.md](DEPLOIEMENT.md)**.

`install.sql` permet aussi de créer les tables à la main (phpMyAdmin). Le fichier `.htaccess` force le HTTPS
et bloque l'accès à `inc/`, `config.php` et aux fichiers internes.

Bootstrap 5, Bootstrap Icons et Chart.js sont inclus dans `assets/vendor/` : aucune dépendance externe.

## Logo

Le logo s'affiche dans le menu, l'onglet du navigateur et en tête des pages imprimées (relevés fournisseurs…).
Pour mettre le logo officiel de Dune Rooftop, déposez simplement le fichier dans `gestion/assets/` sous le nom
**`logo.png`** (ou `logo.jpg` / `logo.webp`) : il remplace automatiquement le logo par défaut `logo.svg`.
Idéalement une image carrée d'au moins 256 × 256 px.

Le nom (`APP_NAME`) et la devise (`DEVISE`, `DH`) se changent dans `inc/functions.php`.
