<?php
session_start();
require '../config/config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Fetch the 'delivery_note' ID from the query parameter
$delivery_note_id = $_GET['id'] ?? null;
if (!$delivery_note_id) {
    echo "Invalid delivery note ID.";
    exit();
}

// Fetch delivery note details from the database
$stmt = $pdo->prepare("
    SELECT dn.delivery_note_code, dn.delivery_date, dn.status_id, dn.order_id, o.client_id, o.order_code, o.order_total,
           s.class_name, s.status_name
    FROM delivery_notes dn
    INNER JOIN orders o ON dn.order_id = o.id
    INNER JOIN statuses s ON dn.status_id = s.status_id
    WHERE dn.id = ?
");
$stmt->execute([$delivery_note_id]);
$delivery_note = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$delivery_note) {
    echo "Delivery note not found.";
    exit();
}
$stmt = $pdo->prepare("SELECT * FROM company_info WHERE id = 1 LIMIT 1");
$stmt->execute();
$company = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch client details from the orders table
$stmt = $pdo->prepare("
    SELECT c.client_name, c.address, c.city, c.postal_code, c.ice, c.i_f, c.rc, c.website, c.telephone, c.email
    FROM clients c
    WHERE c.id = ?
");
$stmt->execute([$delivery_note['client_id']]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch products for this order (same as delivery note products)
$productStmt = $pdo->prepare("
    SELECT p.product_name, od.unit_price, od.quantity, od.total_price
    FROM order_details od
    INNER JOIN products p ON od.product_id = p.id
    WHERE od.order_id = ?
");
$productStmt->execute([$delivery_note['order_id']]);
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
    <title>View Delivery Note</title>
    <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">
    <style>
        @media print {
            .no-print, .main-sidebar, .main-header, .main-footer { display: none; }
            .content-wrapper { margin: 0; }
            @page { margin: 10mm; }
            body { margin: 0; }
            .page { page-break-after: always; margin-bottom: 20px; }
            .page:last-child { page-break-after: auto; }
        }

        .order-document {
            padding: 20px;
            background-color: #fff;
            border: 1px solid #ddd;
        }
        .order-header, .order-header1, .order-body, .order-footer {
            margin-bottom: 20px;
        }
        .order-header h1 {
            font-size: 24px;
            font-weight: bold;
        }
        .company-info, .client-info {
            display: inline-block;
            vertical-align: top;
            width: 49.5%;
        }
        .company-logo, .order-info {
            display: inline-block;
            vertical-align: top;
            width: 49.5%;
        }
        .order-info { text-align: right; }
        .client-info { text-align: right; }

        .order-table {
            border-collapse: collapse;
            width: 100%;
            table-layout: fixed;
        }
        .order-table thead th {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            border-right: 2px solid #000;
            border-left: 2px solid #000;
            background : #dbdbdb;
        }
        .order-table thead th:first-child {
            width: 5%;
            font-size: 0.9em;
            text-align: center;
        }
        .order-table thead th:nth-child(2) {
            width: 60%;
        }
        .order-table thead th:nth-child(3), 
        .order-table thead th:nth-child(4),
        .order-table thead th:nth-child(5) {
            text-align: center;
        }
        .order-table tbody td {
            border-right: 2px solid #000;
            border-left: 2px solid #000;
        }
        .order-table tbody tr:last-child td {
            border-bottom: 2px solid #000;
        }
        .order-table tfoot th {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            border-right: 2px solid #000;
            border-left: 2px solid #000;
            background : #dbdbdb;
            font-size: 1.2em;
        }
        .page-number {
            position: absolute;
            bottom: 20px;
            width: 100%;
            text-align: center;
            font-weight: bold;
        }
        .page {
            position: relative;
            page-break-after: always;
            padding-bottom: 60px;
        }
        .page:last-child {
            page-break-after: auto;
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
                        <h1><?php echo htmlspecialchars($delivery_note['delivery_note_code']); ?> - <span class="<?php echo htmlspecialchars($delivery_note['class_name']); ?>"><?php echo htmlspecialchars($delivery_note['status_name']); ?></span> - <button onclick="window.print()" class="btn btn-dark no-print"><i class="fas fa-print"></i></button></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item active">View Delivery Note</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div id="printArea"></div>
            </div>
        </section>
    </div>

    <?php include '../includes/footer.php'; ?>
</div>

<script src="../plugins/jquery/jquery.min.js"></script>
<script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../dist/js/adminlte.min.js"></script>
<script>
    // Define the max rows per page
    const maxRows = 20;

    // Define the products array (replace this with your fetched product data)
    const products = <?php echo json_encode($products); ?>;    
    const company = <?php echo json_encode($company); ?>;
    const delivery_note = <?php echo json_encode($delivery_note); ?>;
    const client = <?php echo json_encode($client); ?>;

    function createPage(start, end, pageNumber, totalPages, isLastPage) {
        // Creating a new page div
        let pageDiv = document.createElement('div');
        pageDiv.classList.add('page', 'order-document');

        // Adding headers for every page
        pageDiv.innerHTML += `
            <div class="order-header1">
                <div class="company-logo">
                    <img src="/${company.logo_path}" alt="Company Logo" width="100">                                      
                </div>
                <div class="order-info">
                    <h4><b>Bon de Livraison : </b>${delivery_note.delivery_note_code}</h4>
                    <h4>${new Date(delivery_note.delivery_date).toLocaleDateString()}</h4>                                      
                </div>
            </div>
            <div class="order-header">
                <div class="company-info"><br>
                    <h4>${company.company_name}</h4>
                    <p>${company.address}, ${company.city} ${company.postal_code}<br>
                    ICE: ${company.ice} | IF: ${company.i_f} | RC: ${company.rc}<br>
                    Phone: ${company.phone}<br>
                    Email: ${company.email}<br>
                    Website: ${company.website}
                    </p>
                </div>
                <div class="client-info"><br>
                    <h4>${client.client_name}</h4>
                    <p>${client.address}, ${client.city} ${client.postal_code}<br>
                    ICE: ${client.ice} | IF: ${client.i_f} | RC: ${client.rc}<br>
                    Phone: ${client.telephone}<br>
                    Email: ${client.email}<br>
                    Website: ${client.website}</p>
                </div>
            </div>
            <div class="order-body">
                <table class="table order-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody${start}">
                    </tbody>
                    ${isLastPage ? `
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-right">Total:</th>
                                <th><center>${Number(delivery_note.order_total).toFixed(2)}</center></th>
                            </tr>
                        </tfoot>
                    ` : ''}
                </table>
            </div>
            <div class="page-number">
                <p>${pageNumber} / ${totalPages}</p>
            </div>
        `;

        document.getElementById('printArea').appendChild(pageDiv);

        // Populate table rows
        let tableBody = document.getElementById(`tableBody${start}`);
        let index = start + 1;
        for (let i = start; i < end; i++) {
            if (i >= products.length) {
                // Add empty rows if we’re on the last page and need padding
                let emptyRow = `
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                `;
                tableBody.innerHTML += emptyRow;
            } else {
                // Populate with product data
                let product = products[i];
                tableBody.innerHTML += `
                    <tr>
                        <td><center>${index++}</center></td>
                        <td>${product.product_name}</td>
                        <td><center>${Number(product.unit_price).toFixed(2)}</center></td>
                        <td><center>${product.quantity}</center></td>
                        <td><center>${Number(product.total_price).toFixed(2)}</center></td>
                    </tr>
                `;
            }
        }
    }

    // Calculate pages and call createPage
    let totalPages = Math.ceil(products.length / maxRows);
    for (let i = 0; i < totalPages; i++) {
        let start = i * maxRows;
        let end = start + maxRows;
        createPage(start, end, i + 1, totalPages, i === totalPages - 1);
    }
</script>
</body>
</html>
