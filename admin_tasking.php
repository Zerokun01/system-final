<?php
session_start();

// Check if the user is logged in as an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit;
}

require 'dbcon.php';

// Fetch admin details
$stmt = $pdo->prepare("SELECT * FROM admin WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch students
$studentsStmt = $pdo->query("SELECT * FROM student");
$students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

/// Handle task assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $assigned_student_id = $_POST['student_id'];  // this stays the form field name
    $deadline = $_POST['deadline'];

    $taskStmt = $pdo->prepare("INSERT INTO tasks (title, description, assigned_student_id, deadline, status) VALUES (?, ?, ?, ?, 'assigned')");
    $taskStmt->execute([$title, $description, $assigned_student_id, $deadline]);
}

// Fetch tasks
$tasksStmt = $pdo->query("SELECT tasks.*, student.first_name, student.last_name FROM tasks JOIN student ON tasks.assigned_student_id = student.id");
$tasks = $tasksStmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Task Assignment</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    body {
      font-family: "Poppins", sans-serif;
      margin: 0;
      padding: 0;
      display: flex;
      background-color: #f4f6f9;
      color: #2c3e50;
    }
    .sidebar {
      width: 250px;
      background-color: #34495e;
      height: 100vh;
      padding: 20px;
      display: flex;
      flex-direction: column;
    }
    .sidebar h2 {
      text-align: center;
      color: #ecf0f1;
      font-weight: 600;
      margin-bottom: 40px;
    }
    .sidebar ul {
      list-style: none;
      padding: 0;
    }
    .sidebar ul li {
      padding: 15px;
      cursor: pointer;
      border-radius: 8px;
      transition: background 0.3s ease;
      color: #ecf0f1;
    }
    .sidebar ul li:hover {
      background-color: #2c3e50;
    }
    .sidebar ul li i {
      margin-right: 10px;
    }
    .profile {
      text-align: center;
      margin-top: auto;
    }
    .profile img {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      margin-bottom: 10px;
      border: 2px solid #bdc3c7;
    }
    #firstName {
      font-size: 1.2rem;
      font-weight: 600;
      color: #ecf0f1;
    }
    .logout {
      margin-top: 10px;
      padding: 10px;
      background-color: #e74c3c;
      border-radius: 5px;
      text-decoration: none;
      display: inline-block;
      color: #ffffff;
      transition: background 0.3s ease;
    }
    .logout:hover {
      background-color: #c0392b;
    }
    .main-content {
      flex: 1;
      padding: 20px;
      background-color: #ffffff;
    }
  </style>
</head>
<body>
  <div class="sidebar">
    <h2><i class="fas fa-user-shield"></i> ADMIN PANEL</h2>
    <ul>
      <li><i class="fas fa-home"></i> <a href="admin_dashboard.php" style="text-decoration: none; color: inherit;">Home</a></li>
      <li><i class="fas fa-tasks"></i> <a href="admin_task.php" style="text-decoration: none; color: inherit;">Task</a></li>
      <li><i class="fas fa-chart-line"></i> Analytics</li>
    </ul>
    <div class="profile">
      <img src="<?= $admin['profile_image'] ? 'uploads/' . htmlspecialchars($admin['profile_image']) : 'profiles/default.jpg' ?>" alt="Profile Picture"/>
      <p id="firstName"><?= htmlspecialchars($admin['first_name']) ?></p>
      <a href="logout.php" class="logout">Logout</a>
    </div>
  </div>
  <div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2>Assign Task</h2>
      <div><i class="fas fa-clock"></i> <span id="time"></span></div>
    </div>
    <form method="POST" class="border p-4 rounded bg-white shadow-sm">
      <div class="mb-3">
        <label for="title" class="form-label">Task Title</label>
        <input type="text" name="title" id="title" class="form-control" required>
      </div>
      <div class="mb-3">
        <label for="description" class="form-label">Description</label>
        <textarea name="description" id="description" class="form-control" rows="3" required></textarea>
      </div>
      <div class="mb-3">
        <label for="student_id" class="form-label">Assign to Student</label>
        <select name="student_id" id="student_id" class="form-select" required>
          <option value="" disabled selected>Select Student</option>
          <?php foreach ($students as $student): ?>
            <option value="<?= $student['id'] ?>">
              <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3">
        <label for="deadline" class="form-label">Deadline</label>
        <input type="date" name="deadline" id="deadline" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary">Assign Task</button>
    </form>

    <div class="mt-5">
      <h3>Task List</h3>
      <table class="table table-striped">
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
          <?php foreach ($tasks as $task): ?>
            <tr>
              <td><?= htmlspecialchars($task['title']) ?></td>
              <td><?= htmlspecialchars($task['description']) ?></td>
              <td><?= htmlspecialchars($task['first_name'] . ' ' . $task['last_name']) ?></td>
              <td><?= htmlspecialchars($task['deadline']) ?></td>
              <td><?= htmlspecialchars($task['status']) ?></td>
              <td>
                <a href="view_task.php?id=<?= $task['id'] ?>" class="text-info me-2"><i class="fas fa-eye"></i></a>
                <a href="edit_task.php?id=<?= $task['id'] ?>" class="text-warning me-2"><i class="fas fa-edit"></i></a>
                <a href="delete_task.php?id=<?= $task['id'] ?>" class="text-danger"><i class="fas fa-trash"></i></a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

<script>
  function updateClock() {
    document.getElementById("time").textContent = new Date().toLocaleString();
  }
  setInterval(updateClock, 1000);
  updateClock();
</script>
</body>
</html>
