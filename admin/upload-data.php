<?php
// admin/upload-data.php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dataType = $_POST['data_type'] ?? '';
    
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === 0) {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");
        
        if ($handle !== FALSE) {
            fgetcsv($handle, 1000, ","); // Skip header row
            
            try {
                $pdo->beginTransaction();
                
                if ($dataType === 'schools') {
                    // Placement Schools CSV: School Name, District, Sector, Capacity
                    $stmt = $pdo->prepare("INSERT INTO placement_schools (school_name, district, sector, capacity) VALUES (?, ?, ?, ?)");
                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        if (!empty($data[0])) {
                            $stmt->execute([trim($data[0]), trim($data[1] ?? ''), trim($data[2] ?? ''), intval($data[3] ?? 10)]);
                        }
                    }
                    $message = "Placement schools uploaded successfully!";
                } 
                elseif ($dataType === 'academic_units') {
                    // Academic CSV: College Name, School Name, Department Name, Combination Name
                    $stmtCol = $pdo->prepare("INSERT INTO colleges (name) VALUES (?)");
                    $stmtSch = $pdo->prepare("INSERT INTO academic_schools (college_id, name) VALUES (?, ?)");
                    $stmtDept = $pdo->prepare("INSERT INTO departments (school_id, name) VALUES (?, ?)");
                    $stmtComb = $pdo->prepare("INSERT INTO combinations (department_id, name) VALUES (?, ?)");
                    
                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        $colName  = trim($data[0] ?? '');
                        $schName  = trim($data[1] ?? '');
                        $deptName = trim($data[2] ?? '');
                        $combName = trim($data[3] ?? '');
                        
                        if (!empty($colName)) {
                            // 1. College
                            $chkCol = $pdo->prepare("SELECT id FROM colleges WHERE name = ? LIMIT 1");
                            $chkCol->execute([$colName]);
                            $col = $chkCol->fetch();
                            $colId = $col ? $col['id'] : null;
                            if (!$colId) {
                                $stmtCol->execute([$colName]);
                                $colId = $pdo->lastInsertId();
                            }
                            
                            // 2. Academic School
                            if (!empty($schName) && $colId) {
                                $chkSch = $pdo->prepare("SELECT id FROM academic_schools WHERE college_id = ? AND name = ? LIMIT 1");
                                $chkSch->execute([$colId, $schName]);
                                $sch = $chkSch->fetch();
                                $schId = $sch ? $sch['id'] : null;
                                if (!$schId) {
                                    $stmtSch->execute([$colId, $schName]);
                                    $schId = $pdo->lastInsertId();
                                }
                                
                                // 3. Department
                                if (!empty($deptName) && $schId) {
                                    $chkDept = $pdo->prepare("SELECT id FROM departments WHERE school_id = ? AND name = ? LIMIT 1");
                                    $chkDept->execute([$schId, $deptName]);
                                    $dept = $chkDept->fetch();
                                    $deptId = $dept ? $dept['id'] : null;
                                    if (!$deptId) {
                                        $stmtDept->execute([$schId, $deptName]);
                                        $deptId = $pdo->lastInsertId();
                                    }
                                    
                                    // 4. Combination
                                    if (!empty($combName) && $deptId) {
                                        $stmtComb->execute([$deptId, $combName]);
                                    }
                                }
                            }
                        }
                    }
                    $message = "Academic hierarchy (College → School → Dept → Combination) uploaded successfully!";
                }
                
                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Upload failed: " . $e->getMessage();
            }
            fclose($handle);
        } else {
            $error = "Could not open uploaded file.";
        }
    } else {
        $error = "Please select a valid CSV file.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin CSV Upload - UR Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #F3F6FA; padding: 3rem; }
        .card { background: #fff; border-radius: 8px; padding: 2rem; max-width: 550px; margin: 0 auto; border: 1px solid #cbd5e1; box-shadow: 0 4px 6px rgba(0,0,0,0.03); }
        .btn { background: #12356B; color: #fff; padding: 0.75rem 1.5rem; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; width: 100%; }
        .form-group { margin-bottom: 1.25rem; }
        label { font-weight: 600; display: block; margin-bottom: 0.5rem; color: #1E293B; }
        select, input[type="file"] { width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
    </style>
</head>
<body>

<div class="card">
    <h2 style="color: #12356B; margin-top: 0;">📤 Admin CSV Data Upload</h2>
    
    <?php if ($message): ?>
        <div style="background: #d1e7dd; color: #0f5132; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.9rem;">✅ <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div style="background: #f8d7da; color: #842029; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.9rem;">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Select Data Category</label>
            <select name="data_type" required>
                <option value="academic_units">Academic Units (College → School → Dept → Combination)</option>
                <option value="schools">Placement Schools (School Name, District, Sector, Capacity)</option>
            </select>
        </div>

        <div class="form-group">
            <label>Upload CSV File (.csv)</label>
            <input type="file" name="csv_file" accept=".csv" required>
        </div>

        <button type="submit" class="btn">Process & Upload Data</button>
    </form>
    
    <p style="margin-top: 1.5rem; font-size: 0.85rem; text-align: center;">
        <a href="../dashboard.php" style="color: #64748B; text-decoration: none;">← Return to Dashboard</a>
    </p>
</div>

</body>
</html>