<?php
session_start();
require '../config/config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// CSRF Token generation for form submission security
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Function to generate unique devis code
function generateDevisCode($pdo) {
    // Fetch the last devis code and increment it
    $stmt = $pdo->query("SELECT MAX(id) AS max_id FROM devis");
    $lastId = $stmt->fetch(PDO::FETCH_ASSOC)['max_id'];
    $newId = $lastId + 1;
    return "DEV" . str_pad($newId, 4, "0", STR_PAD_LEFT);
}

// Form processing logic (on POST submission)
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Token validation
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = "Invalid CSRF token.";
    }

    // Data sanitization and validation
    $client_id = htmlspecialchars(trim($_POST['client_id']));
    $valid_from = htmlspecialchars(trim($_POST['valid_from']));
    $valid_until = htmlspecialchars(trim($_POST['valid_until']));
    $status_id = htmlspecialchars(trim($_POST['status_id']));
    
    $products = [];
    foreach ($_POST['product_id'] as $key => $product_id) {
        $price = $_POST['product_price'][$key];
        if (!empty($product_id) && !empty($price)) {
            $products[] = [
                'id' => htmlspecialchars($product_id),
                'price' => htmlspecialchars($price)
            ];
        }
    }

    // Server-side validation
    if (empty($client_id)) $errors[] = "Client ID is required.";
    if (empty($valid_from)) $errors[] = "Valid from date is required.";
    if (empty($valid_until)) $errors[] = "Valid until date is required.";
    if (empty($status_id)) $errors[] = "Status is required.";
    if (empty($products)) $errors[] = "At least one product must be added.";

    // If no errors, process form data (database insert logic)
    if (empty($errors)) {
        // Start a transaction for safety
        $pdo->beginTransaction();

        try {
            // Generate the devis code
            $devis_code = generateDevisCode($pdo);

            // Insert the devis into the `devis` table
            $stmt = $pdo->prepare("INSERT INTO devis (devis_code, client_id, created_at, valid_from, valid_until, status_id) VALUES (?, ?, NOW(), ?, ?, ?)");
            $stmt->execute([$devis_code, $client_id, $valid_from, $valid_until, $status_id]);
            $devis_id = $pdo->lastInsertId(); // Get the inserted devis's ID

            // Insert products into the `devis_details` table
            foreach ($products as $product) {
                $stmt = $pdo->prepare("INSERT INTO devis_details (devis_id, product_id, price) VALUES (?, ?, ?)");
                $stmt->execute([$devis_id, $product['id'], $product['price']]);
            }

            // Commit the transaction
            $pdo->commit();
            $success = true;

        } catch (Exception $e) {
            // Rollback the transaction in case of an error
            $pdo->rollBack();
            $errors[] = "An error occurred: " . $e->getMessage();
        }
    }
}

// Fetch clients for the dropdown
$clients = $pdo->query("SELECT id, client_name FROM clients")->fetchAll(PDO::FETCH_ASSOC);

// Fetch products for the Select2 dropdown
$productsList = $pdo->query("SELECT id, product_name FROM products")->fetchAll(PDO::FETCH_ASSOC);

// Fetch statuses for the dropdown
$statuses = $pdo->query("SELECT status_id, status_name FROM statuses WHERE entity_type = 'devis'")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Devis</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">
    <link rel="stylesheet" href="../plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="../plugins/select2-bootstrap4-theme/select2-bootstrap4.css">
    <style>
        .form-group { margin-bottom: 20px; }
    </style>
</head>
<body class="hold-transition dark-mode sidebar-mini layout-fixed text-gray">
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
                        <h1>New Devis</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item"><a href="devis.php">Devis</a></li>
                            <li class="breadcrumb-item active">New Devis</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">

                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Devis Information</h3>
                            </div>
                            <form role="form" method="post">
                                <div class="card-body">

                                    <?php if ($success): ?>
                                        <div class="alert alert-success">Devis created successfully!</div>
                                    <?php elseif (!empty($errors)): ?>
                                        <div class="alert alert-danger">
                                            <ul>
                                                <?php foreach ($errors as $error): ?>
                                                    <li><?php echo htmlspecialchars($error); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>

                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            <label for="client_id">Client</label>
                                            <select class="form-control form-control-border border-width-2" id="client_id" name="client_id" style="width: 100%;">
                                                <option selected disabled>Select Client</option>
                                                <?php foreach ($clients as $client): ?>
                                                    <option value="<?php echo $client['id']; ?>"><?php echo htmlspecialchars($client['client_name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="valid_from">Valid From</label>
                                            <input type="date" class="form-control form-control-border border-width-2" id="valid_from" name="valid_from">
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="valid_until">Valid Until</label>
                                            <input type="date" class="form-control form-control-border border-width-2" id="valid_until" name="valid_until">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label for="status_id">Status</label>
                                            <select class="form-control form-control-border border-width-2" id="status_id" name="status_id" style="width: 100%;">
                                                <option selected disabled>Select Status</option>
                                                <?php foreach ($statuses as $status): ?>
                                                    <option value="<?php echo $status['status_id']; ?>"><?php echo htmlspecialchars($status['status_name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    

                                    <div id="product-fields">
                                        <div class="product-field row">
                                            <div class="form-group col-md-5">
                                                <label for="product_id">Product</label>
                                                <select class="form-control select2" name="product_id[]" style="width: 100%;">
                                                    <option selected disabled>Select Product</option>
                                                    <?php foreach ($productsList as $product): ?>
                                                        <option value="<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['product_name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-5">
                                                <label for="product_price">Price</label>
                                                <input type="text" class="form-control" name="product_price[]" placeholder="Enter Price">
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label>&nbsp;</label>
                                                <button type="button" class="btn btn-danger btn-remove-product" style="margin-top: 2rem;">Remove</button>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="button" class="btn btn-primary" id="add-product">Add Product</button>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-success">Create Devis</button>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>
</div>

<!-- Scripts -->
<script src="../plugins/jquery/jquery.min.js"></script>
<script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../plugins/select2/js/select2.full.min.js"></script>
<script src="../dist/js/adminlte.min.js"></script>
<script>
    $(function () {
        // Initialize Select2 elements
        $('.select2').select2({
            theme: 'bootstrap4',
            placeholder: 'Select Product',
            allowClear: false
        });

        // Add product field functionality
        $('#add-product').on('click', function () {
            var newProductField = `
                <div class="product-field row">
                    <div class="form-group col-md-5">
                        <label for="product_id">Product</label>
                        <select class="form-control select2" name="product_id[]" style="width: 100%;">
                            <option selected disabled>Select Product</option>
                            <?php foreach ($productsList as $product): ?>
                                <option value="<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['product_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-5">
                        <label for="product_price">Price</label>
                        <input type="text" class="form-control" name="product_price[]" placeholder="Enter Price">
                    </div>
                    <div class="form-group col-md-2">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-danger btn-remove-product" style="margin-top: 2rem;">Remove</button>
                    </div>
                </div>
            `;
            $('#product-fields').append(newProductField);
            $('.select2').select2({ theme: 'dark' }); // Reinitialize Select2 for new fields
        });

        // Remove product field functionality
        $('#product-fields').on('click', '.btn-remove-product', function () {
            $(this).closest('.product-field').remove();
        });
    });
</script>
</body>
</html>
