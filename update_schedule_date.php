<?php
require_once 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id']) && isset($_POST['field']) && isset($_POST['date'])) {
    $db = new Database();
    $conn = $db->connect();
    
    $requestId = $_POST['request_id'];
    $field = $_POST['field'];
    $date = $_POST['date'];
    
    // Validate field
    $validFields = ['scheduled_pickup', 'rescheduled_pickup'];
    if (!in_array($field, $validFields)) {
        $_SESSION['error'] = "Invalid field specified";
        header("Location: registrar.php?view=document-requests");
        exit();
    }
    
    $stmt = $conn->prepare("UPDATE document_requests SET {$field} = ? WHERE id = ?");
    $stmt->bind_param("si", $date, $requestId);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Schedule date updated successfully";
    } else {
        $_SESSION['error'] = "Error updating schedule date: " . $conn->error;
    }
    
    $stmt->close();
    $conn->close();
    
    header("Location: registrar.php?view=document-requests");
    exit();
}
?>