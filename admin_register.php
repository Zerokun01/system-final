<?php
require 'dbcon.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Debugging: Log received POST and FILES data
        error_log(print_r($_POST, true));
        error_log(print_r($_FILES, true));

        // Retrieve POST data
        $first_name = htmlspecialchars(trim($_POST['first_name']));
        $last_name = htmlspecialchars(trim($_POST['last_name']));
        $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
        $password = password_hash($_POST['admin_password'], PASSWORD_DEFAULT); // Use 'admin_password'

        // Validate email
        if (!$email) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid email address']);
            exit;
        }

        // Handle profile image upload
        $profile_image = '';
        if ($_FILES['profile_image']['tmp_name']) { // Use 'profile_image'
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true); // Create the uploads directory if it doesn't exist
            }
            $target_file = $target_dir . basename($_FILES['profile_image']['name']); // Use 'profile_image'
            move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file); // Use 'profile_image'
            $profile_image = basename($_FILES['profile_image']['name']); // Use 'profile_image'
        }

        // Insert user data into the database
        $stmt = $pdo->prepare("INSERT INTO admin (first_name, last_name, email, password, profile_image) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$first_name, $last_name, $email, $password, $profile_image]);

        // Return success response
        echo json_encode(['status' => 'success', 'message' => 'Registration successful!']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error registering user: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>