<?php
require_once 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $db = new Database();
    $conn = $db->connect();
    
    $message = $_POST['message'];
    
    $stmt = $conn->prepare("INSERT INTO announcements (message, date) VALUES (?, NOW())");
    $stmt->bind_param("s", $message);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Announcement published successfully";
    } else {
        $_SESSION['error'] = "Error publishing announcement: " . $conn->error;
    }
    
    $stmt->close();
    $conn->close();
    
    header("Location: registrar.php?view=dashboard");
    exit();
}
?>