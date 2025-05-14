<?php
header('Content-Type: application/json');
include("../conn/conn.php");

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Validate required fields
$required = ['tbl_student_id', 'student_name', 'course_section'];
$missing = array_diff($required, array_keys($_POST));

if (!empty($missing)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields: ' . implode(', ', $missing)]);
    exit();
}

// Validate data
$studentId = $_POST['tbl_student_id'];
$studentName = trim($_POST['student_name']);
$studentCourse = trim($_POST['course_section']);

if (empty($studentName) || empty($studentCourse)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

// Validate section (optional)
$validSections = ['BIT-CT', 'BIT-WAF', 'BIT-Drafting', 'BIT-Electrical'];
if (!in_array($studentCourse, $validSections)) {
    echo json_encode(['success' => false, 'message' => 'Invalid section selected']);
    exit();
}

try {
    $stmt = $conn->prepare("UPDATE tbl_student SET student_name = :student_name, course_section = :course_section WHERE tbl_student_id = :tbl_student_id");
    
    $stmt->bindParam(":tbl_student_id", $studentId, PDO::PARAM_INT); 
    $stmt->bindParam(":student_name", $studentName, PDO::PARAM_STR); 
    $stmt->bindParam(":course_section", $studentCourse, PDO::PARAM_STR);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update student']);
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>