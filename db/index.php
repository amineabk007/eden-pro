<?php
// DB/index.php
session_start();
require '../config/config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { // Only admins can access
    header('Location: ../login.php');
    exit();
}

// Function to back up the database
function backupDatabase(PDO $pdo, $backupFile)
{
    try {
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $backupContent = "";

        foreach ($tables as $table) {
            // Get CREATE TABLE statement
            $createTable = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC)['Create Table'];
            $backupContent .= "$createTable;\n\n";

            // Get INSERT statements for table data
            $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $values = array_map(function ($value) use ($pdo) {
                    return $value === null ? 'NULL' : $pdo->quote($value);
                }, array_values($row));
                $backupContent .= "INSERT INTO `$table` VALUES (" . implode(", ", $values) . ");\n";
            }
            $backupContent .= "\n";
        }

        // Save to file
        file_put_contents($backupFile, $backupContent);
        return "Backup successfully created.";
    } catch (Exception $e) {
        return "Error during backup: " . $e->getMessage();
    }
}

// Secure backup directory outside web root
$backupDirectory = __DIR__ . '/../backups/'; // Adjust path as needed
if (!is_dir($backupDirectory)) {
    mkdir($backupDirectory, 0755, true);
}

// Handle backup action
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['backup'])) {
        $backupFile = $backupDirectory . 'backup-' . date('Y-m-d_H-i-s') . '.sql';
        $message = backupDatabase($pdo, $backupFile);
    }
    if (isset($_POST['run_query']) && isset($_POST['query'])) {
        $query = trim($_POST['query']);
        if (preg_match('/^(SELECT|INSERT|UPDATE)\b/i', $query)) {
            try {
                $stmt = $pdo->query($query);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $message = 'Query executed successfully.';
            } catch (Exception $e) {
                $message = 'Error: ' . htmlspecialchars($e->getMessage());
            }
        } else {
            $message = 'Only SELECT, INSERT, and UPDATE queries are allowed.';
        }
    }
}

// Handle secure file download
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['download'])) {
    $file = basename($_GET['download']); // Sanitize input
    $filePath = $backupDirectory . $file;

    if (file_exists($filePath) && strpos(realpath($filePath), realpath($backupDirectory)) === 0) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit();
    } else {
        $message = 'File not found or access denied.';
    }
}

if (isset($_POST['restore']) && isset($_POST['backup_file'])) {
    $backupFile = $backupDirectory . basename($_POST['backup_file']); // Sanitize input
    try {
        $sql = file_get_contents($backupFile);
        $queries = explode(";\n", $sql); // Split SQL file into individual queries
        foreach ($queries as $query) {
            if (!empty(trim($query))) {
                $pdo->exec($query);
            }
        }
        $message = "Backup successfully restored.";
    } catch (Exception $e) {
        $message = "Error during restore: " . $e->getMessage();
    }
}

// Handle clear tables action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_tables'])) {
    if (isset($_POST['tables']) && is_array($_POST['tables'])) {
        // Iterate over the selected tables
        foreach ($_POST['tables'] as $table) {
            try {
                // Delete all rows from the selected table
                $pdo->exec("DELETE FROM `$table`");

                // Reset the auto-increment ID for the selected table
                $pdo->exec("ALTER TABLE `$table` AUTO_INCREMENT = 1");

                echo "<div class='alert alert-success'>Data cleared and ID reset for table: " . htmlspecialchars($table) . "</div>";
            } catch (Exception $e) {
                echo "<div class='alert alert-danger'>Error clearing data from table: " . htmlspecialchars($table) . " - " . $e->getMessage() . "</div>";
            }
        }
    } else {
        echo "<div class='alert alert-warning'>Please select at least one table to clear.</div>";
    }
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Database Management</title>
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
    <?php include '../includes/navbar.php'; ?>
    <?php include '../includes/sidebar.php'; ?>
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <h1>Database Management</h1>
                <?php if ($message): ?>
                    <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
            </div>
        </section>
        <section class="content">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Backup Database</h3>
                </div>
                <div class="card-body">
                    <form method="post">
                        <button type="submit" name="backup" class="btn btn-primary">Create Backup</button>
                    </form>
                    <h4 class="mt-4">Existing Backups:</h4>
                    <ul>
                        <?php
                        $files = glob($backupDirectory . '*.sql');
                        foreach ($files as $file) {
                            $fileName = basename($file);
                            echo "<li><a href='?download=" . urlencode($fileName) . "'>$fileName</a></li>";
                        }
                        ?>
                    </ul>
                </div>
            </div>
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title">Backup Restore</h3>
                </div>
                <div class="card-body">
                <form method="post">
                    <label for="backup_file">Select a backup file to restore:</label>
                    <select name="backup_file" id="backup_file" class="form-control">
                        <?php
                        foreach ($files as $file) {
                            $fileName = basename($file);
                            echo "<option value='$fileName'>$fileName</option>";
                        }
                        ?>
                    </select>
                    <button type="submit" name="restore" class="btn btn-danger mt-3" onclick="return confirm('Are you sure you want to restore this backup?');">Restore Backup</button>
                </form>
                </div>
            </div>
            <!-- HTML Form for Selecting Tables to Clear Data -->
            <div class="card card-outline card-danger">
                <div class="card-header">
                    <h3 class="card-title">Clear Data from Tables</h3>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="form-group">
                            <label>Select Tables to Clear:</label><br>
                            <?php
                            // Get list of tables from the database
                            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                            foreach ($tables as $table) {
                                echo "<div class='form-check'>";
                                echo "<input class='form-check-input' type='checkbox' name='tables[]' value='" . htmlspecialchars($table) . "' id='" . htmlspecialchars($table) . "'>";
                                echo "<label class='form-check-label' for='" . htmlspecialchars($table) . "'>" . htmlspecialchars($table) . "</label>";
                                echo "</div>";
                            }
                            ?>
                        </div>
                        <button type="submit" name="clear_tables" class="btn btn-danger" onclick="return confirm('Are you sure you want to clear data from the selected tables?');">Clear Data</button>
                    </form>
                </div>
            </div>

            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">Run Custom Query</h3>
                </div>
                <div class="card-body">
                    <form method="post">
                        <textarea name="query" rows="5" class="form-control" placeholder="Enter your SQL query here..." required></textarea>
                        <button type="submit" name="run_query" class="btn btn-success mt-3 mb-3">Run Query</button>
                    </form>
                    <?php
                        if (isset($stmt) && $stmt !== null && $stmt->columnCount() > 0) { // Check if $stmt is set and valid
                            echo "<table id='dbTable' class='table table-bordered table-striped'>";
                            echo "<thead><tr>";
                            for ($i = 0; $i < $stmt->columnCount(); $i++) {
                                $meta = $stmt->getColumnMeta($i);
                                echo "<th>{$meta['name']}</th>";
                            }
                            echo "</tr></thead><tbody>";
                            foreach ($results as $row) {
                                echo "<tr>";
                                foreach ($row as $col) {
                                    echo "<td>" . htmlspecialchars($col) . "</td>";
                                }
                                echo "</tr>";
                            }
                            echo "</tbody></table>";
                        }
                    ?>
                </div>
            </div>
        </section>
    </div>
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

    <!-- Page specific script -->
    <script>
        $(function () {
            $("#dbTable").DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
                "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
            }).buttons().container().appendTo('#dbTable_wrapper .col-md-6:eq(0)');
        });
    </script>
</body>
</html>
