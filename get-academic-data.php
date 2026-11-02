<?php
// get-academic-data.php
require_once __DIR__ . '/includes/db.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'get_schools' && isset($_GET['college_id'])) {
    $stmt = $pdo->prepare("SELECT id, name FROM academic_schools WHERE college_id = ? ORDER BY name ASC");
    $stmt->execute([$_GET['college_id']]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit();
}

if ($action === 'get_departments' && isset($_GET['school_id'])) {
    $stmt = $pdo->prepare("SELECT id, name FROM departments WHERE school_id = ? ORDER BY name ASC");
    $stmt->execute([$_GET['school_id']]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit();
}

if ($action === 'get_combinations' && isset($_GET['department_id'])) {
    $stmt = $pdo->prepare("SELECT id, name FROM combinations WHERE department_id = ? ORDER BY name ASC");
    $stmt->execute([$_GET['department_id']]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit();
}

echo json_encode([]);