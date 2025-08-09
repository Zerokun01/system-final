<?php
require 'dbcon.php';
require 'vendor/autoload.php';
 // Make sure this defines $pdo
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

    if (!$email) {
        die("Invalid email.");
    }

    // Check both tables
    $tables = ['admin', 'student'];
    $found = false;
    $tableUsed = '';

    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SELECT * FROM `$table` WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $found = true;
            $tableUsed = $table;
            break;
        }
    }

    if (!$found) {
        die("Email not found.");
    }

    // Generate token and expiration
    $token = bin2hex(random_bytes(50));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Save token to DB
    $stmt = $pdo->prepare("UPDATE `$tableUsed` SET reset_token = ?, reset_expires = ? WHERE email = ?");
    $stmt->execute([$token, $expires, $email]);

    // Create reset link
    $resetLink =  "http://localhost/finals-carim/carim/reset_password.php?token=$token";

    // Send email using PHPMailer
    require 'vendor/autoload.php';
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'marychriscarcedo52@gmail.com';
        $mail->Password   = 'wbau zjbw tozf zbna';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Fix SSL certificate issue (temporarily)
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ),
        );

        $mail->setFrom('marychriscarcedo52@gmail.com', 'Your Site');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request';
        $mail->Body    = "Click the link below to reset your password:<br><a href='$resetLink'>$resetLink</a>";

        $mail->send();
        echo "<div class='alert alert-success'>Reset link sent to your email.</div>";
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>Mailer Error: " . $mail->ErrorInfo . "</div>";
    }
}
?>