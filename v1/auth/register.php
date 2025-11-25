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
$input = json_decode(file_get_contents("php://input"), true);

// Extract and validate input fields
$name = isset($input["name"]) ? $input["name"] : null;
$email = isset($input["email"]) ? $input["email"] : null;
$password = isset($input["password"]) ? $input["password"] : null;



foreach (['name' => $name, 'email' => $email, 'password' => $password] as $field => $value) {
    if (!is_string($value) || trim($value) === '') {
        http_response_code(400);
        echo json_encode([
            'status' => false,
            'message' => ucfirst($field) . ' is required.'
        ]);
        exit;
    }
}

try {
    $auth = new EmailPasswordAuth();
    $result = $auth->register($name, $email, $password);

    if ($result['status']) {
        http_response_code(201);
    } else {
        http_response_code(400);
    }

    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}