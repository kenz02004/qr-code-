<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../conn/conn.php");

// Verify database connection
if (!$conn) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed',
        'error' => 'Could not connect to database'
    ]);
    exit();
}

// Configuration
$minMinutesBetweenCheckInOut = 1; // Minimum minutes required between check-in and check-out

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['qr_code'])) {
        $qrCode = trim($_POST['qr_code']);

        // Validate QR code
        if (empty($qrCode)) {
            echo json_encode([
                'success' => false, 
                'message' => 'QR code is empty'
            ]);
            exit();
        }

        if (!preg_match('/^[a-zA-Z0-9]{10}$/', $qrCode)) {
            echo json_encode([
                'success' => false, 
                'message' => 'Invalid QR code format'
            ]);
            exit();
        }

        try {
            // Begin transaction
            $conn->beginTransaction();

            // Check if student exists
            $selectStmt = $conn->prepare("SELECT tbl_student_id FROM tbl_student WHERE generated_code = :generated_code");
            $selectStmt->bindParam(":generated_code", $qrCode, PDO::PARAM_STR);
            
            if (!$selectStmt->execute()) {
                throw new Exception("Failed to execute student query: " . implode(" - ", $selectStmt->errorInfo()));
            }

            $result = $selectStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                $conn->rollBack();
                echo json_encode([
                    'success' => false, 
                    'message' => 'No student found with this QR code'
                ]);
                exit();
            }

            $studentID = $result["tbl_student_id"];
            $currentDate = date("Y-m-d");
            $currentTime = date("Y-m-d H:i:s");

            // Get the most recent attendance record for today
            $checkStmt = $conn->prepare("SELECT tbl_attendance_id, time_in, time_out 
                                       FROM tbl_attendance 
                                       WHERE tbl_student_id = :student_id 
                                       AND DATE(time_in) = :current_date
                                       ORDER BY time_in DESC
                                       LIMIT 1");
            $checkStmt->bindParam(":student_id", $studentID, PDO::PARAM_INT);
            $checkStmt->bindParam(":current_date", $currentDate, PDO::PARAM_STR);
            
            if (!$checkStmt->execute()) {
                throw new Exception("Failed to execute attendance check query: " . implode(" - ", $checkStmt->errorInfo()));
            }

            if ($checkStmt->rowCount() > 0) {
                $attendance = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                // If time_out is null, we need to check if we should record time-out
                if (is_null($attendance['time_out'])) {
                    $timeIn = strtotime($attendance['time_in']);
                    $timeNow = strtotime($currentTime);
                    $timeDifference = $timeNow - $timeIn;
                    
                    // Check minimum time difference
                    if ($timeDifference >= ($minMinutesBetweenCheckInOut * 60)) {
                        $updateStmt = $conn->prepare("UPDATE tbl_attendance 
                                                     SET time_out = :time_out
                                                     WHERE tbl_attendance_id = :attendance_id");
                        $updateStmt->bindParam(":time_out", $currentTime, PDO::PARAM_STR);
                        $updateStmt->bindParam(":attendance_id", $attendance['tbl_attendance_id'], PDO::PARAM_INT);
                        
                        if ($updateStmt->execute()) {
                            $conn->commit();
                            echo json_encode([
                                'success' => true, 
                                'message' => 'Time out recorded successfully',
                                'action' => 'time_out',
                                'time_in' => $attendance['time_in'],
                                'time_out' => $currentTime
                            ]);
                        } else {
                            throw new Exception("Failed to update time out: " . implode(" - ", $updateStmt->errorInfo()));
                        }
                    } else {
                        $conn->rollBack();
                        echo json_encode([
                            'success' => false, 
                            'message' => 'Please wait at least '.$minMinutesBetweenCheckInOut.' minute(s) before time out',
                            'time_remaining' => ($minMinutesBetweenCheckInOut * 60) - $timeDifference
                        ]);
                    }
                } else {
                    // Create new time in record if previous record is complete
                    $stmt = $conn->prepare("INSERT INTO tbl_attendance (tbl_student_id, time_in) 
                                           VALUES (:tbl_student_id, :time_in)");
                    
                    $stmt->bindParam(":tbl_student_id", $studentID, PDO::PARAM_INT); 
                    $stmt->bindParam(":time_in", $currentTime, PDO::PARAM_STR); 

                    if ($stmt->execute()) {
                        $lastInsertId = $conn->lastInsertId();
                        $conn->commit();
                        echo json_encode([
                            'success' => true, 
                            'message' => 'Time in recorded successfully',
                            'action' => 'time_in',
                            'attendance_id' => $lastInsertId,
                            'time_in' => $currentTime
                        ]);
                    } else {
                        throw new Exception("Failed to insert time in: " . implode(" - ", $stmt->errorInfo()));
                    }
                }
                exit();
            }

            // No existing record for today - create new time-in
            $stmt = $conn->prepare("INSERT INTO tbl_attendance (tbl_student_id, time_in) 
                                   VALUES (:tbl_student_id, :time_in)");
            
            $stmt->bindParam(":tbl_student_id", $studentID, PDO::PARAM_INT); 
            $stmt->bindParam(":time_in", $currentTime, PDO::PARAM_STR); 

            if ($stmt->execute()) {
                $lastInsertId = $conn->lastInsertId();
                $conn->commit();
                echo json_encode([
                    'success' => true, 
                    'message' => 'Time in recorded successfully',
                    'action' => 'time_in',
                    'attendance_id' => $lastInsertId,
                    'time_in' => $currentTime
                ]);
            } else {
                throw new Exception("Failed to insert time in: " . implode(" - ", $stmt->errorInfo()));
            }

        } catch (PDOException $e) {
            $conn->rollBack();
            error_log("[" . date('Y-m-d H:i:s') . "] Database Error: " . $e->getMessage());
            
            echo json_encode([
                'success' => false, 
                'message' => 'Database error occurred',
                'error' => $e->getMessage()
            ]);
        } catch (Exception $e) {
            $conn->rollBack();
            error_log("[" . date('Y-m-d H:i:s') . "] Application Error: " . $e->getMessage());
            
            echo json_encode([
                'success' => false, 
                'message' => 'Error processing attendance',
                'error' => $e->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'QR code not provided'
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request method'
    ]);
}