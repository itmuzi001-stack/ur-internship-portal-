<?php
// send_otp.php
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// Load environment variables from .env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

/**
 * Sends the actual 6-digit OTP code email to the registering student.
 */
function sendOtpEmail($recipientEmail, $otpCode) {
    $mail = new PHPMailer(true);

    try {
        // Server settings from .env
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USER'] ?? '';
        $mail->Password   = $_ENV['SMTP_PASS'] ?? '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $_ENV['SMTP_PORT'] ?? 587;

        // Bypass local SSL certificate verification errors on XAMPP/Windows
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Recipients
        $mail->setFrom(
            $_ENV['SMTP_FROM_EMAIL'] ?? $_ENV['SMTP_USER'], 
            $_ENV['SMTP_FROM_NAME'] ?? 'UR Internship Portal'
        );
        $mail->addAddress($recipientEmail);

        // Content - Real OTP Email Template
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Verification Code - UR Internship Portal';
        $mail->Body    = '
            <div style="font-family: Arial, sans-serif; padding: 25px; color: #0F1B2D; background-color: #F8FAFC; border-radius: 8px;">
                <h2 style="color: #12356B; margin-top: 0;">UR Internship Portal</h2>
                <p>Hello,</p>
                <p>Thank you for registering. Please use the following 6-digit OTP code to verify your email address:</p>
                
                <div style="background: #FFFFFF; padding: 15px 25px; border-radius: 6px; font-size: 28px; font-weight: bold; letter-spacing: 6px; color: #12356B; text-align: center; width: fit-content; margin: 20px auto; border: 1px solid #E2E8F0;">
                    ' . $otpCode . '
                </div>
                
                <p>This code will expire in <strong>15 minutes</strong>.</p>
                <p style="font-size: 0.85rem; color: #64748B; margin-top: 30px;">If you did not request this code, please ignore this email.</p>
            </div>
        ';

        $mail->send();
        return true;

    } catch (Exception $e) {
        return false;
    }
}