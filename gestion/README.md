# Eden Gestion — module de gestion financière

Application web (PHP / MySQL) en français, intégrée à Eden : elle utilise la même base (`config/config.php`) et la même connexion (`login.php`).

## Modules

| Page | Rôle |
|---|---|
| **Tableau de bord** (`index.php`) | CA espèces/carte, achats, salaires, charges, résultat estimé, flux de trésorerie, graphiques, dettes fournisseurs, rappels (salaires non payés, charges fixes non saisies, CA d'hier manquant) |
| **Chiffre d'affaires** (`ventes.php`) | Saisie quotidienne des encaissements **en espèces** et **par carte** |
| **Fournisseurs** (`fournisseurs.php`) | Fiches fournisseurs, solde dû, relevé de compte imprimable |
| **Achats** (`achats.php`) | Factures / BL d'achat par fournisseur |
| **Paiements fournisseurs** (`paiements.php`) | Règlements (espèces, carte, chèque, virement) avec référence |
| **Employés** (`employes.php`) | Personnel, salaire de base, actif/inactif |
| **Avances** (`avances.php`) | Avances sur salaire avec le mois où elles seront déduites |
| **Salaires** (`salaires.php`) | Paie mensuelle : base + primes − retenues − avances = net à payer |
| **Charges** (`charges.php`) | Charges **fixes** (loyer, assurance, internet…) et **variables** (électricité, eau, gaz…), catégories modifiables |

## Installation

1. Copier le dossier `gestion/` dans le projet (déjà fait).
2. Vérifier `config/config.php` (base `eden`).
3. Se connecter à Eden puis ouvrir `http://votre-serveur/eden-pro/gestion/`.

Les tables (préfixe `g_`) sont créées automatiquement au premier accès. Le fichier `install.sql` permet aussi de les créer à la main (phpMyAdmin).

Bootstrap 5, Bootstrap Icons et Chart.js sont inclus dans `assets/vendor/` : aucune connexion Internet n'est nécessaire.

## Calculs

- **Résultat estimé** = CA − achats − salaires bruts payés (base + primes − retenues) − charges.
- **Flux de trésorerie** = CA encaissé − (paiements fournisseurs + salaires nets + avances versées + charges).
- **Solde fournisseur** = total des achats − total des paiements.
- Une avance est rattachée à un **mois de déduction** ; une fois le salaire de ce mois payé, l'avance est verrouillée.

La devise (`DH`) se change dans `inc/functions.php` (constante `DEVISE`).
