<?php
require 'dbcon.php';
require 'functions.php';

session_start();

// Get user details from session
$user_id = $_SESSION['user_id'] ?? null;
$user_type = $_SESSION['role'] ?? 'unknown'; // Default to 'unknown' if role is missing

if ($user_id && $user_type) {
    // Log logout
    logAuditTrail(
        $pdo,
        $user_id,
        $user_type,
        'Logout',
        "User with ID {$user_id} logged out."
    );

    // Clear session data
    session_unset();
    session_destroy();

    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'No user logged in']);
}
session_destroy();
header("Location: login.html");
exit;
?>
?>