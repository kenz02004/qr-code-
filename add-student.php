<?php
header('Content-Type: application/json');
include("../conn/conn.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate all required fields exist
    $required = ['student_name', 'course_section', 'generated_code'];
    $missing = array_diff($required, array_keys($_POST));
    
    if (!empty($missing)) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields: ' . implode(', ', $missing)
        ]);
        exit();
    }

    $studentName = trim($_POST['student_name']);
    $courseSection = trim($_POST['course_section']);
    $generatedCode = trim($_POST['generated_code']);

    if (empty($studentName) || empty($courseSection) || empty($generatedCode)) {
        echo json_encode([
            'success' => false, 
            'message' => 'All fields are required'
        ]);
        exit();
    }

    try {
        $stmt = $conn->prepare("INSERT INTO tbl_student (student_name, course_section, generated_code) 
                               VALUES (:student_name, :course_section, :generated_code)");
        
        $stmt->bindParam(":student_name", $studentName);
        $stmt->bindParam(":course_section", $courseSection);
        $stmt->bindParam(":generated_code", $generatedCode);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Failed to add student to database'
            ]);
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'Database error occurred'
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request method'
    ]);
}
?>