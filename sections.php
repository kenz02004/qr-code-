<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include('./conn/conn.php');

// Handle section addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['section_name'])) {
    $sectionName = $_POST['section_name'];
    
    try {
        $stmt = $conn->prepare("INSERT INTO tbl_sections (section_name) VALUES (:section_name)");
        $stmt->bindParam(":section_name", $sectionName);
        $stmt->execute();
        header("Location: sections.php");
        exit();
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get all sections
$sections = $conn->query("SELECT * FROM tbl_sections ORDER BY section_name")->fetchAll(PDO::FETCH_ASSOC);

// Get students for selected section if any
$selectedSection = isset($_GET['section']) ? $_GET['section'] : null;
$sectionStudents = [];
if ($selectedSection) {
    $stmt = $conn->prepare("SELECT * FROM tbl_student WHERE course_section = :section ORDER BY student_name");
    $stmt->bindParam(":section", $selectedSection);
    $stmt->execute();
    $sectionStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sections - QR Code Attendance System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
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

        .container {
            width: 90%;
        }

        .card {
            border-radius: 20px;
            background-color: rgba(255, 255, 255, 0.8);
            box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
        }

        .card-header {
            background-color: #343a40;
            color: white;
            border-radius: 20px 20px 0 0 !important;
        }

        .section-link {
            color: #007bff;
            cursor: pointer;
            text-decoration: none;
        }

        .section-link:hover {
            text-decoration: underline;
        }

        .active-section {
            font-weight: bold;
            color: #0056b3;
        }

        .back-link {
            margin-bottom: 15px;
            display: inline-block;
        }

        .student-table {
            margin-top: 20px;
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
                <li class="nav-item">
                    <a class="nav-link" href="./database.php">Database</a>
                </li>
                <li class="nav-item active">
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
        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h4>Manage Sections</h4>
                </div>
                <div class="card-body">
                    <?php if (!$selectedSection): ?>
                        <form method="POST" class="mb-4">
                            <div class="form-row">
                                <div class="col-md-8">
                                    <input type="text" name="section_name" class="form-control" placeholder="Enter new section name" required>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary">Add Section</button>
                                </div>
                            </div>
                        </form>

                        <table class="table table-bordered">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Section Name</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sections as $section): ?>
                                <tr>
                                    <td>
                                        <a href="?section=<?= urlencode($section['section_name']) ?>" 
                                           class="section-link <?= ($selectedSection == $section['section_name']) ? 'active-section' : '' ?>">
                                            <?= htmlspecialchars($section['section_name']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <button class="btn btn-danger btn-sm" onclick="deleteSection(<?= $section['id'] ?>)">Delete</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <a href="sections.php" class="back-link">&larr; Back to all sections</a>
                        <h5>Students in <?= htmlspecialchars($selectedSection) ?></h5>
                        
                        <?php if (count($sectionStudents) > 0): ?>
                            <div class="table-responsive student-table">
                                <table class="table table-striped">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>#</th>
                                            <th>Student Name</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sectionStudents as $index => $student): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><?= htmlspecialchars($student['student_name']) ?></td>
                                            <td>
                                                <a href="masterlist.php" class="btn btn-info btn-sm">View Details</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                No students found in this section.
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function deleteSection(id) {
            if (confirm("Are you sure you want to delete this section? This will remove the section from all students assigned to it.")) {
                window.location = "delete-section.php?id=" + id;
            }
        }
    </script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>
</body>
</html>