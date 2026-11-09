<?php
// gestion/inc/db.php — Connexion et création automatique des tables du module Gestion.

if (getenv('GESTION_SQLITE')) {
    // Mode test local (SQLite) : GESTION_SQLITE=/chemin/base.sqlite
    $pdo = new PDO('sqlite:' . getenv('GESTION_SQLITE'), null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
} else {
    // Base MySQL de l'application (config/config.php)
    require __DIR__ . '/../../config/config.php';
}

function gestion_install(PDO $pdo): void
{
    try {
        $pdo->query('SELECT 1 FROM g_charges LIMIT 1');
        gestion_migrate($pdo); // déjà installé : mise à jour éventuelle
        return;
    } catch (PDOException $e) {
        // tables absentes : on les crée
    }

    $sqlite = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
    $id  = $sqlite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';
    $fk  = $sqlite ? 'INTEGER' : 'INT';
    $end = $sqlite ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
    $now = 'created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP';

    $sql = [
        "CREATE TABLE IF NOT EXISTS g_ventes (
            id $id,
            date_vente DATE NOT NULL,
            montant_especes DECIMAL(12,2) NOT NULL DEFAULT 0,
            montant_carte DECIMAL(12,2) NOT NULL DEFAULT 0,
            nb_couverts INT NOT NULL DEFAULT 0,
            note VARCHAR(255) NULL,
            $now
        )$end",
        "CREATE TABLE IF NOT EXISTS g_fournisseurs (
            id $id,
            nom VARCHAR(150) NOT NULL,
            telephone VARCHAR(50) NULL,
            email VARCHAR(150) NULL,
            adresse VARCHAR(255) NULL,
            $now
        )$end",
        "CREATE TABLE IF NOT EXISTS g_achats (
            id $id,
            fournisseur_id $fk NOT NULL,
            date_achat DATE NOT NULL,
            num_facture VARCHAR(100) NULL,
            categorie VARCHAR(20) NOT NULL DEFAULT 'nourriture',
            montant DECIMAL(12,2) NOT NULL,
            note VARCHAR(255) NULL,
            $now,
            FOREIGN KEY (fournisseur_id) REFERENCES g_fournisseurs(id)
        )$end",
        "CREATE TABLE IF NOT EXISTS g_paiements_fournisseurs (
            id $id,
            fournisseur_id $fk NOT NULL,
            date_paiement DATE NOT NULL,
            montant DECIMAL(12,2) NOT NULL,
            mode VARCHAR(20) NOT NULL DEFAULT 'especes',
            reference VARCHAR(100) NULL,
            note VARCHAR(255) NULL,
            $now,
            FOREIGN KEY (fournisseur_id) REFERENCES g_fournisseurs(id)
        )$end",
        "CREATE TABLE IF NOT EXISTS g_employes (
            id $id,
            nom VARCHAR(150) NOT NULL,
            poste VARCHAR(100) NULL,
            telephone VARCHAR(50) NULL,
            salaire_base DECIMAL(12,2) NOT NULL DEFAULT 0,
            date_embauche DATE NULL,
            actif TINYINT NOT NULL DEFAULT 1,
            $now
        )$end",
        "CREATE TABLE IF NOT EXISTS g_avances (
            id $id,
            employe_id $fk NOT NULL,
            date_avance DATE NOT NULL,
            montant DECIMAL(12,2) NOT NULL,
            mois_deduction CHAR(7) NOT NULL,
            note VARCHAR(255) NULL,
            $now,
            FOREIGN KEY (employe_id) REFERENCES g_employes(id)
        )$end",
        "CREATE TABLE IF NOT EXISTS g_salaires (
            id $id,
            employe_id $fk NOT NULL,
            mois CHAR(7) NOT NULL,
            salaire_base DECIMAL(12,2) NOT NULL,
            primes DECIMAL(12,2) NOT NULL DEFAULT 0,
            retenues DECIMAL(12,2) NOT NULL DEFAULT 0,
            avances DECIMAL(12,2) NOT NULL DEFAULT 0,
            net_paye DECIMAL(12,2) NOT NULL,
            date_paiement DATE NOT NULL,
            mode VARCHAR(20) NOT NULL DEFAULT 'especes',
            note VARCHAR(255) NULL,
            $now,
            UNIQUE (employe_id, mois),
            FOREIGN KEY (employe_id) REFERENCES g_employes(id)
        )$end",
        "CREATE TABLE IF NOT EXISTS g_categories_charges (
            id $id,
            nom VARCHAR(100) NOT NULL,
            type VARCHAR(10) NOT NULL DEFAULT 'variable'
        )$end",
        "CREATE TABLE IF NOT EXISTS g_charges (
            id $id,
            categorie_id $fk NOT NULL,
            date_charge DATE NOT NULL,
            montant DECIMAL(12,2) NOT NULL,
            mode VARCHAR(20) NOT NULL DEFAULT 'especes',
            note VARCHAR(255) NULL,
            $now,
            FOREIGN KEY (categorie_id) REFERENCES g_categories_charges(id)
        )$end",
    ];
    foreach ($sql as $q) {
        $pdo->exec($q);
    }

    // Catégories de charges par défaut (restaurant)
    $defaults = [
        ['Loyer', 'fixe'], ['Assurance', 'fixe'], ['Internet / Téléphone', 'fixe'],
        ['Abonnements (caisse, logiciels, TV…)', 'fixe'], ['Location terminal TPE', 'fixe'],
        ['Comptable', 'fixe'], ['Licence / autorisations', 'fixe'],
        ['Électricité', 'variable'], ['Eau', 'variable'], ['Gaz (bouteilles)', 'variable'],
        ['Produits d\'entretien & hygiène', 'variable'], ['Linge & blanchisserie', 'variable'],
        ['Entretien & réparations', 'variable'], ['Commissions carte bancaire', 'variable'],
        ['Animation / DJ / musique', 'variable'], ['Marketing & réseaux sociaux', 'variable'],
        ['Transport / livraison', 'variable'], ['Impôts & taxes', 'variable'], ['Divers', 'variable'],
    ];
    $st = $pdo->prepare('INSERT INTO g_categories_charges (nom, type) VALUES (?, ?)');
    foreach ($defaults as $d) {
        $st->execute($d);
    }
}

/** Ajoute les colonnes apparues dans les versions récentes (installations existantes). */
function gestion_migrate(PDO $pdo): void
{
    $cols = [
        'g_ventes' => ['nb_couverts', 'INT NOT NULL DEFAULT 0'],
        'g_achats' => ['categorie', "VARCHAR(20) NOT NULL DEFAULT 'nourriture'"],
    ];
    foreach ($cols as $table => [$col, $def]) {
        try {
            $pdo->query("SELECT $col FROM $table LIMIT 1");
        } catch (PDOException $e) {
            $pdo->exec("ALTER TABLE $table ADD COLUMN $col $def");
        }
    }
}

gestion_install($pdo);
