<?php
// bl/view.php
session_start();
require '../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Fetch delivery note details
$delivery_note_id = $_GET['id'];
$deliveryNoteQuery = $pdo->prepare("
    SELECT dn.delivery_note_code, dn.delivery_date, o.order_code, o.order_date, o.order_total,
           c.client_code, c.client_name, c.telephone, c.email, c.logo, 
           s.status_name, s.class_name,
           od.product_id, p.product_name, od.quantity, od.unit_price
    FROM delivery_notes dn
    JOIN orders o ON dn.order_id = o.id
    JOIN clients c ON o.client_id = c.id
    JOIN statuses s ON dn.status_id = s.status_id
    JOIN order_details od ON o.id = od.order_id
    JOIN products p ON od.product_id = p.id
    WHERE dn.id = ?
");
$deliveryNoteQuery->execute([$delivery_note_id]);
$deliveryNote = $deliveryNoteQuery->fetchAll(PDO::FETCH_ASSOC);

if (!$deliveryNote) {
    echo "Delivery note not found.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Delivery Note</title>
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">
    <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
</head>
<body class="hold-transition dark-mode sidebar-mini layout-fixed">
<div class="wrapper">
    <?php include '../includes/navbar.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Delivery Note Details</h1>
                    </div>
                    <div class="col-sm-6 text-right">
                        <a href="print.php?id=<?php echo $delivery_note_id; ?>" target="_blank" class="btn btn-secondary"><i class="fas fa-print"></i> Print</a>
                        <button id="pdfExport" class="btn btn-secondary"><i class="fas fa-file-pdf"></i> Export PDF</button>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Delivery Note Information</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Client and Order Information -->
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <img src="/uploads/logos/<?php echo htmlspecialchars($deliveryNote[0]['logo']); ?>" alt="Client Logo" class="img-thumbnail" style="max-width: 100px;">
                            </div>
                            <div class="col-md-5">
                                <h5><strong>Client:</strong> <?php echo htmlspecialchars($deliveryNote[0]['client_name']); ?></h5>
                                <p><strong>Code:</strong> <?php echo htmlspecialchars($deliveryNote[0]['client_code']); ?></p>
                                <p><strong>Contact:</strong> <?php echo htmlspecialchars($deliveryNote[0]['telephone']); ?> | <?php echo htmlspecialchars($deliveryNote[0]['email']); ?></p>
                            </div>
                            <div class="col-md-4">
                                <p><strong>BL Code:</strong> <?php echo htmlspecialchars($deliveryNote[0]['delivery_note_code']); ?></p>
                                <p><strong>Order Code:</strong> <?php echo htmlspecialchars($deliveryNote[0]['order_code']); ?></p>
                                <p><strong>Delivery Date:</strong> <?php echo htmlspecialchars($deliveryNote[0]['delivery_date']); ?></p>
                                <p><strong>Status:</strong> <span class="<?php echo htmlspecialchars($deliveryNote[0]['class_name']); ?>"><?php echo htmlspecialchars($deliveryNote[0]['status_name']); ?></span></p>
                            </div>
                        </div>

                        <!-- Product Details Table -->
                        <table id="productsTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($deliveryNote as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                        <td><?php echo htmlspecialchars($item['unit_price']); ?></td>
                                        <td><?php echo htmlspecialchars($item['quantity'] * $item['unit_price']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <!-- Total Order Price -->
                        <div class="row mt-4">
                            <div class="col-12 text-right">
                                <h5><strong>Total Order Price:</strong> <?php echo htmlspecialchars($deliveryNote[0]['order_total']); ?></h5>
                            </div>
                        </div>

                        <!-- Additional Notes Section with AJAX Save -->
                        <div class="form-group mt-4">
                            <label for="deliveryNotes">Additional Notes</label>
                            <textarea id="deliveryNotes" class="form-control" rows="3" placeholder="Add any specific delivery instructions here..."></textarea>
                            <button id="saveNotes" class="btn btn-success mt-2">Save Notes</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <?php include '../includes/footer.php'; ?>
</div>

<script src="../plugins/jquery/jquery.min.js"></script>
<script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../dist/js/adminlte.min.js"></script>
<!-- jsPDF and jsPDF-AutoTable CDN Links -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>

<script>
    $(function () {
        $('#productsTable').DataTable({
            "paging": false,
            "responsive": true
        });

        // Save Notes with AJAX
        $('#saveNotes').click(function() {
            const notes = $('#deliveryNotes').val();
            $.ajax({
                url: 'save_notes.php',
                method: 'POST',
                data: { delivery_note_id: <?php echo $delivery_note_id; ?>, notes: notes },
                success: function(response) {
                    alert(response.message || 'Notes saved successfully.');
                },
                error: function() {
                    alert('Failed to save notes.');
                }
            });
        });

        $('#pdfExport').click(function() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Add title
            doc.text("Delivery Note - <?php echo htmlspecialchars($deliveryNote[0]['delivery_note_code']); ?> - <?php echo htmlspecialchars($deliveryNote[0]['client_name']); ?> - <?php echo htmlspecialchars($deliveryNote[0]['delivery_date']); ?>", 10, 10);

            // Define autoTable
            doc.autoTable({
                html: '#productsTable',
                startY: 20,  // Space after the title
                theme: 'grid',  // Grid theme for table styling
                headStyles: { fillColor: [22, 160, 133] },  // Custom head styling
                margin: { top: 20 }
            });

            // Save PDF
            doc.save('Delivery_Note_<?php echo htmlspecialchars($deliveryNote[0]['delivery_note_code']); ?>.pdf');
        });

    });
</script>
</body>
</html>
