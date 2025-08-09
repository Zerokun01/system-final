<?php
session_start();

// Check if the user is logged in as an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit;
}

require 'dbcon.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $student_id = filter_var($_POST['student_id'], FILTER_VALIDATE_INT);
    $deadline = $_POST['deadline'];
    
    // Basic validation
    if (empty($title) || empty($description) || !$student_id || empty($deadline)) {
        $_SESSION['error'] = "All fields are required";
        header("Location: admin_assign_task.php");
        exit;
    }
    
    try {
        // Insert the task into the database
        $stmt = $pdo->prepare("
            INSERT INTO tasks (title, description, student_id, deadline, status, created_at) 
            VALUES (:title, :description, :student_id, :deadline, 'pending', NOW())
        ");
        
        $stmt->execute([
            'title' => $title,
            'description' => $description,
            'student_id' => $student_id,
            'deadline' => $deadline
        ]);
        
        $_SESSION['success'] = "Task assigned successfully!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Error assigning task: " . $e->getMessage();
    }
    
    header("Location: admin_assign_task.php");
    exit;
}

// If not POST request, redirect back to task page
header("Location: admin_assign_task.php");
exit; 