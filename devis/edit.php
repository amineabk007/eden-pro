<?php
// edit.php
session_start();
require '../config/config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Get the devis ID from the query string
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}
$devis_id = $_GET['id'];

// Fetch the devis details
$devis = $pdo->prepare("
    SELECT d.id, d.devis_code, d.client_id, d.created_at, d.valid_from, d.valid_until, d.status_id, c.client_name
    FROM devis d
    JOIN clients c ON d.client_id = c.id
    WHERE d.id = ?
");
$devis->execute([$devis_id]);
$devis = $devis->fetch(PDO::FETCH_ASSOC);

if (!$devis) {
    header('Location: index.php');
    exit();
}

// Fetch all clients for the dropdown
$clients = $pdo->query("SELECT id, client_name FROM clients")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all statuses for the dropdown
$statuses = $pdo->query("SELECT status_id, status_name FROM statuses WHERE entity_type = 'devis'")->fetchAll(PDO::FETCH_ASSOC);

// Fetch the products in the devis
$products = $pdo->prepare("
    SELECT dd.product_id, p.product_name, dd.price
    FROM devis_details dd
    JOIN products p ON dd.product_id = p.id
    WHERE dd.devis_id = ?
");
$products->execute([$devis_id]);
$products = $products->fetchAll(PDO::FETCH_ASSOC);

// Fetch all products not in the current devis
$existingProductIds = implode(',', array_column($products, 'product_id'));
$availableProducts = $pdo->prepare("
    SELECT id, product_name FROM products 
    WHERE id NOT IN ($existingProductIds)
");
$availableProducts->execute();
$availableProducts = $availableProducts->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $client_id = $_POST['client_id'];
    $valid_from = $_POST['valid_from'];
    $valid_until = $_POST['valid_until'];
    $status_id = $_POST['status_id'];
    $product_prices = $_POST['product_prices'];  // This will have products' IDs and their prices

    // Update the devis
    $updateDevis = $pdo->prepare("
        UPDATE devis 
        SET client_id = ?, valid_from = ?, valid_until = ?, status_id = ?
        WHERE id = ?
    ");
    $updateDevis->execute([$client_id, $valid_from, $valid_until, $status_id, $devis_id]);

    // Fetch the current products in the devis
    $currentProducts = $pdo->prepare("SELECT product_id, price FROM devis_details WHERE devis_id = ?");
    $currentProducts->execute([$devis_id]);
    $currentProducts = $currentProducts->fetchAll(PDO::FETCH_KEY_PAIR);  // product_id => price

    // Update or insert product details
    foreach ($product_prices as $product_id => $price) {
        if (!empty($price)) {
            if (isset($currentProducts[$product_id])) {
                // If the product already exists, check if the price has changed
                if ($currentProducts[$product_id] != $price) {
                    // Price has changed, update it
                    $updateProduct = $pdo->prepare("
                        UPDATE devis_details SET price = ? 
                        WHERE devis_id = ? AND product_id = ?
                    ");
                    $updateProduct->execute([$price, $devis_id, $product_id]);
                }
            } else {
                // Product is new, insert it
                $insertProduct = $pdo->prepare("
                    INSERT INTO devis_details (devis_id, product_id, price) 
                    VALUES (?, ?, ?)
                ");
                $insertProduct->execute([$devis_id, $product_id, $price]);
            }
        }
    }

    // Remove products that were deleted from the form
    foreach ($currentProducts as $product_id => $price) {
        if (!isset($product_prices[$product_id])) {
            // Product was removed, delete it
            $deleteProduct = $pdo->prepare("
                DELETE FROM devis_details 
                WHERE devis_id = ? AND product_id = ?
            ");
            $deleteProduct->execute([$devis_id, $product_id]);
        }
    }

    // Redirect to index with a success message
    header('Location: index.php?update_success=1');
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Devis</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">
    <link rel="stylesheet" href="../plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="../plugins/select2-bootstrap4-theme/select2-bootstrap4.css">
</head>
<body class="hold-transition dark-mode sidebar-mini">
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
                        <h1>Edit Devis</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item"><a href="devis.php">Devis</a></li>
                            <li class="breadcrumb-item active">Edit Devis</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>
        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Form for editing the devis -->
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Edit Devis <?php echo htmlspecialchars($devis['devis_code']); ?></h3>
                    </div>
                    <form method="POST">
                        <div class="card-body">
                            <!-- Client Selection -->
                            <div class="row">
                            <div class="form-group col-md-4">
                                <label for="client_id">Client</label>
                                <select name="client_id" id="client_id" class="form-control">
                                    <?php foreach ($clients as $client): ?>
                                        <option value="<?php echo $client['id']; ?>" <?php echo ($client['id'] == $devis['client_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($client['client_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Validity Dates -->
                            <div class="form-group col-md-3">
                                <label for="valid_from">Valid From</label>
                                <input type="date" name="valid_from" id="valid_from" class="form-control" value="<?php echo htmlspecialchars($devis['valid_from']); ?>">
                            </div>

                            <div class="form-group col-md-3">
                                <label for="valid_until">Valid Until</label>
                                <input type="date" name="valid_until" id="valid_until" class="form-control" value="<?php echo htmlspecialchars($devis['valid_until']); ?>">
                            </div>

                            <!-- Status Selection -->
                            <div class="form-group col-md-2">
                                <label for="status_id">Status</label>
                                <select name="status_id" id="status_id" class="form-control">
                                    <?php foreach ($statuses as $status): ?>
                                        <option value="<?php echo $status['status_id']; ?>" <?php echo ($status['status_id'] == $devis['status_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($status['status_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            </div>

                            <!-- Product List -->
                            <div class="form-group">
                                <label>Products</label>
                                <table class="table table-bordered">
                                    <thead>
                                    <tr>
                                        <th>Product Name</th>
                                        <th>Price</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody id="productTable">
                                    <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                            <td>
                                                <input type="number" name="product_prices[<?php echo $product['product_id']; ?>]" class="form-control" value="<?php echo htmlspecialchars($product['price']); ?>">
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-danger btn-sm remove-product">
                                                    <i class="fas fa-trash-alt"></i> Remove
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Add New Product -->
                            <div class="form-group">
                                <label for="new_product">Add Product</label>
                                <div class="input-group">
                                    <select id="new_product" class="form-control select2">
                                        <option value="">Select a product</option>
                                        <?php foreach ($availableProducts as $product): ?>
                                            <option value="<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['product_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="number" id="new_product_price" class="form-control" placeholder="Price">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-primary" id="add_product"><i class="fas fa-plus"></i> Add</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit button -->
                        <div class="card-footer">
                            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Update Devis</button>
                            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>
</div>

<script src="../plugins/jquery/jquery.min.js"></script>
<script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../dist/js/adminlte.min.js"></script>
<script src="../plugins/select2/js/select2.full.min.js"></script>
<script>
    $(function () {
    // Initialize Select2 for product selection
    $('.select2').select2({
        theme: 'bootstrap4'
    });

    // Add new product to the table
    $('#add_product').on('click', function () {
        var productId = $('#new_product').val();
        var productName = $('#new_product option:selected').text();
        var productPrice = $('#new_product_price').val();

        // Check if both product and price are selected/entered
        if (!productId) {
            alert("Please select a product.");
            return;
        }
        
        if (!productPrice || productPrice <= 0) {
            alert("Please enter a valid price.");
            return;
        }

        // Append the product to the table if everything is valid
        $('#productTable').append(`
            <tr>
                <td>${productName}</td>
                <td>
                    <input type="number" name="product_prices[${productId}]" class="form-control" value="${productPrice}">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-product">
                        <i class="fas fa-trash-alt"></i> Remove
                    </button>
                </td>
            </tr>
        `);

        // Clear the input fields after adding the product
        $('#new_product').val('').trigger('change');
        $('#new_product_price').val('');
    });

    // Remove product from the table
    $(document).on('click', '.remove-product', function () {
        $(this).closest('tr').remove();
    });
});

</script>
</body>
</html>
