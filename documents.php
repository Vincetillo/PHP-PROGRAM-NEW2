<?php
require_once 'db.php';

header("Content-Type: application/json");

try {
    $db = new Database();
    $conn = $db->connect();
    
    $stmt = $conn->prepare("SELECT id, name, description, available FROM documents");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $documents = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode($documents);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>