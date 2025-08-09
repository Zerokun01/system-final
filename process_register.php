<?php
require 'dbcon.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Retrieve POST data
        $first_name = htmlspecialchars(trim($_POST['firstName'])); // Match the HTML field name
        $last_name = htmlspecialchars(trim($_POST['lastName']));    // Match the HTML field name
        $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'];
        $profile_image = '';

        // Validate inputs
        if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
            echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
            exit;
        }

        // Validate email
        if (!$email) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid email address']);
            exit;
        }

        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM admin WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Email already exists. Please use a different email address.']);
            exit;
        }

        // Handle profile image upload
        if ($_FILES['profileImage']['tmp_name']) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true); // Create the uploads directory if it doesn't exist
            }
            $target_file = $target_dir . basename($_FILES['profileImage']['name']);
            move_uploaded_file($_FILES['profileImage']['tmp_name'], $target_file);
            $profile_image = basename($_FILES['profileImage']['name']);
        }

        // Insert user data into the database
        $stmt = $pdo->prepare("INSERT INTO admin (first_name, last_name, email, password, profile_image) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$first_name, $last_name, $email, $hashed_password, $profile_image]);

        // Return success response
        echo json_encode(['status' => 'success', 'message' => 'Registration successful!']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error registering user: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>