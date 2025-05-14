<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Attendance System</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <!-- Data Table -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.css" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@500&display=swap');

        * {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(to bottom, rgba(255,255,255,0.15) 0%, rgba(0,0,0,0.15) 100%), 
                        radial-gradient(at top center, rgba(255,255,255,0.40) 0%, rgba(0,0,0,0.40) 120%) #989898;
            background-blend-mode: multiply,multiply;
            background-attachment: fixed;
            background-repeat: no-repeat;
            background-size: cover;
        }

        .main {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 91.5vh;
        }

        .student-container {
            height: 90%;
            width: 90%;
            border-radius: 20px;
            padding: 40px;
            background-color: rgba(255, 255, 255, 0.8);
        }

        .student-container > div {
            box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
            border-radius: 10px;
            padding: 30px;
            height: 100%;
        }

        .title {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* QR Card Styles */
        .qr-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            max-width: 350px;
            margin: 0 auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .qr-card-header, .qr-card-footer {
            text-align: center;
            margin: 10px 0;
        }

        .qr-card-header h5 {
            font-weight: bold;
            margin-bottom: 5px;
        }

        /* Print Styles */
        @media print {
            body * {
                visibility: hidden;
            }
            .qr-card, .qr-card * {
                visibility: visible;
            }
            .qr-card {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                box-shadow: none;
            }
            .no-print {
                display: none !important;
            }
        }

        /* DataTables Overrides */
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_paginate {
            display: none;
        }

        /* Custom styles for expandable rows */
        .hidden-row {
            background-color: #f8f9fa;
        }
        .hidden-row td {
            border-top: none;
            padding: 0;
        }
        .student-subtable {
            background-color: white;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="navbar-brand ml-4" href="#">QR Code Attendance System</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" 
            aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item"><a class="nav-link" href="./index.php">Home</a></li>
            <li class="nav-item"><a class="nav-link" href="./masterlist.php">List of Students</a></li>
            <li class="nav-item"><a class="nav-link" href="./attendance.php">Attendance</a></li>
            <li class="nav-item"><a class="nav-link" href="./database.php">Database</a></li>
            <li class="nav-item"><a class="nav-link" href="./sections.php">Sections</a></li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item mr-3"><a class="nav-link" href="logout.php">Logout</a></li>
        </ul>
    </div>
</nav>
    
<div class="main">
    <div class="student-container">
        <div class="student-list">
            <div class="title">
                <h4>List of Students</h4>
                <button class="btn btn-dark" data-toggle="modal" data-target="#addStudentModal">Add Student</button>
            </div>
            <hr>
            <div class="table-container table-responsive">
                <table class="table text-center table-sm" id="studentTable">
                    <thead class="thead-dark">
                        <tr>
                            <th width="40%">Section</th>
                            <th width="30%">Student Count</th>
                            <th width="30%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            include ('./conn/conn.php');
                            $sections = $conn->query("SELECT * FROM tbl_sections ORDER BY section_name")->fetchAll();
                            
                            foreach ($sections as $section) {
                                $sectionName = $section['section_name'];
                                $stmt = $conn->prepare("SELECT COUNT(*) as student_count FROM tbl_student WHERE course_section = :section");
                                $stmt->bindParam(':section', $sectionName);
                                $stmt->execute();
                                $countResult = $stmt->fetch();
                                $studentCount = $countResult['student_count'];
                                
                                if ($studentCount > 0) {
                                    $stmt = $conn->prepare("SELECT * FROM tbl_student WHERE course_section = :section ORDER BY student_name");
                                    $stmt->bindParam(':section', $sectionName);
                                    $stmt->execute();
                                    $students = $stmt->fetchAll();
                        ?>
                        <?php $sectionID = 'section-' . preg_replace('/[^a-zA-Z0-9]/', '-', $sectionName); ?>
                        <tr data-toggle="collapse" data-target="#<?= $sectionID ?>" class="accordion-toggle section-header">
                            <td><?= htmlspecialchars($sectionName) ?></td>
                            <td><?= $studentCount ?> student(s)</td>
                            <td>
                                <button class="btn btn-sm btn-outline-secondary">View Students</button>
                            </td>
                        </tr>
                        <tr class="hidden-row">
                            <td colspan="3" class="p-0">
                                <div class="collapse" id="<?= $sectionID ?>">
                                    <table class="table table-sm table-hover mb-0 student-subtable">
                                        <thead class="thead-light">
                                            <tr>
                                                <th width="10%">ID</th>
                                                <th width="40%">Name</th>
                                                <th width="30%">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($students as $row) { 
                                                $studentID = $row["tbl_student_id"];
                                                $studentName = $row["student_name"];
                                                $studentCourse = $row["course_section"];
                                                $qrCode = $row["generated_code"];
                                            ?>
                                            <tr>
                                                <td><?= $studentID ?></td>
                                                <td><?= htmlspecialchars($studentName) ?></td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <button class="btn btn-success btn-sm" data-toggle="modal" 
                                                                data-target="#qrCodeModal<?= $studentID ?>">
                                                            <img src="https://cdn-icons-png.flaticon.com/512/1341/1341632.png" 
                                                                 alt="QR Code" width="16">
                                                        </button>
                                                        
                                                        <div class="modal fade" id="qrCodeModal<?= $studentID ?>" tabindex="-1" aria-hidden="true">
                                                            <div class="modal-dialog">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title"><?= htmlspecialchars($studentName) ?>'s QR Code</h5>
                                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                            <span aria-hidden="true">&times;</span>
                                                                        </button>
                                                                    </div>
                                                                    <div class="modal-body text-center">
                                                                        <div class="qr-card" id="qr-card-<?= $studentID ?>">
                                                                            <div class="qr-card-header">
                                                                                <h5><?= htmlspecialchars($studentName) ?></h5>
                                                                                <p><?= htmlspecialchars($studentCourse) ?></p>
                                                                            </div>
                                                                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= $qrCode ?>" 
                                                                                 alt="QR Code for <?= htmlspecialchars($studentName) ?>" width="300">
                                                                            <div class="qr-card-footer">
                                                                                <p>Student ID: <?= $studentID ?></p>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" 
                                                                                onclick="printQrCode('qr-card-<?= $studentID ?>')">Print</button>
                                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <button class="btn btn-secondary btn-sm" onclick="updateStudent(<?= $studentID ?>)">&#128393;</button>
                                                        <button class="btn btn-danger btn-sm" onclick="deleteStudent(<?= $studentID ?>)">&#10006;</button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                        <?php } } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" data-backdrop="static" data-keyboard="false" tabindex="-1" 
     aria-labelledby="addStudent" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addStudent">Add Student</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="addStudentForm">
                    <div class="form-group">
                        <label for="studentName">Full Name:</label>
                        <input type="text" class="form-control" id="studentName" name="student_name" required>
                    </div>
                    <div class="form-group">
                        <label for="studentCourse">Course and Section:</label>
                        <select class="form-control" id="studentCourse" name="course_section" required>
                            <option value="">Select Section</option>
                            <?php foreach ($sections as $section) {
                                echo '<option value="'.htmlspecialchars($section['section_name']).'">'
                                    .htmlspecialchars($section['section_name']).'</option>';
                            } ?>
                        </select>
                    </div>
                    <button type="button" class="btn btn-secondary form-control qr-generator" 
                            onclick="generateQrCode()">Generate QR Code</button>

                    <div class="qr-con text-center" style="display: none;">
                        <input type="hidden" class="form-control" id="generatedCode" name="generated_code">
                        <p>Take a pic with your QR code.</p>
                        <img class="mb-4" src="" id="qrImg" alt="Generated QR Code" style="max-width: 100%;">
                        <div class="spinner-border text-primary qr-loading" role="status" style="display: none;">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                    <div class="modal-footer modal-close" style="display: none;">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-dark">Add Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Update Student Modal -->
<div class="modal fade" id="updateStudentModal" data-backdrop="static" data-keyboard="false" tabindex="-1" 
     aria-labelledby="updateStudent" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateStudent">Update Student</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="updateStudentForm">
                    <input type="hidden" class="form-control" id="updateStudentId" name="tbl_student_id">
                    <div class="form-group">
                        <label for="updateStudentName">Full Name:</label>
                        <input type="text" class="form-control" id="updateStudentName" name="student_name" required>
                    </div>
                    <div class="form-group">
                        <label for="updateStudentCourse">Course and Section:</label>
                        <select class="form-control" id="updateStudentCourse" name="course_section" required>
                            <option value="">Select Section</option>
                            <?php foreach ($sections as $section) {
                                echo '<option value="'.htmlspecialchars($section['section_name']).'">'
                                    .htmlspecialchars($section['section_name']).'</option>';
                            } ?>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-dark">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript Libraries -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<script>
$(document).ready(function() {
    // Only initialize DataTables for student subtables
    $('.student-subtable').DataTable({
        paging: false,
        searching: false,
        info: false,
        ordering: false,
        responsive: true
    });

    // Reset form when add modal is closed
    $('#addStudentModal').on('hidden.bs.modal', function() {
        $('#addStudentForm')[0].reset();
        $('.qr-con').hide();
        $('.modal-close').hide();
        $('.qr-generator').show().html('Generate QR Code');
        $('#studentName').prop('readOnly', false);
        $('#studentCourse').prop('disabled', false);
    });
});

// Student management functions
function updateStudent(id) {
    $("#updateStudentModal").modal("show");
    $("#updateStudentName").val("Loading...");
    $("#updateStudentCourse").val("Loading...");
    
    $.ajax({
        url: "./endpoint/get-student.php?id=" + id,
        type: "GET",
        dataType: "json",
        success: function(response) {
            if (response.success) {
                $("#updateStudentId").val(response.data.tbl_student_id);
                $("#updateStudentName").val(response.data.student_name);
                $("#updateStudentCourse").val(response.data.course_section);
            } else {
                alert(response.message);
                $("#updateStudentModal").modal("hide");
            }
        },
        error: function(xhr) {
            alert("Error loading student data: " + xhr.responseText);
            $("#updateStudentModal").modal("hide");
        }
    });
}

function deleteStudent(id) {
    if (confirm("Are you sure you want to delete this student?")) {
        window.location = "./endpoint/delete-student.php?student=" + id;
    }
}

// QR Code generation
function generateRandomCode(length) {
    const crypto = window.crypto || window.msCrypto;
    const array = new Uint32Array(length);
    
    if (crypto) {
        crypto.getRandomValues(array);
        return Array.from(array, dec => ('0' + dec.toString(16)).slice(-2)).join('').slice(0, length);
    } else {
        const characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        let randomString = '';
        for (let i = 0; i < length; i++) {
            randomString += characters.charAt(Math.floor(Math.random() * characters.length));
        }
        return randomString;
    }
}

function generateQrCode() {
    const studentName = $("#studentName").val().trim();
    const studentCourse = $("#studentCourse").val();
    
    if (!studentName) {
        alert("Please enter student name first.");
        return;
    }
    
    if (!studentCourse) {
        alert("Please select a course section first.");
        return;
    }

    $('.qr-generator').html('Generating...');
    $('.qr-loading').show();

    let text = generateRandomCode(10);
    $("#generatedCode").val(text);

    const img = new Image();
    img.onload = function() {
        $("#qrImg").attr("src", this.src);
        $('.qr-con').show();
        $('.modal-close').show();
        $('.qr-generator').hide();
        $('.qr-loading').hide();
        $('#studentName').prop('readOnly', true);
        $('#studentCourse').prop('disabled', true);
    };
    img.onerror = function() {
        alert("Failed to generate QR code. Please try again.");
        $('.qr-generator').html('Generate QR Code');
        $('.qr-loading').hide();
    };
    img.src = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(text)}`;
}

function printQrCode(elementId) {
    const printContent = document.getElementById(elementId).outerHTML;
    const originalContent = document.body.innerHTML;
    
    document.body.innerHTML = printContent;
    window.print();
    document.body.innerHTML = originalContent;
}

// Form submissions
$("#addStudentForm").on("submit", function(e) {
    e.preventDefault();
    
    const studentName = $("#studentName").val().trim();
    const courseSection = $("#studentCourse").val();
    const generatedCode = $("#generatedCode").val();
    
    if (!studentName || !courseSection || !generatedCode) {
        alert("Please fill all fields and generate QR code");
        return;
    }

    $(".modal-close button[type='submit']").html('<span class="spinner-border spinner-border-sm" role="status"></span> Adding...');
    
    $.ajax({
        url: "./endpoint/add-student.php",
        type: "POST",
        data: {
            student_name: studentName,
            course_section: courseSection,
            generated_code: generatedCode
        },
        success: function(response) {
            if (response.success) {
                window.location.reload();
            } else {
                alert(response.message);
                $(".modal-close button[type='submit']").html('Add Student');
            }
        },
        error: function(xhr) {
            alert("Error: " + xhr.responseText);
            $(".modal-close button[type='submit']").html('Add Student');
        }
    });
});

$("#updateStudentForm").on("submit", function(e) {
    e.preventDefault();
    
    const studentId = $("#updateStudentId").val();
    const studentName = $("#updateStudentName").val().trim();
    const courseSection = $("#updateStudentCourse").val();
    
    if (!studentName || !courseSection) {
        alert("Please fill all fields");
        return;
    }

    $("#updateStudentForm button[type='submit']").html('<span class="spinner-border spinner-border-sm" role="status"></span> Updating...');
    
    $.ajax({
        url: "./endpoint/update-student.php",
        type: "POST",
        data: {
            tbl_student_id: studentId,
            student_name: studentName,
            course_section: courseSection
        },
        success: function(response) {
            if (response.success) {
                window.location.reload();
            } else {
                alert(response.message);
                $("#updateStudentForm button[type='submit']").html('Update');
            }
        },
        error: function(xhr) {
            alert("Error: " + xhr.responseText);
            $("#updateStudentForm button[type='submit']").html('Update');
        }
    });
});
</script>
</body>
</html>