<?php
require_once 'db.php';

header("Content-Type: application/json");
session_start();

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$studentNumber = $_SESSION['username'];

try {
    $db = new Database();
    $conn = $db->connect();
    
    $stmt = $conn->prepare("SELECT 
        dr.id AS request_id, 
        d.name AS document_name, 
        dr.request_date, 
        dr.status 
        FROM document_requests dr
        JOIN request_documents rd ON dr.id = rd.request_id
        JOIN documents d ON rd.document_id = d.id
        WHERE dr.student_number = ?
        ORDER BY dr.request_date DESC");
    $stmt->bind_param("s", $studentNumber);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $requests = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode($requests);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>