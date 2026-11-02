<?php
$pageTitle = "Application Status - UR Internship Portal";
$metaDescription = "Track your internship placement application status.";
$currentPage = 'status';

require_once __DIR__ . '/includes/db.php';

$tracking = trim($_GET['tracking'] ?? '');
$application = null;
$errorMsg = '';

if (!empty($tracking) && $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT a.*, s.school_name, s.district, s.province FROM applications a JOIN schools s ON a.school_id = s.id WHERE a.tracking_number = ?");
        $stmt->execute([$tracking]);
        $application = $stmt->fetch();
        
        if (!$application) {
            $errorMsg = "No application found with tracking number: " . htmlspecialchars($tracking);
        }
    } catch (PDOException $e) {
        $errorMsg = "Database error: " . $e->getMessage();
    }
}

include_once __DIR__ . '/includes/header.php';
?>

<main id="main-content">
    <section class="section-padding" style="background-color: var(--bg); min-height: 80vh;">
        <div class="container" style="max-width: 700px;">
            <div class="section-header" style="margin-bottom: 2rem;">
                <h2>Track Your Application</h2>
                <p>Enter your tracking serial number to check your internship placement status.</p>
            </div>

            <div class="intake-card" style="margin-bottom: 2rem;">
                <form method="GET" action="status.php" style="display: flex; gap: 1rem;">
                    <input type="text" name="tracking" value="<?= htmlspecialchars($tracking) ?>" required placeholder="e.g. UR-INT-2026-ABC123" style="flex: 1; padding: 0.75rem; border: 1px solid var(--line); border-radius: 4px; font-size: 1rem;">
                    <button type="submit" class="btn btn-amber">Check Status</button>
                </form>
            </div>

            <?php if (isset($_GET['submitted']) && $_GET['submitted'] === 'success' && !empty($tracking)): ?>
                <div style="background: var(--green-bg); color: var(--green); padding: 1.25rem; border-radius: 6px; margin-bottom: 2rem; text-align: center; border: 1px solid #c3e6cb; font-weight: 600;">
                    🎉 Application submitted successfully! Your tracking serial number is: <code style="background: #fff; padding: 2px 6px; border-radius: 4px; color: var(--ink);"><?= htmlspecialchars($tracking) ?></code>
                </div>
            <?php endif; ?>

            <?php if ($errorMsg): ?>
                <div style="background: var(--red-bg); color: var(--red); padding: 1.25rem; border-radius: 6px; margin-bottom: 2rem; text-align: center; border: 1px solid #f5c2c7;">
                    ⚠️ <?= htmlspecialchars($errorMsg) ?>
                </div>
            <?php endif; ?>

            <?php if ($application): ?>
                <div class="intake-card">
                    <div class="intake-header">
                        <h2>Application Details</h2>
                        <span class="badge badge-open" style="text-transform: uppercase;"><?= htmlspecialchars($application['status']) ?></span>
                    </div>
                    <div class="intake-details">
                        <div class="detail-item"><span class="detail-label">Tracking Serial Number:</span> <span class="detail-value" style="color: var(--blue);"><?= htmlspecialchars($application['tracking_number']) ?></span></div>
                        <div class="detail-item"><span class="detail-label">Registration Number:</span> <span class="detail-value"><?= htmlspecialchars($application['reg_number']) ?></span></div>
                        <div class="detail-item"><span class="detail-label">Full Name:</span> <span class="detail-value"><?= htmlspecialchars($application['full_name']) ?></span></div>
                        <div class="detail-item"><span class="detail-label">College:</span> <span class="detail-value"><?= htmlspecialchars($application['college']) ?></span></div>
                        <div class="detail-item"><span class="detail-label">Department / Combination:</span> <span class="detail-value"><?= htmlspecialchars($application['department']) ?></span></div>
                        <div class="detail-item"><span class="detail-label">Assigned Host School:</span> <span class="detail-value"><?= htmlspecialchars($application['school_name']) ?> (<?= htmlspecialchars($application['district']) ?>)</span></div>
                        <div class="detail-item"><span class="detail-label">Submission Date:</span> <span class="detail-value"><?= htmlspecialchars($application['created_at']) ?></span></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php
include_once __DIR__ . '/includes/footer.php';
?>