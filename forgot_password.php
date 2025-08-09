<?php
require 'dbcon.php';
require 'vendor/autoload.php';

 // Make sure this defines $pdo

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';

    if (empty($email)) {
        die('<div class="alert alert-danger">Email is required.</div>');
    }

    // Generate token and expiry
    $token = bin2hex(random_bytes(50));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Check admin table
    $stmt = $pdo->prepare("SELECT id FROM admin WHERE email = ?");
    $stmt->execute([$email]);
    $role = '';
    $userId = null;

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();
        $userId = $user['id'];
        $role = 'admin';
    } else {
        // Check student table
        $stmt = $pdo->prepare("SELECT id FROM student WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            $userId = $user['id'];
            $role = 'student';
        }
    }

    if (!empty($role) && $userId) {
        // Save token using $pdo
        $update = $pdo->prepare("UPDATE {$role} SET reset_token = ?, reset_expires = ? WHERE id = ?");
        $update->execute([$token, $expires, $userId]);

        // Generate link
        $resetLink = "http://localhost/finals-carim/carim/reset_password.php?token=$token";

        // Send email
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'marychriscarcedo52@gmail.com';
            $mail->Password   = 'wbau zjbw tozf zbna'; // App Password (no spaces)
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Optional: Bypass SSL certificate verification (for testing only)
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            // Recipients
            $mail->setFrom('marychriscarcedo52@gmail.com', 'CARIM Support');
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';

            $mail->Body = '
                <html>
                <body style="font-family: Arial, sans-serif; line-height: 1.6;">
                    <h2>Password Reset Request</h2>
                    <p>Hello,</p>
                    <p>We received a request to reset your password. Click the button below to proceed:</p>
                    <p style="margin: 25px 0;">
                        <a href="'.$resetLink.'" 
                           style="background-color: #4CAF50; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; display: inline-block;">
                            Reset Password
                        </a>
                    </p>
                    <p>If you did not request a password reset, please ignore this email.</p>
                    <p>This link will expire in 1 hour.</p>
                    <p>Best regards,<br><strong>CARIM Support Team</strong></p>
                </body>
                </html>';

            $mail->AltBody = "Hello,\n\nWe received a request to reset your password. Click the link below to reset your password:\n\n$resetLink\n\nIf you didn't request a password reset, you can ignore this email.\n\nThis link will expire in 1 hour.\n\nBest regards,\nCARIM Support Team";

            $mail->send();
            echo "<div class='alert alert-success'>Password reset link has been sent to your email.</div>";
        } catch (Exception $e) {
            echo "<div class='alert alert-danger'>Message could not be sent. Mailer Error: {$mail->ErrorInfo}</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>Email not found in our records.</div>";
    }
} else {
    // Show simple email input form (fallback)
    ?>
   <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap @5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e6f7ff, #fffde7);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .card {
            width: 100%;
            max-width: 420px;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.6s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card-body {
            padding: 2rem;
        }

        .form-label {
            font-weight: 500;
        }

        .form-control {
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
        }

        .btn-primary {
            background-color: #0d6efd;
            border: none;
            border-radius: 0.5rem;
            padding: 0.75rem;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #0b5ed7;
        }

        .alert {
            border-radius: 0.5rem;
        }

        .text-link {
            text-decoration: underline;
            color: #0d6efd;
        }

        .text-link:hover {
            color: #0a58ca;
        }
    </style>
</head>
<body>

<div class="card">
    <div class="card-body">

        <h4 class="text-center mb-4">Forgot Password</h4>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php elseif (isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">Enter your email</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="you@example.com" required />
            </div>
            <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
        </form>

        <p class="text-center mt-3">
            <a href="login.html" class="text-link">← Back to Login</a>
        </p>

    </div>
</div>

</body>
</html>
    <?php
}
?>