<?php
require 'dbcon.php';

try {
    // Add submission_link and submission_file columns
    $sql = "ALTER TABLE tasks 
            ADD COLUMN submission_link TEXT NULL AFTER deadline,
            ADD COLUMN submission_file VARCHAR(255) NULL AFTER submission_link,
            MODIFY COLUMN status ENUM('assigned', 'submitted', 'completed') DEFAULT 'assigned'";
    
    $pdo->exec($sql);
    echo "Tasks table updated successfully!";
} catch (PDOException $e) {
    die("Error updating tasks table: " . $e->getMessage());
}
?> 