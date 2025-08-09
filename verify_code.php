<?php
require 'dbcon.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $code = trim($_POST['code']);

    if (!$email || !$code) {
        echo json_encode(['status' => 'error', 'message' => 'Missing email or code']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM student WHERE email = ? AND verification_code = ? AND is_verified = 0");
        $stmt->execute([$email, $code]);

        if ($stmt->rowCount() === 1) {
            $pdo->prepare("UPDATE student SET is_verified = 1, verification_code = NULL WHERE email = ?")
                ->execute([$email]);

            echo json_encode(['status' => 'success', 'message' => 'Email verified!', 'redirect' => 'login.html']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid email or verification code.']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
    }
}
