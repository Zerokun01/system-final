<?php
session_start();

// Check if the user is logged in as an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit;
}

require 'dbcon.php';

// Fetch all students for the dropdown
try {
    $studentsStmt = $pdo->query("SELECT id, first_name, last_name FROM student WHERE is_verified = 1");
    $students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching students: " . $e->getMessage());
}

// Fetch all tasks
try {
    $tasksStmt = $pdo->query("
        SELECT t.*, s.first_name, s.last_name 
        FROM tasks t 
        LEFT JOIN student s ON t.student_id = s.id 
        ORDER BY t.deadline ASC
    ");
    $tasks = $tasksStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching tasks: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Assignment</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
            display: flex;
        }

        .sidebar {
            width: 250px;
            background-color: #34495e;
            height: 100vh;
            padding: 20px;
            position: fixed;
        }

        .sidebar h2 {
            color: #ecf0f1;
            text-align: center;
            margin-bottom: 30px;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar ul li {
            padding: 15px;
            color: #ecf0f1;
            cursor: pointer;
            border-radius: 8px;
            margin-bottom: 5px;
        }

        .sidebar ul li:hover {
            background-color: #2c3e50;
        }

        .sidebar ul li i {
            margin-right: 10px;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 250px);
        }

        .task-form {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .task-list {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .status-pending {
            color: #e74c3c;
        }

        .status-completed {
            color: #27ae60;
        }
        
        .alert {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2><i class="fas fa-user-shield"></i> ADMIN PANEL</h2>
        <ul>
            <a href="admin_dashboard.php" style="text-decoration: none; color: inherit;">
                <li><i class="fas fa-home"></i> Home</li>
            </a>
            <a href="admin_assign_task.php" style="text-decoration: none; color: inherit;">
                <li class="active"><i class="fas fa-tasks"></i> Task</li>
            </a>
            <li><i class="fas fa-chart-line"></i> Analytics</li>
            
            <a href="audit_trail.php" style="text-decoration: none; color: inherit;">
                <li class="active"><i class="fas fa-history"></i>Audit Trail</li>
            </a>
        </ul>
    </div>

    <div class="main-content">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <div class="task-form">
            <h3 class="mb-4">Assign New Task</h3>
            <form action="process_task.php" method="POST">
                <div class="mb-3">
                    <label for="title" class="form-label">Task Title</label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="student" class="form-label">Select Student</label>
                    <select class="form-control" id="student" name="student_id" required>
                        <option value="">Choose a student...</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?= htmlspecialchars($student['id']) ?>">
                                <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="deadline" class="form-label">Deadline</label>
                    <input type="datetime-local" class="form-control" id="deadline" name="deadline" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Assign Task</button>
            </form>
        </div>

        <div class="task-list">
            <h3 class="mb-4">Task List</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Assigned To</th>
                        <th>Deadline</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tasks)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No tasks assigned yet</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tasks as $task): ?>
                            <tr>
                                <td><?= htmlspecialchars($task['title']) ?></td>
                                <td><?= htmlspecialchars($task['description']) ?></td>
                                <td><?= htmlspecialchars($task['first_name'] . ' ' . $task['last_name']) ?></td>
                                <td><?= date('Y-m-d H:i', strtotime($task['deadline'])) ?></td>
                                <td>
                                    <?php if ($task['status'] == 'completed'): ?>
                                        <span class="status-completed"><i class="fas fa-check-circle"></i> Completed</span>
                                    <?php else: ?>
                                        <span class="status-pending"><i class="fas fa-clock"></i> Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="edit_task.php?id=<?= $task['id'] ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete_task.php?id=<?= $task['id'] ?>" class="btn btn-sm btn-danger ms-1" onclick="return confirm('Are you sure you want to delete this task?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 