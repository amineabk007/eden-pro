<?php
// profile.php
session_start();
require 'config/config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch company info
$stmt = $pdo->prepare("SELECT * FROM company_info WHERE id = 1");
$stmt->execute();
$company = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $company_name = $_POST['company_name'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $postal_code = $_POST['postal_code'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $website = $_POST['website'];
    $ice = $_POST['ice'];
    $i_f = $_POST['i_f'];
    $rc = $_POST['rc'];
    $currency = $_POST['currency'];

    // Logo upload handling
    $logo_path = $company['logo_path'];
    if (!empty($_FILES['logo']['name'])) {
        $target_dir = "uploads/logos/";
        $logo_path = $target_dir . basename($_FILES["logo"]["name"]);
        move_uploaded_file($_FILES["logo"]["tmp_name"], $logo_path);
    }

    // Update company info
    $updateStmt = $pdo->prepare("UPDATE company_info SET 
        company_name = ?, address = ?, city = ?, postal_code = ?, phone = ?, 
        email = ?, website = ?, logo_path = ?, ice = ?, i_f = ?, rc = ?, currency = ?
        WHERE id = 1");
    $updateStmt->execute([
        $company_name, $address, $city, $postal_code, $phone, $email, 
        $website, $logo_path, $ice, $i_f, $rc, $currency
    ]);

    header("Location: company.php?updated=true");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Company - Eden</title>
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700">
    <!-- Custom CSS (Optional) -->
    <link rel="stylesheet" href="dist/css/custom.css">
</head>
<body class="hold-transition dark-mode sidebar-mini layout-fixed layout-navbar-fixed">
    <div class="wrapper">

        <!-- Navbar -->
        <?php include 'includes/navbar.php'; ?>
        <!-- /.navbar -->

        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        <!-- /.sidebar -->

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Company Information</h1>
                        </div><!-- /.col -->
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                                <li class="breadcrumb-item active">Company</li>
                            </ol>
                        </div><!-- /.col -->
                    </div><!-- /.row -->
                </div><!-- /.container-fluid -->
            </div>
            <!-- /.content-header -->

            <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <?php if (isset($_GET['updated'])): ?>
                    <div class="alert alert-success">Company information updated successfully.</div>
                <?php endif; ?>

                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Edit Company Information</h3>
                    </div>
                    <!-- /.card-header -->
                    <!-- form start -->
                    <form action="company.php" method="POST" enctype="multipart/form-data">
                        <div class="card-body">
                            <div class="form-group">
                                <label for="company_name">Company Name</label>
                                <input type="text" class="form-control" id="company_name" name="company_name" 
                                       value="<?php echo htmlspecialchars($company['company_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="address">Address</label>
                                <input type="text" class="form-control" id="address" name="address" 
                                       value="<?php echo htmlspecialchars($company['address']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="city">City</label>
                                <input type="text" class="form-control" id="city" name="city" 
                                       value="<?php echo htmlspecialchars($company['city']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="postal_code">Postal Code</label>
                                <input type="text" class="form-control" id="postal_code" name="postal_code" 
                                       value="<?php echo htmlspecialchars($company['postal_code']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($company['phone']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($company['email']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="website">Website</label>
                                <input type="text" class="form-control" id="website" name="website" 
                                       value="<?php echo htmlspecialchars($company['website']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="logo">Logo</label>
                                <input type="file" class="form-control" id="logo" name="logo">
                                <?php if (!empty($company['logo_path'])): ?>
                                    <p>Current Logo: <img src="<?php echo htmlspecialchars($company['logo_path']); ?>" width="100"></p>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="ice">ICE</label>
                                <input type="text" class="form-control" id="ice" name="ice" 
                                       value="<?php echo htmlspecialchars($company['ice']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="i_f">IF</label>
                                <input type="text" class="form-control" id="i_f" name="i_f" 
                                       value="<?php echo htmlspecialchars($company['i_f']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="rc">RC</label>
                                <input type="text" class="form-control" id="rc" name="rc" 
                                       value="<?php echo htmlspecialchars($company['rc']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="currency">Currency</label>
                                <input type="text" class="form-control" id="currency" name="currency" 
                                       value="<?php echo htmlspecialchars($company['currency']); ?>">
                            </div>
                        </div>
                        <!-- /.card-body -->
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">Update Information</button>
                        </div>
                    </form>
                </div>
                <!-- /.card -->
            </div>
        </section>
    </div>
    <!-- /.content-wrapper -->


        <!-- Footer -->
        <?php include 'includes/footer.php'; ?>
        <!-- /.footer -->

    </div>
    <!-- ./wrapper -->

    <!-- AdminLTE JS -->
    <script src="plugins/jquery/jquery.min.js"></script>
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="dist/js/adminlte.min.js"></script>
    <!-- bs-custom-file-input -->
    <script src="plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
    <script>
        $(document).ready(function () {
            bsCustomFileInput.init()
        })
    </script>
</body>
</html>
