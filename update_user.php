<?php
require 'dbcon.php';

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    $userId = $_POST['user_id'];
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $gender = $_POST['gender'];
    $address = trim($_POST['address']);
    $birthdate = $_POST['birthdate'];
    $course = trim($_POST['course']);

    // Fetch current profile image
    $stmt = $pdo->prepare("SELECT profile_image FROM students WHERE student_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $oldImage = $user['profile_image'] ?? null;

    // Prepare base query and parameters
    $query = "UPDATE students SET first_name=?, last_name=?, email=?, gender=?, user_address=?, birthdate=?, course=?";
    $params = [$firstName, $lastName, $email, $gender, $address, $birthdate, $course];

    // Handle profile image upload
    if (!empty($_FILES['profileImage']['name'])) {
        $uploadDir = "profiles/";
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $imageName = time() . "_" . basename($_FILES['profileImage']['name']);
        $uploadFile = $uploadDir . $imageName;

        if (!move_uploaded_file($_FILES['profileImage']['tmp_name'], $uploadFile)) {
            throw new Exception('Failed to upload profile image.');
        }

        // Delete old profile image
        if ($oldImage && file_exists($oldImage)) {
            unlink($oldImage);
        }

        $query .= ", profile_image=?";
        $params[] = $uploadFile;
    }

    $query .= " WHERE student_id=?";
    $params[] = $userId;

    // Execute the query
    $stmt = $pdo->prepare($query);
    if (!$stmt->execute($params)) {
        throw new Exception('Failed to update user.');
    }

    $response['status'] = 'success';
    $response['message'] = 'Profile updated successfully!';
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>