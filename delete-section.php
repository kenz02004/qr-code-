<?php
include('../conn/conn.php');

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM tbl_sections WHERE id = :id");
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        header("Location: sections.php");
        exit();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>