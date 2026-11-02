<?php
// admin/schools.php
session_start();
$pageTitle = "Admin: Import Internship Host Schools";

require_once __DIR__ . '/../includes/db.php';

// Security Check: Enforce Admin Role Access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$msg = '';
$errorMsg = '';

// Handle Single School Deletion using Prepared Statements
if (isset($_GET['delete_id'])) {
    $deleteId = intval($_GET['delete_id']);
    try {
        $delStmt = $pdo->prepare("DELETE FROM schools WHERE id = ?");
        $delStmt->execute([$deleteId]);
        header("Location: schools.php");
        exit();
    } catch (PDOException $e) {
        $errorMsg = "Error deleting school: " . $e->getMessage();
    }
}

// Handle CSV File Upload Import (With Auto-Truncate for Fresh Lists)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_excel'])) {
    if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath   = $_FILES['excel_file']['tmp_name'];
        $fileName      = $_FILES['excel_file']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Strict extension check for CSV / TXT
        if (in_array($fileExtension, ['csv', 'txt'], true)) {
            $addedCount = 0;
            if (($handle = fopen($fileTmpPath, "r")) !== FALSE) {
                try {
                    // Wipe old host schools list for a fresh import
                    $pdo->exec("TRUNCATE TABLE schools");

                    // Parameterized statement preventing SQL injection
                    $stmt = $pdo->prepare("
                        INSERT INTO schools (school_name, province, district, cell, village, is_active) 
                        VALUES (?, ?, ?, ?, ?, 1)
                    ");
                    
                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        // Skip header row if present
                        if (count($data) >= 5 && !str_contains(strtolower($data[0]), 'school') && !str_contains(strtolower($data[0]), 'name')) {
                            $sName    = trim($data[0]);
                            $sProv    = trim($data[1]);
                            $sDist    = trim($data[2]);
                            $sCell    = trim($data[3]);
                            $sVillage = trim($data[4]);

                            if (!empty($sName) && !empty($sProv) && !empty($sDist)) {
                                try {
                                    $stmt->execute([$sName, $sProv, $sDist, $sCell, $sVillage]);
                                    $addedCount++;
                                } catch (PDOException $e) {
                                    // Skip individual row error
                                }
                            }
                        }
                    }
                    fclose($handle);
                    $msg = "Successfully cleared old data and imported {$addedCount} host schools into the database!";
                } catch (PDOException $e) {
                    $errorMsg = "Database truncation or import error: " . $e->getMessage();
                }
            } else {
                $errorMsg = "Could not open or read the uploaded file.";
            }
        } else {
            $errorMsg = "Please upload a valid CSV file. (In Excel, select Save As ➔ CSV UTF-8).";
        }
    } else {
        $errorMsg = "Please select a valid file to upload.";
    }
}

// Fetch host schools list
$schools = [];
if ($pdo) {
    try {
        $schools = $pdo->query("SELECT * FROM schools ORDER BY school_name ASC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $errorMsg = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <style>
        :root {
            --bg: #f8fafc;
            --white: #ffffff;
            --ink: #0f172a;
            --muted: #64748b;
            --blue: #1e3a8a;
            --amber: #f59e0b;
            --line: #e2e8f0;
            --green-bg: #d1fae5;
            --green: #065f46;
            --red-bg: #fee2e2;
            --red: #991b1b;
        }
        body { font-family: system-ui, -apple-system, sans-serif; background-color: var(--bg); color: var(--ink); margin: 0; padding: 0; }
        .dash-header { background-color: #0F1B2D; color: #FFFFFF; padding: 1rem 2rem; border-bottom: 4px solid var(--amber); }
        .dash-nav-wrapper { max-width: 950px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .dash-header h1 { margin: 0; font-size: 1.25rem; color: #60A5FA; }
        .dash-nav-links { display: flex; gap: 1.5rem; align-items: center; list-style: none; margin: 0; padding: 0; }
        .dash-nav-links a { color: #CBD5E1; text-decoration: none; font-size: 0.9rem; font-weight: 500; }
        .btn-logout { background-color: #DC2626 !important; color: #FFFFFF !important; padding: 0.4rem 0.9rem; border-radius: 4px; }
        .container { max-width: 950px; margin: 0 auto; padding: 3rem 1rem; min-height: 100vh; box-sizing: border-box; }
        .section-header { text-align: center; margin-bottom: 2rem; }
        .section-header h2 { color: var(--blue); margin-bottom: 0.5rem; }
        .alert-success { padding: 1.25rem; background: var(--green-bg); color: var(--green); border-radius: 6px; margin-bottom: 2rem; border: 1px solid #c3e6cb; text-align: center; font-weight: 600; }
        .alert-error { padding: 1.25rem; background: var(--red-bg); color: var(--red); border-radius: 6px; margin-bottom: 2rem; border: 1px solid #f5c2c7; text-align: center; }
        .card { background: var(--white); border: 1px solid var(--line); border-radius: 8px; padding: 2.5rem; box-shadow: 0 4px 12px rgba(15, 27, 45, 0.03); margin-bottom: 3rem; }
        .instructions { margin-bottom: 1.5rem; background: var(--bg); padding: 1.25rem; border-radius: 6px; border: 1px dashed var(--line); }
        .instructions h4 { color: var(--blue); margin-bottom: 0.5rem; font-size: 1rem; margin-top: 0; }
        .instructions p { font-size: 0.875rem; color: var(--muted); margin: 0; line-height: 1.6; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--ink); }
        .form-group input[type="file"] { width: 100%; padding: 0.75rem; border: 1px solid var(--line); border-radius: 4px; background: var(--bg); font-size: 0.95rem; box-sizing: border-box; }
        .btn-amber { background-color: var(--amber); color: var(--ink); border: none; font-weight: 700; cursor: pointer; border-radius: 4px; width: 100%; padding: 0.85rem; font-size: 1rem; transition: opacity 0.2s; }
        .btn-amber:hover { opacity: 0.9; }
        .table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .table-header h3 { color: var(--blue); margin: 0; }
        .table-wrapper { background: var(--white); border: 1px solid var(--line); border-radius: 8px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem; }
        th { background: var(--bg); border-bottom: 1px solid var(--line); padding: 0.75rem; color: var(--ink); }
        td { padding: 0.75rem; border-bottom: 1px solid var(--line); }
        td.muted { color: var(--muted); }
    </style>
</head>
<body>

<header class="dash-header">
    <div class="dash-nav-wrapper">
        <h1>🛠️ UR Admin Portal</h1>
        <ul class="dash-nav-links">
            <li><a href="dashboard.php">Applications Dashboard</a></li>
            <li><a href="schools.php">Upload Host Schools</a></li>
            <li><a href="../logout.php" class="btn-logout">Log Out</a></li>
        </ul>
    </div>
</header>

<main class="container">
    <div class="section-header">
        <h2>Admin: Import Internship Host Schools</h2>
        <p style="color: var(--muted);">Upload your official CSV spreadsheet to update available host secondary schools for student placement.</p>
    </div>

    <?php if ($msg): ?>
        <div class="alert-success">
            🎉 <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMsg): ?>
        <div class="alert-error">
            ⚠️ <?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="instructions">
            <h4>📋 Expected File CSV Format (5 Columns):</h4>
            <p>
                Ensure your Excel spreadsheet has columns in this exact order: <br>
                <code style="background: rgba(0,0,0,0.05); padding: 3px 8px; border-radius: 4px; font-weight: 600; color: var(--ink);">
                    School Name, Province, District, Cell, Village
                </code>
            </p>
            <p style="font-size: 0.8rem; margin-top: 0.5rem; color: #b91c1c;">
                <em>⚠️ Warning: Uploading a new CSV automatically replaces all currently saved placement schools.</em>
            </p>
        </div>

        <form method="POST" action="schools.php" enctype="multipart/form-data">
            <div class="form-group">
                <label for="excel_file">Select Spreadsheet File (.csv) *</label>
                <input type="file" name="excel_file" id="excel_file" accept=".csv" required>
            </div>

            <button type="submit" name="upload_excel" class="btn-amber">Upload and Process CSV Spreadsheet</button>
        </form>
    </div>

    <div class="table-header">
        <h3>Imported Host Schools</h3>
        <span style="font-size: 0.9rem; color: var(--muted);">Total Registered: <strong><?= count($schools) ?></strong></span>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>School Name</th>
                    <th>Province</th>
                    <th>District</th>
                    <th>Cell</th>
                    <th>Village</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($schools)): ?>
                    <?php foreach ($schools as $s): ?>
                        <tr>
                            <td class="muted"><?= $s['id'] ?></td>
                            <td><strong><?= htmlspecialchars($s['school_name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td><?= htmlspecialchars($s['province'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($s['district'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($s['cell'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($s['village'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="text-align: center;">
                                <a href="schools.php?delete_id=<?= $s['id'] ?>" 
                                   onclick="return confirm('Are you sure you want to remove this school?');" 
                                   style="background: #fee2e2; color: #991b1b; padding: 0.35rem 0.75rem; border-radius: 4px; text-decoration: none; font-size: 0.85rem; font-weight: 600;">
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="padding: 1.5rem; text-align: center; color: var(--muted);">No host schools uploaded yet. Upload your CSV file using the box above.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

</body>
</html>