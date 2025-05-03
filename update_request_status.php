<?php
require_once 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id']) && isset($_POST['status'])) {
    $db = new Database();
    $conn = $db->connect();
    
    $requestId = $_POST['request_id'];
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE document_requests SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $requestId);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Request status updated successfully";
    } else {
        $_SESSION['error'] = "Error updating request status: " . $conn->error;
    }
    
    $stmt->close();
    $conn->close();
    
    header("Location: registrar.php?view=document-requests");
    exit();
}
?>