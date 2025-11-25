<?php
// The cors.php middleware must be included first to handle preflight OPTIONS requests.
require_once __DIR__ . '/../../src/middlewares/cors.php';

// The cors.php script handles the OPTIONS method and exits, so the rest of this
// script will only run for other methods like POST.

require_once __DIR__ . '/../../src/GoogleAuth.php';

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

// Fallback to GET for testing (optional, can remove for production)
$idToken = $input['id_token'] ?? ($_GET['id_token'] ?? null);

if (!$idToken) {
    http_response_code(400);
    echo json_encode([
        'status' => false,
        'message' => 'Missing id_token'
    ]);
    exit;
}

// Process Google login
try {
    $googleAuth = new GoogleAuth();
    $result = $googleAuth->verifyAndLogin($idToken);

    if ($result['status'] === true) {
        // The result from verifyAndLogin already contains user, access_token, etc.
        echo json_encode([
            'status' => true,
            'message' => 'Login successful',
            'data' => $result // Return the full successful payload
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'status' => false,
            'message' => $result['message'] ?? 'Login failed'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
