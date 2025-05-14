<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include('./conn/conn.php');

// Get current month and year
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Adjust for previous/next month navigation
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'prev') {
        $month--;
        if ($month < 1) {
            $month = 12;
            $year--;
        }
    } elseif ($_GET['action'] == 'next') {
        $month++;
        if ($month > 12) {
            $month = 1;
            $year++;
        }
    }
}

// Get first day of the month
$firstDay = mktime(0, 0, 0, $month, 1, $year);
$monthName = date('F', $firstDay);
$dayOfWeek = date('w', $firstDay); // 0=Sunday, 6=Saturday
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

// Get current date for comparison
$currentDate = date('Y-m-d');
$currentDayOfMonth = date('j');
$currentMonth = date('n');
$currentYear = date('Y');

// Get all days with attendance records this month
$attendanceDays = [];
$stmt = $conn->prepare("SELECT DISTINCT DATE(time_in) as attendance_date 
                       FROM tbl_attendance 
                       WHERE MONTH(time_in) = :month AND YEAR(time_in) = :year");
$stmt->bindParam(':month', $month, PDO::PARAM_INT);
$stmt->bindParam(':year', $year, PDO::PARAM_INT);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($result as $row) {
    $day = date('j', strtotime($row['attendance_date']));
    $attendanceDays[$day] = true;
}

// Check if a specific date is selected
$selectedDate = isset($_GET['date']) ? $_GET['date'] : null;
$dateAttendance = [];
if ($selectedDate) {
    // Get all students
    $stmt = $conn->prepare("SELECT tbl_student_id, student_name FROM tbl_student ORDER BY student_name");
    $stmt->execute();
    $allStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get attendance records for the selected date
    $stmt = $conn->prepare("SELECT tbl_student.student_name, tbl_attendance.time_in, tbl_attendance.time_out, tbl_attendance.tbl_student_id 
                           FROM tbl_attendance 
                           JOIN tbl_student ON tbl_student.tbl_student_id = tbl_attendance.tbl_student_id 
                           WHERE DATE(time_in) = :date 
                           ORDER BY time_in DESC");
    $stmt->bindParam(':date', $selectedDate);
    $stmt->execute();
    $attendanceRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create an array with student_id as key for easy lookup
    $presentStudents = [];
    foreach ($attendanceRecords as $record) {
        $presentStudents[$record['tbl_student_id']] = $record;
    }
    
    // Combine all students with their attendance status
    $dateAttendance = [];
    foreach ($allStudents as $student) {
        if (isset($presentStudents[$student['tbl_student_id']])) {
            // Student is present
            $record = $presentStudents[$student['tbl_student_id']];
            $dateAttendance[] = [
                'student_name' => $record['student_name'],
                'time_in' => $record['time_in'],
                'time_out' => $record['time_out'],
                'status' => 'Present'
            ];
        } else {
            // Student is absent
            $dateAttendance[] = [
                'student_name' => $student['student_name'],
                'time_in' => null,
                'time_out' => null,
                'status' => 'Absent'
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Calendar - QR Code Attendance System</title>
    
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
            min-height: 91.5vh;
            padding: 20px 0;
        }

        .calendar-container {
            width: 90%;
            border-radius: 20px;
            padding: 40px;
            background-color: rgba(255, 255, 255, 0.8);
            box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .calendar-nav {
            display: flex;
            gap: 10px;
        }

        .calendar {
            width: 100%;
            border-collapse: collapse;
        }

        .calendar th {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 10px;
        }

        .calendar td {
            border: 1px solid #dee2e6;
            height: 100px;
            vertical-align: top;
            padding: 5px;
        }

        .calendar-day {
            height: 100%;
            display: flex;
            flex-direction: column;
            overflow: hidden; /* Prevent content from expanding the cell */
        }

        .day-number {
            align-self: flex-end;
            font-weight: bold;
            margin-bottom: 2px;
            font-size: 14px;
        }

        .day-status {
            font-size: 10px;
            line-height: 1.2;
            text-align: right;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .has-attendance {
            background-color: #d4edda;
            cursor: pointer;
        }

        .has-attendance:hover {
            background-color: #c3e6cb;
        }

        .selected-day {
            background-color: #007bff;
            color: white;
        }

        .selected-day .day-number {
            color: white;
        }

        .attendance-list {
            margin-top: 30px;
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: rgba(0, 0, 0, 0.1) 0px 4px 6px;
        }

        .attendance-table {
            width: 100%;
        }

        .time-out {
            color: #dc3545;
        }
        .time-in {
            color: #28a745;
        }

        /* Print-specific styles */
        @media print {
            body * {
                visibility: hidden;
            }
            .attendance-list, .attendance-list * {
                visibility: visible;
            }
            .attendance-list {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 0;
                padding: 20px;
                box-shadow: none;
                border-radius: 0;
            }
            .no-print {
                display: none !important;
            }
            .print-header {
                display: block !important;
                text-align: center;
                margin-bottom: 20px;
            }
        }

        .print-header {
            display: none;
        }
        
        .current-day {
            background-color: #fff3cd !important;
            border: 2px solid #ffc107 !important;
        }
        
        .future-day {
            background-color: #f8f9fa !important;
            color: #6c757d !important;
        }
        
        .no-records {
            background-color: #f8d7da !important;
            cursor: not-allowed;
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
        <div class="calendar-container">
            <div class="calendar-header">
                <h2><?php echo "$monthName $year"; ?></h2>
                <div class="calendar-nav">
                    <a href="?action=prev&month=<?php echo $month; ?>&year=<?php echo $year; ?><?php echo $selectedDate ? '&date='.$selectedDate : ''; ?>" class="btn btn-secondary">Previous</a>
                    <a href="?month=<?php echo date('n'); ?>&year=<?php echo date('Y'); ?>" class="btn btn-outline-secondary">Today</a>
                    <a href="?action=next&month=<?php echo $month; ?>&year=<?php echo $year; ?><?php echo $selectedDate ? '&date='.$selectedDate : ''; ?>" class="btn btn-secondary">Next</a>
                </div>
            </div>

            <table class="calendar">
                <thead>
                    <tr>
                        <th>Sun</th>
                        <th>Mon</th>
                        <th>Tue</th>
                        <th>Wed</th>
                        <th>Thu</th>
                        <th>Fri</th>
                        <th>Sat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $currentDay = 1;
                    echo '<tr>';
                    
                    // Fill in empty cells before the first day of the month
                    for ($i = 0; $i < $dayOfWeek; $i++) {
                        echo '<td></td>';
                    }
                    
                    // Fill in the days of the month
                    while ($currentDay <= $daysInMonth) {
                        if ($dayOfWeek == 7) {
                            $dayOfWeek = 0;
                            echo '</tr><tr>';
                        }
                        
                        $isSelected = $selectedDate && date('j', strtotime($selectedDate)) == $currentDay && date('n', strtotime($selectedDate)) == $month && date('Y', strtotime($selectedDate)) == $year;
                        $hasAttendance = isset($attendanceDays[$currentDay]);
                        $isCurrentDay = ($currentDay == $currentDayOfMonth) && ($month == $currentMonth) && ($year == $currentYear);
                        $isFutureDate = ($year > $currentYear) || 
                                       ($year == $currentYear && $month > $currentMonth) || 
                                       ($year == $currentYear && $month == $currentMonth && $currentDay > $currentDayOfMonth);
                        
                        $cellClass = '';
                        
                        if ($isSelected) {
                            $cellClass = 'selected-day';
                        } elseif ($isCurrentDay) {
                            $cellClass = 'current-day';
                        } elseif ($isFutureDate) {
                            $cellClass = 'future-day';
                        } elseif (!$hasAttendance && !$isFutureDate) {
                            $cellClass = 'no-records';
                        } elseif ($hasAttendance) {
                            $cellClass = 'has-attendance';
                        }
                        
                        $dateString = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($currentDay, 2, '0', STR_PAD_LEFT);
                        $onClick = $isFutureDate ? '' : 'onclick="location.href=\'?month=' . $month . '&year=' . $year . '&date=' . $dateString . '\'"';
                        
                        echo '<td class="' . $cellClass . '" ' . $onClick . '>';
                        echo '<div class="calendar-day">';
                        echo '<div class="day-number">' . $currentDay . '</div>';
                        
                        if ($hasAttendance) {
                            echo '<div class="day-status text-success">Recorded</div>';
                        } elseif (!$isFutureDate && !$hasAttendance) {
                            echo '<div class="day-status text-danger">No Records</div>';
                        } elseif ($isFutureDate) {
                            echo '<div class="day-status text-muted">Future</div>';
                        }
                        
                        echo '</div>';
                        echo '</td>';
                        
                        $currentDay++;
                        $dayOfWeek++;
                    }
                    
                    // Fill in empty cells after the last day of the month
                    while ($dayOfWeek < 7) {
                        echo '<td></td>';
                        $dayOfWeek++;
                    }
                    
                    echo '</tr>';
                    ?>
                </tbody>
            </table>

            <?php if ($selectedDate && !empty($dateAttendance)): ?>
                <div class="attendance-list">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3>Attendance for <?php echo date('F j, Y', strtotime($selectedDate)); ?></h3>
                        <button onclick="window.print()" class="btn btn-primary no-print">Print Attendance</button>
                    </div>
                    <div class="print-header">
                        <h2>QR Code Attendance System</h2>
                        <h3>Attendance Report</h3>
                        <p>Date: <?php echo date('F j, Y', strtotime($selectedDate)); ?></p>
                        <p>Generated on: <?php echo date('F j, Y h:i A'); ?></p>
                    </div>
                    <table class="table attendance-table">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Student Name</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
    <?php foreach ($dateAttendance as $index => $record): ?>
        <tr>
            <td><?php echo $index + 1; ?></td>
            <td><?php echo htmlspecialchars($record['student_name']); ?></td>
            <td>
    <?php if ($record['time_in']): ?>
        <span class="time-in"><?= date('h:i A', strtotime($record['time_in'])) ?></span>
    <?php else: ?>
        <span class="text-muted">-</span>
    <?php endif; ?>
</td>
<td>
    <?php if ($record['time_out']): ?>
        <span class="time-out"><?= date('h:i A', strtotime($record['time_out'])) ?></span>
    <?php elseif ($record['status'] == 'Present'): ?>
        <span class="text-muted">Not recorded</span>
    <?php else: ?>
        <span class="text-muted">-</span>
    <?php endif; ?>
</td>
            <td>
                <?php if ($record['time_out']): ?>
                    <span class="time-out"><?php echo date('h:i A', strtotime($record['time_out'])); ?></span>
                <?php elseif ($record['status'] == 'Present'): ?>
                    <span class="text-muted">Not recorded</span>
                <?php else: ?>
                    <span class="text-muted">-</span>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($record['status'] == 'Present'): ?>
                    <?php if ($record['time_out']): ?>
                        <span class="badge badge-success">Completed</span>
                    <?php else: ?>
                        <span class="badge badge-warning">Time In Only</span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="badge badge-danger">Absent</span>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
</tbody>
                    </table>
                </div>
            <?php elseif ($selectedDate): ?>
                <div class="attendance-list">
                    <h3>No attendance records for <?php echo date('F j, Y', strtotime($selectedDate)); ?></h3>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>
</body>
</html>