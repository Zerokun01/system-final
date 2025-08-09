<?php
require 'dbcon.php';
require 'functions.php'; // Ensure this includes the logAuditTrail function
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
$password = trim($_POST['password']);

if (!$email || !$password) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit;
}

// Try student login (must be verified)
$stmt = $pdo->prepare("SELECT * FROM student WHERE email = ? AND is_verified = 1");
$stmt->execute([$email]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if ($student && password_verify($password, $student['password'])) {
    $_SESSION['user_id'] = $student['id'];
    $_SESSION['role'] = 'student';

    // Log successful student login
    logAuditTrail(
        $pdo,
        $student['id'],
        'student',
        'Login',
        "Student with ID {$student['id']} logged in."
    );

    echo json_encode([
        'success' => true,
        'message' => 'Student login successful',
        'user_id' => $student['id'],
        'redirect' => 'students_dashboard.php'
    ]);
    exit;
}

// Try admin login
$stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ?");
$stmt->execute([$email]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin && password_verify($password, $admin['password'])) {
    $_SESSION['user_id'] = $admin['id'];
    $_SESSION['role'] = 'admin';

    // Log successful admin login
    logAuditTrail(
        $pdo,
        $admin['id'],
        'admin',
        'Login',
        "Admin with ID {$admin['id']} logged in."
    );

    echo json_encode([
        'success' => true,
        'message' => 'Admin login successful',
        'user_id' => $admin['id'],
        'redirect' => 'admin_dashboard.php'
    ]);
    exit;
}

// Log failed login attempt
logAuditTrail(
    $pdo,
    null, // No user ID since it's a failed attempt
    'unknown', // Unknown user type
    'Failed Login',
    "Failed login attempt for email: {$email}"
);

echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
?>