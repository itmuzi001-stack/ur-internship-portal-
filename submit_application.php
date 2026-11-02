<?php
// Enable error reporting for smooth debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/db.php';

if (!$pdo) {
    die("Database Connection Error: \$pdo is null. Check your config.php and .env files.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $intake_id  = $_POST['intake_id'] ?? null;
    $reg_number = trim($_POST['reg_number'] ?? '');
    $full_name  = trim($_POST['full_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $college    = trim($_POST['college'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $school_id  = $_POST['school_id'] ?? null;

    if ($intake_id && $reg_number && $full_name && $email && $college && $department && $phone && $school_id) {
        try {
            // Generate unique tracking serial number
            $tracking_number = 'UR-INT-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));

            $stmt = $pdo->prepare("INSERT INTO applications (intake_id, reg_number, full_name, email, college, department, phone, school_id, tracking_number, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$intake_id, $reg_number, $full_name, $email, $college, $department, $phone, $school_id, $tracking_number]);
            
            // Redirect to status page with the tracking number
            header("Location: status.php?submitted=success&tracking=" . urlencode($tracking_number));
            exit();
        } catch (PDOException $e) {
            die("Database SQL Error saving application: " . htmlspecialchars($e->getMessage()));
        }
    } else {
        die("Error: All form fields are required. Please go back and complete the application form.");
    }
} else {
    header("Location: apply.php");
    exit();
}