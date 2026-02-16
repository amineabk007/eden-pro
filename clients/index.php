<?php
// clients/index.php
session_start();
require '../config/config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Fetch all clients from the database
try {
    $stmt = $pdo->query("SELECT * FROM clients");
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die('Error fetching clients: ' . $e->getMessage());
}
?>

<head>
    <meta charset="UTF-8">
    <title>Clients - Eden</title>
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="../plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="../plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="../plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
    <!-- Google Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700">
</head>

<body class="hold-transition dark-mode sidebar-mini layout-fixed layout-navbar-fixed">
    <div class="wrapper">

        <!-- Navbar -->
        <?php include '../includes/navbar.php'; ?>
        <!-- /.navbar -->

        <!-- Sidebar -->
        <?php include '../includes/sidebar.php'; ?>
        <!-- /.sidebar -->

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Clients</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="#">Home</a></li>
                                <li class="breadcrumb-item active">Clients</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Client List</h3>
                                    <div class="card-tools">
                                        <a href="create.php" class="btn btn-primary btn-sm">Add Client</a>
                                    </div>
                                </div>
                                <!-- /.card-header -->
                                <div class="card-body">
                                    <table id="clientsTable" class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Logo</th>
                                                <th>Client Code</th>
                                                <th>Client Name</th>
                                                <th>Client Type</th>
                                                <th>Telephone</th>
                                                <th>Email</th>
                                                <th>City</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($clients as $client): ?>
                                                <tr>
                                                    <td><img src='/uploads/logos/<?php echo htmlspecialchars($client['logo']); ?>' alt='Logo' class='img-thumbnail' style='max-width: 50px; max-height: 50px;'></td>
                                                    <td><?php echo htmlspecialchars($client['client_code']); ?></td>
                                                    <td><?php echo htmlspecialchars($client['client_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($client['client_type']); ?></td>
                                                    <td><?php echo htmlspecialchars($client['telephone']); ?></td>
                                                    <td><?php echo htmlspecialchars($client['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($client['city']); ?></td>
                                                    <td>
                                                        <a href="view.php?id=<?php echo $client['id']; ?>" class="btn btn-info btn-sm" title="View Client">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="edit.php?id=<?php echo $client['id']; ?>" class="btn btn-warning btn-sm" title="Edit Client">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="delete.php?id=<?php echo $client['id']; ?>" class="btn btn-danger btn-sm" title="Delete Client" onclick="return confirm('Are you sure?');">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <!-- /.card-body -->
                            </div>
                            <!-- /.card -->
                        </div>
                        <!-- /.col -->
                    </div>
                    <!-- /.row -->
                </div>
                <!-- /.container-fluid -->
            </section>
            <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->

        <!-- Footer -->
        <?php include '../includes/footer.php'; ?>
        <!-- /.footer -->
        
    </div>

    <!-- jQuery -->
    <script src="../plugins/jquery/jquery.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables -->
    <script src="../plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="../plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="../plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
    <script src="../plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
    <script src="../plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
    <script src="../plugins/jszip/jszip.min.js"></script>
    <script src="../plugins/pdfmake/pdfmake.min.js"></script>
    <script src="../plugins/pdfmake/vfs_fonts.js"></script>
    <script src="../plugins/datatables-buttons/js/buttons.html5.min.js"></script>
    <script src="../plugins/datatables-buttons/js/buttons.print.min.js"></script>
    <script src="../plugins/datatables-buttons/js/buttons.colVis.min.js"></script>
    <!-- AdminLTE App -->
    <script src="../dist/js/adminlte.min.js"></script>

    <!-- Page specific script -->
    <script>
        $(function () {
            $("#clientsTable").DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
                "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
            }).buttons().container().appendTo('#clientsTable_wrapper .col-md-6:eq(0)');
        });
    </script>
</body>
</html>
