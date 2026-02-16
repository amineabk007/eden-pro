<?php
// admin/users.php
session_start();
require '../config/config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { // Only admins can access
    header('Location: ../login.php');
    exit();
}

// Fetch users from the database
$stmt = $pdo->prepare('SELECT user_id, username, email, role, created_at FROM Users');
$stmt->execute();
$users = $stmt->fetchAll();

// Function to return role badge class
function getRoleBadgeClass($role) {
    switch ($role) {
        case 'admin':
            return 'badge badge-success';
        case 'client':
            return 'badge badge-warning';
        case 'User':
            return 'badge badge-success';
        default:
            return 'badge badge-secondary';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Users - Eden</title>
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
                        <h1>Users Management</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Users</li>
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
                                <h3 class="card-title">Users List</h3>
                                <div class="card-tools">
                                    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addUserModal">Add User</button>
                                </div>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                <table id="usersTable" class="table table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <th>User ID</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Registered At</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($users)): ?>
                                            <?php foreach ($users as $user): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($user['user_id'] ?? ''); ?></td>
                                                    <td><?= htmlspecialchars($user['username'] ?? ''); ?></td>
                                                    <td><?= htmlspecialchars($user['email'] ?? ''); ?></td>
                                                    <td>
                                                        <span class="<?= getRoleBadgeClass($user['role']); ?>">
                                                            <?= htmlspecialchars($user['role'] ?? ''); ?>
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars($user['created_at'] ?? ''); ?></td>
                                                    <td>
                                                        <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editUserModal" data-id="<?= $user['user_id']; ?>" data-username="<?= $user['username']; ?>" data-email="<?= $user['email']; ?>" data-role="<?= $user['role']; ?>">Edit</button>
                                                        <a href="delete_user.php?id=<?= $user['user_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6">No users found.</td>
                                            </tr>
                                        <?php endif; ?>
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

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="add_user.php" method="POST" enctype="multipart/form-data">
                    <!-- Username -->
                    <div class="input-group mb-3">
                        <input type="text" name="username" class="form-control" placeholder="Username" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-user"></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Email -->
                    <div class="input-group mb-3">
                        <input type="email" name="email" class="form-control" placeholder="Email" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-envelope"></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Password -->
                    <div class="input-group mb-3">
                        <input type="password" name="password" class="form-control" placeholder="Password" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Confirm Password -->
                    <div class="input-group mb-3">
                        <input type="password" name="confirm_password" class="form-control" placeholder="Retype password" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Phone -->
                    <div class="input-group mb-3">
                        <input type="text" name="phone" class="form-control" placeholder="Phone">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-phone"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Avatar -->
                    <div class="input-group mb-3">
                        <div class="custom-file">
                            <input type="file" name="avatar" class="custom-file-input" id="avatar">
                            <label class="custom-file-label" for="avatar">Choose avatar</label>
                        </div>
                    </div>

                    <!-- Role (Optional for Admin or other roles) -->
                    <div class="input-group mb-3">
                        <select name="role" class="form-control">
                            <option value="admin">Admin</option>
                            <option value="client">Client</option>
                        </select>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn bg-olive btn-block">Add User</button>
                        </div>
                    </div>
                </form>
                <div id="errorMessages"></div> <div id="successMessage"></div>
            </div>
        </div>
    </div>
</div>


<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="edit_user.php" method="post">
                    <input type="hidden" id="edit_user_id" name="user_id">
                    <div class="form-group">
                        <label for="edit_username">Username</label>
                        <input type="text" class="form-control" id="edit_username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_email">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_role">Role</label>
                        <select class="form-control" id="edit_role" name="role" required>
                            <option value="admin">Admin</option>
                            <option value="client">Client</option>
                            <option value="user">User</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-warning">Update User</button>
                </form>
            </div>
        </div>
    </div>
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
<!-- Include bs-custom-file-input for custom file input fields -->
<script src="../plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
    <script>
        $(document).ready(function () {
            bsCustomFileInput.init()
        });
    </script>

<script>
    $(document).ready(function () {
        $('#usersTable').DataTable({
            "paging": true, // Enable pagination
            "pageLength": 10, // Default number of rows per page
            "responsive": true, 
            "lengthChange": false,
            "buttons": ["copy", "csv", "excel", "pdf", "print"]
        }).buttons().container().appendTo('#usersTable_wrapper .col-md-6:eq(0)');
        

        // Populate Edit User modal with data
        $('#editUserModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget); // Button that triggered the modal
            var userId = button.data('id');
            var username = button.data('username');
            var email = button.data('email');
            var role = button.data('role');

            var modal = $(this);
            modal.find('#edit_user_id').val(userId);
            modal.find('#edit_username').val(username);
            modal.find('#edit_email').val(email);
            modal.find('#edit_role').val(role);
        });
    });
</script>
<script>
$(document).ready(function () {
    $("#addUserForm").submit(function (e) {
        e.preventDefault(); // Prevent default form submission
        var formData = new FormData(this); // Get form data including file

        $.ajax({
            url: 'add_user.php', // Your PHP file
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                // Parse the JSON response
                var res = JSON.parse(response);

                // Clear any previous error/success messages
                $("#errorMessages").empty();
                $("#successMessage").empty();

                if (res.success) {
                    // Show success message
                    $("#successMessage").html('<div class="alert alert-success">' + res.success + '</div>');
                    // Optionally, reset the form
                    $("#addUserForm")[0].reset();
                } else if (res.errors) {
                    // Show error messages
                    var errorsHtml = '<div class="alert alert-danger"><ul>';
                    res.errors.forEach(function (error) {
                        errorsHtml += '<li>' + error + '</li>';
                    });
                    errorsHtml += '</ul></div>';
                    $("#errorMessages").html(errorsHtml);
                }
            },
            error: function (xhr, status, error) {
                // Handle errors
                $("#errorMessages").html('<div class="alert alert-danger">An error occurred. Please try again.</div>');
            }
        });
    });
});

</script>
</body>
</html>
