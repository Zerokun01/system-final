<?php
session_start();
require 'dbcon.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];

    if ($role === 'student') {
        $stmt = $pdo->prepare("SELECT first_name, last_name, contact, profile_picture FROM student WHERE id = ?");
    } else {
        $stmt = $pdo->prepare("SELECT name, profile_picture FROM admin WHERE id = ?");
    }

    $stmt->execute([$user_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Profile not found'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching profile data'
    ]);
}
?> 