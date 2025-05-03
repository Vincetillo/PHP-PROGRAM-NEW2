<?php
session_start();

// Check if user is logged in as registrar
if ($_SESSION['role'] !== 'registrar') {
    header("Location: index.php");
    exit;
}

// Database connection using MySQLi
require_once 'db.php';
$db = new Database();
$conn = $db->connect();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'publish_announcement':
                $message = $_POST['message'];
                $stmt = $conn->prepare("INSERT INTO announcements (message, date) VALUES (?, NOW())");
                $stmt->bind_param("s", $message);
                $stmt->execute();
                $stmt->close();
                break;
                
            case 'publish_transaction_days':
                // Clear existing data
                $conn->query("TRUNCATE TABLE transaction_days");
                
                // Insert new data
                $stmt = $conn->prepare("INSERT INTO transaction_days (day_of_week, start_time, end_time, day_order) VALUES (?, ?, ?, ?)");
                
                foreach ($_POST['days'] as $day) {
                    $stmt->bind_param("sssi", $day['day_of_week'], $day['start_time'], $day['end_time'], $day['day_order']);
                    $stmt->execute();
                }
                $stmt->close();
                break;
                
            case 'update_request_status':
                $requestId = $_POST['request_id'];
                $status = $_POST['status'];
                $stmt = $conn->prepare("UPDATE document_requests SET status = ? WHERE id = ?");
                $stmt->bind_param("si", $status, $requestId);
                $stmt->execute();
                $stmt->close();
                break;
                
            case 'update_schedule_date':
                $requestId = $_POST['request_id'];
                $field = $_POST['field'];
                $date = $_POST['date'];
                $stmt = $conn->prepare("UPDATE document_requests SET {$field} = ? WHERE id = ?");
                $stmt->bind_param("si", $date, $requestId);
                $stmt->execute();
                $stmt->close();
                break;
        }
    }
}

// Get current view
$activeView = isset($_GET['view']) ? $_GET['view'] : 'dashboard';

// Get data for views
$announcement = '';
$transactionDays = [];
$documentRequests = [];
$totalRequests = 0;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$itemsPerPage = 12;
$offset = ($currentPage - 1) * $itemsPerPage;

// Get latest announcement
$stmt = $conn->prepare("SELECT message FROM announcements ORDER BY date DESC LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $announcement = $row['message'];
}
$stmt->close();

// Get transaction days
$stmt = $conn->prepare("SELECT day_of_week, start_time, end_time, day_order FROM transaction_days ORDER BY day_order");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $transactionDays[] = $row;
}
$stmt->close();

// Get document requests
if ($activeView === 'document-requests') {
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM document_requests";
    if (!empty($searchTerm)) {
        $countQuery .= " WHERE student_name LIKE ? OR student_number LIKE ? OR request_number LIKE ?";
    }

    $countStmt = $conn->prepare($countQuery);
    if (!empty($searchTerm)) {
        $searchParam = "%$searchTerm%";
        $countStmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
    }
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalRequests = $countResult->fetch_assoc()['total'];
    $countStmt->close();

    // Get paginated data
    $query = "SELECT * FROM document_requests";
    if (!empty($searchTerm)) {
        $query .= " WHERE student_name LIKE ? OR student_number LIKE ? OR request_number LIKE ?";
    }
    $query .= " ORDER BY date_requested DESC LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($query);
    if (!empty($searchTerm)) {
        $searchParam = "%$searchTerm%";
        $stmt->bind_param("sssii", $searchParam, $searchParam, $searchParam, $itemsPerPage, $offset);
    } else {
        $stmt->bind_param("ii", $itemsPerPage, $offset);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $documentRequests[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MOGCHS Registrar Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="stylesheet" href="registrar.css">
    <script>
        function updateRequestStatus(requestId, status) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'registrar.php?view=document-requests';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'update_request_status';
            form.appendChild(actionInput);
            
            const requestInput = document.createElement('input');
            requestInput.type = 'hidden';
            requestInput.name = 'request_id';
            requestInput.value = requestId;
            form.appendChild(requestInput);
            
            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'status';
            statusInput.value = status;
            form.appendChild(statusInput);
            
            document.body.appendChild(form);
            form.submit();
        }
        
        function updateScheduleDate(requestId, field) {
            const date = prompt('Enter date (YYYY-MM-DD):');
            if (date) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'registrar.php?view=document-requests';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'update_schedule_date';
                form.appendChild(actionInput);
                
                const requestInput = document.createElement('input');
                requestInput.type = 'hidden';
                requestInput.name = 'request_id';
                requestInput.value = requestId;
                form.appendChild(requestInput);
                
                const fieldInput = document.createElement('input');
                fieldInput.type = 'hidden';
                fieldInput.name = 'field';
                fieldInput.value = field;
                form.appendChild(fieldInput);
                
                const dateInput = document.createElement('input');
                dateInput.type = 'hidden';
                dateInput.name = 'date';
                dateInput.value = date;
                form.appendChild(dateInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function downloadReport() {
            window.location.href = 'download-report.php';
        }
    </script>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="profile-section">
                <div class="profile-photo"></div>
                <div class="profile-info">
                    <h3><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest'; ?></h3>
                    <p class="role-tag">Registrar</p>
                </div>
                <p class="student-number">2023-12345</p>
            </div>

            <nav class="nav-links">
                <a href="registrar.php?view=dashboard" class="nav-link <?php echo $activeView === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="registrar.php?view=document-requests" class="nav-link <?php echo $activeView === 'document-requests' ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i> Document Requests
                </a>
                <a href="registrar.php?view=account" class="nav-link <?php echo $activeView === 'account' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i> Account
                </a>
            </nav>

            <a href="logout.php" class="logout-button">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>

        <div class="main-content">
            <?php if ($activeView === 'account'): ?>
                <div class="account-view">
                    <h2>Account Information</h2>
                    <div class="account-info">
                        <div class="info-row">
                            <label>Full Name:</label>
                            <p><?php echo isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : 'N/A'; ?></p>
                        </div>
                        <div class="info-row">
                            <label>Address:</label>
                            <p><?php echo isset($_SESSION['address']) ? htmlspecialchars($_SESSION['address']) : 'N/A'; ?></p>
                        </div>
                        <div class="info-row">
                            <label>Contact No.:</label>
                            <p><?php echo isset($_SESSION['contact_number']) ? htmlspecialchars($_SESSION['contact_number']) : 'N/A'; ?></p>
                        </div>
                        <div class="info-row">
                            <label>Email:</label>
                            <p><?php echo isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : 'N/A'; ?></p>
                        </div>
                    </div>
                </div>
            <?php elseif ($activeView === 'dashboard'): ?>
                <div class="dashboard-view">
                    <h2>Announcements</h2>
                    <div class="announcement-card">
                        <?php if (isset($_GET['edit_announcement']) && $_GET['edit_announcement'] == '1'): ?>
                            <form method="POST" action="registrar.php?view=dashboard">
                                <input type="hidden" name="action" value="publish_announcement">
                                <textarea name="message" placeholder="Type your announcement here..."><?php echo htmlspecialchars($announcement); ?></textarea>
                                <div class="announcement-actions">
                                    <a href="registrar.php?view=dashboard" class="cancel-btn">Cancel</a>
                                    <button type="submit" class="publish-btn">Publish</button>
                                </div>
                            </form>
                        <?php else: ?>
                            <p><?php echo $announcement ? htmlspecialchars($announcement) : 'No current announcements'; ?></p>
                            <div class="announcement-actions">
                                <a href="registrar.php?view=dashboard&edit_announcement=1" class="edit-btn">Edit</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif ($activeView === 'document-requests'): ?>
                <div class="document-requests-view">
                    <div class="requests-header">
                        <h2>DOCUMENT REQUESTS</h2>
                        <div class="requests-actions">
                            <form class="search-form" method="GET" action="registrar.php">
                                <input type="hidden" name="view" value="document-requests">
                                <input type="text" name="search" placeholder="Search requests..." value="<?php echo htmlspecialchars($searchTerm); ?>" />
                                <button type="submit"><i class="fas fa-search"></i></button>
                            </form>
                            <button class="download-btn" onclick="downloadReport()">
                                <i class="fas fa-download"></i> Download Report
                            </button>
                        </div>
                    </div>

                    <div class="requests-table-container">
                        <table class="requests-table">
                            <thead>
                                <tr>
                                    <th>Request No.</th>
                                    <th>Student Number</th>
                                    <th>Student Name</th>
                                    <th>Course</th>
                                    <th>Contact #</th>
                                    <th>Email</th>
                                    <th>Date Requested</th>
                                    <th>Scheduled Pick-Up</th>
                                    <th>Rescheduled Pick-Up</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documentRequests as $request): ?>
                                    <tr>
                                        <td><a href="#"><?php echo htmlspecialchars($request['request_number']); ?></a></td>
                                        <td><?php echo htmlspecialchars($request['student_number']); ?></td>
                                        <td><?php echo htmlspecialchars($request['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($request['course']); ?></td>
                                        <td><?php echo htmlspecialchars($request['contact_number']); ?></td>
                                        <td><?php echo htmlspecialchars($request['email']); ?></td>
                                        <td><?php echo htmlspecialchars($request['date_requested']); ?></td>
                                        <td>
                                            <?php if ($request['scheduled_pickup']): ?>
                                                <span class="date-with-icon">
                                                    <?php echo htmlspecialchars($request['scheduled_pickup']); ?>
                                                    <i class="fas fa-check completed-icon"></i>
                                                </span>
                                            <?php else: ?>
                                                <button class="calendar-btn" onclick="updateScheduleDate(<?php echo $request['id']; ?>, 'scheduled_pickup')">
                                                    <i class="fas fa-calendar"></i> Set Date
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($request['rescheduled_pickup']): ?>
                                                <span class="date-with-icon">
                                                    <?php echo htmlspecialchars($request['rescheduled_pickup']); ?>
                                                </span>
                                            <?php else: ?>
                                                <button class="calendar-btn" onclick="updateScheduleDate(<?php echo $request['id']; ?>, 'rescheduled_pickup')">
                                                    <i class="fas fa-calendar"></i> Set Date
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <select class="status-select <?php echo strtolower($request['status']); ?>" 
                                                    onchange="updateRequestStatus(<?php echo $request['id']; ?>, this.value)">
                                                <option value="Pending" class="pending" <?php echo $request['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="Ready" class="ready" <?php echo $request['status'] === 'Ready' ? 'selected' : ''; ?>>Ready</option>
                                                <option value="Received" class="received" <?php echo $request['status'] === 'Received' ? 'selected' : ''; ?>>Received</option>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="requests-footer">
                        <div class="pagination">
                            <button <?php echo $currentPage === 1 ? 'disabled' : ''; ?> 
                                    onclick="window.location.href='registrar.php?view=document-requests&page=<?php echo $currentPage - 1; ?>&search=<?php echo urlencode($searchTerm); ?>'">
                                Previous
                            </button>
                            <span>Page <?php echo $currentPage; ?></span>
                            <button <?php echo $currentPage * $itemsPerPage >= $totalRequests ? 'disabled' : ''; ?>
                                    onclick="window.location.href='registrar.php?view=document-requests&page=<?php echo $currentPage + 1; ?>&search=<?php echo urlencode($searchTerm); ?>'">
                                Next
                            </button>
                        </div>
                        <div class="viewing-info">
                            Viewing <?php echo ($currentPage - 1) * $itemsPerPage + 1; ?>-<?php echo min($currentPage * $itemsPerPage, $totalRequests); ?> of <?php echo $totalRequests; ?> requests
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="right-sidebar">
            <div class="announcements-card">
                <h3>Announcements</h3>
                <div class="announcement-content">
                    <?php if (isset($_GET['edit_announcement']) && $_GET['edit_announcement'] == '1'): ?>
                        <textarea placeholder="Type your announcement here..."><?php echo htmlspecialchars($announcement); ?></textarea>
                    <?php else: ?>
                        <p><?php echo $announcement ? htmlspecialchars($announcement) : 'No current announcements'; ?></p>
                    <?php endif; ?>
                </div>
                <div class="card-actions">
                    <?php if (isset($_GET['edit_announcement']) && $_GET['edit_announcement'] == '1'): ?>
                        <a href="registrar.php?view=dashboard" class="cancel-btn">Cancel</a>
                        <button class="publish-btn">Publish</button>
                    <?php else: ?>
                        <a href="registrar.php?view=dashboard&edit_announcement=1" class="edit-btn">Edit</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="transaction-days-card">
                <h3>Transaction Days</h3>
                <form method="POST" action="registrar.php">
                    <input type="hidden" name="action" value="publish_transaction_days">
                    <div class="transaction-days-content">
                        <?php foreach ($transactionDays as $day): ?>
                            <div class="day-row">
                                <label><?php echo htmlspecialchars($day['day_of_week']); ?>:</label>
                                <input type="time" name="days[<?php echo $day['day_order'] - 1; ?>][start_time]" value="<?php echo htmlspecialchars($day['start_time']); ?>">
                                <span>to</span>
                                <input type="time" name="days[<?php echo $day['day_order'] - 1; ?>][end_time]" value="<?php echo htmlspecialchars($day['end_time']); ?>">
                                <input type="hidden" name="days[<?php echo $day['day_order'] - 1; ?>][day_of_week]" value="<?php echo htmlspecialchars($day['day_of_week']); ?>">
                                <input type="hidden" name="days[<?php echo $day['day_order'] - 1; ?>][day_order]" value="<?php echo $day['day_order']; ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" class="publish-btn">Publish Schedule</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>