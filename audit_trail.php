<?php
session_start();

require 'dbcon.php';

// Make sure user is admin or redirect
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit;
}

// Fetch admin info for profile sidebar
try {
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching admin details: " . $e->getMessage());
}

// Fetch audit trail records, ordered newest first
$stmt = $pdo->prepare("
    SELECT a.*, 
           CASE 
               WHEN a.user_type = 'student' THEN CONCAT(s.first_name, ' ', s.last_name)
               WHEN a.user_type = 'admin' THEN CONCAT(adm.first_name, ' ', adm.last_name)
               ELSE 'System'
           END as user_name
    FROM audit_trail a
    LEFT JOIN student s ON a.user_id = s.id AND a.user_type = 'student'
    LEFT JOIN admin adm ON a.user_id = adm.id AND a.user_type = 'admin'
    ORDER BY a.timestamp DESC
");
$stmt->execute();
$auditTrail = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Audit Trail</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />

  <style>
    body {
      font-family: "Poppins", sans-serif;
      margin: 0;
      padding: 0;
      display: flex;
      background-color: #f4f6f9;
      color: #2c3e50;
      min-height: 100vh;
    }
    .sidebar {
      width: 250px;
      background-color: #34495e;
      height: 100vh;
      padding: 20px;
      display: flex;
      flex-direction: column;
      position: fixed;
      top: 0;
      left: 0;
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
      flex-grow: 1;
    }
    .sidebar ul li {
      padding: 15px;
      border-radius: 8px;
      transition: background 0.3s ease;
      color: #ecf0f1;
      cursor: pointer;
      margin-bottom: 5px;
    }
    .sidebar ul li:hover, .sidebar ul li.active {
      background-color: #2c3e50;
    }
    .sidebar ul li i {
      margin-right: 10px;
    }
    .sidebar ul li a {
      color: #ecf0f1;
      text-decoration: none;
      display: block;
    }
    .sidebar ul li a:hover {
      color: #fff;
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
      object-fit: cover;
    }
    #firstName {
      font-size: 1.2rem;
      font-weight: 600;
      color: #ecf0f1;
      margin-bottom: 10px;
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
      margin-left: 250px;
      padding: 30px;
      flex-grow: 1;
      background-color: #fff;
      min-height: 100vh;
      box-sizing: border-box;
    }
    .dashboard-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
    }
    .dashboard-header h2 {
      font-weight: 600;
    }
    #clock {
      font-size: 1rem;
      font-weight: 500;
      color: #7f8c8d;
    }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    th, td {
      padding: 12px 15px;
      border-bottom: 1px solid #ecf0f1;
      text-align: left;
      color: #2c3e50;
    }
    thead th {
      background-color: #34495e;
      color: #ecf0f1;
      text-transform: uppercase;
      font-weight: 600;
    }
    tbody tr:hover {
      background-color: #f1f4f8;
    }
    .user-type {
      text-transform: capitalize;
      font-weight: 600;
    }
    .user-type.admin {
      color: #007bff;
    }
    .user-type.student {
      color: #28a745;
    }
    .user-type.system {
      color: #6c757d;
    }
  </style>
</head>
<body>

  <div class="sidebar">
    <h2><i class="fas fa-user-shield"></i> ADMIN PANEL</h2>
    <ul>
      <li><a href="admin_dashboard.php"><i class="fas fa-home"></i> Home</a></li>
      <li><a href="admin_tasking.php"><i class="fas fa-tasks"></i> Task</a></li>
      <li><a href="#"><i class="fas fa-chart-line"></i> Analytics</a></li>
      <li class="active"><a href="audit_trail.php"><i class="fas fa-history"></i> Audit Trail</a></li>
       <li><a href="admin_view_submissions.php" style="text-decoration: none; color: inherit;">
      <i class="fas fa-clipboard-check"></i> View History
    </a></li>
    </ul>
    <div class="profile">
      <img src="<?= $admin['profile_image'] ? 'uploads/' . htmlspecialchars($admin['profile_image']) : 'profiles/default.jpg' ?>" alt="Profile Picture" />
      <p id="firstName"><?= htmlspecialchars($admin['first_name']) ?></p>
      <a href="logout.php" class="logout">Logout</a>
    </div>
  </div>

  <div class="main-content">
    <div class="dashboard-header">
      <h2>Audit Trail Logs</h2>
      <div id="clock"><i class="fas fa-clock"></i> <span id="time"></span></div>
    </div>

    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>User ID</th>
          <th>User Type</th>
          <th>Action</th>
          <th>Description</th>
          <th>Timestamp</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($auditTrail): ?>
          <?php $count = 1; foreach ($auditTrail as $log): ?>
            <tr>
              <td><?= $count++ ?></td>
              <td><?= htmlspecialchars($log['user_id']) ?></td>
              <td class="user-type <?= htmlspecialchars(strtolower($log['user_type'] ?? 'system')) ?>">
                <?= htmlspecialchars(ucfirst($log['user_type'] ?? 'System')) ?>
              </td>
              <td><?= htmlspecialchars($log['action']) ?></td>
              <td><?= htmlspecialchars($log['description']) ?></td>
              <td><?= htmlspecialchars($log['timestamp']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="6" class="text-center">No audit trail found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <script>
    function updateClock() {
      const now = new Date();
      const options = { hour: 'numeric', minute: 'numeric', second: 'numeric' };
      document.getElementById('time').textContent = now.toLocaleTimeString(undefined, options);
    }
    setInterval(updateClock, 1000);
    updateClock();
  </script>

</body>
</html>
