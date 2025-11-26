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

        // --- AUTHORIZATION ---
        // Only users (teachers) can create their own appraisals.
        if ($user['role'] !== 'user') {
            http_response_code(403);
            echo json_encode(['status' => false, 'message' => 'Forbidden: Only users can create appraisals.']);
            exit;
        }

        // --- SECURITY ---
        // Force 'created_by' to be the ID of the authenticated user.
        $input['created_by'] = $user['id'];

        $result = $teacherAppraisals->createAppraisal($input);
        echo json_encode($result);
        break;

    case 'GET':
        // GET case: Return appraisal records with filters
        $filters = $_GET;

        // --- AUTHORIZATION ---
        // If the user is a 'user' (teacher), force the filter to only show their own appraisals.
        if ($user['role'] === 'user') {
            $filters['created_by'] = $user['id'];
        }
        // Admins and Superadmins can see all appraisals based on the filters they provide.

        $result = $teacherAppraisals->getAppraisals($filters);
        echo json_encode($result);
        break;

    case 'PUT':
        // PUT case: Update an existing appraisal record
        $input = json_decode(file_get_contents("php://input"), true);
        $appraisalId = $input["id"] ?? null;

        if (!$appraisalId) {
            http_response_code(400);
            echo json_encode(['status' => false, 'message' => 'Appraisal ID is required.']);
            exit;
        }

        // --- AUTHORIZATION ---
        if ($user['role'] === 'user') {
            // A user can only update their own appraisal, and only if it's a draft.
            $appraisalData = $teacherAppraisals->getAppraisals(['id' => $appraisalId, 'created_by' => $user['id']]);

            if (empty($appraisalData['data'])) {
                http_response_code(403); // Forbidden, as it's not their appraisal
                echo json_encode(['status' => false, 'message' => 'Forbidden: You can only update your own appraisals.']);
                exit;
            }

            if ($appraisalData['data'][0]['status'] !== 'draft') {
                http_response_code(403);
                echo json_encode(['status' => false, 'message' => 'Forbidden: This appraisal has been submitted and can no longer be edited.']);
                exit;
            }
        }
        // Admins and Superadmins can update any appraisal.

        $result = $teacherAppraisals->updateAppraisal($appraisalId, $input);
        echo json_encode($result);
        break;

    case 'DELETE':
        // DELETE case: Delete an appraisal record
        $appraisalId = $_GET['id'] ?? null; // Get ID from query param for DELETE

        // --- AUTHORIZATION ---
        // Only a superadmin can delete records.
        if ($user['role'] !== 'superadmin') {
            http_response_code(403);
            echo json_encode(['status' => false, 'message' => 'Forbidden: You do not have permission to delete appraisals.']);
            exit;
        }

        $result = $teacherAppraisals->deleteAppraisal($appraisalId);
        echo json_encode($result);
        break;

    default:
        http_response_code(405);
        echo json_encode(["status" => false, "message" => "Method Not Allowed"]);
        break;
}