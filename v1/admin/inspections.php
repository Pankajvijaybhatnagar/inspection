<?php
// /v1/admin/school-inspections.php

require_once __DIR__ . '/../../src/middlewares/cors.php';
require_once __DIR__ . '/../../src/SchoolInspections.php'; // Require the SchoolInspections class
require_once __DIR__ . '/../../src/middlewares/auth.php';

header('Content-Type: application/json');

// --- AUTHENTICATION ---
// Protects all inspection endpoints. If authentication fails, the script will stop here.
 $user = authenticate();

 $method = $_SERVER['REQUEST_METHOD'];
 $schoolInspections = new SchoolInspections(); // Instantiate the SchoolInspections class

switch ($method) {
    case 'POST':
        // POST case: Create a new inspection record
        $input = json_decode(file_get_contents("php://input"), true);
        
        // --- SECURITY ---
        // Set the creator of this inspection report to the currently authenticated user
        $input['created_by'] = $user['id']; 
        
        $result = $schoolInspections->createInspection($input);
        echo json_encode($result);
        break;

    case 'GET':
        // GET case: Return inspection records with filters
        $filters = $_GET;
        $result = $schoolInspections->getInspections($filters);
        echo json_encode($result);
        break;

    case 'PUT':
        // PUT case: Update an existing inspection record
        $input = json_decode(file_get_contents("php://input"), true);
        $inspectionId = isset($input["id"]) ? $input["id"] : null;

        $result = $schoolInspections->updateInspection($inspectionId, $input);
        echo json_encode($result);
        break;

    case 'DELETE':
        // DELETE case: Delete an inspection record
        $input = json_decode(file_get_contents("php://input"), true);
        $inspectionId = isset($input["id"]) ? $input["id"] : null;

        $result = $schoolInspections->deleteInspection($inspectionId);
        echo json_encode($result);
        break;

    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(["error" => "Method not allowed"]);
        break;
}