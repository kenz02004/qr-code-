<?php
header('Content-Type: application/json');
include("../conn/conn.php");

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id'])) {
        $studentId = $_GET['id'];
        
        try {
            // Validate student ID
            if (!is_numeric($studentId)) {
                echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
                exit();
            }
            
            // Prepare and execute query
            $stmt = $conn->prepare("SELECT tbl_student_id, student_name, course_section, generated_code 
                                   FROM tbl_student 
                                   WHERE tbl_student_id = :student_id 
                                   LIMIT 1");
            $stmt->bindParam(":student_id", $studentId, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $student = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'tbl_student_id' => $student['tbl_student_id'],
                        'student_name' => $student['student_name'],
                        'course_section' => $student['course_section'],
                        'generated_code' => $student['generated_code']
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Student not found']);
            }
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error occurred']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Student ID not provided']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>