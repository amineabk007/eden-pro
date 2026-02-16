<?php
session_start();
require '../config/config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Fetch categories for the dropdown
$categoriesQuery = "SELECT * FROM categories";
$categoriesStmt = $pdo->prepare($categoriesQuery);
$categoriesStmt->execute();
$categories = $categoriesStmt->fetchAll();

// Fetch products for the listing
$productsQuery = "SELECT p.*, c.category_name FROM products p JOIN categories c ON p.category_id = c.id";
$productsStmt = $pdo->prepare($productsQuery);
$productsStmt->execute();
$products = $productsStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Products</title>
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
                        <h1>Manage Products</h1>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <!-- Add Product Form -->
                    <div class="col-md-6">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Add New Product</h3>
                            </div>
                            <div class="card-body">
                                <form id="addProductForm" enctype="multipart/form-data">
                                    <div class="form-group">
                                        <label for="productName">Product Name</label>
                                        <input type="text" id="productName" name="productName" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="category">Category</label>
                                        <select id="category" name="category" class="form-control" required>
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['category_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="productImage">Product Image</label>
                                        <input type="file" id="productImage" name="productImage" class="form-control" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Add Product</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- View Selected Product -->
                    <div class="col-md-6">
                        <div class="card card-secondary">
                            <div class="card-header">
                                <h3 class="card-title">Product Details</h3>
                            </div>
                            <div class="card-body" id="productDetails">
                                <p>Select a product to view details.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Product List -->
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Product List</h3>
                            </div>
                            <div class="card-body">
                                <table id="productTable" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Category</th>
                                            <th>Image</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($products as $product): ?>
                                            <tr data-id="<?php echo $product['id']; ?>" data-category-id="<?php echo $product['category_id']; ?>">

                                                <td><?php echo $product['id']; ?></td>
                                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                                <td> <?php
                                                        // Use a placeholder image if the product image is NULL or empty
                                                        $imagePath = !empty($product['image']) ? "../uploads/products/" . htmlspecialchars($product['image']) : "../uploads/products/placeholder.jpg";
                                                        ?>
                                                        <img src="<?php echo $imagePath; ?>" alt="Product Image" class="img-fluid" style="width: 50px;"></td>
                                                <td>
                                                    <button class="btn btn-info btn-view">View</button>
                                                    <button class="btn btn-warning btn-edit" data-id="<?php echo $product['id']; ?>">Edit</button>
                                                    <button class="btn btn-danger btn-delete" data-id="<?php echo $product['id']; ?>">Delete</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
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

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1" role="dialog" aria-labelledby="editProductLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProductLabel">Edit Product</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editProductForm" enctype="multipart/form-data">
                    <input type="hidden" name="productId" id="editProductId">
                    <div class="form-group">
                        <label for="editProductName">Product Name</label>
                        <input type="text" class="form-control" id="editProductName" name="productName" required>
                    </div>
                    <div class="form-group">
                        <label for="editCategory">Category</label>
                        <select class="form-control" id="editCategory" name="category" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['category_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <img src="" alt="Product Image" class="img-thumbnail mt-2" style="width: 150px; height: 150px;">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="editProductImage">Product Image</label>
                            <input type="file" class="form-control" id="editProductImage" name="productImage">
                        </div>                        
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
            $("#productTable").DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
                "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
            }).buttons().container().appendTo('#productTable_wrapper .col-md-6:eq(0)');
        

        // Use event delegation to handle view button clicks
        $(document).on('click', '.btn-view', function () {
            const row = $(this).closest('tr');
            const productId = row.data('id');
            const productName = row.find('td:nth-child(2)').text();
            const productCategory = row.find('td:nth-child(3)').text();
            const productImage = row.find('td:nth-child(4) img').attr('src');

            $('#productDetails').html(`
                <h4>${productName}</h4>
                <p><strong>Category:</strong> ${productCategory}</p>
                <img src="${productImage}" alt="Product Image" class="img-thumbnail mt-2" style="width: 150px; height: 150px;">
            `);
        });

        // Handle add product form submission
        $('#addProductForm').on('submit', function (e) {
            e.preventDefault();

            var formData = new FormData(this);

            $.ajax({
                type: 'POST',
                url: 'add_product.php',
                data: formData,
                processData: false,
                contentType: false,
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

        // Use event delegation to handle edit button clicks
        $(document).on('click', '.btn-edit', function () {
            const row = $(this).closest('tr');
            const productId = $(this).data('id');
            const productName = row.find('td:nth-child(2)').text();
            const productCategoryId = row.data('category-id');
            const productImage = row.find('td:nth-child(4) img').attr('src');

            $('#editProductId').val(productId);
            $('#editProductName').val(productName);
            $('#editCategory').val(productCategoryId);

            // Update the modal image source
            $('#editProductModal img').attr('src', productImage);

            $('#editProductModal').modal('show');
        });

        // Handle edit product form submission
        $('#editProductForm').on('submit', function (e) {
            e.preventDefault();

            var formData = new FormData(this);

            $.ajax({
                type: 'POST',
                url: 'edit_product.php',
                data: formData,
                processData: false,
                contentType: false,
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

        // Use event delegation to handle delete button clicks
        $(document).on('click', '.btn-delete', function () {
            const productId = $(this).data('id');
            if (confirm('Are you sure you want to delete this product?')) {
                $.ajax({
                    type: 'POST',
                    url: 'delete_product.php',
                    data: { id: productId },
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
    });
</script>

</body>
</html>
