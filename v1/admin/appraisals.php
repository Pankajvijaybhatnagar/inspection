<?php
// /v1/admin/teacher-appraisals.php

require_once __DIR__ . '/../../src/middlewares/cors.php';
require_once __DIR__ . '/../../src/TeacherAppraisals.php'; // Require the TeacherAppraisals class
require_once __DIR__ . '/../../src/middlewares/auth.php';

header('Content-Type: application/json');

// --- AUTHENTICATION ---
// Protects all appraisal endpoints. If authentication fails, the script will stop here.
 $user = authenticate();

 $method = $_SERVER['REQUEST_METHOD'];
 $teacherAppraisals = new TeacherAppraisals(); // Instantiate the TeacherAppraisals class

switch ($method) {
    case 'POST':
        // POST case: Create a new appraisal record
        $input = json_decode(file_get_contents("php://input"), true);
        
        // --- SECURITY ---
        // Set the creator of this appraisal to the currently authenticated user
        $input['created_by'] = $user['id']; 
        
        $result = $teacherAppraisals->createAppraisal($input);
        echo json_encode($result);
        break;

    case 'GET':
        // GET case: Return appraisal records with filters
        $filters = $_GET;
        $result = $teacherAppraisals->getAppraisals($filters);
        echo json_encode($result);
        break;

    case 'PUT':
        // PUT case: Update an existing appraisal record
        $input = json_decode(file_get_contents("php://input"), true);
        $appraisalId = isset($input["id"]) ? $input["id"] : null;

        $result = $teacherAppraisals->updateAppraisal($appraisalId, $input);
        echo json_encode($result);
        break;

    case 'DELETE':
        // DELETE case: Delete an appraisal record
        $input = json_decode(file_get_contents("php://input"), true);
        $appraisalId = isset($input["id"]) ? $input["id"] : null;

        $result = $teacherAppraisals->deleteAppraisal($appraisalId);
        echo json_encode($result);
        break;

    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(["error" => "Method not allowed"]);
        break;
}