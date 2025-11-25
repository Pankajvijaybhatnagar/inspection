<?php 
require_once __DIR__ . '/../../src/middlewares/cors.php';
require_once __DIR__ . '/../../src/EmailPasswordAuth.php';

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
$input = json_decode(file_get_contents("php://input"), true) ?? [];

$email = $input["email"] ?? null;
$password = $input["password"] ?? null;

// The login logic is wrapped in a try-catch block for robust error handling.
try {
    $auth = new EmailPasswordAuth();
    $result = $auth->login($email, $password);

    // The login method already sets the appropriate HTTP status code (200 for success, 401/403 for failure).
    // We just need to echo the JSON response.
    echo json_encode($result);

} catch (Exception $e) {
    // Catch any other unexpected errors (e.g., database connection issues)
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'A server error occurred: ' . $e->getMessage()
    ]);
}

?>
