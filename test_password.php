<?php
require 'dbcon.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test email - replace with an actual email from your database
$test_email = 'test@example.com';

echo "<h2>Password Hash Test</h2>";

// First, let's check if we can connect to the database
try {
    $pdo->query("SELECT 1");
    echo "Database connection successful!<br><br>";
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Check student table
echo "<h3>Checking student table:</h3>";
$stmt = $pdo->query("DESCRIBE student");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Table columns: " . implode(", ", $columns) . "<br><br>";

// Get a sample student record
$stmt = $pdo->query("SELECT * FROM student LIMIT 1");
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if ($student) {
    echo "Sample student record found:<br>";
    echo "Email: " . htmlspecialchars($student['email']) . "<br>";
    echo "Password hash length: " . strlen($student['password']) . "<br>";
    echo "Password hash: " . htmlspecialchars($student['password']) . "<br><br>";
    
    // Test password hashing
    $test_password = "password123";
    $hash = password_hash($test_password, PASSWORD_DEFAULT);
    echo "Test password hash:<br>";
    echo "Original password: " . $test_password . "<br>";
    echo "Generated hash: " . $hash . "<br>";
    echo "Hash length: " . strlen($hash) . "<br>";
    echo "Verification test: " . (password_verify($test_password, $hash) ? "PASSED" : "FAILED") . "<br><br>";
} else {
    echo "No student records found<br><br>";
}

// Check admin table
echo "<h3>Checking admin table:</h3>";
$stmt = $pdo->query("DESCRIBE admin");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Table columns: " . implode(", ", $columns) . "<br><br>";

// Get a sample admin record
$stmt = $pdo->query("SELECT * FROM admin LIMIT 1");
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin) {
    echo "Sample admin record found:<br>";
    echo "Email: " . htmlspecialchars($admin['email']) . "<br>";
    echo "Password hash length: " . strlen($admin['password']) . "<br>";
    echo "Password hash: " . htmlspecialchars($admin['password']) . "<br><br>";
} else {
    echo "No admin records found<br><br>";
}

// Test password_verify function
echo "<h3>Testing password_verify function:</h3>";
$test_cases = [
    '$2y$10$' => 'Valid bcrypt hash',
    '$2a$10$' => 'Old bcrypt hash',
    '$1$' => 'MD5 hash',
    '' => 'No hash prefix'
];

foreach ($test_cases as $prefix => $desc) {
    echo "Testing $desc: ";
    $test_hash = $prefix . str_repeat('x', 50);
    echo password_verify('test', $test_hash) ? "Works" : "Fails";
    echo "<br>";
}
?> 