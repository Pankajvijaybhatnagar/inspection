<?php
// /v1/auth/inspections.php

require_once __DIR__ . '/../../src/middlewares/cors.php';
require_once __DIR__ . '/../../src/middlewares/authenticate.php';
require_once __DIR__ . '/../../src/SchoolInspections.php';

header('Content-Type: application/json');

// --- AUTHENTICATION ---
// The authenticate() function will check for a valid JWT and return the user's payload
// or exit with a 401 Unauthorized error if the token is invalid or missing.
$user = authenticate();

$method = $_SERVER['REQUEST_METHOD'];
$inspectionsManager = new SchoolInspections();

// The path will be something like /v1/auth/inspections.php/123
// We need to get the ID from the path.
$path = $_SERVER['PATH_INFO'] ?? null;
$id = $path ? (int) ltrim($path, '/') : null;

switch ($method) {
    case 'GET':
        // If an ID is provided in the URL (e.g., /inspections.php/123), fetch a single inspection.
        // Otherwise, fetch a list of inspections.
        if ($id) {
            $filters = ['id' => $id];
        } else {
            // Pass all GET query parameters as filters for searching and pagination.
            // e.g., ?status=draft&page=2&limit=20&search=school_name
            $filters = $_GET;
        }

        $result = $inspectionsManager->getInspections($filters);

        // If fetching a single inspection by ID and no data is found, return a 404.
        if ($id && empty($result['data'])) {
            http_response_code(404);
            echo json_encode(['status' => false, 'message' => 'Inspection not found.']);
        } else {
            echo json_encode($result);
        }
        break;

    case 'POST':
        // Read the JSON input from the request body.
        $data = json_decode(file_get_contents('php://input'), true);

        // Add the creator's ID from the authenticated user's token.
        $data['created_by'] = $user['id'];

        $result = $inspectionsManager->createInspection($data);
        echo json_encode($result);
        break;

    case 'PUT':
        // An ID is required to update an inspection.
        if (!$id) {
            http_response_code(400);
            echo json_encode(['status' => false, 'message' => 'Inspection ID is required for update.']);
            exit;
        }

        // Read the JSON input from the request body.
        $data = json_decode(file_get_contents('php://input'), true);

        // The 'created_by' field should not be updatable via this method.
        unset($data['created_by']);

        $result = $inspectionsManager->updateInspection($id, $data);
        echo json_encode($result);
        break;

    case 'DELETE':
        // An ID is required to delete an inspection.
        if (!$id) {
            http_response_code(400);
            echo json_encode(['status' => false, 'message' => 'Inspection ID is required for deletion.']);
            exit;
        }

        $result = $inspectionsManager->deleteInspection($id);
        echo json_encode($result);
        break;

    case 'OPTIONS':
        // Handle preflight requests for CORS
        http_response_code(204);
        break;

    default:
        // If any other method is used, return a 405 Method Not Allowed error.
        http_response_code(405);
        echo json_encode(['status' => false, 'message' => 'Method not allowed.']);
        break;
}

?>