<?php
// test_smtp.php
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// Load .env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "<h3>Testing SMTP Connection & .env Credentials...</h3>";

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = $_ENV['SMTP_HOST'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['SMTP_USER'];
    $mail->Password   = $_ENV['SMTP_PASS'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $_ENV['SMTP_PORT'];

    // Bypass local XAMPP SSL restrictions
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    $mail->setFrom($_ENV['SMTP_FROM_EMAIL'], $_ENV['SMTP_FROM_NAME']);
    $mail->addAddress($_ENV['SMTP_USER']); // Send test to yourself

    $mail->isHTML(true);
    $mail->Subject = 'SMTP Diagnostic Test - UR Internship';
    $mail->Body    = '<b>If you are reading this, your .env credentials and Gmail App Password are working perfectly!</b>';

    $mail->send();
    echo "<p style='color: green; font-weight: bold;'>SUCCESS: Test email sent successfully to " . htmlspecialchars($_ENV['SMTP_USER']) . "!</p>";

} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>FAILED: Could not connect or authenticate.</p>";
    echo "<pre>Error Details: " . htmlspecialchars($mail->ErrorInfo) . "</pre>";
    echo "<p><strong>What this means:</strong> If you see an authentication error, your Google App Password in your .env file needs to be regenerated from your Google Account.</p>";
}