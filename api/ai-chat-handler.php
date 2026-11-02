<?php
// api/ai-chat-handler.php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';

// Read raw POST body and handle JSON decoding
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

$userMessage = trim($input['message'] ?? $_POST['message'] ?? '');

if (empty($userMessage)) {
    echo json_encode(['reply' => 'Please provide a message.']);
    exit();
}

// =========================================================================
// RAG: FETCH LIVE DATABASE CONTEXT & SCHOOL LIST
// =========================================================================
$totalSchools = 0;
$schoolListText = "No schools loaded yet.";
$activeIntakeName = 'Not Open';
$closingDate = 'N/A';

if ($pdo) {
    try {
        // 1. Fetch all active schools for listing and counting
        $schoolStmt = $pdo->query("SELECT school_name, district, province FROM schools WHERE is_active = 1 ORDER BY school_name ASC");
        $schools = $schoolStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $totalSchools = count($schools);

        if ($totalSchools > 0) {
            $schoolNamesArray = [];
            foreach ($schools as $s) {
                $schoolNamesArray[] = "- " . $s['school_name'] . " (" . $s['district'] . " District, " . $s['province'] . ")";
            }
            // Join all schools into a bulleted text list for the AI
            $schoolListText = implode("\n", $schoolNamesArray);
        }

        // 2. Get active intake details
        $intakeStmt = $pdo->query("SELECT * FROM intakes WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
        $activeIntake = $intakeStmt->fetch(PDO::FETCH_ASSOC);
        if ($activeIntake) {
            $activeIntakeName = $activeIntake['intake_name'] ?? 'Year 4 Internship Intake';
            $closingDate = $activeIntake['closes_at'] ?? 'TBD';
        }
    } catch (PDOException $e) {
        // Fallback silently if query fails
    }
}

// =========================================================================
// DYNAMIC SYSTEM INSTRUCTIONS (Secure Environment Variable Configuration)
// =========================================================================
$apiKey = getenv('GOOGLE_API_KEY') ?: '';
$apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent?key=" . $apiKey;

$systemInstruction = "You are the official University of Rwanda (UR) Year 4 Internship Assistant.\n"
    . "LIVE DATABASE CONTEXT:\n"
    . "- Total Active Host Schools: {$totalSchools}\n"
    . "- Full List of Available Host Schools:\n{$schoolListText}\n\n"
    . "Guidelines:\n"
    . "1. If the student asks to 'list all schools' or requests school names, output the full bulleted list provided in the database context above.\n"
    . "2. Keep answers professional and encouraging.";

$data = [
    "system_instruction" => [
        "parts" => [["text" => $systemInstruction]]
    ],
    "contents" => [
        "parts" => [["text" => $userMessage]]
    ]
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
curl_setopt($ch, CURLOPT_TIMEOUT, 6);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// If Google API responds successfully, return its answer
if ($httpCode === 200 && $response) {
    $responseData = json_decode($response, true);
    $aiReply = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';
    if (!empty($aiReply)) {
        echo json_encode(['reply' => trim($aiReply)]);
        exit();
    }
}

// =========================================================================
// SMART LOCAL FALLBACK (Handles listing directly if offline)
// =========================================================================
$msgLower = strtolower($userMessage);
$fallbackReply = "Welcome to the University of Rwanda Year 4 Internship Portal! We have **{$totalSchools}** host schools available.";

if (strpos($msgLower, 'list') !== false || strpos($msgLower, 'schools') !== false || strpos($msgLower, 'all') !== false) {
    $fallbackReply = "Here are all the **{$totalSchools}** active host secondary schools currently loaded in the system:\n\n{$schoolListText}";
} elseif (strpos($msgLower, 'step') !== false || strpos($msgLower, 'apply') !== false) {
    $fallbackReply = "The application consists of 4 steps: Academic Details -> Student ID -> Choose School -> Review & Submit.";
}

echo json_encode(['reply' => $fallbackReply]);