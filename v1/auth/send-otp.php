<?php
require_once __DIR__ . '/../../src/middlewares/cors.php';
require __DIR__ . '/../../src/MailSender.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => false, 'message' => 'Method not allowed. Use POST.']);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
$email = isset($input["email"]) ? $input["email"] : null;

if (!is_string($email) || trim($email) === '') {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Email is required.']);
    exit;
}

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
    echo json_encode(['status' => false, 'message' => 'Email not found. Please register first.']);
    exit;
}

if ($user['is_verified']) {
    http_response_code(403);
    echo json_encode(['status' => false, 'message' => 'User already verified. Please login.']);
    exit;
}

$stmt = $db->prepare("SELECT * FROM otps WHERE user_id = :user_id AND expires_at > NOW()");
$stmt->execute(['user_id' => $user['id']]);
$existingOtp = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existingOtp) {
    $otp = $existingOtp['otp_code'];
} else {
    $otp = strval(random_int(100000, 999999));
    $sql = "INSERT INTO otps (user_id, otp_code, expires_at, created_at)
            VALUES (:user_id, :otp, DATE_ADD(NOW(), INTERVAL 10 MINUTE), NOW())
            ON DUPLICATE KEY UPDATE otp_code = :otp, expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE), created_at = NOW()";
    $stmt = $db->prepare($sql);
    $stmt->execute(['user_id' => $user['id'], 'otp' => $otp]);
}

$name = $user['name'];
$recipient = $email;
$emailSubject = 'GIEO Gita: Account Verification';
$emailBody = '<h1>Jai Shri Krishna ' . $name . '!</h1><p>Your verification code is </p><h1><strong>' . $otp . '</strong></h1><p>This code will expire in 10 minutes.</p>';

$mailer = new MailSender($config);
$success = $mailer->sendEmail($recipient, $emailSubject, $emailBody);

if ($success) {
    http_response_code(200);
    echo json_encode(['status' => true, 'message' => "Email successfully sent to $recipient!"]);
} else {
    http_response_code(422);
    echo json_encode(['status' => false, 'message' => 'Failed to send email. Please try again later.']);
}
?>
