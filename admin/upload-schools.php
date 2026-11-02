<?php
// admin/upload-schools.php
session_start();

$host = 'localhost';
$db   = 'ur_internship_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

$message = "";

// Handle CSV File Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['school_csv'])) {
    $fileTmpPath = $_FILES['school_csv']['tmp_name'];
    $fileName = $_FILES['school_csv']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if ($fileExtension === 'csv') {
        if (($handle = fopen($fileTmpPath, 'r')) !== FALSE) {
            // Skip header row if exists
            fgetcsv($handle);

            $stmt = $pdo->prepare("INSERT INTO placement_schools (school_code, school_name, district) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE school_name = VALUES(school_name), district = VALUES(district)");

            $count = 0;
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($data) >= 3) {
                    $schoolCode = trim($data[0]);
                    $schoolName = trim($data[1]);
                    $district   = trim($data[2]);

                    $stmt->execute([$schoolCode, $schoolName, $district]);
                    $count++;
                }
            }
            fclose($handle);
            $message = "Successfully imported/updated {$count} placement schools from CSV!";
        } else {
            $message = "Error: Could not open uploaded CSV file.";
        }
    } else {
        $message = "Error: Please upload a valid .csv file.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Placement Schools - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #F3F6FA; margin: 0; padding: 0; color: #0F1B2D; }
        header { background-color: #0F1B2D; color: #FFFFFF; padding: 1.5rem 2rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 4px solid #E8A317; }
        .container { max-width: 800px; margin: 2rem auto; padding: 0 1.5rem; }
        .card { background: #FFFFFF; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); padding: 2rem; border: 1px solid #D9E0EA; }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; font-weight: 600; margin-bottom: 0.5rem; color: #12356B; }
        input[type="file"] { border: 1px solid #D9E0EA; padding: 0.75rem; border-radius: 6px; width: 100%; background: #F8FAFC; }
        .btn { background-color: #E8A317; color: #0F1B2D; border: none; padding: 0.75rem 1.5rem; font-weight: 600; border-radius: 6px; cursor: pointer; }
        .alert { background-color: #D1FAE5; color: #065F46; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; font-weight: 500; }
    </style>
</head>
<body>

<header>
    <h1>🏫 Admin - Manage Placement Schools</h1>
    <a href="dashboard.php" style="color: #CBD5E1; text-decoration: none;">&larr; Back to Dashboard</a>
</header>

<div class="container">
    <div class="card">
        <h2 style="margin-top: 0; color: #12356B;">Upload Schools via CSV</h2>
        <p style="color: #64748B; font-size: 0.95rem;">Upload a CSV file containing columns: <code>School Code, School Name, District</code>.</p>

        <?php if (!empty($message)): ?>
            <div class="alert"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="school_csv">Select CSV File:</label>
                <input type="file" name="school_csv" id="school_csv" accept=".csv" required>
            </div>
            <button type="submit" class="btn">Upload & Sync Schools</button>
        </form>
    </div>
</div>

</body>
</html>