<?php
require 'dbcon.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        student_id INT NOT NULL,
        deadline DATETIME NOT NULL,
        status ENUM('pending', 'completed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES student(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql);
    echo "Tasks table created successfully!";
} catch (PDOException $e) {
    die("Error creating tasks table: " . $e->getMessage());
}
?> 