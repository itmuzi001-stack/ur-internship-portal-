<?php
// admin/dashboard.php
session_start();

// Database Connection Settings
$host = 'localhost';
$db   = 'ur_internships';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

$message = "";
$messageType = "success";

// 1. HANDLE CSV SCHOOL UPLOAD ACTION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['school_csv'])) {
    $fileTmpPath = $_FILES['school_csv']['tmp_name'];
    $fileName = $_FILES['school_csv']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if ($fileExtension === 'csv') {
        if (($handle = fopen($fileTmpPath, 'r')) !== FALSE) {
            fgetcsv($handle); // skip header row
            
            $stmt = $pdo->prepare("INSERT INTO placement_schools (school_code, school_name, district) 
                                   VALUES (?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE school_name = VALUES(school_name), district = VALUES(district)");

            $count = 0;
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($data) >= 3) {
                    $schoolCode = trim($data[0]);
                    $schoolName = trim($data[1]);
                    $district   = trim($data[2]);

                    if (!empty($schoolCode) && !empty($schoolName)) {
                        $stmt->execute([$schoolCode, $schoolName, $district]);
                        $count++;
                    }
                }
            }
            fclose($handle);
            $message = "Successfully uploaded and synchronized {$count} placement schools!";
            $messageType = "success";
        } else {
            $message = "Error: Unable to process the uploaded CSV file.";
            $messageType = "error";
        }
    } else {
        $message = "Error: Invalid file format. Please upload a valid .csv file.";
        $messageType = "error";
    }
}

// 2. HANDLE APPLICATION STATUS UPDATES (Approve / Reject)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if (in_array($action, ['approve', 'reject'])) {
        $newStatus = ($action === 'approve') ? 'Approved' : 'Rejected';
        $updateStmt = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?");
        $updateStmt->execute([$newStatus, $id]);
        
        header("Location: dashboard.php?msg=status_updated");
        exit();
    }
}

// 3. FETCH STATISTICAL METRICS
$totalApps = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
$pendingApps = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'Pending'")->fetchColumn();
$approvedApps = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'Approved'")->fetchColumn();
$rejectedApps = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'Rejected'")->fetchColumn();
$totalSchools = $pdo->query("SELECT COUNT(*) FROM placement_schools")->fetchColumn();

// 4. FETCH STUDENT APPLICATIONS (WITH OPTIONAL FILTER)
$filterStatus = $_GET['status_filter'] ?? 'ALL';
if (in_array($filterStatus, ['Pending', 'Approved', 'Rejected'])) {
    $stmt = $pdo->prepare("SELECT * FROM applications WHERE status = ? ORDER BY created_at DESC");
    $stmt->execute([$filterStatus]);
} else {
    $stmt = $pdo->query("SELECT * FROM applications ORDER BY created_at DESC");
}
$applications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - UR Internship Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: #F3F6FA; margin: 0; padding: 0; color: #0F1B2D; }
        
        /* Navigation & Header */
        header { background-color: #0F1B2D; color: #FFFFFF; padding: 1rem 2rem; border-bottom: 4px solid #E8A317; position: relative; }
        .nav-wrapper { max-width: 1240px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        header h1 { margin: 0; font-size: 1.35rem; color: #60A5FA; display: flex; align-items: center; gap: 0.5rem; }
        
        /* Desktop Links */
        .nav-links { display: flex; gap: 1.5rem; align-items: center; list-style: none; margin: 0; padding: 0; }
        .nav-links a { color: #CBD5E1; text-decoration: none; font-size: 0.9rem; font-weight: 500; transition: color 0.2s; }
        .nav-links a:hover { color: #E8A317; }
        
        /* Hamburger Button (Mobile) */
        .hamburger { display: none; background: none; border: none; color: #FFFFFF; font-size: 1.8rem; cursor: pointer; padding: 0; }
        
        /* Mobile Dropdown Menu */
        .mobile-menu { display: none; flex-direction: column; background: #12356B; padding: 1rem 2rem; gap: 1rem; border-bottom: 2px solid #E8A317; }
        .mobile-menu a { color: #FFFFFF; text-decoration: none; font-weight: 500; }
        
        .container { max-width: 1240px; margin: 2rem auto; padding: 0 1.5rem; }
        
        /* Alert Banners */
        .alert { padding: 0.85rem 1.15rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 500; font-size: 0.95rem; }
        .alert-success { background-color: #D1FAE5; color: #065F46; border: 1px solid #A7F3D0; }
        .alert-error { background-color: #FEE2E2; color: #991B1B; border: 1px solid #FCA5A5; }
        
        /* Stats Metrics Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.25rem; margin-bottom: 2rem; }
        .stat-card { background: #FFFFFF; padding: 1.25rem; border-radius: 10px; border: 1px solid #D9E0EA; box-shadow: 0 2px 6px rgba(0,0,0,0.03); }
        .stat-card .title { font-size: 0.85rem; color: #64748B; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-card .number { font-size: 1.8rem; font-weight: 700; color: #0F1B2D; margin-top: 0.4rem; }
        
        /* Cards & Tables */
        .card { background: #FFFFFF; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.04); padding: 1.75rem; border: 1px solid #D9E0EA; margin-bottom: 2rem; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem; }
        .card-title { margin: 0; color: #12356B; font-size: 1.2rem; font-weight: 700; }
        
        .upload-box { background: #F8FAFC; border: 2px dashed #CBD5E1; padding: 1.25rem; border-radius: 8px; text-align: center; }
        .form-inline { display: flex; gap: 1rem; align-items: center; justify-content: center; flex-wrap: wrap; }
        
        .btn { padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.88rem; font-weight: 600; text-decoration: none; cursor: pointer; border: none; display: inline-flex; align-items: center; gap: 0.3rem; transition: opacity 0.2s; }
        .btn:hover { opacity: 0.88; }
        .btn-gold { background-color: #E8A317; color: #0F1B2D; }
        .btn-approve { background-color: #059669; color: #FFFFFF; }
        .btn-reject { background-color: #DC2626; color: #FFFFFF; }
        
        .badge { padding: 0.35rem 0.75rem; border-radius: 50px; font-size: 0.8rem; font-weight: 600; display: inline-block; }
        .badge-pending { background-color: #FEF3C7; color: #D97706; }
        .badge-approved { background-color: #D1FAE5; color: #059669; }
        .badge-rejected { background-color: #FEE2E2; color: #DC2626; }

        table { width: 100%; border-collapse: collapse; margin-top: 0.5rem; font-size: 0.92rem; }
        th, td { padding: 0.85rem 1rem; text-align: left; border-bottom: 1px solid #E2E8F0; }
        th { background-color: #F8FAFC; color: #4A5668; font-weight: 600; }
        tr:hover { background-color: #F8FAFC; }

        /* Responsive Breakpoints */
        @media (max-width: 768px) {
            .nav-links { display: none; }
            .hamburger { display: block; }
            .table-container { overflow-x: auto; }
        }
    </style>
</head>
<body>

<header>
    <div class="nav-wrapper">
        <h1>🎓 UR Admin Portal</h1>
        
        <!-- Desktop Menu -->
        <ul class="nav-links">
            <li><a href="#csv-section">Sync Schools</a></li>
            <li><a href="#applications-section">Applications</a></li>
            <li><a href="../status.php" target="_blank">Verify Student Status ↗</a></li>
            <li><a href="../index.php">Student Portal</a></li>
        </ul>

        <!-- Mobile Hamburger Button -->
        <button class="hamburger" onclick="toggleMobileMenu()" aria-label="Toggle Navigation">
            ☰
        </button>
    </div>
</header>

<!-- Mobile Dropdown Navigation -->
<div id="mobileMenu" class="mobile-menu">
    <a href="#csv-section" onclick="toggleMobileMenu()">Sync Schools</a>
    <a href="#applications-section" onclick="toggleMobileMenu()">Applications</a>
    <a href="../status.php" target="_blank">Verify Student Status ↗</a>
    <a href="../index.php">Student Portal</a>
</div>

<div class="container">

    <!-- Global Alert Messages -->
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'status_updated'): ?>
        <div class="alert alert-success">Application status updated successfully! Connection verified.</div>
    <?php endif; ?>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Overview Statistics Counters -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="title">Total Submissions</div>
            <div class="number"><?= $totalApps ?></div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #D97706;">
            <div class="title">Pending Review</div>
            <div class="number" style="color: #D97706;"><?= $pendingApps ?></div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #059669;">
            <div class="title">Approved</div>
            <div class="number" style="color: #059669;"><?= $approvedApps ?></div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #DC2626;">
            <div class="title">Rejected</div>
            <div class="number" style="color: #DC2626;"><?= $rejectedApps ?></div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #12356B;">
            <div class="title">Uploaded Schools</div>
            <div class="number" style="color: #12356B;"><?= $totalSchools ?></div>
        </div>
    </div>

    <!-- Section 1: CSV School Placement Management -->
    <div class="card" id="csv-section">
        <div class="card-header">
            <h2 class="card-title">🏫 Synchronize Placement Schools Database</h2>
            <span style="font-size: 0.88rem; color: #64748B;">Upload <code>.csv</code> containing: <strong>school_code, school_name, district</strong></span>
        </div>
        
        <div class="upload-box">
            <form action="" method="POST" enctype="multipart/form-data" class="form-inline">
                <input type="file" name="school_csv" accept=".csv" required style="padding: 0.4rem; background: #FFFFFF; border: 1px solid #CBD5E1; border-radius: 6px;">
                <button type="submit" class="btn btn-gold">
                    ⬆️ Upload & Sync Schools
                </button>
            </form>
        </div>
    </div>

    <!-- Section 2: Student Placement Applications Table -->
    <div class="card" id="applications-section">
        <div class="card-header">
            <h2 class="card-title">📋 Student Placement Submissions</h2>
            
            <form action="" method="GET" style="display: flex; gap: 0.5rem; align-items: center;">
                <label for="status_filter" style="font-size: 0.88rem; font-weight: 600; color: #4A5668;">Filter Status:</label>
                <select name="status_filter" id="status_filter" onchange="this.form.submit()" style="padding: 0.4rem 0.75rem; border: 1px solid #CBD5E1; border-radius: 6px; font-size: 0.88rem;">
                    <option value="ALL" <?= $filterStatus === 'ALL' ? 'selected' : '' ?>>All Submissions</option>
                    <option value="Pending" <?= $filterStatus === 'Pending' ? 'selected' : '' ?>>Pending Only</option>
                    <option value="Approved" <?= $filterStatus === 'Approved' ? 'selected' : '' ?>>Approved Only</option>
                    <option value="Rejected" <?= $filterStatus === 'Rejected' ? 'selected' : '' ?>>Rejected Only</option>
                </select>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Tracking Code</th>
                        <th>Student Name</th>
                        <th>Student Reg ID</th>
                        <th>Program / Dept</th>
                        <th>Selected Placement School</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($applications) > 0): ?>
                        <?php foreach ($applications as $app): ?>
                            <tr>
                                <td><strong style="color: #12356B;"><?= htmlspecialchars($app['tracking_code']) ?></strong></td>
                                <td><?= htmlspecialchars($app['full_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($app['registration_number']) ?></td>
                                <td><?= htmlspecialchars($app['program']) ?></td>
                                <td><?= htmlspecialchars($app['placement_school']) ?></td>
                                <td>
                                    <?php 
                                        $statusClass = 'badge-pending';
                                        if ($app['status'] === 'Approved') $statusClass = 'badge-approved';
                                        if ($app['status'] === 'Rejected') $statusClass = 'badge-rejected';
                                    ?>
                                    <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($app['status'] ?? 'Pending') ?></span>
                                </td>
                                <td>
                                    <a href="dashboard.php?action=approve&id=<?= $app['id'] ?>" class="btn btn-approve" title="Approve Application">✓ Approve</a>
                                    <a href="dashboard.php?action=reject&id=<?= $app['id'] ?>" class="btn btn-reject" title="Reject Application">✕ Reject</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: #64748B; padding: 2.5rem;">
                                No student applications match the selected criteria.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
function toggleMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    menu.style.display = (menu.style.display === 'flex') ? 'none' : 'flex';
}
</script>

</body>
</html>