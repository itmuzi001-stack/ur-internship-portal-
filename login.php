<?php
// login.php
session_start();
$pageTitle = "Login - UR Internship Portal";

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/send_otp.php';

$error = '';
$success = '';

// Check if redirected from successful verification
if (isset($_GET['verified']) && $_GET['verified'] === 'success') {
    $success = "Email verified successfully! Please log in to your account.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please fill in both your email and password.";
    } elseif ($pdo) {
        try {
            // Include 'role' in the selected columns
            $stmt = $pdo->prepare("SELECT id, full_name, password, is_verified, role FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                if ((int)$user['is_verified'] !== 1) {
                    $error = "Your email address is not verified yet. Please check your inbox for the OTP.";
                } elseif (password_verify($password, $user['password'])) {
                    // Password is correct, now generate a 30-second expiring OTP for secure login
                    $otp = sprintf("%06d", mt_rand(100000, 999999));
                    $otpExpires = date('Y-m-d H:i:s', strtotime('+30 seconds'));

                    // Save OTP & expiry to user record
                    $upd = $pdo->prepare("UPDATE users SET otp_code = ?, otp_expires_at = ? WHERE id = ?");
                    $upd->execute([$otp, $otpExpires, $user['id']]);

                    // Send OTP email
                    if (sendOtpEmail($email, $otp)) {
                        // Store pending user identifiers in session for the verification step
                        $_SESSION['pending_user_id'] = $user['id'];
                        $_SESSION['verify_email']    = $email;

                        // Redirect to OTP verification page
                        header("Location: verify-otp.php");
                        exit();
                    } else {
                        $error = "Failed to send login verification OTP email. Please try again.";
                    }
                } else {
                    $error = "Invalid password. Please try again.";
                }
            } else {
                $error = "No account found with that email address.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}

include_once __DIR__ . '/includes/header.php';
?>

<main id="main-content" style="padding: 3rem 0;">
    <div class="container" style="max-width: 500px;">
        <div class="intake-card">
            <div class="intake-header">
                <h2>Student Login</h2>
                <span class="badge badge-open">Portal Access</span>
            </div>

            <?php if (!empty($success)): ?>
                <div style="background-color: #d1e7dd; color: #0f5132; padding: 0.75rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.9rem; border: 1px solid #badbcc;">
                    ✅ <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div style="background-color: #f8d7da; color: #842029; padding: 0.75rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.9rem; border: 1px solid #f5c2c7;">
                    ⚠️ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Email Address</label>
                    <input type="email" name="email" required placeholder="your.email@ur.ac.rw" style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Password</label>
                    <input type="password" name="password" required style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>

                <button type="submit" class="btn btn-amber" style="width: 100%;">Log In & Send OTP</button>
            </form>

            <p style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: #64748B;">
                Don't have an account? <a href="signup.php" style="color: #12356B; font-weight: bold;">Sign up here</a>
            </p>
        </div>
    </div>
</main>

<?php include_once __DIR__ . '/includes/footer.php'; ?>