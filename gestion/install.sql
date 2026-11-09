-- Dune Rooftop — module Gestion du restaurant — schéma MySQL (base `eden`).
-- Facultatif : les tables sont aussi créées automatiquement au premier accès à /gestion/.

CREATE TABLE IF NOT EXISTS g_ventes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date_vente DATE NOT NULL,
    montant_especes DECIMAL(12,2) NOT NULL DEFAULT 0,
    montant_carte DECIMAL(12,2) NOT NULL DEFAULT 0,
    nb_couverts INT NOT NULL DEFAULT 0,
    note VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS g_fournisseurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    telephone VARCHAR(50) NULL,
    email VARCHAR(150) NULL,
    adresse VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS g_achats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fournisseur_id INT NOT NULL,
    date_achat DATE NOT NULL,
    num_facture VARCHAR(100) NULL,
    categorie VARCHAR(20) NOT NULL DEFAULT 'nourriture',   -- nourriture, boissons, consommables, autre
    montant DECIMAL(12,2) NOT NULL,
    note VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fournisseur_id) REFERENCES g_fournisseurs(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS g_paiements_fournisseurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fournisseur_id INT NOT NULL,
    date_paiement DATE NOT NULL,
    montant DECIMAL(12,2) NOT NULL,
    mode VARCHAR(20) NOT NULL DEFAULT 'especes',
    reference VARCHAR(100) NULL,
    note VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fournisseur_id) REFERENCES g_fournisseurs(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS g_employes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    poste VARCHAR(100) NULL,
    telephone VARCHAR(50) NULL,
    salaire_base DECIMAL(12,2) NOT NULL DEFAULT 0,
    date_embauche DATE NULL,
    actif TINYINT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS g_avances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employe_id INT NOT NULL,
    date_avance DATE NOT NULL,
    montant DECIMAL(12,2) NOT NULL,
    mois_deduction CHAR(7) NOT NULL,          -- AAAA-MM : salaire sur lequel l'avance est déduite
    note VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employe_id) REFERENCES g_employes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS g_salaires (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employe_id INT NOT NULL,
    mois CHAR(7) NOT NULL,                    -- AAAA-MM
    salaire_base DECIMAL(12,2) NOT NULL,
    primes DECIMAL(12,2) NOT NULL DEFAULT 0,
    retenues DECIMAL(12,2) NOT NULL DEFAULT 0,
    avances DECIMAL(12,2) NOT NULL DEFAULT 0,
    net_paye DECIMAL(12,2) NOT NULL,
    date_paiement DATE NOT NULL,
    mode VARCHAR(20) NOT NULL DEFAULT 'especes',
    note VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (employe_id, mois),
    FOREIGN KEY (employe_id) REFERENCES g_employes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS g_categories_charges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    type VARCHAR(10) NOT NULL DEFAULT 'variable'  -- 'fixe' ou 'variable'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS g_charges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categorie_id INT NOT NULL,
    date_charge DATE NOT NULL,
    montant DECIMAL(12,2) NOT NULL,
    mode VARCHAR(20) NOT NULL DEFAULT 'especes',
    note VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categorie_id) REFERENCES g_categories_charges(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO g_categories_charges (nom, type) VALUES
('Loyer','fixe'), ('Assurance','fixe'), ('Internet / Téléphone','fixe'), ('Abonnements (caisse, logiciels, TV…)','fixe'),
('Location terminal TPE','fixe'), ('Comptable','fixe'), ('Licence / autorisations','fixe'),
('Électricité','variable'), ('Eau','variable'), ('Gaz (bouteilles)','variable'), ('Produits d''entretien & hygiène','variable'),
('Linge & blanchisserie','variable'), ('Entretien & réparations','variable'), ('Commissions carte bancaire','variable'),
('Animation / DJ / musique','variable'), ('Marketing & réseaux sociaux','variable'), ('Transport / livraison','variable'),
('Impôts & taxes','variable'), ('Divers','variable');
