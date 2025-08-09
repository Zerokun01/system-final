<?php
session_start();

// Check if the user is logged in as a student
if (isset($_SESSION['student_id'])) {
    echo json_encode(['logged_in' => true, 'user_type' => 'student']);
    exit;
}

// Check if the user is logged in as an admin
if (isset($_SESSION['admin_id'])) {
    echo json_encode(['logged_in' => true, 'user_type' => 'admin']);
    exit;
}

// If no session found for student or admin
echo json_encode(['logged_in' => false]);
?>
