<?php
session_start();
require_once 'db.php';

if ($_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit;
}

// Initialize database connection
$db = new Database();
$conn = $db->connect();

// Fetch data from database
$announcements = [];
$transactionDays = [];
$documents = [];
$requestHistory = [];
$accountInfo = [];

try {
    // Fetch announcements
    $result = $conn->query("SELECT message, date FROM announcements ORDER BY date DESC");
    $announcements = $result->fetch_all(MYSQLI_ASSOC);

    // Fetch transaction days
    $result = $conn->query("SELECT day_of_week, start_time, end_time FROM transaction_days ORDER BY day_order");
    $transactionDays = $result->fetch_all(MYSQLI_ASSOC);

    // Fetch documents
    $result = $conn->query("SELECT id, name, description, available FROM documents");
    $documents = $result->fetch_all(MYSQLI_ASSOC);

    // Fetch request history for current student
    $studentNumber = $_SESSION['username'];
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
    $requestHistory = $result->fetch_all(MYSQLI_ASSOC);

    // Fetch account info
    $stmt = $conn->prepare("SELECT 
        first_name, middle_initial, surname, student_number, 
        email, contact_no, address, strand, year, section 
        FROM students WHERE student_number = ?");
    $stmt->bind_param("s", $studentNumber);
    $stmt->execute();
    $result = $stmt->get_result();
    $accountInfo = $result->fetch_assoc();

} catch (Exception $e) {
    // Handle errors silently for production, or log them
    error_log("Database error: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    try {
        // Begin transaction
        $conn->begin_transaction();

        // Insert request
        $stmt = $conn->prepare("INSERT INTO document_requests 
            (student_number, email, contact_no, surname, first_name, middle_initial, 
            strand, year, section, status, request_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
        
        $stmt->bind_param(
            "sssssssss", 
            $_POST['studentNumber'],
            $_POST['email'],
            $_POST['contactNo'],
            $_POST['surname'],
            $_POST['firstName'],
            $_POST['middleInitial'],
            $_POST['strand'],
            $_POST['year'],
            $_POST['section']
        );
        $stmt->execute();
        $requestId = $conn->insert_id;

        // Insert requested documents
        if (isset($_POST['documents'])) {
            $docStmt = $conn->prepare("INSERT INTO request_documents 
                (request_id, document_id, assessment_year, semester) 
                VALUES (?, ?, ?, ?)");
            
            foreach ($_POST['documents'] as $docId) {
                $year = $_POST['year-'.$docId] ?? '';
                $semester = $_POST['semester-'.$docId] ?? '';
                
                $docStmt->bind_param("iiss", $requestId, $docId, $year, $semester);
                $docStmt->execute();
            }
        }

        // Commit transaction
        $conn->commit();
        
        // Redirect to prevent form resubmission
        header("Location: student.php?tab=request-history");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $errorMessage = "Error submitting request: " . $e->getMessage();
    }
}

$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';
$showRequestForm = isset($_GET['request']) && $_GET['request'] === 'true';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | MOGCHS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        /* Layout Structure */
        .student-dashboard {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 220px;
            background-color: #1f1f6e;
            padding: 20px 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
            color: white;
            position: fixed;
            height: 100vh;
        }
        
        .main {
            flex: 1;
            padding: 30px 40px;
            margin-left: 220px;
            margin-right: 250px;
            background-color: #ffffff;
            min-height: 100vh;
        }
        
        .right-sidebar {
            width: 250px;
            background-color: #1f1f6e;
            padding: 20px;
            color: white;
            position: fixed;
            right: 0;
            height: 100vh;
            overflow-y: auto;
        }
        
        
        .sidebar img.logo {
            width: 80px;
            height: 80px;
            margin-bottom: 15px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
        }
        
        .sidebar .avatar {
            width: 80px;
            height: 80px;
            background-color: #6cb0d3;
            border-radius: 50%;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .sidebar .badge {
            background-color: #ffc800;
            color: black;
            padding: 5px 12px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 30px;
            text-transform: uppercase;
        }
        
        .sidebar nav {
            width: 100%;
        }
        
        .sidebar nav a {
            display: block;
            margin: 15px 0;
            color: #aaa;
            text-decoration: none;
            font-weight: 500;
            transition: 0.3s;
            padding: 8px 10px;
            border-radius: 5px;
        }
        
        .sidebar nav a.active,
        .sidebar nav a:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        
        .top-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            gap: 20px;
        }
        
        .announcement-section,
        .transaction-section {
            flex: 1;
            background: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            border-radius: 10px;
        }
        
        .announcement-section h3,
        .transaction-section h3 {
            color: #3c3c9e;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .announcements-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .announcement-item {
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .announcement-item:last-child {
            border-bottom: none;
        }
        
        .announcement-item p {
            margin-bottom: 5px;
            color: #444;
        }
        
        .announcement-item small {
            color: #888;
            font-size: 12px;
        }
        
        .transaction-days {
            margin-top: 10px;
        }
        
        .day-item {
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .day-item:last-child {
            border-bottom: none;
        }
        
        .day-item p {
            color: #444;
        }
        
        .request-section {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .request-section h1 {
            color: #3c3c9e;
            margin-bottom: 15px;
            font-size: 28px;
        }
        
        .request-section .sub {
            color: #666;
            margin-bottom: 25px;
            font-size: 16px;
        }
        
        .request-btn {
            background-color: #3c3c9e;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .request-btn:hover {
            background-color: #2f2f7c;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }
        
        .track-section {
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .track-section h2 {
            font-size: 22px;
            margin-bottom: 20px;
            color: #fff;
            background-color: #3c3c9e;
            padding: 10px 15px;
            border-radius: 8px;
            display: inline-block;
        }
        
        .track-steps {
            margin-top: 15px;
            padding-left: 20px;
            position: relative;
        }
        
        .track-steps::before {
            content: '';
            position: absolute;
            left: 6px;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: #ddd;
        }
        
        .track-step {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            position: relative;
        }
        
        .track-step span {
            display: inline-block;
            width: 14px;
            height: 14px;
            background-color: #4CAF50;
            border-radius: 50%;
            margin-right: 15px;
            position: relative;
            z-index: 1;
        }
        
        .track-step p {
            color: #555;
            font-size: 15px;
        }
        
       
        .request-form-container {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }
        
        .form-title {
            background: #4CAF50;
            color: white;
            text-align: center;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 25px;
            font-size: 20px;
            font-weight: bold;
        }
        
        .date {
            text-align: right;
            margin-bottom: 20px;
            color: #666;
            font-size: 14px;
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .form-section h3 {
            color: #3c3c9e;
            margin-bottom: 20px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
            font-size: 18px;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            flex: 1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3c3c9e;
        }
        
        .documents-list {
            margin-top: 15px;
        }
        
        .document-item {
            display: flex;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #eee;
            gap: 15px;
        }
        
        .document-item.disabled {
            opacity: 0.6;
        }
        
        .document-item input[type="checkbox"] {
            margin-right: 5px;
        }
        
        .document-item label {
            flex: 1;
            color: #333;
        }
        
        .document-item select {
            padding: 8px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-width: 120px;
            background-color: #fff;
            font-size: 13px;
        }
        
        .reminder-box {
            background: #f9f9f9;
            padding: 15px;
            margin: 25px 0;
            border-radius: 5px;
            border-left: 4px solid #f44336;
            font-size: 14px;
        }
        
        .reminder-text {
            color: #f44336;
            font-weight: bold;
        }
        
        .reminder-box a {
            color: #3c3c9e;
            text-decoration: none;
        }
        
        .reminder-box a:hover {
            text-decoration: underline;
        }
        
        .submit-btn {
            background: #4CAF50;
            color: white;
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .submit-btn:hover {
            background: #45a049;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
       
        .documents-content {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .documents-content h1 {
            color: #3c3c9e;
            margin-bottom: 25px;
            font-size: 28px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .documents-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .document-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }
        
        .document-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .document-card.unavailable {
            opacity: 0.7;
            background-color: #f9f9f9;
        }
        
        .document-card h3 {
            color: #3c3c9e;
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .document-card p {
            color: #666;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .document-card a {
            background: #3c3c9e;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            width: 100%;
            display: inline-block;
            text-align: center;
            text-decoration: none;
        }
        
        .document-card a:hover {
            background: #2f2f7c;
        }
        
        .document-card a.disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        /* Request History Styles */
        .request-history-content {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .request-history-content h1 {
            color: #3c3c9e;
            margin-bottom: 25px;
            font-size: 28px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .request-history-content table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .request-history-content th {
            background-color: #3c3c9e;
            color: white;
            padding: 12px 15px;
            text-align: left;
            font-weight: 500;
        }
        
        .request-history-content td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            color: #555;
        }
        
        .request-history-content tr:hover {
            background-color: #f9f9f9;
        }
        
        .status-pending {
            color: #FF9800;
            font-weight: 500;
        }
        
        .status-approved {
            color: #2196F3;
            font-weight: 500;
        }
        
        .status-released {
            color: #4CAF50;
            font-weight: 500;
        }
        
       
        .account-content {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .account-content h1 {
            color: #3c3c9e;
            margin-bottom: 25px;
            font-size: 28px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .account-details {
            margin-top: 20px;
        }
        
        .detail-row {
            display: flex;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            width: 180px;
            color: #555;
            font-weight: 500;
        }
        
        .detail-value {
            flex: 1;
            color: #333;
        }
        
        
        .right-sidebar h3 {
            background-color: #e0f8e3;
            color: #1a4d2e;
            padding: 12px 15px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .right-sidebar h3 i {
            margin-right: 10px;
        }
        
        .step-card {
            background-color: #fffbe7;
            color: #333;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            font-size: 14px;
        }
        
        .step-card span {
            font-weight: bold;
            color: #3c3c9e;
            display: block;
            margin-bottom: 8px;
            font-size: 15px;
        }
        
        
        @media (max-width: 1200px) {
            .right-sidebar {
                display: none;
            }
            
            .main {
                margin-right: 0;
            }
        }
        
        @media (max-width: 992px) {
            .sidebar {
                width: 80px;
                padding: 15px 10px;
                overflow: hidden;
            }
            
            .sidebar .logo,
            .sidebar .avatar,
            .sidebar .badge,
            .sidebar nav a span {
                display: none;
            }
            
            .sidebar nav a {
                text-align: center;
                padding: 10px 0;
            }
            
            .main {
                margin-left: 80px;
            }
        }
        
        @media (max-width: 768px) {
            .top-section {
                flex-direction: column;
            }
            
            .announcement-section,
            .transaction-section {
                width: 100%;
            }
            
            .form-row {
                flex-direction: column;
                gap: 15px;
            }
        }
        
        @media (max-width: 576px) {
            .sidebar {
                width: 60px;
                padding: 10px 5px;
            }
            
            .main {
                margin-left: 60px;
                padding: 20px 15px;
            }
            
            .request-section h1 {
                font-size: 24px;
            }
        }
        
        /* Error message styling */
        .error-message {
            color: #f44336;
            background-color: #ffebee;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #f44336;
        }
    </style>
</head>
<body>
    <div class="student-dashboard">
        <div class="sidebar">
            <img src="/school-logo.png" alt="School Logo" class="logo">
            <div class="avatar"></div>
            <div class="badge">Student</div>
            
            <nav>
                <a href="?tab=dashboard" class="<?= $activeTab === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
                <a href="?tab=documents" class="<?= $activeTab === 'documents' ? 'active' : '' ?>">Documents</a>
                <a href="?tab=request-history" class="<?= $activeTab === 'request-history' ? 'active' : '' ?>">Request History</a>
                <a href="?tab=account" class="<?= $activeTab === 'account' ? 'active' : '' ?>">Account</a>
            </nav>
        </div>

        <div class="main">
            <?php if (isset($errorMessage)): ?>
                <div class="error-message">
                    <?= htmlspecialchars($errorMessage) ?>
                </div>
            <?php endif; ?>

            <?php if ($activeTab === 'dashboard'): ?>
                <?php if ($showRequestForm): ?>
                    <div class="request-form-container">
                        <h2 class="form-title">DOCUMENT REQUEST FORM</h2>
                        <div class="date">Date: <?= date('m/d/Y') ?></div>
                        
                        <form method="POST" action="student.php?tab=dashboard&request=true">
                            <div class="form-section">
                                <h3>STUDENT INFORMATION</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Student Number</label>
                                        <input 
                                            type="text" 
                                            name="studentNumber" 
                                            value="<?= htmlspecialchars($accountInfo['student_number'] ?? '') ?>" 
                                            required 
                                        />
                                    </div>
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input 
                                            type="email" 
                                            name="email" 
                                            value="<?= htmlspecialchars($accountInfo['email'] ?? '') ?>" 
                                            required 
                                        />
                                    </div>
                                    <div class="form-group">
                                        <label>Contact No</label>
                                        <input 
                                            type="text" 
                                            name="contactNo" 
                                            value="<?= htmlspecialchars($accountInfo['contact_no'] ?? '') ?>" 
                                            required 
                                        />
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Surname</label>
                                        <input 
                                            type="text" 
                                            name="surname" 
                                            value="<?= htmlspecialchars($accountInfo['surname'] ?? '') ?>" 
                                            required 
                                        />
                                    </div>
                                    <div class="form-group">
                                        <label>First Name</label>
                                        <input 
                                            type="text" 
                                            name="firstName" 
                                            value="<?= htmlspecialchars($accountInfo['first_name'] ?? '') ?>" 
                                            required 
                                        />
                                    </div>
                                    <div class="form-group">
                                        <label>Middle Initial</label>
                                        <input 
                                            type="text" 
                                            name="middleInitial" 
                                            value="<?= htmlspecialchars($accountInfo['middle_initial'] ?? '') ?>" 
                                        />
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Strand</label>
                                        <input 
                                            type="text" 
                                            name="strand" 
                                            value="<?= htmlspecialchars($accountInfo['strand'] ?? '') ?>" 
                                            required 
                                        />
                                    </div>
                                    <div class="form-group">
                                        <label>Year</label>
                                        <input 
                                            type="text" 
                                            name="year" 
                                            value="<?= htmlspecialchars($accountInfo['year'] ?? '') ?>" 
                                            required 
                                        />
                                    </div>
                                    <div class="form-group">
                                        <label>Section</label>
                                        <input 
                                            type="text" 
                                            name="section" 
                                            value="<?= htmlspecialchars($accountInfo['section'] ?? '') ?>" 
                                            required 
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h3>DOCUMENTS AVAILABLE</h3>
                                <div class="documents-list">
                                    <?php foreach ($documents as $doc): ?>
                                        <div class="document-item <?= $doc['available'] ? '' : 'disabled' ?>">
                                            <input 
                                                type="checkbox" 
                                                id="doc-<?= $doc['id'] ?>"
                                                name="documents[]"
                                                value="<?= $doc['id'] ?>"
                                                <?= $doc['available'] ? '' : 'disabled' ?>
                                            />
                                            <label for="doc-<?= $doc['id'] ?>"><?= htmlspecialchars($doc['name']) ?></label>
                                            <?php if ($doc['available']): ?>
                                                <select name="year-<?= $doc['id'] ?>" disabled>
                                                    <option value="">Select Year</option>
                                                    <option value="2023">2023</option>
                                                    <option value="2022">2022</option>
                                                    <option value="2021">2021</option>
                                                </select>
                                                <select name="semester-<?= $doc['id'] ?>" disabled>
                                                    <option value="">Select Semester</option>
                                                    <option value="1st Semester">1st Semester</option>
                                                    <option value="2nd Semester">2nd Semester</option>
                                                </select>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="reminder-box">
                                <p><strong class="reminder-text">Reminder</strong> Please double-check all information before submitting. <a href="#">Click here</a> for more information.</p>
                            </div>

                            <input type="hidden" name="status" value="pending">
                            <button type="submit" name="submit_request" class="submit-btn">SUBMIT</button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="top-section">
                        <div class="announcement-section">
                            <h3>Announcements</h3>
                            <div class="announcements-list">
                                <?php foreach ($announcements as $announcement): ?>
                                    <div class="announcement-item">
                                        <p><?= htmlspecialchars($announcement['message']) ?></p>
                                        <small><?= date('m/d/Y', strtotime($announcement['date'])) ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="transaction-section">
                            <h3>Transaction Days</h3>
                            <div class="transaction-days">
                                <?php foreach ($transactionDays as $day): ?>
                                    <div class="day-item">
                                        <p><?= htmlspecialchars($day['day_of_week']) ?>: <?= htmlspecialchars($day['start_time']) ?> - <?= htmlspecialchars($day['end_time']) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="request-section">
                        <h1>Request Academic Documents With Ease</h1>
                        <p class="sub">Request your Academic Records from the MOGCHS Online.</p>
                        <a href="?tab=dashboard&request=true" class="request-btn">Request Now</a>
                    </div>

                    <div class="track-section">
                        <h2>Track</h2>
                        <div class="track-steps">
                            <div class="track-step">
                                <span></span>
                                <p>Pending Approval (<?= date('m/d/Y') ?>)</p>
                            </div>
                            <div class="track-step">
                                <span></span>
                                <p>Preparing (<?= date('m/d/Y', strtotime('+1 day')) ?>)</p>
                            </div>
                            <div class="track-step">
                                <span></span>
                                <p>Released (<?= date('m/d/Y', strtotime('+2 days')) ?>)</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php elseif ($activeTab === 'documents'): ?>
                <div class="documents-content">
                    <h1>Available Documents</h1>
                    <div class="documents-list">
                        <?php foreach ($documents as $doc): ?>
                            <div class="document-card <?= $doc['available'] ? '' : 'unavailable' ?>">
                                <h3><?= htmlspecialchars($doc['name']) ?></h3>
                                <p><?= htmlspecialchars($doc['description']) ?></p>
                                <?php if ($doc['available']): ?>
                                    <a href="?tab=dashboard&request=true">Request This Document</a>
                                <?php else: ?>
                                    <a class="disabled">Currently Unavailable</a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php elseif ($activeTab === 'request-history'): ?>
                <div class="request-history-content">
                    <h1>Request History</h1>
                    <table>
                        <thead>
                            <tr>
                                <th>Request ID</th>
                                <th>Document</th>
                                <th>Date Requested</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requestHistory as $request): ?>
                                <tr>
                                    <td>REQ-<?= htmlspecialchars($request['request_id']) ?></td>
                                    <td><?= htmlspecialchars($request['document_name']) ?></td>
                                    <td><?= date('m/d/Y', strtotime($request['request_date'])) ?></td>
                                    <td class="status-<?= strtolower($request['status']) ?>">
                                        <?= htmlspecialchars($request['status']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($activeTab === 'account'): ?>
                <div class="account-content">
                    <h1>Account Information</h1>
                    <div class="account-details">
                        <div class="detail-row">
                            <span class="detail-label">Full Name:</span>
                            <span class="detail-value"><?= htmlspecialchars($accountInfo['first_name']) ?> <?= htmlspecialchars($accountInfo['middle_initial']) ?> <?= htmlspecialchars($accountInfo['surname']) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Student Number:</span>
                            <span class="detail-value"><?= htmlspecialchars($accountInfo['student_number']) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Email:</span>
                            <span class="detail-value"><?= htmlspecialchars($accountInfo['email']) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Contact Number:</span>
                            <span class="detail-value"><?= htmlspecialchars($accountInfo['contact_no']) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Address:</span>
                            <span class="detail-value"><?= htmlspecialchars($accountInfo['address']) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Strand:</span>
                            <span class="detail-value"><?= htmlspecialchars($accountInfo['strand']) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Year & Section:</span>
                            <span class="detail-value"><?= htmlspecialchars($accountInfo['year']) ?> - <?= htmlspecialchars($accountInfo['section']) ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="right-sidebar">
            <h3>
                <i class="icon-lightbulb"></i> 3 Easy Steps to Create a Request
            </h3>
            <div class="step-card">
                <span>Step 1</span>
                Click the "Request Now" button
            </div>
            <div class="step-card">
                <span>Step 2</span>
                Fill out the request form
            </div>
            <div class="step-card">
                <span>Step 3</span>
                Set an appointment and wait for confirmation via email
            </div>
        </div>
    </div>

    <script>
        // Enable year and semester selects when document checkbox is checked
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('input[type="checkbox"][name="documents[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const docId = this.value;
                    const yearSelect = this.closest('.document-item').querySelector('select[name^="year-"]');
                    const semesterSelect = this.closest('.document-item').querySelector('select[name^="semester-"]');
                    
                    if (this.checked) {
                        yearSelect.disabled = false;
                        
                        // Enable semester select when year is selected
                        yearSelect.addEventListener('change', function() {
                            semesterSelect.disabled = !this.value;
                        });
                    } else {
                        yearSelect.disabled = true;
                        semesterSelect.disabled = true;
                        yearSelect.value = '';
                        semesterSelect.value = '';
                    }
                });
            });
        });
    </script>
</body>
</html>