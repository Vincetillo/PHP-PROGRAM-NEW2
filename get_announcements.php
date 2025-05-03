<?php
require_once 'db.php';
header('Content-Type: application/json');

$db = new Database();
$conn = $db->connect();

$stmt = $conn->prepare("SELECT message, date FROM announcements ORDER BY date DESC LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $announcement = $result->fetch_assoc();
    echo json_encode($announcement);
} else {
    echo json_encode(['message' => 'No announcements found']);
}

$stmt->close();
$conn->close();
?>