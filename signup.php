<?php
// signup.php
session_start();
$pageTitle = "Sign Up - UR Internship Portal";

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/send_otp.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $regNumber   = trim($_POST['registration_number'] ?? '');
    $fullName    = trim($_POST['full_name'] ?? '');
    $combination = trim($_POST['combination'] ?? '');
    $phone       = trim($_POST['phone'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $password    = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);

    if (empty($regNumber) || empty($fullName) || empty($email) || empty($_POST['password'])) {
        $error = "Please fill in all required fields.";
    } elseif ($pdo) {
        try {
            // Check if user already exists
            $checkStmt = $pdo->prepare("SELECT id, is_verified FROM users WHERE registration_number = ? OR email = ? LIMIT 1");
            $checkStmt->execute([$regNumber, $email]);
            $existingUser = $checkStmt->fetch();

            $otp = rand(100000, 999999);
            // OTP validity set to 30 seconds
            $otpExpires = date('Y-m-d H:i:s', strtotime('+30 seconds'));

            if ($existingUser) {
                if (isset($existingUser['is_verified']) && $existingUser['is_verified'] == 1) {
                    $error = "This registration number or email is already registered and verified. Please log in instead.";
                } else {
                    // Unverified user: update record, store email in session, resend OTP
                    $_SESSION['verify_email'] = $email;

                    $upd = $pdo->prepare("UPDATE users SET otp_code = ?, otp_expires_at = ?, full_name = ?, combination = ?, phone = ?, password = ? WHERE id = ?");
                    $upd->execute([$otp, $otpExpires, $fullName, $combination, $phone, $password, $existingUser['id']]);

                    if (sendOtpEmail($email, $otp)) {
                        header("Location: verify-otp.php");
                        exit();
                    } else {
                        $error = "Failed to send verification OTP email.";
                    }
                }
            } else {
                // New registration: insert into database
                $stmt = $pdo->prepare("INSERT INTO users (registration_number, full_name, combination, phone, email, password, otp_code, otp_expires_at, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)");
                $stmt->execute([$regNumber, $fullName, $combination, $phone, $email, $password, $otp, $otpExpires]);

                // Store email in session for the verification page
                $_SESSION['verify_email'] = $email;

                if (sendOtpEmail($email, $otp)) {
                    header("Location: verify-otp.php");
                    exit();
                } else {
                    $error = "Account created, but failed to send OTP email.";
                }
            }

        } catch (PDOException $e) {
            $error = "Registration failed: " . htmlspecialchars($e->getMessage());
        }
    } else {
        $error = "Database connection error.";
    }
}

include_once __DIR__ . '/includes/header.php';
?>

<main id="main-content" style="padding: 3rem 0;">
    <div class="container" style="max-width: 650px;">
        <div class="intake-card">
            <div class="intake-header">
                <h2>Student Sign Up</h2>
                <span class="badge badge-open">Step <span id="stepIndicator">1</span> of 3</span>
            </div>

            <?php if (!empty($error)): ?>
                <div style="background-color: #f8d7da; color: #842029; padding: 0.75rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.9rem; border: 1px solid #f5c2c7;">
                    ⚠️ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form id="signupForm" method="POST" novalidate>
                <!-- Step 1: Personal Details -->
                <div class="form-step active" id="step-1">
                    <h3 style="font-size: 1.1rem; color: #12356B; margin-bottom: 1rem;">1. Personal Details</h3>

                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Registration Number</label>
                        <input type="text" name="registration_number" required style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Full Name</label>
                        <input type="text" name="full_name" required style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Combination / Discipline</label>
                        <input type="text" name="combination" placeholder="e.g., Physics-Mathematics-Education" required style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Phone Number</label>
                        <input type="text" name="phone" placeholder="07XXXXXXXX" required style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>

                    <button type="button" class="btn btn-amber" onclick="nextStep(2)" style="width: 100%;">Next: Email & Security</button>
                </div>

                <!-- Step 2: Working Email & Password -->
                <div class="form-step" id="step-2" style="display:none;">
                    <h3 style="font-size: 1.1rem; color: #12356B; margin-bottom: 1rem;">2. Working Email & Password</h3>

                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Working Email Address</label>
                        <input type="email" name="email" required style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                        <small style="color: #64748B;">An OTP verification code valid for 30 seconds will be sent to this email address.</small>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Create Password</label>
                        <input type="password" name="password" required style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>

                    <div style="display: flex; gap: 1rem;">
                        <button type="button" class="btn btn-outline" onclick="nextStep(1)" style="width: 50%;">Back</button>
                        <button type="button" class="btn btn-amber" onclick="nextStep(3); populateReview();" style="width: 50%;">Next: Review</button>
                    </div>
                </div>

                <!-- Step 3: View & Submit -->
                <div class="form-step" id="step-3" style="display:none;">
                    <h3 style="font-size: 1.1rem; color: #12356B; margin-bottom: 1rem;">3. Review and Submit</h3>
                    <p style="color: #64748B; margin-bottom: 1rem; font-size: 0.95rem;">Please review your details before submitting your registration.</p>

                    <div style="background: #f1f5f9; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.9rem; display: flex; flex-direction: column; gap: 0.5rem;">
                        <div><strong>Reg Number:</strong> <span id="revReg"></span></div>
                        <div><strong>Full Name:</strong> <span id="revName"></span></div>
                        <div><strong>Combination:</strong> <span id="revCombo"></span></div>
                        <div><strong>Phone:</strong> <span id="revPhone"></span></div>
                        <div><strong>Email:</strong> <span id="revEmail"></span></div>
                    </div>

                    <div style="display: flex; gap: 1rem;">
                        <button type="button" class="btn btn-outline" onclick="nextStep(2)" style="width: 50%;">Back</button>
                        <button type="submit" class="btn btn-amber" style="width: 50%;">Confirm & Send OTP</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
function nextStep(step) {
    document.querySelectorAll('.form-step').forEach(el => el.style.display = 'none');
    document.getElementById('step-' + step).style.display = 'block';
    document.getElementById('stepIndicator').textContent = step;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
// Initialize step display
document.getElementById('step-1').style.display = 'block';

function populateReview() {
    document.getElementById('revReg').textContent = document.querySelector('[name="registration_number"]').value;
    document.getElementById('revName').textContent = document.querySelector('[name="full_name"]').value;
    document.getElementById('revCombo').textContent = document.querySelector('[name="combination"]').value;
    document.getElementById('revPhone').textContent = document.querySelector('[name="phone"]').value;
    document.getElementById('revEmail').textContent = document.querySelector('[name="email"]').value;
}
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>