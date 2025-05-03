<?php
require_once 'db.php';
header('Content-Type: application/json');

$db = new Database();
$conn = $db->connect();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$itemsPerPage = 12;
$offset = ($page - 1) * $itemsPerPage;

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM document_requests";
if (!empty($search)) {
    $countQuery .= " WHERE student_name LIKE ? OR student_number LIKE ? OR request_number LIKE ?";
}

$countStmt = $conn->prepare($countQuery);
if (!empty($search)) {
    $searchParam = "%$search%";
    $countStmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
}
$countStmt->execute();
$countResult = $countStmt->get_result();
$total = $countResult->fetch_assoc()['total'];
$countStmt->close();

// Get paginated data
$query = "SELECT * FROM document_requests";
if (!empty($search)) {
    $query .= " WHERE student_name LIKE ? OR student_number LIKE ? OR request_number LIKE ?";
}
$query .= " ORDER BY date_requested DESC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
if (!empty($search)) {
    $searchParam = "%$search%";
    $stmt->bind_param("sssii", $searchParam, $searchParam, $searchParam, $itemsPerPage, $offset);
} else {
    $stmt->bind_param("ii", $itemsPerPage, $offset);
}
$stmt->execute();
$result = $stmt->get_result();

$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}

echo json_encode([
    'requests' => $requests,
    'total' => $total,
    'page' => $page,
    'pages' => ceil($total / $itemsPerPage)
]);

$stmt->close();
$conn->close();
?>