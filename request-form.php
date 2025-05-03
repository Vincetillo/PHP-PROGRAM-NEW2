<?php
require_once 'db.php';

header("Content-Type: application/json");
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid request data"]);
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Begin transaction
    $conn->begin_transaction();

    // Insert request
    $stmt = $conn->prepare("INSERT INTO document_requests 
        (student_number, email, contact_no, surname, first_name, middle_initial, 
        strand, year, section, status, request_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
    
    $stmt->bind_param(
        "sssssssss", 
        $data['studentNumber'],
        $data['email'],
        $data['contactNo'],
        $data['surname'],
        $data['firstName'],
        $data['middleInitial'],
        $data['strand'],
        $data['year'],
        $data['section']
    );
    $stmt->execute();
    $requestId = $conn->insert_id;

    // Insert requested documents
    if (isset($data['documents']) && is_array($data['documents'])) {
        $docStmt = $conn->prepare("INSERT INTO request_documents 
            (request_id, document_id, assessment_year, semester) 
            VALUES (?, ?, ?, ?)");
        
        foreach ($data['documents'] as $docId) {
            $year = $data['year-'.$docId] ?? '';
            $semester = $data['semester-'.$docId] ?? '';
            
            $docStmt->bind_param("iiss", $requestId, $docId, $year, $semester);
            $docStmt->execute();
        }
    }

    // Commit transaction
    $conn->commit();
    
    echo json_encode(["success" => true, "message" => "Request submitted successfully"]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>