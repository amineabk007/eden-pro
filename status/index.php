<?php
session_start();
require '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$statuses = $pdo->query("SELECT * FROM statuses ORDER BY entity_type, status_id")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Statuses</title>
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">
    <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="../plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="../plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700">
</head>
<body class="hold-transition dark-mode sidebar-mini layout-fixed text-gray">
<div class="wrapper">

    <?php include '../includes/navbar.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Status Management</h1>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Statuses</h3>
                        <button class="btn btn-primary float-right" data-toggle="modal" data-target="#addStatusModal">Add Status</button>
                    </div>
                    <div class="card-body">
                        <table id="statusTable" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Entity Type</th>
                                    <th>Status Name</th>
                                    <th>Class Name</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($statuses as $status): ?>
                                    <tr>
                                        <td><?php echo $status['status_id']; ?></td>
                                        <td><?php echo htmlspecialchars($status['entity_type']); ?></td>
                                        <td><?php echo htmlspecialchars($status['status_name']); ?></td>
                                        <td><span class="<?php echo htmlspecialchars($status['class_name']); ?>"><?php echo htmlspecialchars($status['status_name']); ?></span></td>
                                        <td>
                                            <button class="btn btn-info btn-sm edit-btn" 
                                                    data-id="<?php echo $status['status_id']; ?>" 
                                                    data-entity="<?php echo htmlspecialchars($status['entity_type']); ?>" 
                                                    data-name="<?php echo htmlspecialchars($status['status_name']); ?>" 
                                                    data-class="<?php echo htmlspecialchars($status['class_name']); ?>">Edit</button>
                                            <button class="btn btn-danger btn-sm delete-btn" data-id="<?php echo $status['status_id']; ?>">Delete</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Add Status Modal -->
    <div class="modal fade" id="addStatusModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form id="addStatusForm" action="status_add.php" method="POST">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Status</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Entity Type</label>
                            <input type="text" name="entity_type" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Status Name</label>
                            <input type="text" name="status_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Class Name</label>
                            <input type="text" name="class_name" class="form-control" placeholder="e.g., badge badge-success" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Add Status</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Status Modal -->
    <div class="modal fade" id="editStatusModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form id="editStatusForm" action="status_edit.php" method="POST">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Status</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="edit-status-id" name="status_id">
                        <div class="form-group">
                            <label>Entity Type</label>
                            <input type="text" id="edit-entity-type" name="entity_type" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Status Name</label>
                            <input type="text" id="edit-status-name" name="status_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Class Name</label>
                            <input type="text" id="edit-class-name" name="class_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</div>

<script src="../plugins/jquery/jquery.min.js"></script>
<script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="../plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="../dist/js/adminlte.min.js"></script>
<script>
    $(function () {
        $("#statusTable").DataTable({
            "responsive": true,
            "lengthChange": false,
            "autoWidth": false,
            "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
        }).buttons().container().appendTo('#statusTable_wrapper .col-md-6:eq(0)');
    });

    $(document).ready(function () {
        $('.edit-btn').click(function () {
            $('#edit-status-id').val($(this).data('id'));
            $('#edit-entity-type').val($(this).data('entity'));
            $('#edit-status-name').val($(this).data('name'));
            $('#edit-class-name').val($(this).data('class'));
            $('#editStatusModal').modal('show');
        });

        $('#addStatusForm').submit(function (e) {
            e.preventDefault();
            $.post('status_add.php', $(this).serialize(), function () {
                location.reload();
            });
        });

        $('#editStatusForm').submit(function (e) {
            e.preventDefault();
            $.post('status_edit.php', $(this).serialize(), function () {
                location.reload();
            });
        });

        $('.delete-btn').click(function () {
            if (confirm('Are you sure you want to delete this status?')) {
                $.post('status_delete.php', { status_id: $(this).data('id') }, function () {
                    location.reload();
                });
            }
        });
    });
</script>
</body>
</html>
