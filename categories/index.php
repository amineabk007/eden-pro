<?php
session_start();
require '../config/config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Fetch categories for the listing
$categoriesQuery = "SELECT * FROM categories";
$categoriesStmt = $pdo->prepare($categoriesQuery);
$categoriesStmt->execute();
$categories = $categoriesStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Categories</title>
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
                        <h1>Manage Categories</h1>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    

                    <!-- Category List -->
                    <div class="col-md-9">
                        <div class="card card-outline card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Category List</h3>
                            </div>
                            <div class="card-body">
                                <table id="categoryTable" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($categories as $category): ?>
                                            <tr data-id="<?php echo $category['id']; ?>">
                                                <td><?php echo $category['id']; ?></td>
                                                <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                                                <td>
                                                    <button class="btn btn-warning btn-edit" data-id="<?php echo $category['id']; ?>">Edit</button>
                                                    <button class="btn btn-danger btn-delete" data-id="<?php echo $category['id']; ?>">Delete</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>


                    <!-- Add Category Form -->
                    <div class="col-md-3">
                        <div class="card card-outline card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Add New Category</h3>
                            </div>
                            <div class="card-body">
                                <form id="addCategoryForm">
                                    <div class="form-group">
                                        <label for="categoryName">Category Name</label>
                                        <input type="text" id="categoryName" name="categoryName" class="form-control" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Add Category</button>
                                </form>
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

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" role="dialog" aria-labelledby="editCategoryLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCategoryLabel">Edit Category</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editCategoryForm">
                    <input type="hidden" name="categoryId" id="editCategoryId">
                    <div class="form-group">
                        <label for="editCategoryName">Category Name</label>
                        <input type="text" class="form-control" id="editCategoryName" name="categoryName" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Save changes</button>
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
<script>
    $(function () {
            $("#categoryTable").DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
                "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
            }).buttons().container().appendTo('#categoryTable_wrapper .col-md-6:eq(0)');
        });

        // Handle add category form submission
        $('#addCategoryForm').on('submit', function (e) {
            e.preventDefault();

            var formData = $(this).serialize();

            $.ajax({
                type: 'POST',
                url: 'add_category.php',
                data: formData,
                success: function (response) {
                    const res = JSON.parse(response);
                    if (res.status === 'success') {
                        alert(res.message);
                        location.reload();
                    } else {
                        alert(res.message);
                    }
                }
            });
        });

        // Handle edit category button click
        $('.btn-edit').on('click', function () {
            const row = $(this).closest('tr');
            const categoryId = $(this).data('id');
            const categoryName = row.find('td:nth-child(2)').text();

            $('#editCategoryId').val(categoryId);
            $('#editCategoryName').val(categoryName);

            $('#editCategoryModal').modal('show');
        });

        // Handle edit category form submission
        $('#editCategoryForm').on('submit', function (e) {
            e.preventDefault();

            var formData = $(this).serialize();

            $.ajax({
                type: 'POST',
                url: 'edit_category.php',
                data: formData,
                success: function (response) {
                    const res = JSON.parse(response);
                    if (res.status === 'success') {
                        alert(res.message);
                        location.reload();
                    } else {
                        alert(res.message);
                    }
                }
            });
        });

        // Handle delete category button click
        $('.btn-delete').on('click', function () {
            const categoryId = $(this).data('id');
            if (confirm('Are you sure you want to delete this category?')) {
                $.ajax({
                    type: 'POST',
                    url: 'delete_category.php',
                    data: { id: categoryId },
                    success: function (response) {
                        const res = JSON.parse(response);
                        if (res.status === 'success') {
                            alert(res.message);
                            location.reload();
                        } else {
                            alert(res.message);
                        }
                    }
                });
            }
        });
    
</script>

</body>
</html>
