<?php
require_once 'db.php';

header("Content-Type: application/json");

try {
    $db = new Database();
    $conn = $db->connect();
    
    $stmt = $conn->prepare("SELECT day_of_week, start_time, end_time FROM transaction_days ORDER BY day_order");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $transactionDays = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode($transactionDays);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>