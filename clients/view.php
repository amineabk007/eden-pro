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

// Fetch client data
$query = "SELECT * FROM clients WHERE id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$client_id]);

if ($stmt->rowCount() === 0) {
    header('Location: index.php'); // Redirect if client not found
    exit();
}

$client = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>View Client</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="../plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
</head>
<body class="hold-transition dark-mode sidebar-mini layout-fixed text-gray">
<div class="wrapper">

  <!-- Navbar -->
  <?php include '../includes/navbar.php'; ?>
  <!-- Sidebar -->
  <?php include '../includes/sidebar.php'; ?>

  <!-- Content Wrapper -->
  <div class="content-wrapper">
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1><?php echo htmlspecialchars($client['client_name']); ?>
            <a href="tel:<?php echo htmlspecialchars($client['telephone']); ?>" class="btn btn-success">
                <i class="fas fa-phone-alt"></i>
                </a>
                <a href="mailto:<?php echo htmlspecialchars($client['email']); ?>" class="btn btn-info">
                <i class="fas fa-envelope"></i>
                </a>
                <a href="<?php echo htmlspecialchars($client['website']); ?>" target="_blank" class="btn btn-primary">
                <i class="fas fa-globe"></i>
                </a></h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item"><a href="/clients">Clients</a></li>
              <li class="breadcrumb-item active"><?php echo htmlspecialchars($client['client_name']); ?></li>
            </ol>
          </div>
        </div>
      </div>
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <!-- Client Details Card -->
          <div class="col-md-4">
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">Client Information</h3>
              </div>
              <div class="card-body">
                <div class="row">
                <div class="col-md-8">
                <strong><i class="fas fa-user mr-1"></i> Name</strong>
                <p class="text-muted"><?php echo htmlspecialchars($client['client_name']); ?></p>
                <hr>

                <strong><i class="fas fa-building mr-1"></i> Client Type</strong>
                <p class="text-muted"><?php echo htmlspecialchars($client['client_type']); ?></p>
                <hr>

                <strong><i class="fas fa-phone-alt mr-1"></i> Telephone</strong>
                <p class="text-muted"><?php echo htmlspecialchars($client['telephone']); ?></p>
                <hr>

                <strong><i class="fas fa-envelope mr-1"></i> Email</strong>
                <p class="text-muted"><?php echo htmlspecialchars($client['email']); ?></p>
                <hr>

                <strong><i class="fas fa-globe mr-1"></i> Website</strong>
                <p class="text-muted"><?php echo htmlspecialchars($client['website']); ?></p>
                <hr>

                <strong><i class="fas fa-map-marker-alt mr-1"></i> Address</strong>
                <p class="text-muted"><?php echo htmlspecialchars($client['address'] . ', ' . $client['city'] . ', ' . $client['postal_code']); ?></p>
                </div>
                <div class="col-md-4">

                <img src="../uploads/logos/<?php echo htmlspecialchars($client['logo']); ?>" alt="Client Logo" class="img-fluid img-thumbnail" style="width: 150px;">
                </p>
                </div>
                </div>
                <div class="d-flex justify-content-end">
                <a href="edit_client.php?id=<?php echo $client['id']; ?>" class="btn btn-warning mr-2">
                    <i class="fas fa-edit"></i> 
                </a>
                <a href="delete_client.php?id=<?php echo $client['id']; ?>" class="btn btn-danger">
                    <i class="fas fa-trash"></i> 
                </a>
                <a href="send_message.php?client_id=<?php echo $client['id']; ?>" class="btn btn-info ml-2">
                    <i class="fas fa-envelope"></i>
                </a>
                </div>

              </div>
            </div>
          </div>

          <!-- Tabs for Order History, Payment History, etc. -->
          <div class="col-md-8">
            <div class="card card-secondary">
              <div class="card-header">
                <h3 class="card-title">Client Details</h3>
              </div>
              <div class="card-body p-0">
                <ul class="nav nav-tabs" id="client-tabs" role="tablist">
                  <li class="nav-item">
                    <a class="nav-link active" id="order-history-tab" data-toggle="pill" href="#order-history" role="tab">Order History</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="payment-history-tab" data-toggle="pill" href="#payment-history" role="tab">Payment History</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="other-tab" data-toggle="pill" href="#other" role="tab">Other Info</a>
                  </li>
                </ul>
                <div class="tab-content">
                  <!-- Order History Tab -->
                  <div class="tab-pane fade show active" id="order-history">
                    <div class="p-3">
                      <h4>Order History</h4>
                      <table id="order-history-table" class="table table-bordered table-striped">
                        <thead>
                          <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                          </tr>
                        </thead>
                        <tbody>
                          <!-- Dynamic order history data will be inserted here in the future -->
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <!-- Payment History Tab -->
                  <div class="tab-pane fade" id="payment-history">
                    <div class="p-3">
                      <h4>Payment History</h4>
                      <table id="payment-history-table" class="table table-bordered table-striped">
                        <thead>
                          <tr>
                            <th>Payment ID</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                          </tr>
                        </thead>
                        <tbody>
                          <!-- Dynamic payment history data will be inserted here in the future -->
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <!-- Other Info Tab -->
                  <div class="tab-pane fade" id="other">
                    <div class="p-3">
                      <h4>Other Info</h4>
                      <p>Additional client-related information can be added here in the future.</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="card card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Recent Activity</h3>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                    <li class="list-group-item">Order #123 placed on 2024-10-18</li>
                    <li class="list-group-item">Payment of $500 received on 2024-10-15</li>
                    <li class="list-group-item">Client details updated on 2024-10-12</li>
                    </ul>
                </div>
            </div>
            <div class="card card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Client Notes</h3>
                </div>
                <div class="card-body">
                    <textarea class="form-control" rows="4" placeholder="Add any important notes about the client here..."></textarea>
                </div>
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
<script src="../plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="../plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="../dist/js/adminlte.min.js"></script>
<script>
  $(function () {
    $('#order-history-table').DataTable({
      "paging": true,
      "lengthChange": false,
      "searching": true,
      "ordering": true,
      "info": true,
      "autoWidth": false,
      "responsive": true,
    });

    $('#payment-history-table').DataTable({
      "paging": true,
      "lengthChange": false,
      "searching": true,
      "ordering": true,
      "info": true,
      "autoWidth": false,
      "responsive": true,
    });
  });
</script>
</body>
</html>
