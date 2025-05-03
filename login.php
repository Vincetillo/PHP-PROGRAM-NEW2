<?php
session_start();
include 'db.php';


$database = new Database();
$conn = $database->connect();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];


    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

          
            if (hash('sha256', $password) === $user['password']) {
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                
                switch ($user['role']) {
                    case 'student':
                        header("Location: student.php");
                        exit;
                    case 'teacher':
                        header("Location: teacher.php");
                        exit;
                    case 'registrar':
                        header("Location: registrar.php");
                        exit;
                    default:
                        echo "Unknown role.";
                }
            } else {
                echo "Incorrect password.";
            }
        } else {
            echo "No user found.";
        }
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}


if (isset($conn) && $conn !== null) {
    $conn->close();
}
?>
