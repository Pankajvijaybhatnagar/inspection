<?php
require_once __DIR__ . '/../../src/middlewares/cors.php';
require __DIR__ . '/../../src/MailSender.php';

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => false,
        'message' => 'Method not allowed. Use POST.'
    ]);
    exit;
}

// Read and decode input JSON
$input = json_decode(file_get_contents("php://input"), true);

$email = isset($input["email"]) ? $input["email"] : null;
$otp = isset($input["otp"]) ? $input["otp"] : null;

if (!is_string($email) || trim($email) === '') {
    http_response_code(400);
    echo json_encode([
        'status' => false,
        'message' => 'Email is required.'
    ]);
    exit;
}
if (!($otp) || trim($otp) === '') {
    http_response_code(400);
    echo json_encode([
        'status' => false,
        'message' => 'OTP is required.'
    ]);
    exit;
}


// checking if email exists in users table
$config = new Config();
$reflection = new ReflectionClass($config);
$property = $reflection->getProperty('db');
$property->setAccessible(true);
$db = $property->getValue($config);
$stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
$stmt->execute(['email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404);
    echo json_encode([
        'status' => false,
        'message' => 'Email not found. Please register first.'
    ]);
    exit;
}

// checking if user is verified
if ($user['is_verified']) {
    http_response_code(403);
    echo json_encode([
        'status' => false,
        'message' => 'User already verified. Please login.'
    ]);
    exit;
}

// geting otp from otps table
$stmt = $db->prepare("SELECT * FROM otps WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1");
$stmt->execute(['user_id' => $user['id']]);
$otpRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$otpRow) {
    http_response_code(404);
    echo json_encode([
        'status' => false,
        'message' => 'No OTP found for this user. Please request a new OTP.'
    ]);
    exit;
}

// checking if otp is expired
$currentDateTime = new DateTime();
$expiresAt = new DateTime($otpRow['expires_at']);

if ($currentDateTime > $expiresAt) {
    http_response_code(400);
    echo json_encode([
        'status' => false,
        'message' => 'OTP has expired. Please request a new OTP.'
    ]);
    exit;
}

// check otp is valid 
if ($otpRow['otp_code'] !== $otp) {
    http_response_code(400);
    echo json_encode([
        'status' => false,
        'message' => 'Invalid OTP. Please try again.'
    ]);
    exit;
}

// mark otp as used
$stmt = $db->prepare("UPDATE otps SET is_used = TRUE WHERE id = :id");
$stmt->execute(['id' => $otpRow['id']]);
// updating user as verified
$stmt = $db->prepare("UPDATE users SET is_verified = TRUE WHERE id = :id");
$stmt->execute(['id' => $user['id']]);
http_response_code(200);
echo json_encode([
    'status' => true,
    'message' => 'OTP verified successfully. User is now verified.'
]);

exit;