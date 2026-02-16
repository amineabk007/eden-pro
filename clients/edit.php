<?php
session_start();
require '../config/config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Check if client ID is provided
if (!isset($_GET['id'])) {
    header('Location: index.php'); // Redirect if no ID is provided
    exit();
}

$client_id = $_GET['id'];

// Fetch client data from the database
$query = "SELECT * FROM clients WHERE id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$client_id]);

if ($stmt->rowCount() === 0) {
    header('Location: index.php'); // Redirect if client not found
    exit();
}

$client = $stmt->fetch();
$errors = [];
$success = false;

// Update client data if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Token validation
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = "Invalid CSRF token.";
    }

    // Data sanitization and validation
    $client_name = htmlspecialchars(trim($_POST['client_name']));
    $client_type = htmlspecialchars(trim($_POST['client_type']));
    $telephone = htmlspecialchars(trim($_POST['telephone']));
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $website = filter_input(INPUT_POST, 'website', FILTER_VALIDATE_URL);
    $address = htmlspecialchars(trim($_POST['address']));
    $city = htmlspecialchars(trim($_POST['city']));
    $zip = htmlspecialchars(trim($_POST['zip']));
    $rc = htmlspecialchars(trim($_POST['rc']));
    $if = htmlspecialchars(trim($_POST['i_f']));
    $ice = htmlspecialchars(trim($_POST['ice']));

    // Server-side validation
    if (empty($client_name)) $errors[] = "Client name is required.";
    if (empty($client_type)) $errors[] = "Client type is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
    if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) $errors[] = "Invalid website URL.";

    // If no errors, process form data (database update logic)
    if (empty($errors)) {
        // Start a transaction for safety
        $pdo->beginTransaction();

        try {
            // Update the client in the `clients` table
            $stmt = $pdo->prepare("UPDATE clients SET client_name = ?, client_type = ?, telephone = ?, email = ?, website = ?, address = ?, city = ?, postal_code = ?, rc = ?, i_f = ?, ice = ? WHERE id = ?");
            $stmt->execute([$client_name, $client_type, $telephone, $email, $website, $address, $city, $zip, $rc, $if, $ice, $client_id]);

            // Handle file upload (Logo)
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['logo']['tmp_name'];
                $fileName = $_FILES['logo']['name'];
                $fileNameCmps = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));
                $allowedfileExtensions = ['jpg', 'gif', 'png'];

                // Check file extension
                if (in_array($fileExtension, $allowedfileExtensions)) {
                    $newFileName = md5(time() . $fileName) . '.' . $fileExtension;

                    // Directory for logo and avatar
                    $uploadLogoDir = '../uploads/logos/';
                    $uploadAvatarDir = '../assets/avatars/';
                    $logoPath = $uploadLogoDir . $newFileName;
                    $avatarPath = $uploadAvatarDir . $newFileName;

                    // Fetch the current logo to remove it if it exists
                    $stmt = $pdo->prepare("SELECT logo FROM clients WHERE id = ?");
                    $stmt->execute([$client_id]);
                    $currentLogo = $stmt->fetchColumn();

                    // Remove the old logo if it exists
                    if (!empty($currentLogo) && file_exists($uploadLogoDir . $currentLogo)) {
                        unlink($uploadLogoDir . $currentLogo); // Delete the old logo
                    }

                    // Move the uploaded file to the logo directory
                    if (move_uploaded_file($fileTmpPath, $logoPath)) {
                        // Now move the logo to the avatar directory
                        if (!copy($logoPath, $avatarPath)) {
                            $errors[] = "Failed to copy logo to avatar directory.";
                        }

                        // Update client's logo in the database
                        $stmt = $pdo->prepare("UPDATE clients SET logo = ? WHERE id = ?");
                        $stmt->execute([$newFileName, $client_id]);

                        // Update user's avatar in the database
                        $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE user_id = (SELECT user_id FROM clients WHERE id = ?)");
                        $stmt->execute([$newFileName, $client_id]);
                    } else {
                        $errors[] = "Failed to move the uploaded file to the logo directory.";
                    }
                } else {
                    $errors[] = "Invalid file type. Only JPG, GIF, and PNG files are allowed.";
                }
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

// CSRF Token generation for form submission security
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Client</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="../plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
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
              <h1>Edit Client</h1>
            </div>
            <div class="col-sm-6">
              <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="clients.php">Clients</a></li>
                <li class="breadcrumb-item active">Edit Client</li>
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
                  <h3 class="card-title">Client Information</h3>
                </div>
                <form role="form" method="post" enctype="multipart/form-data">
                  <div class="card-body">

                    <!-- Display success or error messages -->
                    <?php if ($success): ?>
                      <div class="alert alert-success">Client updated successfully!</div>
                    <?php elseif (!empty($errors)): ?>
                      <div class="alert alert-danger">
                        <ul>
                          <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                          <?php endforeach; ?>
                        </ul>
                      </div>
                    <?php endif; ?>

                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <div class="row">
                      <div class="form-group col-md-4">
                        <label for="client_code">Client Code</label>
                        <input type="text" class="form-control form-control-border border-width-2" id="client_code" name="client_code" value="<?php echo htmlspecialchars($client['client_code']); ?>" readonly>
                      </div>
                      <div class="form-group col-md-4">
                        <label for="client_name">Client Name</label>
                        <input type="text" class="form-control form-control-border border-width-2" id="client_name" name="client_name" value="<?php echo htmlspecialchars($client['client_name']); ?>" placeholder="Enter Client Name">
                      </div>
                      <div class="form-group col-md-4">
                        <label for="client_type">Client Type</label>
                        <select class="form-control form-control-border border-width-2" id="client_type" name="client_type" style="width: 100%;">
                          <option value="Client" <?php echo $client['client_type'] === 'Client' ? 'selected' : ''; ?>>Client</option>
                          <option value="Prospect" <?php echo $client['client_type'] === 'Prospect' ? 'selected' : ''; ?>>Prospect</option>
                          <option value="Supplier" <?php echo $client['client_type'] === 'Supplier' ? 'selected' : ''; ?>>Supplier</option>
                          <option value="Other" <?php echo $client['client_type'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                      </div>
                    </div>

                    <div class="row">
                      <div class="form-group col-md-4">
                        <label for="telephone">Telephone</label>
                        <input type="text" class="form-control form-control-border border-width-2" id="telephone" name="telephone" value="<?php echo htmlspecialchars($client['telephone']); ?>" placeholder="Enter Telephone">
                      </div>
                      <div class="form-group col-md-4">
                        <label for="email">Email</label>
                        <input type="email" class="form-control form-control-border border-width-2" id="email" name="email" value="<?php echo htmlspecialchars($client['email']); ?>" placeholder="Enter Email">
                      </div>
                      <div class="form-group col-md-4">
                        <label for="website">Website</label>
                        <input type="url" class="form-control form-control-border border-width-2" id="website" name="website" value="<?php echo htmlspecialchars($client['website']); ?>" placeholder="Enter Website">
                      </div>
                    </div>

                    <div class="row">
                      <div class="form-group col-md-4">
                        <label for="address">Address</label>
                        <input type="text" class="form-control form-control-border border-width-2" id="address" name="address" value="<?php echo htmlspecialchars($client['address']); ?>" placeholder="Enter Address">
                      </div>
                      <div class="form-group col-md-4">
                        <label for="city">City</label>
                        <input type="text" class="form-control form-control-border border-width-2" id="city" name="city" value="<?php echo htmlspecialchars($client['city']); ?>" placeholder="Enter City">
                      </div>
                      <div class="form-group col-md-4">
                        <label for="zip">Postal Code</label>
                        <input type="text" class="form-control form-control-border border-width-2" id="zip" name="zip" value="<?php echo htmlspecialchars($client['postal_code']); ?>" placeholder="Enter Postal Code">
                      </div>
                    </div>

                    <div class="row">
                      <div class="form-group col-md-4">
                        <label for="rc">RC</label>
                        <input type="text" class="form-control form-control-border border-width-2" id="rc" name="rc" value="<?php echo htmlspecialchars($client['rc']); ?>" placeholder="Enter RC">
                      </div>
                      <div class="form-group col-md-4">
                        <label for="i_f">I.F</label>
                        <input type="text" class="form-control form-control-border border-width-2" id="i_f" name="i_f" value="<?php echo htmlspecialchars($client['i_f']); ?>" placeholder="Enter I.F">
                      </div>
                      <div class="form-group col-md-4">
                        <label for="ice">ICE</label>
                        <input type="text" class="form-control form-control-border border-width-2" id="ice" name="ice" value="<?php echo htmlspecialchars($client['ice']); ?>" placeholder="Enter ICE">
                      </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-4">
                        <label for="logo">Client Logo</label>
                        <div class="custom-file">
                        <input type="file" class="custom-file-input" id="logo" name="logo">
                            <label class="custom-file-label" for="logo">Choose Logo</label>
                        <small class="form-text text-muted">Leave blank if you don't want to change the logo.</small>                      
                        </div>                        
                    </div>
                    <img id="logoPreview" src="" alt="Logo Preview" style="display: none; max-width: 150px;" />
                    </div>
                    </div>

                  <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Update Client</button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
</div>

<!-- Footer -->
<?php include '../includes/footer.php'; ?>

<!-- Scripts -->
<script src="../plugins/jquery/jquery.min.js"></script>
<script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../plugins/select2/js/select2.full.min.js"></script>
<script src="../dist/js/adminlte.min.js"></script>
<script>
  $(function () {
    // Initialize Select2 elements
    $('.select2').select2();

    // Custom file input label update
    $(".custom-file-input").on("change", function() {
      var fileName = $(this).val().split("\\").pop();
      $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
    });
  });

  
    document.getElementById('logo').onchange = function (e) {
        const [file] = e.target.files;
        if (file) {
            document.getElementById('logoPreview').src = URL.createObjectURL(file);
            document.getElementById('logoPreview').style.display = 'block';
        }
    };
</script>
</body>
</html>
