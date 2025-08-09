<?php
function logAuditTrail($pdo, $user_id, $user_type, $action, $description) {
    // Normalize user_type to lowercase and validate
    $valid_types = ['student', 'admin'];
    $user_type = strtolower(trim($user_type));

    if (!in_array($user_type, $valid_types)) {
        $user_type = 'unknown'; // fallback for invalid types
    }

    // Insert into audit trail
    $stmt = $pdo->prepare("
        INSERT INTO audit_trail (user_id, user_type, action, description)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$user_id, $user_type, $action, $description]);
}