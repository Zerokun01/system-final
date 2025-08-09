<?php
session_start();

// Check if the user is logged in as an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit;
}

require 'dbcon.php';

if (isset($_GET['id'])) {
    $task_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    
    if ($task_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = :id");
            $stmt->execute(['id' => $task_id]);
            
            if ($stmt->rowCount() > 0) {
                $_SESSION['success'] = "Task deleted successfully!";
            } else {
                $_SESSION['error'] = "Task not found.";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Error deleting task: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Invalid task ID.";
    }
}

header("Location: admin_assign_task.php");
exit; 