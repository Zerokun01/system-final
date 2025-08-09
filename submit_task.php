<?php
require 'dbcon.php';

// Check if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $task_id = $_POST['task_id'] ?? null;
    $submission_link = $_POST['submission_link'] ?? null;

    if (!$task_id) {
        die("Task ID is required.");
    }

    // Initialize variables for file upload
    $upload_dir = 'uploads/submissions/';  // Make sure this folder exists and is writable
    $uploaded_file_path = null;

    // Handle file upload if file was provided
    if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['submission_file']['tmp_name'];
        $file_name = basename($_FILES['submission_file']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Optional: Validate file type, size, etc.
        $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'png', 'zip'];
        if (!in_array($file_ext, $allowed_extensions)) {
            die("Invalid file type. Allowed types: " . implode(", ", $allowed_extensions));
        }

        // Generate unique file name to prevent overwriting
        $new_file_name = uniqid('submission_', true) . '.' . $file_ext;
        $dest_path = $upload_dir . $new_file_name;

        if (!move_uploaded_file($file_tmp_path, $dest_path)) {
            die("Error moving uploaded file.");
        }

        $uploaded_file_path = $dest_path;
    }

    try {
        // Prepare SQL to update task submission info and status
        $sql = "UPDATE tasks SET submission_link = :submission_link, submission_file = :submission_file, status = 'submitted' WHERE id = :task_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':submission_link' => $submission_link,
            ':submission_file' => $uploaded_file_path,
            ':task_id' => $task_id
        ]);

        echo "Task submitted successfully!";
    } catch (PDOException $e) {
        die("Error submitting task: " . $e->getMessage());
    }

} else {
    die("Invalid request method.");
}
?>
