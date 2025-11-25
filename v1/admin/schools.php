<?php
// /v1/admin/schools.php

require_once __DIR__ . '/../../src/middlewares/cors.php';
require_once __DIR__ . '/../../src/middlewares/auth.php';
require_once __DIR__ . '/../../src/Schools.php';

header('Content-Type: application/json');

// --- AUTHENTICATION ---
// Protects all endpoints in this file. Only authenticated admins can proceed.
$user = authenticate();

// --- AUTHORIZATION ---
// Optional: You could add a check here to ensure the user has a 'superadmin' role
// if you want to restrict school management even further.
// if ($user['role'] !== 'superadmin') {
//     http_response_code(403);
//     echo json_encode(['status' => false, 'message' => 'Forbidden: You do not have permission to manage schools.']);
//     exit;
// }

$method = $_SERVER['REQUEST_METHOD'];
$schoolsManager = new Schools();

// The path will be something like /v1/admin/schools.php/123
// We need to get the ID from the path for GET (single), PUT, and DELETE.
$path = $_SERVER['PATH_INFO'] ?? null;
$id = $path ? (int) ltrim($path, '/') : null;

switch ($method) {
    case 'GET':
        // If an ID is provided, fetch a single school.
        // The getSchools method can filter by ID.
        if ($id) {
            $filters = ['id' => $id];
        } else {
            // Otherwise, pass all GET query parameters as filters for searching.
            // e.g., ?status=active&search=central
            $filters = $_GET;
        }

        $result = $schoolsManager->getSchools($filters);

        // If fetching a single school by ID and no data is found, return a 404.
        if ($id && empty($result['data'])) {
            http_response_code(404);
            echo json_encode(['status' => false, 'message' => 'School not found.']);
        } else {
            echo json_encode($result);
        }
        break;

    case 'POST':
        // Read the JSON input from the request body to create a new school.
        $data = json_decode(file_get_contents('php://input'), true);
        $result = $schoolsManager->createSchool($data);
        echo json_encode($result);
        break;

    case 'PUT':
        // An ID is required to update a school.
        if (!$id) {
            http_response_code(400);
            echo json_encode(['status' => false, 'message' => 'School ID is required for update.']);
            exit;
        }

        // Read the JSON input from the request body.
        $data = json_decode(file_get_contents('php://input'), true);
        $result = $schoolsManager->updateSchool($id, $data);
        echo json_encode($result);
        break;

    case 'DELETE':
        // An ID is required to delete a school.
        if (!$id) {
            http_response_code(400);
            echo json_encode(['status' => false, 'message' => 'School ID is required for deletion.']);
            exit;
        }

        $result = $schoolsManager->deleteSchool($id);
        echo json_encode($result);
        break;

    default:
        http_response_code(405);
        echo json_encode(['status' => false, 'message' => 'Method not allowed.']);
        break;
}