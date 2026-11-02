<?php
/**
 * PDO Database Connection with secure .env loading and environment fallbacks.
 */

// Function to securely load environment variables from a .env file
function loadEnv($filePath) {
    if (!file_exists($filePath)) {
        return;
    }
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Load .env from project root (one level up from includes/)
loadEnv(__DIR__ . '/../.env');

// Dynamically read configuration from environment variables (local .env or cloud provider)
$dbHost    = getenv('DB_HOST') ?: '127.0.0.1';
$dbName    = getenv('DB_NAME') ?: 'ur_internship';
$dbUser    = getenv('DB_USER') ?: 'root';
$dbPass    = getenv('DB_PASS') ?: '';
$dbCharset = getenv('DB_CHARSET') ?: 'utf8mb4';
$dbPort    = getenv('DB_PORT') ?: '3306';

$dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=$dbCharset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    // Log error internally without exposing credentials in stack traces
    error_log("Database Connection Error: " . $e->getMessage());
    $pdo = null;

    // Display detailed error locally for debugging, secure message on production
    if ($dbHost === '127.0.0.1' || $dbHost === 'localhost' || getenv('APP_ENV') === 'development') {
        die("Database Connection Error: " . htmlspecialchars($e->getMessage()));
    } else {
        die("Service temporarily unavailable. Please check cloud configuration.");
    }
}