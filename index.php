<?php
// index.php
session_start();
$pageTitle = "Internship Management Portal";
$metaDescription = "Apply for your Year 4 internship placement and check your placement results.";
$currentPage = 'home';

require_once __DIR__ . '/includes/db.php';

// --- 1. Fetch active intake logic ---
$activeIntake = null;
$intakeError = false;

if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM intakes WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $activeIntake = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Failed to fetch active intake: " . $e->getMessage());
        $intakeError = true;
    }
} else {
    $intakeError = true;
}

// Application State Logic based on current date
$today = new DateTime('now');
$applyDisabled = true;
$badgeClass = 'badge-closed';
$badgeText = 'Applications closed';

if ($activeIntake) {
    $opensAt = new DateTime($activeIntake['opens_at']);
    $closesAt = new DateTime($activeIntake['closes_at']);
    $closesAt->modify('+1 day');

    if ($today < $opensAt) {
        $badgeClass = 'badge-upcoming';
        $badgeText = 'Opens on ' . $opensAt->format('j M Y');
        $applyDisabled = true;
    } elseif ($today >= $opensAt && $today <= $closesAt) {
        $badgeClass = 'badge-open';
        $badgeText = 'Applications open';
        $applyDisabled = false;
    } else {
        $badgeClass = 'badge-closed';
        $badgeText = 'Applications closed';
        $applyDisabled = true;
    }
} else {
    $badgeText = 'No intake is open';
    $applyDisabled = true;
}

// --- 2. Handle Check Result Search ---
$searchResult = null;
$searchError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_result'])) {
    $searchQuery = trim($_POST['tracking_code'] ?? '');

    if (empty($searchQuery)) {
        $searchError = "Please enter your Application ID, Tracking Code, or Registration Number.";
    } elseif ($pdo) {
        try {
            $searchStmt = $pdo->prepare("
                SELECT a.*, u.full_name, u.registration_number, u.combination 
                FROM applications a 
                LEFT JOIN users u ON a.user_id = u.id 
                WHERE a.tracking_code = ? OR u.registration_number = ? OR a.registration_number = ? 
                LIMIT 1
            ");
            $searchStmt->execute([$searchQuery, $searchQuery, $searchQuery]);
            $searchResult = $searchStmt->fetch(PDO::FETCH_ASSOC);

            if (!$searchResult) {
                $searchError = "No placement record found matching that input. Please verify your details.";
            }
        } catch (PDOException $e) {
            $searchError = "Error looking up placement status. Please try again later.";
        }
    } else {
        $searchError = "Database service unavailable.";
    }
}

include_once __DIR__ . '/includes/header.php';
?>

<main id="main-content">
    <!-- 1. Hero Section -->
    <section class="hero-section">
        <div class="container hero-grid">
            <div class="hero-content">
                <div class="eyebrow">Year 4 Placement Portal — Today: <?= $today->format('j M Y') ?></div>
                <h1>Start your internship placement in minutes</h1>
                <p>Choose your college, confirm your registration number and pick an eligible public secondary school.</p>
                <div class="hero-actions">
                    <a href="apply.php" class="btn btn-amber <?= $applyDisabled ? 'disabled' : '' ?>" <?= $applyDisabled ? 'aria-disabled="true"' : '' ?>>Apply for an internship</a>
                    <a href="#check-result-section" class="btn btn-outline">Check application status</a>
                </div>
            </div>

            <!-- Right: Current Intake Card -->
            <div class="intake-card">
                <div class="intake-header">
                    <h2>Current intake</h2>
                    <?php if ($intakeError): ?>
                        <span class="badge badge-error">Service unavailable</span>
                    <?php else: ?>
                        <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($badgeText, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($intakeError): ?>
                    <p style="color: var(--muted); font-size: 0.95rem;">We are currently experiencing technical difficulties retrieving intake details.</p>
                <?php elseif ($activeIntake): ?>
                    <div class="intake-details">
                        <div class="detail-item">
                            <span class="detail-label">Academic year</span>
                            <span class="detail-value"><?= htmlspecialchars($activeIntake['academic_year'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Applications open</span>
                            <span class="detail-value"><?= (new DateTime($activeIntake['opens_at']))->format('j M Y') ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Applications close</span>
                            <span class="detail-value"><?= (new DateTime($activeIntake['closes_at']))->format('j M Y') ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Internship period</span>
                            <span class="detail-value">
                                <?= (new DateTime($activeIntake['internship_start']))->format('j M Y') ?> – 
                                <?= (new DateTime($activeIntake['internship_end']))->format('j M Y') ?>
                            </span>
                        </div>
                    </div>
                <?php else: ?>
                    <p style="color: var(--muted); font-size: 0.95rem;">No intake is open at the moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- 2. Check Placement Result Section -->
    <section class="section-padding" id="check-result-section" style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">
        <div class="container" style="max-width: 750px;">
            <div class="intake-card" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.03);">
                <div class="intake-header" style="margin-bottom: 1.25rem;">
                    <h2 style="color: #12356B; margin: 0; font-size: 1.35rem;">🔍 Check Placement Result</h2>
                    <span class="badge badge-open">Instant Verification</span>
                </div>
                <p style="color: #64748B; font-size: 0.95rem; margin-bottom: 1.5rem;">Enter your Application ID, Tracking Reference, or Student Registration Number to view your approved host school assignment.</p>

                <?php if (!empty($searchError)): ?>
                    <div style="background-color: #f8d7da; color: #842029; padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.9rem; border: 1px solid #f5c2c7;">
                        ⚠️ <?= htmlspecialchars($searchError) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="index.php#check-result-section">
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <input type="text" name="tracking_code" placeholder="e.g. UR-INT-12345 or Reg Number..." value="<?= htmlspecialchars($_POST['tracking_code'] ?? '') ?>" required style="flex-grow: 1; padding: 0.75rem 1rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 1rem;">
                        <button type="submit" name="check_result" class="btn btn-amber" style="padding: 0.75rem 1.75rem;">Check Result</button>
                    </div>
                </form>

                <!-- Search Results Box -->
                <?php if ($searchResult): ?>
                    <div style="margin-top: 2rem; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 1.5rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.75rem; margin-bottom: 1rem;">
                            <h3 style="color: #12356B; margin: 0; font-size: 1.1rem;">Placement Details</h3>
                            <span style="background-color: #d1e7dd; color: #0f5132; padding: 3px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 700;">✓ RECORD FOUND</span>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; font-size: 0.95rem;">
                            <div>
                                <strong style="color: #64748B; display: block; font-size: 0.8rem; text-transform: uppercase;">Student Name:</strong>
                                <strong><?= htmlspecialchars($searchResult['full_name'] ?? 'N/A') ?></strong>
                            </div>
                            <div>
                                <strong style="color: #64748B; display: block; font-size: 0.8rem; text-transform: uppercase;">Registration Number:</strong>
                                <?= htmlspecialchars($searchResult['registration_number'] ?? 'N/A') ?>
                            </div>
                            <div>
                                <strong style="color: #64748B; display: block; font-size: 0.8rem; text-transform: uppercase;">Tracking Code:</strong>
                                <?= htmlspecialchars($searchResult['tracking_code']) ?>
                            </div>
                            <div>
                                <strong style="color: #64748B; display: block; font-size: 0.8rem; text-transform: uppercase;">Application Status:</strong>
                                <span style="color: #0369a1; font-weight: 600;"><?= htmlspecialchars($searchResult['status'] ?? 'Approved') ?></span>
                            </div>
                            <div style="grid-column: span 2; background: #ffffff; padding: 1rem; border: 1px solid #cbd5e1; border-radius: 6px; margin-top: 0.5rem;">
                                <strong style="color: #64748B; display: block; font-size: 0.8rem; text-transform: uppercase;">Assigned Host School:</strong>
                                <span style="color: #12356B; font-weight: 700; font-size: 1.15rem;"><?= htmlspecialchars($searchResult['placement_school']) ?></span>
                            </div>
                        </div>

                        <div style="margin-top: 1.5rem; text-align: right;">
                            <a href="login.php" class="btn btn-amber" style="padding: 0.5rem 1rem; font-size: 0.85rem; text-decoration: none;">Log In to Download Letter →</a>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </section>

    <!-- 3. How it works Section -->
    <section class="section-padding">
        <div class="container">
            <div class="section-header">
                <h2>How it works</h2>
                <p>Follow these four simple steps to secure your public secondary school internship placement.</p>
            </div>
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number" aria-hidden="true">1</div>
                    <h3>Academic unit</h3>
                    <p>Select your college, school and department from our directory.</p>
                </div>
                <div class="step-card">
                    <div class="step-number" aria-hidden="true">2</div>
                    <h3>Registration number</h3>
                    <p>We confirm your student details against the university registry.</p>
                </div>
                <div class="step-card">
                    <div class="step-number" aria-hidden="true">3</div>
                    <h3>Choose a school</h3>
                    <p>Pick from eligible public secondary schools offering your discipline.</p>
                </div>
                <div class="step-card">
                    <div class="step-number" aria-hidden="true">4</div>
                    <h3>Review and submit</h3>
                    <p>Check your details carefully and send your application for approval.</p>
                </div>
            </div>
        </div>
    </section>
</main>

<?php
include_once __DIR__ . '/includes/footer.php';
?>