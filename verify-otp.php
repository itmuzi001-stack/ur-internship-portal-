<?php
// verify-otp.php
session_start();
$pageTitle = "Verify OTP - UR Internship Portal";

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/send_otp.php';

// Determine if session identifiers exist for either login or signup flow
$hasLoginPending = isset($_SESSION['pending_user_id']);
$hasSignupEmail = isset($_SESSION['verify_email']);

if (!$hasLoginPending && !$hasSignupEmail) {
    $sessionError = true;
} else {
    $sessionError = false;
    $email = $_SESSION['verify_email'] ?? null;
    $userId = $_SESSION['pending_user_id'] ?? null;
}

$error = '';
$success = '';

// Fetch active user details based on active session context
$user = null;
if (!$sessionError && $pdo) {
    try {
        if ($userId) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$userId]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
        }
        $user = $stmt->fetch();
        
        if ($user && empty($email)) {
            $email = $user['email'];
        }
    } catch (PDOException $e) {
        $error = "Database error fetching user record.";
    }
}

// Handle Resend OTP Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_otp']) && !$sessionError && $user) {
    try {
        $newOtp = sprintf("%06d", mt_rand(100000, 999999));
        $newExpires = date('Y-m-d H:i:s', strtotime('+30 seconds'));

        $upd = $pdo->prepare("UPDATE users SET otp_code = ?, otp_expires_at = ? WHERE id = ?");
        $upd->execute([$newOtp, $newExpires, $user['id']]);

        if (sendOtpEmail($user['email'], $newOtp)) {
            $success = "A new 6-digit verification code has been sent to your email (Valid for 30 seconds).";
        } else {
            $error = "Failed to resend OTP email. Please try again.";
        }
    } catch (PDOException $e) {
        $error = "Database error while resending OTP.";
    }
}

// Handle OTP Verification Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp']) && !$sessionError && $user) {
    $enteredOtp = trim($_POST['otp_code'] ?? '');

    try {
        if (strtotime($user['otp_expires_at']) < time()) {
            $error = "The OTP code has expired. Please click 'Resend Code' below.";
        } elseif (trim((string)$user['otp_code']) === $enteredOtp) {
            
            if ($userId) {
                // --- LOGIN FLOW VERIFICATION ---
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'] ?? 'student';

                // Clear login pending flags & OTP data
                unset($_SESSION['pending_user_id']);
                unset($_SESSION['verify_email']);

                $clearStmt = $pdo->prepare("UPDATE users SET otp_code = NULL, otp_expires_at = NULL WHERE id = ?");
                $clearStmt->execute([$user['id']]);

                if ($_SESSION['role'] === 'admin') {
                    header("Location: admin/schools.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit();

            } else {
                // --- SIGNUP FLOW VERIFICATION ---
                $updateStmt = $pdo->prepare("UPDATE users SET is_verified = 1, otp_code = NULL, otp_expires_at = NULL WHERE id = ?");
                $updateStmt->execute([$user['id']]);

                unset($_SESSION['verify_email']);

                header("Location: login.php?verified=success");
                exit();
            }

        } else {
            $error = "Invalid OTP code. Please check your email and try again.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . htmlspecialchars($e->getMessage());
    }
}

include_once __DIR__ . '/includes/header.php';
?>

<main id="main-content" style="padding: 3rem 0;">
    <div class="container" style="max-width: 500px;">
        <div class="intake-card">
            <div class="intake-header">
                <h2>Security Verification</h2>
                <span class="badge badge-open">Enter OTP</span>
            </div>

            <?php if ($sessionError): ?>
                <div style="background-color: #f8d7da; color: #842029; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.9rem; border: 1px solid #f5c2c7;">
                    ⚠️ No active verification session found. Please <a href="login.php" style="color: #12356B; font-weight: bold;">Log In</a> or <a href="signup.php" style="color: #12356B; font-weight: bold;">Sign Up</a> first.
                </div>
            <?php else: ?>
                <p style="color: #64748B; margin-bottom: 1.5rem; font-size: 0.95rem;">
                    We sent a 6-digit verification code to <strong><?= htmlspecialchars($email) ?></strong>. Enter it below. 
                    <br><strong style="color: #DC2626; font-size: 0.85net;">Note: This code expires in 30 seconds.</strong>
                </p>

                <?php if (!empty($error)): ?>
                    <div style="background-color: #f8d7da; color: #842029; padding: 0.75rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.9rem; border: 1px solid #f5c2c7;">
                        ⚠️ <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div style="background-color: #d1e7dd; color: #0f5132; padding: 0.75rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.9rem; border: 1px solid #badbcc;">
                        ✅ <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">6-Digit OTP Code</label>
                        <input type="text" name="otp_code" maxlength="6" placeholder="123456" required autocomplete="off" autofocus style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 1.25rem; letter-spacing: 4px; text-align: center;">
                    </div>

                    <button type="submit" name="verify_otp" class="btn btn-amber" style="width: 100%;">Verify & Continue</button>
                </form>

                <!-- Resend Code Button Form -->
                <form method="POST" style="margin-top: 1rem; text-align: center;">
                    <button type="submit" name="resend_otp" style="background: none; border: none; color: #12356B; font-weight: 600; cursor: pointer; text-decoration: underline; font-size: 0.9rem;">
                        Didn't get the code? Resend Code
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include_once __DIR__ . '/includes/footer.php'; ?>