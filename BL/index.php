<?php
// BL/index.php
session_start();
require '../config/config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Fetch all delivery notes including order codes and client information
$blList = $pdo->query("
    SELECT bl.id, bl.delivery_note_code AS bl_code, c.client_name, c.logo, bl.delivery_date, s.status_name, s.class_name, o.order_total, o.order_code
    FROM delivery_notes bl
    JOIN orders o ON bl.order_id = o.id
    JOIN clients c ON o.client_id = c.id
    JOIN statuses s ON bl.status_id = s.status_id
    WHERE s.entity_type = 'bl'
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BL Management</title>
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="../plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="../plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
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
                        <h1>BL Management</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">BLs</li>
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
                                <h3 class="card-title">BL List</h3>
                                <div class="card-tools">
                                    <!-- <a href="create.php" class="btn btn-primary btn-sm">Add BL</a> -->
                                </div>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                <table id="blTable" class="table table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <th>Logo</th>
                                        <th>BL Code</th>
                                        <th>Order Code</th> 
                                        <th>Client</th>
                                        <th>Delivery Date</th>
                                        <th>Status</th>
                                        <th>Total Price</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($blList as $bl): ?>
                                        <tr>
                                            <td><img src='/uploads/logos/<?php echo htmlspecialchars($bl['logo']); ?>' alt='Logo' class='img-thumbnail' style='max-width: 50px; max-height: 50px;'></td>
                                            <td><?php echo htmlspecialchars($bl['bl_code']); ?></td>
                                            <td><?php echo htmlspecialchars($bl['order_code']); ?></td> <!-- Displaying Order Code -->
                                            <td><?php echo htmlspecialchars($bl['client_name']); ?></td>
                                            <td><?php echo htmlspecialchars($bl['delivery_date']); ?></td>
                                            <td><span class="<?php echo htmlspecialchars($bl['class_name']); ?>"><?php echo htmlspecialchars($bl['status_name']); ?></span></td>
                                            <td><?php echo htmlspecialchars($bl['order_total']); ?></td>
                                            <td>
                                                <a href="view.php?id=<?php echo $bl['id']; ?>" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a>
                                                <a href="print.php?id=<?php echo $bl['id']; ?>" class="btn btn-warning btn-sm"><i class="fas fa-print"></i></a>
                                                <a href="delete.php?id=<?php echo $bl['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this BL?');"><i class="fas fa-trash"></i></a>
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
        $('#blTable').DataTable({
            "responsive": true, "lengthChange": false, "autoWidth": false,
            "buttons": ["copy", "csv", "excel", "pdf", "print"]
        }).buttons().container().appendTo('#blTable_wrapper .col-md-6:eq(0)');
    });
</script>
</body>
</html>
