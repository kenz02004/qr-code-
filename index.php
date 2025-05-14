<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include('./conn/conn.php');

// Get database statistics
$studentCount = $conn->query("SELECT COUNT(*) FROM tbl_student")->fetchColumn();
$attendanceCount = $conn->query("SELECT COUNT(*) FROM tbl_attendance")->fetchColumn();
$recentAttendance = $conn->query("SELECT * FROM tbl_attendance 
                                 LEFT JOIN tbl_student ON tbl_student.tbl_student_id = tbl_attendance.tbl_student_id 
                                 ORDER BY time_in DESC LIMIT 5")->fetchAll();

// Check database connection status
$dbStatus = $conn->getAttribute(PDO::ATTR_CONNECTION_STATUS);
$dbName = $conn->query("SELECT DATABASE()")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF -8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Attendance System</title>

    <!-- Bootstrap CSS -->
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
            height: 91.5vh;
        }

        .attendance-container {
            height: 90%;
            width: 90%;
            border-radius: 20px;
            padding: 40px;
            background-color: rgba(255, 255, 255, 0.8);
        }

        .attendance-container > div {
            box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
            border-radius: 10px;
            padding: 30px;
        }

        .attendance-container > div:last-child {
            width: 64%;
            margin-left: auto;
        }

        .camera-status {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 15px;
            border-radius: 5px;
            background-color: rgba(0,0,0,0.7);
            color: white;
            z-index: 1000;
            display: flex;
            align-items: center;
        }

        .camera-status .indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .camera-status.active .indicator {
            background-color:rgb(13, 221, 62);
            box-shadow: 0 0 10pxrgb(12, 99, 32);
        }

        .camera-status.inactive .indicator {
            background-color: #dc3545;
        }

        .scanner-con {
            position: relative;
        }

        #interactive {
            width: 100%;
            height: auto;
            border-radius: 8px;
            background: black;
        }

        .scan-region-highlight {
            border-radius: 8px;
            outline: rgba(0, 0, 0, .25) solid 50vmax;
        }

        .qr-detected-container {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .success-message {
            color: #28a745;
            font-weight: bold;
            margin-bottom: 15px;
        }

        /* New styles for database info */
        .database-info {
            background-color: rgba(0,0,0,0.1);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .database-stats {
            display: flex;
            justify-content: space-around;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            width: 30%;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        
        .stat-label {
            font-size: 14px;
            color: #666;
        }
        
        .recent-activity {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .db-status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }
        
        .db-connected {
            background-color: #28a745;
            box-shadow: 0 0 5px #28a745;
        }
        
        .db-disconnected {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="camera-status inactive" id="cameraStatus">
        <span class="indicator"></span>
        <span>Camera not active</span>
    </div>

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
        <div class="attendance-container row">
            <div class="qr-container col-4">
                <div class="scanner-con">
                    <h5 class="text-center mb-3">Scan your QR Code here</h5>
                    <video id="interactive" class="viewport" width="100%"></video>
                    <div class="text-center mt-2">
                        <small class="text-muted">Position QR code within the camera view</small>
                    </div>
                </div>

                <div class="qr-detected-container">
                    <div class="success-message">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-check-circle" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z"/>
                        </svg>
                        <h4 class="mt-3">Attendance Recorded!</h4>
                    </div>
                    <div class="progress mt-3">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                    </div>
                    <p class="mt-2">Preparing to scan again...</p>
                </div>
            </div>

            <div class="attendance-list">
                <!-- Database Information Section -->
                <div class="database-info">
                    <h4>Database Information</h4>
                    <div class="database-stats">
                        <div class="stat-card">
                            <div class="stat-value"><?= $studentCount ?></div>
                            <div class="stat-label">Total Students</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?= $attendanceCount ?></div>
                            <div class="stat-label">Attendance Recorded</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?= $dbStatus ? 'Online' : 'Offline' ?></div>
                            <div class="stat-label">Status</div>
                        </div>
                    </div>
                    
                    <div class="recent-activity">
                        <h5>Recent Attendance Recorded</h5>
                        <ul class="list-group">
                            <?php foreach($recentAttendance as $record): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?= htmlspecialchars($record['student_name']) ?>
                                <span class="badge badge-primary badge-pill">
                                    <?= date('h:i A', strtotime($record['time_in'])) ?>
                                </span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <h4>List of Present Students</h4>
                <div class="table-container table-responsive">
                    <table class="table text-center table-sm" id="attendanceTable">
                        <thead class="thead-dark">
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Name</th>
                                <th scope="col">Course & Section</th>
                                <th scope="col">Time In</th>
                                <th scope="col">Time Out</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                                $stmt = $conn->prepare("SELECT * FROM tbl_attendance 
             LEFT JOIN tbl_student ON tbl_student.tbl_student_id = tbl_attendance.tbl_student_id 
                                  ORDER BY time_in DESC");
            $stmt->execute();
            $result = $stmt->fetchAll();

            foreach ($result as $row) {
                $attendanceID = $row["tbl_attendance_id"];
                $studentName = $row["student_name"];
                $studentCourse = $row["course_section"];
                $timeIn = $row["time_in"];
                $timeOut = $row["time_out"];
        ?>
        <tr>
            <th scope="row"><?= $attendanceID ?></th>
            <td><?= $studentName ?></td>
            <td><?= $studentCourse ?></td>
            <td><?= date('M d, Y h:i A', strtotime($timeIn)) ?></td>
            <td>
    <?php if ($row['time_in']): ?>
        <span class="time-in"><?= date('h:i A', strtotime($row['time_in'])) ?></span>
    <?php else: ?>
        <span class="text-muted">-</span>
    <?php endif; ?>
</td>
<td>
    <?php if ($row['time_out']): ?>
        <span class="time-out"><?= date('h:i A', strtotime($row['time_out'])) ?></span>
    <?php elseif (!empty($row['time_in'])): ?>
        <span class="text-muted">Not recorded</span>
    <?php else: ?>
        <span class="text-muted">-</span>
    <?php endif; ?>
</td>
            <td>
                <button class="btn btn-danger btn-sm" onclick="deleteAttendance(<?= $attendanceID ?>)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                        <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                        <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4L4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                    </svg>
                </button>
            </td>
        </tr>
        <?php } ?>
    </tbody>
</table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>

    <!-- Instascan JS -->
    <script src="https://rawgit.com/schmich/instascan-builds/master/instascan.min.js"></script>

    <script>
        let scanner;
        let isSubmitting = false;
        let scanTimeout;

        function updateCameraStatus(active) {
            const statusElement = document.getElementById('cameraStatus');
            if (active) {
                statusElement.classList.remove('inactive');
                statusElement.classList.add('active');
                statusElement.querySelector('span:last-child').textContent = 'Camera active';
            } else {
                statusElement.classList.remove('active');
                statusElement.classList.add('inactive');
                statusElement.querySelector('span:last-child').textContent = 'Camera not active';
            }
        }

        function showSuccessMessage() {
            document.querySelector(".scanner-con").style.display = 'none';
            document.querySelector(".qr-detected-container").style.display = 'block';
        }

        function resetScannerUI() {
            document.querySelector(".scanner-con").style.display = 'block';
            document.querySelector(".qr-detected-container").style.display = 'none';
        }

        

        function startCamera() {
            scanner = new Instascan.Scanner({ 
                video: document.getElementById('interactive'),
                mirror: false,
                captureImage: false,
                backgroundScan: true,
                refractoryPeriod: 3000,
                scanPeriod: 1
            });

            scanner.addListener('scan', function(content) {
                console.log('QR detected:', content);
                submitAttendance(content);
            });

            Instascan.Camera.getCameras()
                .then(function(cameras) {
                    if (cameras.length > 0) {
                        scanner.start(cameras[0])
                            .then(() => {
                                updateCameraStatus(true);
                            })
                            .catch(err => {
                                console.error('Camera start error:', err);
                                updateCameraStatus(false);
                                alert('Camera error: ' + err.message);
                            });
                    } else {
                        console.error('No cameras found.');
                        updateCameraStatus(false);
                        alert('No cameras found. Please ensure your camera is connected and permissions are granted.');
                    }
                })
                .catch(function(err) {
                    console.error('Camera access error:', err);
                    updateCameraStatus(false);
                    alert('Camera access error: ' + err.message);
                });
        }

        function deleteAttendance(id) {
    if (confirm("Are you sure you want to delete this attendance record?")) {
        console.log("Attempting to delete attendance record ID:", id);
        
        fetch('./endpoint/delete-attendance.php?attendance=' + id)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log("Delete response:", data);
                if (data.success) {
                    // Show success message
                    alert('Attendance record deleted successfully!');
                    location.reload(); // Refresh page on success
                } else {
                    // Show detailed error message
                    let errorMsg = data.message;
                    if (data.error) {
                        errorMsg += `\n\nTechnical details: ${data.error}`;
                        console.error('Server error:', data.error);
                    }
                    alert(errorMsg);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Deletion failed. Please check console for details and try again.');
            });
    }
}


        function submitAttendance(qrCode) {
    if (isSubmitting) return;
    isSubmitting = true;
    
    showSuccessMessage();
    updateCameraStatus(false);
    
    fetch('./endpoint/add-attendance.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `qr_code=${encodeURIComponent(qrCode)}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        console.log("Attendance response:", data); // Debug log
        
        if (data.success) {
            // Update success message based on action
            const successMessage = document.querySelector(".success-message h4");
            successMessage.textContent = data.message;
            
            // Wait 3 seconds before refreshing to show success message
            scanTimeout = setTimeout(() => {
                location.reload();
            }, 3000);
        } else {
            let errorMsg = data.message;
            if (data.error) {
                errorMsg += `\n\nTechnical details: ${data.error}`;
                console.error('Server error:', data.error);
            }
            alert(errorMsg);
            resetScannerUI();
            startCamera();
            isSubmitting = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to record attendance. Please check console for details.');
        resetScannerUI();
        startCamera();
        isSubmitting = false;
    });
}

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            startCamera();
            
            // Clean up on page unload
            window.addEventListener('beforeunload', function() {
                if (scanner) {
                    scanner.stop();
                }
                if (scanTimeout) {
                    clearTimeout(scanTimeout);
                }
            });
        });
    </script>
</body>
</html>