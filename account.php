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
        first_name, middle_initial, surname, student_number, 
        email, contact_no, address, strand, year, section 
        FROM students WHERE student_number = ?");
    $stmt->bind_param("s", $studentNumber);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $accountInfo = $result->fetch_assoc();
    
    if (!$accountInfo) {
        http_response_code(404);
        echo json_encode(["error" => "Student not found"]);
        exit;
    }
    
    echo json_encode($accountInfo);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>