<?php
session_start();
require 'dbcon.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $code = trim($_POST['verification_code'] ?? '');

        if (!$email || empty($code)) {
            echo json_encode(['status' => 'error', 'message' => 'Email and verification code are required']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id FROM student WHERE email = ? AND verification_code = ? AND is_verified = 0");
        $stmt->execute([$email, $code]);

        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("UPDATE student SET is_verified = 1 WHERE email = ?");
            $stmt->execute([$email]);
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Email verified successfully!',
                'redirect' => 'login.php'
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid verification code or email']);
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Verification failed. Please try again later.']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title text-center mb-4">Email Verification</h3>
                        <form id="verificationForm">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="verification_code" class="form-label">Verification Code</label>
                                <input type="text" class="form-control" id="verification_code" name="verification_code" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Verify Email</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#verificationForm').on('submit', function(e) {
                e.preventDefault();
                
                $.ajax({
                    type: 'POST',
                    url: 'verify.php',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            alert(response.message);
                            if (response.redirect) {
                                window.location.href = response.redirect;
                            }
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                    }
                });
            });
        });
    </script>
</body>
</html>
