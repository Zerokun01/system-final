<?php
require 'dbcon.php';

header('Content-Type: application/json');

// Start session at the beginning
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

// Try logging in as student
$stmt = $pdo->prepare("SELECT * FROM student WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password'])) {
    if ($user['is_verified'] == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Email not verified']);
        exit;
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = 'student';

    echo json_encode([
        'status' => 'success',
        'user_type' => 'student',
        'message' => 'Login successful',
        'redirect' => 'students_dashboard.php'
    ]);
    exit;
}

// If not a student, try admin
$stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ?");
$stmt->execute([$email]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin && password_verify($password, $admin['password'])) {
    $_SESSION['user_id'] = $admin['id'];
    $_SESSION['role'] = 'admin';

    echo json_encode([
        'status' => 'success',
        'user_type' => 'admin',
        'message' => 'Admin login successful',
        'redirect' => 'admin_dashboard.php'
    ]);
    exit;
}

// If neither student nor admin
echo json_encode(['status' => 'error', 'message' => 'Invalid email or password']);
?>
