<?php
session_start();
require 'dbcon.php';


// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.html");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        $studentId = $_SESSION['user_id'];
        
        // Collect form data
        $first_name = htmlspecialchars(trim($_POST['first_name']));
        $last_name = htmlspecialchars(trim($_POST['last_name']));
        $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
        $gender = trim($_POST['gender']);
        $course = htmlspecialchars(trim($_POST['course']));
        $address = htmlspecialchars(trim($_POST['address']));
        $birthdate = $_POST['birthdate'];
        $password = trim($_POST['password']);

        // Validate required fields
        if (!$first_name || !$last_name || !$email || !$gender || !$course || !$address || !$birthdate) {
            header("Location: students_dashboard.php?error=missing_fields");
            exit;
        }

        // Check if email exists for other students
        $stmt = $pdo->prepare("SELECT id FROM student WHERE email = ? AND id != ?");
        $stmt->execute([$email, $studentId]);
        if ($stmt->rowCount() > 0) {
            header("Location: students_dashboard.php?error=email_exists");
            exit;
        }

        // Start building the update query
        $updateFields = [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'gender' => $gender,
            'course' => $course,
            'address' => $address,
            'birthdate' => $birthdate
        ];
        

        

        // Handle profile image upload if provided
        if ($_FILES['profile_image']['tmp_name']) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($_FILES['profile_image']['type'], $allowed)) {
                header("Location: students_dashboard.php?error=invalid_image");
                exit;
            }

            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Generate unique filename
            $fileName = uniqid() . '_' . basename($_FILES['profile_image']['name']);
            $uploadPath = 'uploads/' . $fileName;

            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadDir . $fileName)) {
                $updateFields['profile_image'] = $fileName;

                // Delete old profile image if exists
                $stmt = $pdo->prepare("SELECT profile_image FROM student WHERE id = ?");
                $stmt->execute([$studentId]);
                $oldImage = $stmt->fetchColumn();
                if ($oldImage && file_exists($uploadDir . $oldImage)) {
                    unlink($uploadDir . $oldImage);
                }
            }
        }
        

        // Handle password update if provided
        if (!empty($password)) {
            $updateFields['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        // Build and execute update query
        $sql = "UPDATE student SET ";
        $updates = [];
        $params = [];
        foreach ($updateFields as $field => $value) {
            $updates[] = "$field = ?";
            $params[] = $value;
        }
        $sql .= implode(', ', $updates);
        $sql .= " WHERE id = ?";
        $params[] = $studentId;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        header("Location: students_dashboard.php?profile_updated=success");
        exit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
          header("Location: students_dashboard.php?profile_updated=success");
      exit;


    } catch (Exception $e) {
        header("Location: students_dashboard.php?error=update_failed");
        exit;
    }
    
} else {
    header("Location: students_dashboard.php");
    exit;

}

?> 