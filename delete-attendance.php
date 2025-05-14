<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define environment (change to 'production' for live site)
define('ENVIRONMENT', 'development');

include('../conn/conn.php');

if (!$conn) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database connection failed',
        'error' => ENVIRONMENT === 'development' ? $conn->errorInfo()[2] : null
    ]);
    exit();
}

if (isset($_GET['attendance'])) {
    $attendanceID = filter_input(INPUT_GET, 'attendance', FILTER_VALIDATE_INT);

    if (!$attendanceID) {
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid attendance ID format',
            'received_id' => $_GET['attendance']
        ]);
        exit();
    }

    try {
        $conn->beginTransaction();

        // 1. First verify record exists
        $checkStmt = $conn->prepare("SELECT 1 FROM tbl_attendance WHERE tbl_attendance_id = :id");
        $checkStmt->bindParam(":id", $attendanceID, PDO::PARAM_INT);
        
        if (!$checkStmt->execute()) {
            throw new Exception("Failed to verify record existence: " . implode(" - ", $checkStmt->errorInfo()));
        }

        if ($checkStmt->rowCount() === 0) {
            $conn->rollBack();
            echo json_encode([
                'success' => false, 
                'message' => 'Attendance record not found',
                'attendance_id' => $attendanceID
            ]);
            exit();
        }

        // 2. Perform the deletion (without sequence number updates for now)
        $deleteStmt = $conn->prepare("DELETE FROM tbl_attendance WHERE tbl_attendance_id = :id");
        $deleteStmt->bindParam(":id", $attendanceID, PDO::PARAM_INT);
        
        if (!$deleteStmt->execute()) {
            throw new Exception("Delete failed: " . implode(" - ", $deleteStmt->errorInfo()));
        }

        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Attendance record deleted successfully',
            'attendance_id' => $attendanceID
        ]);

    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("[" . date('Y-m-d H:i:s') . "] Delete Attendance Error: " . $e->getMessage());
        
        echo json_encode([
            'success' => false, 
            'message' => 'Database error occurred',
            'error' => ENVIRONMENT === 'development' ? $e->getMessage() : null
        ]);
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("[" . date('Y-m-d H:i:s') . "] Delete Attendance Error: " . $e->getMessage());
        
        echo json_encode([
            'success' => false, 
            'message' => 'Operation failed',
            'error' => ENVIRONMENT === 'development' ? $e->getMessage() : null
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'No attendance ID provided',
        'request_data' => ENVIRONMENT === 'development' ? $_GET : null
    ]);
}