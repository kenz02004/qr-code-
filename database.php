<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include('./conn/conn.php');

// Get list of tables in the database
$tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

// Handle table actions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $selectedTable = $_POST['table'];
        
        if (isset($_POST['delete_table'])) {
            // Delete table
            $conn->exec("DROP TABLE `$selectedTable`");
            $message = "Table '$selectedTable' deleted successfully!";
            $messageType = 'success';
            // Refresh table list
            $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            $selectedTable = null;
        } 
        elseif (isset($_POST['rename_table'])) {
            $newName = $_POST['new_table_name'];
            // Rename table
            $conn->exec("RENAME TABLE `$selectedTable` TO `$newName`");
            $message = "Table '$selectedTable' renamed to '$newName' successfully!";
            $messageType = 'success';
            // Refresh table list
            $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            $selectedTable = $newName;
        }
        elseif (isset($_POST['truncate_table'])) {
            // Empty table
            $conn->exec("TRUNCATE TABLE `$selectedTable`");
            $message = "Table '$selectedTable' emptied successfully!";
            $messageType = 'success';
        }
        elseif (isset($_POST['add_column'])) {
            $columnName = $_POST['column_name'];
            $columnType = $_POST['column_type'];
            // Add column
            $conn->exec("ALTER TABLE `$selectedTable` ADD COLUMN `$columnName` $columnType");
            $message = "Column '$columnName' added to '$selectedTable' successfully!";
            $messageType = 'success';
        }
        elseif (isset($_POST['delete_row'])) {
            $primaryKey = $_POST['primary_key'];
            $primaryValue = $_POST['primary_value'];
            // Delete row
            $stmt = $conn->prepare("DELETE FROM `$selectedTable` WHERE `$primaryKey` = :value");
            $stmt->bindParam(':value', $primaryValue);
            $stmt->execute();
            $message = "Row deleted from '$selectedTable' successfully!";
            $messageType = 'success';
        }
        elseif (isset($_POST['execute_sql'])) {
            $customSql = $_POST['custom_sql'];
            // Execute custom SQL
            $stmt = $conn->prepare($customSql);
            $stmt->execute();
            $message = "SQL executed successfully!";
            $messageType = 'success';
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get selected table from GET or POST
if (isset($_GET['table'])) {
    $selectedTable = $_GET['table'];
} elseif (isset($_POST['table'])) {
    $selectedTable = $_POST['table'];
} else {
    $selectedTable = null;
}

// Get data for selected table
$tableData = [];
$columns = [];
$primaryKey = '';
$tableInfo = [];

if ($selectedTable && in_array($selectedTable, $tables)) {
    // Get table structure
    $stmt = $conn->query("DESCRIBE `$selectedTable`");
    $tableInfo = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columns = array_column($tableInfo, 'Field');
    
    // Try to determine primary key
    foreach ($tableInfo as $column) {
        if ($column['Key'] == 'PRI') {
            $primaryKey = $column['Field'];
            break;
        }
    }
    if (empty($primaryKey)) $primaryKey = $columns[0];
    
    // Get table data
    $stmt = $conn->query("SELECT * FROM `$selectedTable`");
    $tableData = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get available column types for adding new columns
$columnTypes = [
    'INT', 'VARCHAR(255)', 'TEXT', 'DATE', 'DATETIME', 
    'TIMESTAMP', 'FLOAT', 'DOUBLE', 'BOOLEAN', 'ENUM'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Manager - QR Code Attendance System</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    
    <!-- Data Table CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.css" />
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@500&display=swap');

        * {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(to bottom, rgba(255,255,255,0.15) 0%, rgba(0,0,0,0.15) 100%), radial-gradient(at top center, rgba(255,255,255,0.40) 0%, rgba(0,0,0,0.40) 120%) #989898;
            background-blend-mode: multiply,multiply;
            background-attachment: fixed;
            background-repeat: no-repeat;
            background-size: cover;
        }

        .main {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 91.5vh;
            padding: 20px 0;
        }

        .database-container {
            width: 90%;
            border-radius: 20px;
            padding: 40px;
            background-color: rgba(255, 255, 255, 0.8);
            box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
        }

        .table-list {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: rgba(0, 0, 0, 0.1) 0px 4px 6px;
        }

        .table-list a {
            display: block;
            padding: 8px 15px;
            margin: 5px 0;
            border-radius: 5px;
            color: #333;
            text-decoration: none;
            transition: all 0.3s;
        }

        .table-list a:hover {
            background-color: #f8f9fa;
        }

        .table-list a.active {
            background-color: #007bff;
            color: white;
        }

        .data-container {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: rgba(0, 0, 0, 0.1) 0px 4px 6px;
            overflow-x: auto;
        }

        .sql-console {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin-top: 20px;
        }

        .sql-console textarea {
            font-family: monospace;
        }

        .table-actions {
            margin-bottom: 20px;
        }

        .danger-zone {
            border: 1px solid #dc3545;
            border-radius: 5px;
            padding: 15px;
            margin-top: 20px;
        }

        .danger-zone h5 {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <a class="navbar-brand ml-4" href="#">QR Code Attendance System</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item">
                    <a class="nav-link" href="./index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="./masterlist.php">List of Students</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="./attendance.php">Attendance</a>
                </li>
                <li class="nav-item active">
                    <a class="nav-link" href="./database.php">Database</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="./sections.php">Sections</a>
                </li>
            </ul>
            <ul class="navbar-nav ml-auto">
                <li class="nav-item mr-3">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="main">
        <div class="database-container">
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                    <?= $message ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <h2>Database Manager</h2>
            <p class="mb-4">Full control over database tables and data</p>
            
            <div class="row">
                <div class="col-md-3">
                    <div class="table-list">
                        <h5>Tables</h5>
                        <?php foreach ($tables as $table): ?>
                            <a href="?table=<?= $table ?>" class="<?= $selectedTable === $table ? 'active' : '' ?>">
                                <?= $table ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="col-md-9">
                    <div class="data-container">
                        <?php if ($selectedTable): ?>
                            <div class="table-actions mb-4">
                                <h4><?= $selectedTable ?></h4>
                                <p class="text-muted">Showing <?= count($tableData) ?> records</p>
                                
                                <div class="btn-group mb-3">
                                    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addColumnModal">
                                        Add Column
                                    </button>
                                    <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#renameTableModal">
                                        Rename Table
                                    </button>
                                    <button class="btn btn-danger btn-sm" data-toggle="modal" data-target="#deleteTableModal">
                                        Delete Table
                                    </button>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="table" value="<?= $selectedTable ?>">
                                        <button type="submit" name="truncate_table" class="btn btn-danger btn-sm" 
                                                onclick="return confirm('Are you sure you want to empty this table? This cannot be undone!')">
                                            Empty Table
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped table-bordered" id="dataTable">
                                    <thead class="thead-dark">
                                        <tr>
                                            <?php foreach ($columns as $column): ?>
                                                <th><?= $column ?></th>
                                            <?php endforeach; ?>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tableData as $row): ?>
                                            <tr>
                                                <?php foreach ($columns as $column): ?>
                                                    <td><?= htmlspecialchars($row[$column] ?? 'NULL') ?></td>
                                                <?php endforeach; ?>
                                                <td>
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="table" value="<?= $selectedTable ?>">
                                                        <input type="hidden" name="primary_key" value="<?= $primaryKey ?>">
                                                        <input type="hidden" name="primary_value" value="<?= $row[$primaryKey] ?>">
                                                        <button type="submit" name="delete_row" class="btn btn-danger btn-sm" 
                                                                onclick="return confirm('Are you sure you want to delete this row?')">
                                                            Delete
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="danger-zone mt-4">
                                <h5>Danger Zone</h5>
                                <p>These actions are irreversible. Use with caution.</p>
                                
                                <form method="post">
                                    <input type="hidden" name="table" value="<?= $selectedTable ?>">
                                    
                                    <div class="form-group">
                                        <label for="custom_sql">Execute Custom SQL</label>
                                        <textarea class="form-control" id="custom_sql" name="custom_sql" rows="3" 
                                                  placeholder="SELECT * FROM <?= $selectedTable ?>"></textarea>
                                    </div>
                                    <button type="submit" name="execute_sql" class="btn btn-danger"
                                            onclick="return confirm('Are you sure you want to execute this SQL?')">
                                        Execute SQL
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <h4>Select a table to view data</h4>
                                <p class="text-muted">Choose a table from the left panel</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Column Modal -->
    <div class="modal fade" id="addColumnModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Column to <?= $selectedTable ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="table" value="<?= $selectedTable ?>">
                        
                        <div class="form-group">
                            <label for="column_name">Column Name</label>
                            <input type="text" class="form-control" id="column_name" name="column_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="column_type">Column Type</label>
                            <select class="form-control" id="column_type" name="column_type" required>
                                <?php foreach ($columnTypes as $type): ?>
                                    <option value="<?= $type ?>"><?= $type ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="add_column" class="btn btn-primary">Add Column</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Rename Table Modal -->
    <div class="modal fade" id="renameTableModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rename Table <?= $selectedTable ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="table" value="<?= $selectedTable ?>">
                        
                        <div class="form-group">
                            <label for="new_table_name">New Table Name</label>
                            <input type="text" class="form-control" id="new_table_name" name="new_table_name" 
                                   value="<?= $selectedTable ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="rename_table" class="btn btn-primary">Rename Table</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Table Modal -->
    <div class="modal fade" id="deleteTableModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Table <?= $selectedTable ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="table" value="<?= $selectedTable ?>">
                        <p>Are you sure you want to delete the table <strong><?= $selectedTable ?></strong>? This action cannot be undone!</p>
                        <p class="text-danger">All data in this table will be permanently lost.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_table" class="btn btn-danger">Delete Table</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>
    
    <!-- Data Table JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable();
        });
    </script>
</body>
</html>