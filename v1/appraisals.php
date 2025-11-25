<?php

// Assuming a project structure where this file is in api/v1/
// and the src directory is at the root level.
require_once __DIR__ . '/../src/middlewares/cors.php';
require_once __DIR__ . '/../src/TeacherAppraisals.php'; // Require the TeacherAppraisals class
require_once __DIR__ . '/../src/middlewares/auth.php';
header('Content-Type: application/json');

$user = authenticate(); // Public endpoint, but we still call authenticate() to get user info if available.

 $method = $_SERVER['REQUEST_METHOD'];
 $teacherAppraisals = new TeacherAppraisals(); // Instantiate the TeacherAppraisals class

switch ($method) {
    case 'POST':
        // POST case: Create a new appraisal record
        $input = json_decode(file_get_contents("php://input"), true);
        

        // Since there's no authentication, 'created_by' is not set.
        // The createAppraisal method has been modified to handle this.
        // A default status of 'draft' is recommended for public submissions.
        $input['status'] = 'draft';
        
        $input['created_by'] = $user['id']; // No authenticated user
        $result = $teacherAppraisals->createAppraisal($input);
        echo json_encode($result);
        break;

    case 'GET':
        // GET case: Return approved appraisals with filters
        $filters = $_GET;

        // --- SECURITY & DATA ACCESS CONTROL ---
        // Force the status to 'approved' to prevent unauthorized access to
        // draft, pending, or rejected appraisals.
        $filters['status'] = 'approved';

        // To get a single appraisal, pass its ID in the URL:
        // e.g., /api/v1/public-teacher-appraisals.php?id=123
        // The getAppraisals() function already supports this.
        
        $result = $teacherAppraisals->getAppraisals($filters);
        echo json_encode($result);
        break;

    case 'PUT':
        // PUT case: Update an existing appraisal record
        // WARNING: Anyone can update any record if they know its ID.
        $input = json_decode(file_get_contents("php://input"), true);
        $appraisalId = isset($input["id"]) ? $input["id"] : null;

        if (!$appraisalId) {
            http_response_code(400);
            echo json_encode(["error" => "Missing appraisal ID for update."]);
            break;
        }

        $result = $teacherAppraisals->updateAppraisal($appraisalId, $input);
        echo json_encode($result);
        break;

    // DELETE method is forbidden for this public endpoint
    case 'DELETE':
    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(["error" => "Method not allowed"]);
        break;
}