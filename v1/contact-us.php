<?php

require_once __DIR__ . '/../../src/middlewares/cors.php';
require_once __DIR__ . '/../../src/Inquiries.php';

header('Content-Type: application/json');

// --- NO AUTHENTICATION ---
// This is a public endpoint for submitting a form.

$method = $_SERVER['REQUEST_METHOD'];
$inquiries = new Inquiries();

switch ($method) {
    case 'POST':
        // POST case: Create a new inquiry from the public
        $input = json_decode(file_get_contents("php://input"), true);
        
        $result = $inquiries->createInquiry($input);
        echo json_encode($result);
        break;

    // All other methods are forbidden
    case 'GET':
    case 'PUT':
    case 'DELETE':
    default:
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
        break;
}