<?php
require 'dbcon.php';
require 'vendor/autoload.php'; // Use Composer autoloader

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);

    if (!$email) {
        die(json_encode(['status' => 'error', 'message' => 'Invalid email address']));
    }

    try {
        // Check if student exists and is not verified
        $stmt = $pdo->prepare("SELECT id, first_name FROM student WHERE email = ? AND is_verified = 0");
        $stmt->execute([$email]);

        if ($stmt->rowCount() == 0) {
            echo json_encode(['status' => 'error', 'message' => 'No unverified account found with that email']);
            exit;
        }

        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        $first_name = $student['first_name'];

        // Generate new verification code
        $new_code = rand(100000, 999999);

        // Update database with new code
        $pdo->prepare("UPDATE student SET verification_code = ? WHERE email = ?")
           ->execute([$new_code, $email]);

        // Send new code via email
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'marychriscarcedo52@gmail.com'; // Replace with your Gmail
        $mail->Password   = 'hdje zfzu fqmh brmt'; // Use App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('marychriscarcedo52@gmail.com', 'Student Registration');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'New Verification Code';
        $mail->Body = "
            <h2>Hello, $first_name!</h2>
            <p>You requested a new verification code:</p>
            <h3 style='background:#f9f9f9;padding:10px;border-radius:5px;'>$new_code</h3>
            <p>Please enter this code in the verification page.</p>
        ";

        if (!$mail->send()) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to send verification code']);
            exit;
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'A new verification code has been sent to your email.'
        ]);

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
    }
}
?>