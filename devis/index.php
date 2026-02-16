<?php
// devis/index.php
session_start();
require '../config/config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Fetch all devis
$devisList = $pdo->query("
    SELECT d.id, d.devis_code, c.client_name, d.created_at, d.valid_from, d.valid_until, s.status_name, s.class_name
    FROM devis d
    JOIN clients c ON d.client_id = c.id
    JOIN statuses s ON d.status_id = s.status_id
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Devis Management</title>
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="../plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="../plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <!-- Custom styles -->
    <style>
        
    </style>
</head>
<body class="hold-transition dark-mode sidebar-mini layout-fixed">
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
                        <h1>Devis Management</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Devis</li>
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
                                <h3 class="card-title">Devis List</h3>
                                <div class="card-tools">
                                        <a href="create.php" class="btn btn-primary btn-sm">Add Devis</a>
                                </div>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                <table id="devisTable" class="table table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Code</th>
                                        <th>Client</th>
                                        <th>Created At</th>
                                        <th>Valid From</th>
                                        <th>Valid Until</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($devisList as $devis): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($devis['id']); ?></td>
                                            <td><?php echo htmlspecialchars($devis['devis_code']); ?></td>
                                            <td><?php echo htmlspecialchars($devis['client_name']); ?></td>
                                            <td><?php echo htmlspecialchars($devis['created_at']); ?></td>
                                            <td><?php echo htmlspecialchars($devis['valid_from']); ?></td>
                                            <td><?php echo htmlspecialchars($devis['valid_until']); ?></td>
                                            <td><span class="<?php echo htmlspecialchars($devis['class_name']); ?>"><?php echo htmlspecialchars($devis['status_name']); ?></span></td>
                                            <td>
                                                <a href="view.php?id=<?php echo $devis['id']; ?>" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a>
                                                <a href="edit.php?id=<?php echo $devis['id']; ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                                <a href="delete.php?id=<?php echo $devis['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this devis?');"><i class="fas fa-trash"></i></a>
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
                </div>
            </div>
        </section>
        <!-- /.content -->
    </div>
    <!-- Footer -->
<?php include '../includes/footer.php'; ?>
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
<!-- Custom Script -->
<script>
    $(function () {
        $('#devisTable').DataTable({
            "responsive": true, "lengthChange": false, "autoWidth": false,
            "buttons": ["copy", "csv", "excel", "pdf", "print"]
        }).buttons().container().appendTo('#devisTable_wrapper .col-md-6:eq(0)');
    });
</script>
</body>
</html>
