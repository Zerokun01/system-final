<?php
session_start();

 // Start the session

// Check if the user is logged in as an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit;
}

require 'dbcon.php';

// Fetch admin details from the database
try {
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching admin details: " . $e->getMessage());
}

// Fetch all students from the database
try {
    $studentsStmt = $pdo->query("SELECT * FROM student");
    $students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching students: " . $e->getMessage());
}

// Count verified students
$verifiedCount = 0;
foreach ($students as $student) {
    if (!empty($student['is_verified']) && $student['is_verified'] == 1) {
        $verifiedCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Dashboard</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>

  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"/>

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
    .dashboard-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .dashboard-header h2 {
      color: #2c3e50;
      font-weight: 600;
    }
    #clock {
      font-size: 1rem;
      font-weight: 500;
      color: #7f8c8d;
    }
    .dashboard-cards {
      display: flex;
      gap: 20px;
      margin-top: 20px;
      flex-wrap: wrap;
    }
    .card {
      flex: 1;
      min-width: 200px;
      padding: 20px;
      background-color: #ecf0f1;
      border-radius: 10px;
      border: 1px solid #bdc3c7;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      color: #2c3e50;
      display: flex;
      align-items: center;
      gap: 15px;
    }
    .card i {
      font-size: 28px;
      color: #2980b9;
    }
    .filter-box {
      margin-top: 30px;
    }
    .table-container {
      margin-top: 20px;
      background-color: #ffffff;
      padding: 20px;
      border-radius: 8px;
      border: 1px solid #ecf0f1;
    }
    .table-container h3 {
      margin-bottom: 15px;
      color: #2c3e50;
    }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    th,
    td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid #ecf0f1;
      color: #2c3e50;
    }
    th {
      background-color: #bdc3c7;
      color: #2c3e50;
    }
  </style>
</head>
<body>
  <div class="sidebar">
    <h2><i class="fas fa-user-shield"></i> ADMIN PANEL</h2>
    <ul>
      <li><i class="fas fa-home"></i> Home</li>
      <a href="admin_tasking.php" style="text-decoration: none; color: inherit;">
        <li><i class="fas fa-tasks"></i> Task</li>
      </a>
      <li><i class="fas fa-chart-line"></i> Analytics</li>
      <li><a href="audit_trail.php" style="text-decoration: none; color: inherit;">
      <i class="fas fa-history"></i> Audit Trail
    </a></li>
     <li><a href="admin_view_submissions.php" style="text-decoration: none; color: inherit;">
      <i class="fas fa-clipboard-check"></i> View History
    </a></li>
    </ul>
    <br>
    <div class="profile">
      <img src="<?= $admin['profile_image'] ? 'uploads/' . htmlspecialchars($admin['profile_image']) : 'profiles/default.jpg' ?>" alt="Profile Picture"/>
      <p id="firstName"><?= htmlspecialchars($admin['first_name']) ?></p>
      <a href="logout.php" class="logout">Logout</a>
    </div>
  </div>
  <div class="main-content">
    <div class="dashboard-header">
      <h2>DASHBORD OVERVIEW</h2>
      <div id="clock"><i class="fas fa-clock"></i> <span id="time"></span></div>
    </div>
    <div class="dashboard-cards">
      <div class="card">
        <i class="fas fa-users"></i>
        <div>
          <div>Verified Users</div>
          <div id="verifiedUsersCount"><?= $verifiedCount ?></div>
        </div>
      </div>
      <div class="card">
        <i class="fas fa-signal"></i>
        <div>
          <div>Active Sessions</div>
          <div>--</div>
        </div>
      </div>
      <div class="card">
        <i class="fas fa-heartbeat"></i>
        <div>
          <div>System Health</div>
          <div>Online</div>
        </div>
      </div>
    </div>
    

    <div class="filter-box">
      <input type="text" class="form-control" id="searchInput" placeholder="Search by name or course...">
    </div>

    <div class="table-container">
      <h3>Student List</h3>
      <table id="studentTable">
        <thead>
          <tr>
            <th>ID</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Email</th>
            <th>Course</th>
            <th>Address</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $student): ?>
            <tr>
              <td><?= htmlspecialchars($student['id']) ?></td>
              <td><?= htmlspecialchars($student['first_name']) ?></td>
              <td><?= htmlspecialchars($student['last_name']) ?></td>
              <td><?= htmlspecialchars($student['email']) ?></td>
              <td><?= htmlspecialchars($student['course']) ?></td>
              <td><?= htmlspecialchars($student['address']) ?></td>
              <td>
                <?php if (!empty($student['is_verified']) && $student['is_verified'] == 1): ?>
                  <span class="text-success">Verified</span>
                <?php else: ?>
                  <span class="text-danger">Not Verified</span>
                <?php endif; ?>
              </td>
              <td>
                <a href="edit_student.php?id=<?= $student['id'] ?>" class="text-primary"><i class="fas fa-edit"></i></a>
                <a href="delete_student.php?id=<?= $student['id'] ?>" class="text-danger ms-2"><i class="fas fa-trash"></i></a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <script>
    function updateClock() {
      const clock = document.getElementById('time');
      const now = new Date();
      clock.textContent = now.toLocaleString();
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Filter functionality
    const searchInput = document.getElementById("searchInput");
    searchInput.addEventListener("keyup", function () {
      const filter = searchInput.value.toLowerCase();
      const rows = document.querySelectorAll("#studentTable tbody tr");
      rows.forEach(row => {
        const name = row.cells[1].textContent.toLowerCase() + " " + row.cells[2].textContent.toLowerCase();
        const course = row.cells[4].textContent.toLowerCase();
        if (name.includes(filter) || course.includes(filter)) {
          row.style.display = "";
        } else {
          row.style.display = "none";
        }
      });
    });
  </script>
</body> 
</html>
