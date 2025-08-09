<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
require 'dbcon.php';

// Include PHPMailer
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Sanitize input data
        $first_name = htmlspecialchars(trim($_POST['first_name']));
        $last_name = htmlspecialchars(trim($_POST['last_name']));
        $gender = trim($_POST['gender']);
        $course = htmlspecialchars(trim($_POST['course']));
        $address = htmlspecialchars(trim($_POST['address']));
        $birthdate = $_POST['birthdate'];
        $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $profile_image = '';

        // Validate required fields
        if (empty($first_name) || empty($last_name) || empty($gender) || empty($course) ||
            empty($address) || empty($birthdate) || empty($email)) {
            echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
            exit;
        }

        if (!$email) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid email address']);
            exit;
        }

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM student WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Email already exists']);
            exit;
        }

        // Handle profile image upload
        if (!empty($_FILES['profileImage']['name'])) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $file_name = basename($_FILES["profileImage"]["name"]);
            $target_file = $target_dir . $file_name;

            $check = getimagesize($_FILES["profileImage"]["tmp_name"]);
            if ($check === false) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid image']);
                exit;
            }

            move_uploaded_file($_FILES["profileImage"]["tmp_name"], $target_file);
            $profile_image = $file_name;
        }

        // Generate a unique verification code
        $verification_code = rand(100000, 999999);

        // Insert user into the database
        $stmt = $pdo->prepare("INSERT INTO student 
            (first_name, last_name, gender, course, address, birthdate, email, password, profile_image, verification_code, is_verified) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");
        $stmt->execute([
            $first_name, $last_name, $gender, $course, $address, $birthdate,
            $email, $password, $profile_image, $verification_code
        ]);

        // Send verification email
        require 'vendor/autoload.php';
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'marychriscarcedo52@gmail.com'; // Replace with your Gmail account
            $mail->Password   = 'qsdt himx eugx yukh'; // Replace with your App Password
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

             $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ),
        );

            // Recipients
            $mail->setFrom('marychriscarcedo52@gmail.com', 'Registration System');
            $mail->addAddress($email); // User's email

            // Email content
            $mail->isHTML(true);
            $mail->Subject = 'Verify Your Email Address';
            $mail->Body    = "
                <h2>Welcome!</h2>
                <p>Your verification code is:</p>
                <h3>$verification_code</h3>
                <p>Please enter it at <a href='http://localhost/finals-carim/verify_email_manual.html'>this page</a>.</p>
            ";

            // Send the email
            $mail->send();
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to send verification email: ' . $mail->ErrorInfo]);
            exit;
        }

        // Return success response
        echo json_encode([
            'status' => 'success',
            'message' => 'Registered successfully! Check your email for the verification code.',
            'redirect' => 'verify_email_manual.html'
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>