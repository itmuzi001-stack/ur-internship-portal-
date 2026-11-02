<?php
// placement-letter.php
session_start();
require_once __DIR__ . '/includes/db.php';

// Security Check: Ensure student is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$application = null;

if ($pdo) {
    try {
        // Fetch application details and join with users table to get profile_image
        $stmt = $pdo->prepare("
            SELECT a.*, u.profile_image 
            FROM applications a 
            LEFT JOIN users u ON a.user_id = u.id 
            WHERE a.user_id = ? LIMIT 1
        ");
        $stmt->execute([$userId]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching application for letter: " . $e->getMessage());
    }
}

if (!$application) {
    die("No internship application found for your account. Please complete your application first.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Placement Letter - UR Internship Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #ffffff; color: #0F1B2D; margin: 0; padding: 2rem; line-height: 1.6; }
        .letter-container { max-width: 800px; margin: 0 auto; background: #ffffff; padding: 3rem; border: 1px solid #cbd5e1; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .letter-header { text-align: center; border-bottom: 3px solid #12356B; padding-bottom: 1.5rem; margin-bottom: 2rem; display: flex; align-items: center; justify-content: center; gap: 20px; }
        .letter-logo { width: 75px; height: 75px; object-fit: contain; }
        .header-text h2 { margin: 0; color: #12356B; font-size: 1.4rem; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; }
        .header-text p { margin: 2px 0; color: #64748B; font-size: 0.85rem; text-align: left; }
        .letter-meta { display: flex; justify-content: space-between; margin-bottom: 2rem; font-size: 0.95rem; font-weight: 500; background: #f8fafc; padding: 10px 15px; border-radius: 6px; border: 1px solid #e2e8f0; }
        .section-title { font-size: 1rem; color: #12356B; font-weight: 700; margin-top: 1.5rem; margin-bottom: 0.5rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .info-table td { padding: 8px 0; font-size: 0.95rem; }
        .info-table td.label { font-weight: 600; color: #475569; width: 35%; }
        .validity-badge { display: inline-block; background-color: #d1e7dd; color: #0f5132; padding: 4px 10px; border-radius: 4px; font-size: 0.85rem; font-weight: 700; border: 1px solid #badbcc; }
        .action-bar { text-align: center; margin-top: 2.5rem; }
        .btn-print { background-color: #12356B; color: #ffffff; border: none; padding: 0.75rem 2rem; font-size: 1rem; font-weight: 600; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; transition: background 0.2s; }
        .btn-print:hover { background-color: #0F1B2D; }
        .signature-section { margin-top: 3rem; display: flex; justify-content: space-between; align-items: flex-end; }
        .signature-box { text-align: center; width: 260px; }
        .signature-line { border-top: 1px solid #0F1B2D; margin-top: 10px; padding-top: 5px; font-weight: 600; }
        @media print {
            body { background: transparent; padding: 0; }
            .letter-container { border: none; box-shadow: none; padding: 0; }
            .action-bar { display: none; }
        }
    </style>
</head>
<body>

<div class="letter-container">
    <!-- Header with Official Logo -->
    <div class="letter-header">
        <?php if (file_exists(__DIR__ . '/logo.png')): ?>
            <img src="logo.png" alt="University of Rwanda Logo" class="letter-logo">
        <?php endif; ?>
        <div class="header-text">
            <h2>University of Rwanda</h2>
            <p>Office of the Director of Careers & Year 4 Internships</p>
            <p>Kigali, Rwanda | support.internship@ur.ac.rw</p>
        </div>
    </div>

    <!-- Letter Meta Info & Validity -->
    <div class="letter-meta">
        <div><strong>Tracking Ref:</strong> <?= htmlspecialchars($application['tracking_code']) ?></div>
        <div><strong>Document Status:</strong> <span class="validity-badge">✓ VALID & VERIFIED</span></div>
        <div><strong>Date Issued:</strong> <?= date('F d, Y') ?></div>
    </div>

    <p>To Whom It May Concern,</p>

    <p>This is to formally certify that the individual detailed below is a bonafide Year 4 student at the <strong>University of Rwanda</strong>. As part of our mandatory graduation requirements, the student has been officially approved and assigned to a host institution for their practical internship program.</p>

    <!-- 1. Student Profile & Names with Real Passport Photo -->
    <div class="section-title">1. Student Profile & Credentials</div>
    <div style="display: flex; gap: 20px; align-items: center; margin-bottom: 1.5rem; background: #f8fafc; padding: 1rem; border-radius: 6px; border: 1px solid #e2e8f0;">
        
        <!-- Passport Photo Box -->
        <div style="flex-shrink: 0; text-align: center;">
            <?php if (!empty($application['profile_image']) && file_exists(__DIR__ . '/' . $application['profile_image'])): ?>
                <img src="<?= htmlspecialchars($application['profile_image']) ?>" alt="Student Passport Photo" style="width: 90px; height: 110px; object-fit: cover; border: 2px solid #12356B; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <?php else: ?>
                <div style="width: 90px; height: 110px; background: #e2e8f0; border: 1px dashed #94a3b8; display: flex; align-items: center; justify-content: center; text-align: center; font-size: 0.7rem; color: #64748B; border-radius: 4px; padding: 5px;">
                    No Photo Uploaded
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Student Info Table -->
        <div style="flex-grow: 1;">
            <table class="info-table">
                <tr>
                    <td class="label">Full Name:</td>
                    <td><strong><?= htmlspecialchars($application['full_name']) ?></strong></td>
                </tr>
                <tr>
                    <td class="label">Registration Number:</td>
                    <td><?= htmlspecialchars($application['registration_number']) ?></td>
                </tr>
                <tr>
                    <td class="label">College:</td>
                    <td><?= htmlspecialchars($application['college']) ?></td>
                </tr>
                <tr>
                    <td class="label">Academic Program:</td>
                    <td><?= htmlspecialchars($application['combination']) ?></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- 2. Schools Chosen & Placement Details -->
    <div class="section-title">2. Host School Placement Assignment</div>
    <table class="info-table" style="margin-bottom: 1.5rem;">
        <tr>
            <td class="label">Assigned Host School:</td>
            <td><strong style="color: #12356B; font-size: 1.05rem;"><?= htmlspecialchars($application['placement_school']) ?></strong></td>
        </tr>
    </table>

    <!-- 3. Dates & Period -->
    <div class="section-title">3. Authorized Period & Validity</div>
    <table class="info-table" style="margin-bottom: 1.5rem;">
        <tr>
            <td class="label">Internship Term / Period:</td>
            <td>Academic Year 2026 / 2027 (Standard Year 4 Field Placement Cycle)</td>
        </tr>
        <tr>
            <td class="label">Document Validity:</td>
            <td>Valid throughout the duration of the approved academic internship window.</td>
        </tr>
    </table>

    <p>We kindly request the administration and staff of the host school to grant the student full cooperation, supervision, and access to learning resources required during this period.</p>

    <p>We deeply appreciate your continued partnership with the University of Rwanda.</p>

    <!-- 4. Signatory Authority -->
    <div class="signature-section">
        <div style="font-size: 0.85rem; color: #64748B;">
            <em>Note: This electronic document is generated securely<br>via the UR Internship Portal and requires no physical seal.</em>
        </div>
        <div class="signature-box">
            <!-- Digital Signature Check -->
            <?php 
                $sigFile = '';
                if (file_exists(__DIR__ . '/assets/signature.jpeg')) {
                    $sigFile = 'assets/signature.jpeg';
                } elseif (file_exists(__DIR__ . '/assets/signature.jpg')) {
                    $sigFile = 'assets/signature.jpg';
                } elseif (file_exists(__DIR__ . '/assets/signature.png')) {
                    $sigFile = 'assets/signature.png';
                }
            ?>

            <?php if ($sigFile): ?>
                <img src="<?= htmlspecialchars($sigFile) ?>" alt="Authorized Signature" style="max-height: 65px; max-width: 160px; object-fit: contain; display: block; margin: 0 auto 2px auto;">
            <?php else: ?>
                <div style="font-style: italic; color: #12356B; font-size: 0.8rem; font-weight: 600; margin-bottom: 5px;">[ Electronically Signed ]</div>
            <?php endif; ?>

            <div class="signature-line">Director of Internships</div>
            <div style="font-size: 0.85rem; color: #475569;">University of Rwanda</div>
        </div>
    </div>

    <!-- Print / Save as PDF Action Bar -->
    <div class="action-bar">
        <button onclick="window.print()" class="btn-print">🖨️ Print / Save as PDF</button>
        <br><br>
        <a href="dashboard.php" style="color: #64748B; text-decoration: none; font-size: 0.9rem;">← Back to Student Dashboard</a>
    </div>
</div>

</body>
</html>