<?php
session_start();
require 'dbcon.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $student_id = $_POST['student'] ?? '';
    $deadline = $_POST['deadline'] ?? '';

    if ($title && $description && $student_id && $deadline) {
        $stmt = $pdo->prepare("INSERT INTO tasks (title, description, assigned_student_id, deadline) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $description, $student_id, $deadline]);
        header("Location: admin_task.php?success=Task assigned successfully");
        exit;
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
