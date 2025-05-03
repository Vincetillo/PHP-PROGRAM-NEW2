<?php
session_start();
require_once 'db.php'; // Using the Database class instead of db_connect.php

if ($_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit;
}

// Create database connection
$database = new Database();
$conn = $database->connect();

// Fetch teacher data from database
$teacher_id = $_SESSION['user_id'];
$teacher_data = [];
$classes = [];

try {
    // Get teacher profile
    $stmt = $conn->prepare("SELECT * FROM teachers WHERE id = ?");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $teacher_data = $result->fetch_assoc();
    
    // Get teacher's classes
    $stmt = $conn->prepare("SELECT * FROM classes WHERE teacher_id = ?");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $classes_result = $stmt->get_result();
    while ($row = $classes_result->fetch_assoc()) {
        $classes[] = $row;
    }
    
    // Get students for the Students tab
    if (isset($_GET['class_id'])) {
        $class_id = $_GET['class_id'];
        $stmt = $conn->prepare("SELECT students.* FROM students 
                               JOIN class_students ON students.id = class_students.student_id 
                               WHERE class_students.class_id = ?");
        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        $students_result = $stmt->get_result();
        $students = [];
        while ($row = $students_result->fetch_assoc()) {
            $students[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = $_POST['full_name'];
        $address = $_POST['address'];
        $contact_number = $_POST['contact_number'];
        $email = $_POST['email'];
        
        try {
            $stmt = $conn->prepare("UPDATE teachers SET full_name = ?, address = ?, contact_number = ?, email = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $full_name, $address, $contact_number, $email, $teacher_id);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                $success = "Profile updated successfully!";
                // Refresh teacher data
                $stmt = $conn->prepare("SELECT * FROM teachers WHERE id = ?");
                $stmt->bind_param("i", $teacher_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $teacher_data = $result->fetch_assoc();
            }
        } catch (Exception $e) {
            $error = "Error updating profile: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['add_student'])) {
        $student_id = $_POST['student_id'];
        $class_id = $_POST['class_id'];
        
        try {
            $stmt = $conn->prepare("INSERT INTO class_students (class_id, student_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $class_id, $student_id);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                $success = "Student added to class successfully!";
            }
        } catch (Exception $e) {
            $error = "Error adding student: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MOGCHS - Teacher Account</title>
    <style>
        .account-container {
            display: grid;
            grid-template-columns: 250px 1fr 50px;
            min-height: 100vh;
            font-family: 'Arial', sans-serif;
        }

        
        .sidebar {
            background-color: #1a237e; 
            color: white;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }

        .left-sidebar {
            align-items: center;
        }

        .right-sidebar {
            align-items: flex-end;
        }

        .logo-container {
            margin-bottom: 30px;
        }

        .school-logo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
        }

        .profile-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 30px;
        }

        .profile-picture {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
            margin-bottom: 10px;
        }

        .profile-name {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .role-badge {
            background-color: #f44336; 
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }

        .sidebar-nav {
            display: flex;
            flex-direction: column;
            width: 100%;
            margin-bottom: auto;
        }

        .nav-link {
            color: white;
            padding: 10px 15px;
            margin: 5px 0;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .nav-link:hover {
            background-color: #303f9f;
        }

        .nav-link.active {
            background-color: #303f9f;
            font-weight: bold;
        }

        .logout-btn {
            background-color: transparent;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .logout-btn:hover {
            background-color: #303f9f;
        }

        .power-icon {
            margin-right: 8px;
            color: #4caf50; 
        }

        .notification-icon {
            margin-top: 20px;
        }

        .bell-icon {
            font-size: 20px;
            color: #ffeb3b; 
        }

        
        .main-content {
            background-color: #f8fff0; 
            padding: 30px;
        }

        .account-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .underline {
            height: 3px;
            width: 100px;
            background-color: #1a237e; 
            margin-bottom: 30px;
        }

        .profile-card {
            background-color: #f44336; 
            border-radius: 15px;
            padding: 20px;
            color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .profile-card-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            position: relative;
        }

        .profile-card-picture {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            margin-right: 20px;
        }

        .profile-card-info h2 {
            margin: 0;
            font-size: 24px;
        }

        .profile-card-info p {
            margin: 5px 0 0;
            font-size: 14px;
        }

        .profile-details {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            color: #333;
        }

        .detail-field {
            margin-bottom: 15px;
        }

        .detail-field label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #666;
        }

        .detail-field input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
            color: #333;
        }

        .detail-field input[type="submit"] {
            background-color: #1a237e;
            color: white;
            cursor: pointer;
            border: none;
            padding: 12px;
            font-weight: bold;
        }

        .detail-field input[type="submit"]:hover {
            background-color: #303f9f;
        }

        .class-selector {
            margin-bottom: 20px;
        }

        .class-selector select {
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
            width: 100%;
            max-width: 300px;
        }

        .student-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .student-card {
            background-color: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .student-card h3 {
            margin-top: 0;
            color: #1a237e;
        }

        .add-student-form {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }

        .add-student-form h3 {
            margin-top: 0;
        }

        .error-message {
            color: #f44336;
            margin-bottom: 15px;
        }

        .success-message {
            color: #4caf50;
            margin-bottom: 15px;
        }

        
        .page-footer {
            grid-column: 1 / -1;
            text-align: center;
            padding: 20px;
            background-color: white;
            border-top: 1px solid #ddd;
        }

        .footer-link {
            color: #1a237e;
            text-decoration: none;
        }

        .footer-link:hover {
            text-decoration: underline;
        }

        
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="account-container">
        
        <div class="sidebar left-sidebar">
            <div class="logo-container">
                <img src="/school-logo.png" alt="School Logo" class="school-logo" />
            </div>

            <div class="profile-container">
                <img src="/teacher-profile.jpg" alt="Teacher Profile" class="profile-picture" />
                <div class="profile-name"><?php echo htmlspecialchars($teacher_data['full_name'] ?? $_SESSION['username']); ?></div>
                <div class="role-badge">SHS-Teacher</div>
            </div>

            <nav class="sidebar-nav">
                <a href="#" class="nav-link active" onclick="showTab('dashboard')">Dashboard</a>
                <a href="#" class="nav-link" onclick="showTab('students')">Students</a>
                <a href="#" class="nav-link" onclick="showTab('account')">Account</a>
            </nav>

            <a href="logout.php" class="logout-btn">
                <span class="power-icon">⚡</span> Log Out
            </a>
        </div>

        
        <div class="main-content">
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <div id="dashboard" class="tab-content active">
                <h1 class="account-title">DASHBOARD</h1>
                <div class="underline"></div>
                <p>Welcome to your teacher dashboard, <?php echo htmlspecialchars($teacher_data['full_name'] ?? $_SESSION['username']); ?>!</p>
                <p>Here you can view your classes, manage students, and update your account information.</p>
                
                <h2>Your Classes</h2>
                <?php if (!empty($classes)): ?>
                    <div class="student-list">
                        <?php foreach ($classes as $class): ?>
                            <div class="student-card">
                                <h3><?php echo htmlspecialchars($class['class_name']); ?></h3>
                                <p>Subject: <?php echo htmlspecialchars($class['subject']); ?></p>
                                <p>Schedule: <?php echo htmlspecialchars($class['schedule']); ?></p>
                                <p>Room: <?php echo htmlspecialchars($class['room']); ?></p>
                                <a href="?class_id=<?php echo $class['id']; ?>&tab=students">View Students</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>You don't have any classes assigned yet.</p>
                <?php endif; ?>
            </div>

            
            <div id="students" class="tab-content">
                <h1 class="account-title">STUDENTS</h1>
                <div class="underline"></div>
                
                <div class="class-selector">
                    <form method="get" action="">
                        <input type="hidden" name="tab" value="students">
                        <select name="class_id" onchange="this.form.submit()">
                            <option value="">Select a class</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>" <?php echo (isset($_GET['class_id']) && $_GET['class_id'] == $class['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($class['class_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
                
                <?php if (isset($students)): ?>
                    <div class="profile-card">
                        <div class="profile-card-header">
                            <img src="/teacher-profile.jpg" alt="Teacher Profile" class="profile-card-picture" />
                            <div class="profile-card-info">
                                <h2>Student List</h2>
                                <p>Class: <?php echo htmlspecialchars($classes[array_search($_GET['class_id'], array_column($classes, 'id'))]['class_name']); ?></p>
                            </div>
                        </div>
                        
                        <?php if (!empty($students)): ?>
                            <div class="student-list">
                                <?php foreach ($students as $student): ?>
                                    <div class="student-card">
                                        <h3><?php echo htmlspecialchars($student['full_name']); ?></h3>
                                        <p>Student Number: <?php echo htmlspecialchars($student['student_number']); ?></p>
                                        <p>Grade Level: <?php echo htmlspecialchars($student['grade_level']); ?></p>
                                        <p>Section: <?php echo htmlspecialchars($student['section']); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p>No students enrolled in this class yet.</p>
                        <?php endif; ?>
                        
                        <div class="add-student-form">
                            <h3>Add Student to Class</h3>
                            <form method="post" action="">
                                <input type="hidden" name="class_id" value="<?php echo $_GET['class_id']; ?>">
                                <div class="detail-field">
                                    <label>Student ID</label>
                                    <input type="text" name="student_id" required>
                                </div>
                                <div class="detail-field">
                                    <input type="submit" name="add_student" value="Add Student">
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <p>Please select a class to view students.</p>
                <?php endif; ?>
            </div>

            
            <div id="account" class="tab-content">
                <h1 class="account-title">ACCOUNT</h1>
                <div class="underline"></div>

                <div class="profile-card">
                    <div class="profile-card-header">
                        <img src="/teacher-profile.jpg" alt="Teacher Profile" class="profile-card-picture" />
                        <div class="profile-card-info">
                            <h2><?php echo htmlspecialchars($teacher_data['full_name'] ?? $_SESSION['username']); ?></h2>
                            <p>Teacher Account</p>
                        </div>
                    </div>

                    <div class="profile-details">
                        <form method="post" action="">
                            <div class="detail-field">
                                <label>Full Name</label>
                                <input type="text" name="full_name" value="<?php echo htmlspecialchars($teacher_data['full_name'] ?? ''); ?>" required>
                            </div>

                            <div class="detail-field">
                                <label>Address</label>
                                <input type="text" name="address" value="<?php echo htmlspecialchars($teacher_data['address'] ?? ''); ?>" required>
                            </div>

                            <div class="detail-field">
                                <label>Contact No.</label>
                                <input type="text" name="contact_number" value="<?php echo htmlspecialchars($teacher_data['contact_number'] ?? ''); ?>" required>
                            </div>

                            <div class="detail-field">
                                <label>Email</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($teacher_data['email'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="detail-field">
                                <input type="submit" name="update_profile" value="Update Profile">
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="sidebar right-sidebar">
            <div class="notification-icon">
                <span class="bell-icon">🔔</span>
            </div>
        </div>

        
        <footer class="page-footer">
            <p>
                <a href="https://mogchs.edu.ph" class="footer-link">MOGCHS Website</a> | 
                © Copyright 2025 MOGCHS - CDO | Document Request System
            </p>
        </footer>
    </div>

    <script>
        function showTab(tabId) {
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(tab => {
                tab.classList.remove('active');
            });

            
            document.getElementById(tabId).classList.add('active');

            
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
            
            // Update URL without reloading
            history.pushState(null, null, `?tab=${tabId}`);
        }
        
        // Check URL for tab parameter on page load
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            
            if (tabParam) {
                showTab(tabParam);
                // Update active nav link
                const navLinks = document.querySelectorAll('.nav-link');
                navLinks.forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('onclick').includes(tabParam)) {
                        link.classList.add('active');
                    }
                });
            }
        });
    </script>
</body>
</html>