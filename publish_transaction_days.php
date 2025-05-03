<?php
require_once 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['days'])) {
    $db = new Database();
    $conn = $db->connect();
    
    // Clear existing data
    $conn->query("TRUNCATE TABLE transaction_days");
    
    // Insert new data
    $stmt = $conn->prepare("INSERT INTO transaction_days (day_of_week, start_time, end_time, day_order) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $day_of_week, $start_time, $end_time, $day_order);
    
    $success = true;
    foreach ($_POST['days'] as $day) {
        $day_of_week = $day['day_of_week'];
        $start_time = $day['start_time'];
        $end_time = $day['end_time'];
        $day_order = $day['day_order'];
        
        if (!$stmt->execute()) {
            $success = false;
            break;
        }
    }
    
    if ($success) {
        $_SESSION['success'] = "Transaction days updated successfully";
    } else {
        $_SESSION['error'] = "Error updating transaction days: " . $conn->error;
    }
    
    $stmt->close();
    $conn->close();
    
    header("Location: registrar.php?view=dashboard");
    exit();
}
?>