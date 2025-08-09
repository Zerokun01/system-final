<?php
session_start();
require 'dbcon.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit;
}

// Fetch admin info
$stmt = $pdo->prepare("SELECT * FROM admin WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Get filter parameters
$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build the query
$query = "
    SELECT t.*, s.first_name, s.last_name, s.course
    FROM tasks t
    JOIN student s ON t.assigned_student_id = s.id
    WHERE 1=1
";
$params = [];

if ($status === 'submitted') {
    $query .= " AND t.status = 'submitted'";
} elseif ($status === 'completed') {
    $query .= " AND t.status = 'completed'";
} elseif ($status === 'pending') {
    $query .= " AND t.status = 'assigned'";
}

if ($search) {
    $query .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR t.title LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

$query .= " ORDER BY t.deadline ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count statistics
$totalTasks = $pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
$submittedTasks = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'submitted'")->fetchColumn();
$completedTasks = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'completed'")->fetchColumn();
$pendingTasks = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'assigned'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Submissions - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: "Poppins", sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            background-color: #f4f6f9;
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
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            margin: 0;
            font-size: 2rem;
            color: #2c3e50;
        }
        .stat-card p {
            margin: 5px 0 0;
            color: #7f8c8d;
        }
        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .submission-table {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .badge {
            padding: 8px 12px;
            border-radius: 20px;
        }
        .badge-submitted {
            background-color: #3498db;
            color: white;
        }
        .badge-completed {
            background-color: #2ecc71;
            color: white;
        }
        .badge-pending {
            background-color: #f1c40f;
            color: black;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2><i class="fas fa-user-shield"></i> ADMIN PANEL</h2>
        <ul>
            <li><i class="fas fa-home"></i> <a href="admin_dashboard.php" style="text-decoration: none; color: inherit;">Home</a></li>
            <li><i class="fas fa-tasks"></i> <a href="admin_tasking.php" style="text-decoration: none; color: inherit;">Assign Tasks</a></li>
            <li class="active"><i class="fas fa-clipboard-check"></i> <a href="admin_view_submissions.php" style="text-decoration: none; color: inherit;">View Submissions</a></li>
        </ul>
    </div>

    <div class="main-content">
        <h2 class="mb-4">Task Submissions</h2>

        <div class="stats-cards">
            <div class="stat-card">
                <h3><?= $totalTasks ?></h3>
                <p>Total Tasks</p>
            </div>
            <div class="stat-card">
                <h3><?= $submittedTasks ?></h3>
                <p>Submitted Tasks</p>
            </div>
            <div class="stat-card">
                <h3><?= $completedTasks ?></h3>
                <p>Completed Tasks</p>
            </div>
            <div class="stat-card">
                <h3><?= $pendingTasks ?></h3>
                <p>Pending Tasks</p>
            </div>
        </div>

        <div class="filters">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search tasks..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-4">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All Tasks</option>
                        <option value="submitted" <?= $status === 'submitted' ? 'selected' : '' ?>>Submitted</option>
                        <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="admin_view_submissions.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>

        <div class="submission-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>Task Title</th>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Deadline</th>
                        <th>Status</th>
                        <th>Submission</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $task): ?>
                        <tr>
                            <td><?= htmlspecialchars($task['title']) ?></td>
                            <td><?= htmlspecialchars($task['first_name'] . ' ' . $task['last_name']) ?></td>
                            <td><?= htmlspecialchars($task['course']) ?></td>
                            <td><?= date('M d, Y', strtotime($task['deadline'])) ?></td>
                            <td>
                                <?php if ($task['status'] === 'submitted'): ?>
                                    <span class="badge badge-submitted">Submitted</span>
                                <?php elseif ($task['status'] === 'completed'): ?>
                                    <span class="badge badge-completed">Completed</span>
                                <?php else: ?>
                                    <span class="badge badge-pending">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($task['submission_link']): ?>
                                    <a href="<?= htmlspecialchars($task['submission_link']) ?>" target="_blank" class="btn btn-sm btn-info">
                                        <i class="fas fa-link"></i> View Link
                                    </a>
                                <?php endif; ?>
                                <?php if ($task['submission_file']): ?>
                                    <a href="<?= htmlspecialchars($task['submission_file']) ?>" target="_blank" class="btn btn-sm btn-info">
                                        <i class="fas fa-file"></i> View File
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($task['status'] === 'submitted'): ?>
                                    <form method="POST" action="mark_task_complete.php" style="display: inline;">
                                        <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Mark this task as completed?')">
                                            <i class="fas fa-check"></i> Mark Complete
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-primary" onclick="viewTaskDetails(<?= $task['id'] ?>)">
                                    <i class="fas fa-eye"></i> Details
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewTaskDetails(taskId) {
            // Implement task details view functionality
            window.location.href = `view_task_details.php?id=${taskId}`;
        }
    </script>
</body>
</html> 