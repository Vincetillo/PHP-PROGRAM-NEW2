<?php
require_once 'db.php';
header('Content-Type: application/json');

$db = new Database();
$conn = $db->connect();

$stmt = $conn->prepare("SELECT day_of_week, start_time, end_time, day_order FROM transaction_days ORDER BY day_order");
$stmt->execute();
$result = $stmt->get_result();

$days = [];
while ($row = $result->fetch_assoc()) {
    $days[] = $row;
}

echo json_encode($days);

$stmt->close();
$conn->close();
?>