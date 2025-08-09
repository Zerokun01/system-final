<?php
session_start();
require 'dbcon.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id'])) {
    $taskId = filter_var($_POST['task_id'], FILTER_VALIDATE_INT);
    
    if ($taskId) {
        try {
            // Update task status to completed
            $stmt = $pdo->prepare("UPDATE tasks SET status = 'completed' WHERE id = ? AND status = 'submitted'");
            $stmt->execute([$taskId]);
            
            if ($stmt->rowCount() > 0) {
                $_SESSION['success'] = "Task marked as completed successfully!";
            } else {
                $_SESSION['error'] = "Task not found or already completed.";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Error updating task status: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Invalid task ID.";
    }
}

header("Location: admin_view_submissions.php");
exit; 