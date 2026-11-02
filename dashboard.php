<?php
// dashboard.php
session_start();
$pageTitle = "Student Dashboard - UR Internship Portal";

require_once __DIR__ . '/includes/db.php';

// Security Check: Redirect to login if user session is not active
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$userData = null;
$existingApp = null;
$error = '';
$uploadSuccess = '';

// Handle Image Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_pic'])) {
    $file = $_FILES['profile_pic'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];
        
        if (in_array($ext, $allowed)) {
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileName = 'user_' . $userId . '_' . time() . '.' . $ext;
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $dbPath = 'uploads/' . $fileName;
                $updateStmt = $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                $updateStmt->execute([$dbPath, $userId]);
                $uploadSuccess = "Profile picture updated successfully!";
            } else {
                $error = "Failed to move uploaded file.";
            }
        } else {
            $error = "Only JPG, JPEG, and PNG files are allowed.";
        }
    } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
        $error = "Error uploading file. Code: " . $file['error'];
    }
}

if ($pdo) {
    try {
        // Fetch user profile details including profile_image
        $stmt = $pdo->prepare("SELECT registration_number, full_name, combination, phone, email, profile_image, created_at FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $userData = $stmt->fetch();

        if (!$userData) {
            session_destroy();
            header("Location: login.php");
            exit();
        }

        // Check if student already submitted an application
        $appStmt = $pdo->prepare("SELECT tracking_code, placement_school, status, created_at FROM applications WHERE user_id = ? OR registration_number = ? LIMIT 1");
        $appStmt->execute([$userId, $userData['registration_number']]);
        $existingApp = $appStmt->fetch();

    } catch (PDOException $e) {
        $error = "Could not load profile details.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: #F3F6FA; margin: 0; padding: 0; color: #0F1B2D; }
        
        .dash-header { background-color: #0F1B2D; color: #FFFFFF; padding: 1rem 2rem; border-bottom: 4px solid #E8A317; }
        .dash-nav-wrapper { max-width: 1100px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .dash-header h1 { margin: 0; font-size: 1.25rem; color: #60A5FA; }
        .dash-nav-links { display: flex; gap: 1.5rem; align-items: center; list-style: none; margin: 0; padding: 0; }
        .dash-nav-links a { color: #CBD5E1; text-decoration: none; font-size: 0.9rem; font-weight: 500; transition: color 0.2s; }
        .dash-nav-links a:hover { color: #E8A317; }
        .btn-logout { background-color: #DC2626 !important; color: #FFFFFF !important; padding: 0.4rem 0.9rem; border-radius: 4px; }

        .container { max-width: 1100px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 2rem; box-shadow: 0 2px 6px rgba(0,0,0,0.03); }
        .btn-amber { background-color: #E8A317; color: #0F1B2D; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 6px; font-weight: 600; display: inline-block; transition: background-color 0.2s; border: none; cursor: pointer; }
        .btn-amber:hover { background-color: #d19213; }
        .upload-form { margin-top: 10px; font-size: 0.85rem; }
    </style>
</head>
<body>

<header class="dash-header">
    <div class="dash-nav-wrapper">
        <h1>🎓 UR Student Portal</h1>
        <ul class="dash-nav-links">
            <li><a href="dashboard.php">Dashboard & Status</a></li>
            <li><a href="apply.php">Apply for Internship</a></li>
            <li><a href="logout.php" class="btn-logout">Log Out</a></li>
        </ul>
    </div>
</header>

<main class="container">
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <div>
                <h2 style="color: #12356B; margin: 0;">Student Status Dashboard</h2>
                <span style="background-color: #d1e7dd; color: #0f5132; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 600;">Verified Account</span>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div style="background-color: #f8d7da; color: #842029; padding: 0.75rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.9rem; border: 1px solid #f5c2c7;">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($uploadSuccess)): ?>
            <div style="background-color: #d1e7dd; color: #0f5132; padding: 0.75rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.9rem; border: 1px solid #badbcc;">
                ✅ <?= htmlspecialchars($uploadSuccess) ?>
            </div>
        <?php endif; ?>

        <?php if ($userData): ?>
            <!-- Student Profile Header with Real Picture Upload -->
            <div style="display: flex; align-items: center; gap: 2rem; margin-bottom: 2rem; background: #f8fafc; padding: 1.5rem; border-radius: 8px; border: 1px solid #e2e8f0; flex-wrap: wrap;">
                <div style="text-align: center;">
                    <?php if (!empty($userData['profile_image']) && file_exists(__DIR__ . '/' . $userData['profile_image'])): ?>
                        <img src="<?= htmlspecialchars($userData['profile_image']) ?>" alt="Profile Picture" style="width: 90px; height: 90px; border-radius: 50%; object-fit: cover; border: 2px solid #12356B; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <?php else: ?>
                        <?php 
                            $nameParts = explode(' ', trim($userData['full_name'] ?? 'Student'));
                            $initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
                        ?>
                        <div style="width: 90px; height: 90px; border-radius: 50%; background: #12356B; color: #E8A317; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; font-weight: bold; border: 2px solid #E8A317; margin: 0 auto;">
                            <?= $initials ?>
                        </div>
                    <?php endif; ?>

                    <!-- Upload Form -->
                    <form action="dashboard.php" method="POST" enctype="multipart/form-data" class="upload-form">
                        <input type="file" name="profile_pic" accept="image/png, image/jpeg" required style="font-size: 0.75rem; width: 140px; margin-bottom: 4px;"><br>
                        <button type="submit" style="background: #12356B; color: white; border: none; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; cursor: pointer;">Upload Photo</button>
                    </form>
                </div>

                <div style="flex-grow: 1;">
                    <h3 style="color: #12356B; margin: 0 0 4px 0; font-size: 1.25rem;">
                        Welcome, <?= htmlspecialchars($userData['full_name']) ?> 👋
                    </h3>
                    <p style="color: #64748B; font-size: 0.9rem; margin: 0;">
                        Upload your official passport photo above to have it embedded directly into your placement letter.
                    </p>
                </div>
            </div>

            <div style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; border: 1px solid #e2e8f0;">
                <h4 style="color: #12356B; font-size: 1rem; margin-bottom: 1rem; border-bottom: 1px solid #cbd5e1; padding-bottom: 0.5rem;">Student Info</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; font-size: 0.95rem;">
                    <div>
                        <strong style="color: #475569; display: block; font-size: 0.8rem; text-transform: uppercase;">Reg Number:</strong>
                        <?= htmlspecialchars($userData['registration_number']) ?>
                    </div>
                    <div>
                        <strong style="color: #475569; display: block; font-size: 0.8rem; text-transform: uppercase;">Email:</strong>
                        <?= htmlspecialchars($userData['email']) ?>
                    </div>
                    <div>
                        <strong style="color: #475569; display: block; font-size: 0.8rem; text-transform: uppercase;">Combination / Discipline:</strong>
                        <?= htmlspecialchars($userData['combination'] ?? 'N/A') ?>
                    </div>
                    <div>
                        <strong style="color: #475569; display: block; font-size: 0.8rem; text-transform: uppercase;">Phone:</strong>
                        <?= htmlspecialchars($userData['phone'] ?? 'N/A') ?>
                    </div>
                </div>
            </div>

            <div style="background: #f1f5f9; padding: 1.5rem; border-radius: 8px; text-align: center; border: 1px solid #cbd5e1;">
                <h4 style="color: #12356B; margin-bottom: 0.5rem;">Placement Application Status</h4>
                
                <?php if ($existingApp): ?>
                    <p style="color: #0f5132; font-weight: 600; margin-bottom: 0.5rem;">Application Submitted ✅</p>
                    <p style="color: #475569; font-size: 0.9rem; margin-bottom: 0.5rem;">
                        <strong>Tracking Code:</strong> <?= htmlspecialchars($existingApp['tracking_code']) ?>
                    </p>
                    <p style="color: #475569; font-size: 0.9rem; margin-bottom: 0.5rem;">
                        <strong>Selected School:</strong> <?= htmlspecialchars($existingApp['placement_school']) ?>
                    </p>
                    <p style="color: #475569; font-size: 0.9rem; margin-bottom: 0;">
                        <strong>Status:</strong> <span style="background: #cff4fc; color: #055160; padding: 2px 8px; border-radius: 4px; font-weight: 600;"><?= htmlspecialchars($existingApp['status'] ?? 'Pending') ?></span>
                    </p>
                <?php else: ?>
                    <p style="color: #475569; font-size: 0.9rem; margin-bottom: 1rem;">You have not submitted an internship placement application yet.</p>
                    <a href="apply.php" class="btn-amber">Apply Now</a>
                <?php endif; ?>
            </div>

            <!-- Official Placement Document Section -->
            <?php if ($existingApp): ?>
                <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 1.5rem; margin-top: 2rem;">
                    <h4 style="color: #12356B; margin-top: 0; margin-bottom: 0.5rem;">Official Internship Documents</h4>
                    <p style="color: #64748B; font-size: 0.95rem; margin-bottom: 1rem;">
                        Download your official electronic placement introduction letter containing your profile credentials, assigned school, authorized dates, and electronic verification.
                    </p>
                    
                    <a href="placement-letter.php" target="_blank" class="btn-amber">
                        📄 View & Download Placement Letter (PDF)
                    </a>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</main>

</body>
</html>