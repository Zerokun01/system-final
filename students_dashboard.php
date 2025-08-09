<?php
session_start();
require 'dbcon.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.html");
    exit;
}

$pageTitle = "Student Dashboard";
require 'includes/header.php';

$studentId = $_SESSION['user_id'];

// Handle task submission POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_task'])) {
    $taskId = $_POST['task_id'];
    $link = trim($_POST['task_link'] ?? '');
    $uploadPath = '';
    $errors = [];

    if (isset($_FILES['task_file']) && $_FILES['task_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['task_file'];

        $allowed = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'image/jpeg',
            'image/png'
        ];

        if (!in_array($file['type'], $allowed)) {
            $errors[] = "Invalid file type. Allowed: PDF, DOCX, JPG, PNG.";
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = "File size must be under 5MB.";
        }

        if (empty($errors)) {
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = uniqid() . '_' . basename($file['name']);
            $uploadPath = 'uploads/' . $fileName;

            if (!move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
                $errors[] = "Failed to upload file.";
            }
        }
    }

    if (empty($link) && empty($uploadPath)) {
        $errors[] = "Please provide a file or a link.";
    }

    if (empty($errors)) {
        $updateSql = "UPDATE tasks SET status = 'submitted', submission_link = :link, submission_file = :file WHERE id = :taskId AND assigned_student_id = :studentId";
        $stmtUpdate = $pdo->prepare($updateSql);
        $stmtUpdate->execute([
            ':link' => $link ?: null,
            ':file' => $uploadPath ?: null,
            ':taskId' => $taskId,
            ':studentId' => $studentId,
        ]);
        header("Location: students_dashboard.php?msg=submitted");
        exit;
    } else {
        $errorMsg = implode(', ', $errors);
    }
}

// Fetch student info for sidebar (optional)
$stmt = $pdo->prepare("SELECT * FROM student WHERE id = :id");
$stmt->execute(['id' => $studentId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Filter status (default all)
$statusFilter = $_GET['status'] ?? 'all';

// Base query
$query = "SELECT * FROM tasks WHERE assigned_student_id = :studentId";
$params = [':studentId' => $studentId];

// Apply filter
if ($statusFilter === 'pending') {
    $query .= " AND status = 'assigned'";
} elseif ($statusFilter === 'submitted') {
    $query .= " AND status = 'submitted'";
} elseif ($statusFilter === 'overdue') {
    $query .= " AND status = 'assigned' AND deadline < CURDATE()";
}

$query .= " ORDER BY deadline ASC";

$stmtTasks = $pdo->prepare($query);
$stmtTasks->execute($params);
$tasks = $stmtTasks->fetchAll(PDO::FETCH_ASSOC);

// Dashboard overview counts
$countPending = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_student_id = :id AND status = 'assigned'");
$countPending->execute(['id' => $studentId]);
$pendingCount = $countPending->fetchColumn();

$countSubmitted = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_student_id = :id AND status = 'submitted'");
$countSubmitted->execute(['id' => $studentId]);
$submittedCount = $countSubmitted->fetchColumn();

$countOverdue = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_student_id = :id AND status = 'assigned' AND deadline < CURDATE()");
$countOverdue->execute(['id' => $studentId]);
$overdueCount = $countOverdue->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Student Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
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
    .sidebar ul li a {
      text-decoration: none;
      color: inherit;
      display: block;
    }
    .sidebar ul li i {
      margin-right: 10px;
    }
    .profile {
      text-align: center;
      margin-top: auto;
      padding-bottom: 20px;
    }
    .profile img {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      margin-bottom: 10px;
      border: 2px solid #bdc3c7;
      object-fit: cover;
      cursor: pointer;
      transition: transform 0.2s ease;
    }
    .profile img:hover {
      transform: scale(1.05);
      border-color: #3498db;
    }
    #firstName {
      font-size: 1.2rem;
      font-weight: 600;
      color: #ecf0f1;
      margin-bottom: 15px;
    }
    .logout-btn {
      display: block;
      width: 100%;
      padding: 10px;
      background-color: #e74c3c;
      border: none;
      border-radius: 5px;
      color: #ffffff;
      text-decoration: none;
      transition: background-color 0.3s ease;
      text-align: center;
      margin-top: 10px;
    }
    .logout-btn:hover {
      background-color: #c0392b;
      color: #ffffff;
      text-decoration: none;
    }
    .main-content {
      flex: 1;
      padding: 20px;
      background-color: #ffffff;
      display: flex;
      flex-direction: column;
    }
    .dashboard-overview {
      display: flex;
      justify-content: space-between;
      margin-bottom: 40px;
      align-items: center;
      position: relative;
      padding-bottom: 40px;
    }
    .overview-box {
      flex: 1;
      margin: 0 10px;
      padding: 20px;
      background-color: #e9ecef;
      border-radius: 10px;
      text-align: center;
      box-shadow: 0 0 6px rgba(0,0,0,0.1);
    }
    .overview-box h3 {
      margin-bottom: 10px;
    }
    .clock {
      font-size: 1.2rem;
      font-weight: 600;
      color: #34495e;
      position: absolute;
      bottom: -30px;
      right: 20px;
    }
  </style>
</head>
<body>
  <div class="sidebar">
    <h2><i class="fas fa-user-graduate"></i> STUDENT PANEL</h2>
    <ul>
      <li><i class="fas fa-tachometer-alt"></i> <a href="students_dashboard.php">Dashboard</a></li>
      <li><i class="fas fa-tasks"></i> <a href="students_tasks.php">My Tasks</a></li>
    </ul>
    <div class="profile">
      <img src="<?= $student['profile_image'] ? 'uploads/' . htmlspecialchars($student['profile_image']) : 'profiles/default.jpg' ?>" 
           alt="Profile Picture" 
           id="profileImage" 
           data-bs-toggle="modal" 
           data-bs-target="#editProfileModal"
           title="Click to edit profile" />
      <p id="firstName"><?= htmlspecialchars($student['first_name'] ?? 'Student') ?></p>
      <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </div>
  <div class="main-content">
    <div class="dashboard-overview">
      <div class="overview-box">
        <h3>Pending Tasks</h3>
        <p><?= $pendingCount ?></p>
      </div>
      <div class="overview-box">
        <h3>Submitted Tasks</h3>
        <p><?= $submittedCount ?></p>
      </div>
      <div class="overview-box">
        <h3>Overdue Tasks</h3>
        <p><?= $overdueCount ?></p>
      </div>
      <div class="clock" id="liveClock"></div>
    </div>

    <h4>My Tasks</h4>

    <div class="mb-3">
      <form method="GET" class="d-flex align-items-center" style="gap: 10px;">
        <label for="statusFilter" class="form-label mb-0">Filter:</label>
        <select id="statusFilter" name="status" class="form-select" style="width: 200px;" onchange="this.form.submit()">
          <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All</option>
          <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
          <option value="submitted" <?= $statusFilter === 'submitted' ? 'selected' : '' ?>>Submitted</option>
          <option value="overdue" <?= $statusFilter === 'overdue' ? 'selected' : '' ?>>Overdue</option>
        </select>
      </form>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'submitted'): ?>
      <div class="alert alert-success">Task successfully submitted!</div>
    <?php endif; ?>

    <?php if (!empty($errorMsg)): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <table class="table table-bordered table-hover">
      <thead>
        <tr>
          <th>Title</th>
          <th>Description</th>
          <th>Deadline</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($tasks as $task): ?>
          <tr>
            <td><?= htmlspecialchars($task['title']) ?></td>
            <td><?= htmlspecialchars($task['description']) ?></td>
            <td><?= htmlspecialchars($task['deadline']) ?></td>
            <td>
              <?php
                $status = $task['status'];
                if ($status === 'assigned') echo '<span class="badge bg-warning text-dark">Pending</span>';
                elseif ($status === 'submitted') echo '<span class="badge bg-info text-dark">Submitted</span>';
                elseif ($status === 'completed') echo '<span class="badge bg-success">Completed</span>';
              ?>
            </td>
            <td>
              <?php if ($status === 'assigned'): ?>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#submitModal" 
                        data-taskid="<?= $task['id'] ?>" data-tasktitle="<?= htmlspecialchars($task['title']) ?>">
                  Submit
                </button>
              <?php elseif ($status === 'submitted'): ?>
                <?php 
                  if (!empty($task['submission_link'])) {
                    echo '<a href="' . htmlspecialchars($task['submission_link']) . '" target="_blank" class="btn btn-info btn-sm">View Link</a> ';
                  }
                  if (!empty($task['submission_file'])) {
                    echo '<a href="' . htmlspecialchars($task['submission_file']) . '" target="_blank" class="btn btn-info btn-sm">View File</a>';
                  }
                ?>
              <?php else: ?>
                -
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Submit Task Modal -->
  <div class="modal fade" id="submitModal" tabindex="-1" aria-labelledby="submitModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <form method="POST" enctype="multipart/form-data" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="submitModalLabel">Submit Task</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="task_id" id="modal_task_id" />
          <div class="mb-3">
            <label for="task_file" class="form-label">Upload File (optional)</label>
            <input type="file" class="form-control" id="task_file" name="task_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" />
            <small class="form-text text-muted">Max size 5MB. Allowed types: PDF, DOCX, JPG, PNG.</small>
          </div>
          <div class="mb-3">
            <label for="task_link" class="form-label">Or Submit Link (optional)</label>
            <input type="url" class="form-control" id="task_link" name="task_link" placeholder="https://example.com/your-submission" />
            <small class="form-text text-muted">You can submit either a file or a link.</small>
          </div>
        </div>
        <div class="modal-footer">
          <span class="text-danger" id="modal_error_msg"><?= $errorMsg ?? '' ?></span>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="submit_task" class="btn btn-success">Submit</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Profile Modal -->
  <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <form method="POST" action="update_profile.php" enctype="multipart/form-data" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="edit_first_name" class="form-label">First Name</label>
            <input type="text" class="form-control" id="edit_first_name" name="first_name" value="<?= htmlspecialchars($student['first_name']) ?>" required>
          </div>
          <div class="mb-3">
            <label for="edit_last_name" class="form-label">Last Name</label>
            <input type="text" class="form-control" id="edit_last_name" name="last_name" value="<?= htmlspecialchars($student['last_name']) ?>" required>
          </div>
          <div class="mb-3">
            <label for="edit_email" class="form-label">Email</label>
            <input type="email" class="form-control" id="edit_email" name="email" value="<?= htmlspecialchars($student['email']) ?>" required>
          </div>
          <div class="mb-3">
            <label for="edit_gender" class="form-label">Gender</label>
            <select class="form-select" id="edit_gender" name="gender" required>
              <option value="Male" <?= $student['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
              <option value="Female" <?= $student['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="edit_course" class="form-label">Course</label>
            <input type="text" class="form-control" id="edit_course" name="course" value="<?= htmlspecialchars($student['course']) ?>" required>
          </div>
          <div class="mb-3">
            <label for="edit_address" class="form-label">Address</label>
            <textarea class="form-control" id="edit_address" name="address" rows="2" required><?= htmlspecialchars($student['address']) ?></textarea>
          </div>
          <div class="mb-3">
            <label for="edit_birthdate" class="form-label">Birthdate</label>
            <input type="date" class="form-control" id="edit_birthdate" name="birthdate" value="<?= htmlspecialchars($student['birthdate']) ?>" required>
          </div>
          <div class="mb-3">
            <label for="edit_profile_image" class="form-label">Profile Picture</label>
            <input type="file" class="form-control" id="edit_profile_image" name="profile_image" accept="image/*">
            <small class="form-text text-muted">Leave empty to keep current picture</small>
          </div>
          <div class="mb-3">
            <label for="edit_password" class="form-label">New Password</label>
            <input type="password" class="form-control" id="edit_password" name="password" minlength="6">
            <small class="form-text text-muted">Leave empty to keep current password</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Live Clock
    function updateClock() {
      const clock = document.getElementById('liveClock');
      const now = new Date();
      clock.textContent = now.toLocaleTimeString();
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Pass task data to modal
    var submitModal = document.getElementById('submitModal')
    submitModal.addEventListener('show.bs.modal', function (event) {
      var button = event.relatedTarget
      var taskId = button.getAttribute('data-taskid')
      var taskTitle = button.getAttribute('data-tasktitle')

      var modalTitle = submitModal.querySelector('.modal-title')
      var modalTaskIdInput = submitModal.querySelector('#modal_task_id')

      modalTitle.textContent = 'Submit Task: ' + taskTitle
      modalTaskIdInput.value = taskId

      submitModal.querySelector('#task_file').value = ''
      submitModal.querySelector('#task_link').value = ''
      submitModal.querySelector('#modal_error_msg').textContent = ''
    })

    // Show success message if profile was updated
    <?php if (isset($_GET['profile_updated']) && $_GET['profile_updated'] === 'success'): ?>
    document.addEventListener('DOMContentLoaded', function() {
      const toast = new bootstrap.Toast(document.createElement('div'));
      toast.innerHTML = `
        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
          <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
              <strong class="me-auto">Success</strong>
              <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
              Profile updated successfully!
            </div>
          </div>
        </div>
      `;
      document.body.appendChild(toast);
      setTimeout(() => toast.remove(), 3000);
    });
    <?php endif; ?>
  </script>
</body>
</html>
