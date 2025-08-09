<?php
session_start();
require 'dbcon.php';
require 'vendor/autoload.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_token = $_POST['token'] ?? null;
    $password = $_POST['password'] ?? null;

    if (!$submitted_token || empty($password)) {
        $error = "Invalid request or password is empty.";
    } else {
        $tables = ['admin', 'student'];
        $tableUsed = null;

        foreach ($tables as $table) {
            $stmt = $pdo->prepare("SELECT * FROM `$table` WHERE `reset_token` = ? AND `reset_expires` > NOW()");
            $stmt->execute([$submitted_token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $tableUsed = $table;
                break;
            }
        }

        if ($tableUsed) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE `$tableUsed` SET 
                `password` = ?, 
                `reset_token` = NULL, 
                `reset_expires` = NULL 
                WHERE `reset_token` = ?");
            $stmt->execute([$hashedPassword, $submitted_token]);

            $success = "✅ Password updated successfully!";
        } else {
            $error = "❌ Invalid or expired token.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap @5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e0f7fa, #fffde7);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.6s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
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

        .form-label {
            font-weight: 500;
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

<div class="card p-4 w-100" style="max-width: 420px;">
    <h4 class="text-center mb-4">Reset Your Password</h4>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
        <p class="text-center"><a href="login.html" class="text-link">← Back to Login</a></p>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
        <p class="text-center"><a href="forgot_password.php" class="text-link">Try again</a></p>
    <?php else: ?>
        <form method="POST">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

            <div class="mb-3">
                <label for="password" class="form-label">New Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="Enter new password" required />
            </div>

            <button type="submit" class="btn btn-primary w-100">Reset Password</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>