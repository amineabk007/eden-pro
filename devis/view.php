<?php
session_start();
require '../config/config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Fetch the 'devis' ID from the query parameter
$devis_id = $_GET['id'] ?? null;
if (!$devis_id) {
    echo "Invalid devis ID.";
    exit();
}

// Fetch devis details from the database
$stmt = $pdo->prepare("
    SELECT d.devis_code, d.valid_from, d.valid_until, d.created_at, c.client_name, c.address, c.city, c.postal_code, c.ice, c.i_f, c.rc, c.website, c.telephone, c.email, 
           s.class_name, s.status_name
    FROM devis d
    INNER JOIN clients c ON d.client_id = c.id
    INNER JOIN statuses s ON d.status_id = s.status_id
    WHERE d.id = ?
");
$stmt->execute([$devis_id]);
$devis = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$devis) {
    echo "Devis not found.";
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM company_info WHERE id = 1 LIMIT 1");
$stmt->execute();
$company = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch products for this devis
$productStmt = $pdo->prepare("
    SELECT p.product_name, dd.price, u.unit_name
    FROM devis_details dd
    INNER JOIN products p ON dd.product_id = p.id
    LEFT JOIN units u ON p.unit_id = u.unit_id
    WHERE dd.devis_id = ?
");
$productStmt->execute([$devis_id]);
$products = $productStmt->fetchAll(PDO::FETCH_ASSOC);

// Define max rows to fill the page
$max_rows = 20;
$current_rows = count($products);
$empty_rows_needed = $max_rows - $current_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>View Devis</title>
    <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">
    <style>
        @media print {
            .no-print, .main-sidebar, .main-header, .main-footer { display: none; }
            .content-wrapper { margin: 0; }
            /* Set print margins to start the content from the top of the page */
            @page {
                margin: 10mm;
            }
            body {
                margin: 0;
            }
        }

        .devis-document {
            padding: 20px;
            background-color: #fff;
            border: 1px solid #ddd;
        }
        .devis-header, .devis-body, .devis-footer {
            margin-bottom: 20px;
        }
        .devis-header h1 {
            font-size: 24px;
            font-weight: bold;
        }
        .company-info, .client-info {
            display: inline-block;
            vertical-align: top;
            width: 48%;
        }
        .company-logo, .devis-info {
            display: inline-block;
            vertical-align: top;
            width: 48%;
        }
        .devis-info { text-align: right; }
        .client-info { text-align: right; }

        /* Style the table with vertical borders only in the body */
        .devis-table {
            border-collapse: collapse;
            width: 100%;
            table-layout: fixed;
        }
        .devis-table thead th {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            border-right: 2px solid #000;
            border-left: 2px solid #000;
            background : #dbdbdb;
            /* Adjust font sizes */
        }
        .devis-table thead th:first-child {
            width: 5%;
            font-size: 0.9em;
            text-align: center;
        }
        .devis-table thead th:nth-child(2) {
            width: 70%; /* Make Product column larger */
        }
        .devis-table thead th:nth-child(3), 
        .devis-table thead th:nth-child(4),
        .devis-table thead th:nth-child(5) {
            text-align: center; /* Smaller for ID, Unit, Price columns */
        }
        .devis-table tbody td {
            border-right: 2px solid #000;
            border-left: 2px solid #000;
        }
        .devis-table tbody tr:last-child td {
            border-bottom: 2px solid #000;
        }
        
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <!-- Navbar -->
    <?php include '../includes/navbar.php'; ?>
    <!-- Sidebar -->
    <?php include '../includes/sidebar.php'; ?>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><?php echo htmlspecialchars($devis['devis_code']); ?> - <span class="<?php echo htmlspecialchars($devis['class_name']); ?>"><?php echo htmlspecialchars($devis['status_name']); ?></span></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item active">View Devis</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body devis-document">
                                <!-- Header with Company and Client Info -->
                                <div class="company-logo">
                                    <img src="/<?php echo htmlspecialchars($company['logo_path']); ?>" alt="Company Logo" width="100">                                      
                                </div>
                                <div class="devis-info">
                                    <h4>Devis : <?php echo htmlspecialchars($devis['devis_code']); ?></h4>
                                    <h4><?php echo date('d-m-Y', strtotime($devis['created_at'])); ?></h4>                                      
                                </div>
                                <div class="devis-header">
                                <div class="company-info"><br>
                                    <h4><?php echo htmlspecialchars($company['company_name']); ?></h4>
                                    <p><?php echo htmlspecialchars($company['address'] . ', ' . $company['city'] . ' ' . $company['postal_code']); ?><br>
                                    ICE: <?php echo htmlspecialchars($company['ice']); ?> | IF: <?php echo htmlspecialchars($company['i_f']); ?> | RC: <?php echo htmlspecialchars($company['rc']); ?><br>
                                    Phone: <?php echo htmlspecialchars($company['phone']); ?><br>
                                    Email: <?php echo htmlspecialchars($company['email']); ?><br>
                                    Website: <?php echo htmlspecialchars($company['website']); ?>
                                    </p>
                                </div>

                                    <div class="client-info"><br>
                                        <h4><?php echo htmlspecialchars($devis['client_name']); ?></h4>
                                        <p><?php echo htmlspecialchars($devis['address'] . ', ' . $devis['city'] . ' ' . $devis['postal_code']); ?><br>
                                        ICE: <?php echo htmlspecialchars($devis['ice']); ?> | IF: <?php echo htmlspecialchars($devis['i_f']); ?> | RC: <?php echo htmlspecialchars($devis['rc']); ?><br>
                                           Phone: <?php echo htmlspecialchars($devis['telephone']); ?><br>
                                           Email: <?php echo htmlspecialchars($devis['email']); ?><br>
                                           Website: <?php echo htmlspecialchars($devis['website']); ?></p>
                                    </div>
                                </div>

                                <!-- Devis Details Table -->
                                <div class="devis-body">
                                    <table class="table devis-table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Product</th>
                                                <th>Unit</th>
                                                <th>Price</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $index = 1; ?>
                                            <?php foreach ($products as $product): ?>
                                                <tr>
                                                    <td><center><?php echo $index++; ?></center></td>
                                                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                                    <td><center><?php echo htmlspecialchars($product['unit_name'] ?? ''); ?></center></td>
                                                    <td><center><?php echo htmlspecialchars(number_format($product['price'], 2)); ?> MAD</center></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php for ($i = 0; $i < $empty_rows_needed; $i++): ?>
                                                <tr>
                                                    <td>&nbsp;</td>
                                                    <td>&nbsp;</td>
                                                    <td>&nbsp;</td>
                                                    <td>&nbsp;</td>
                                                </tr>
                                            <?php endfor; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Footer -->
                                <div class="devis-footer text-right">
                                    <p><strong>Generated on:</strong> <?php echo date('Y-m-d', strtotime($devis['created_at'])); ?></p>
                                </div>

                                <!-- Print Button -->
                                <button onclick="window.print()" class="btn btn-primary no-print">Print Devis</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <?php include '../includes/footer.php'; ?>
</div>

<script src="../plugins/jquery/jquery.min.js"></script>
<script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../dist/js/adminlte.min.js"></script>
</body>
</html>
