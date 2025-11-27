<?php
// /v1/admin/teacher-appraisals.php

require_once __DIR__ . '/../../src/middlewares/cors.php';
require_once __DIR__ . '/../../src/TeacherAppraisals.php'; // Require the TeacherAppraisals class
require_once __DIR__ . '/../../src/EmailPasswordAuth.php';
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
        // Only teachers can create their own appraisals.
        if ($user['role'] !== 'teacher') {
            http_response_code(403);
            echo json_encode(['status' => false, 'message' => 'Forbidden: Only teachers can create appraisals.']);
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
        // A teacher can only see their own appraisals.
        if ($user['role'] === 'teacher') {
            $filters['created_by'] = $user['id'];
        } 
        // A school can only see appraisals from their own school.
        elseif ($user['role'] === 'school') {
            // We need to get the school name of the logged-in user.
            $auth = new EmailPasswordAuth();
            $schoolUserData = $auth->getUsers(['id' => $user['id']]);
            if (!empty($schoolUserData['data'][0]['school_name'])) {
                $filters['school_name'] = $schoolUserData['data'][0]['school_name'];
            }
        }

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
        if ($user['role'] === 'teacher') {
            // A user can only update their own appraisal, and only if it's a draft.
            $appraisalData = $teacherAppraisals->getAppraisals(['id' => $appraisalId, 'created_by' => $user['id']]);

            if (empty($appraisalData['data'])) {
                http_response_code(404); // Not Found, as they shouldn't even know it exists.
                echo json_encode(['status' => false, 'message' => 'Forbidden: You can only update your own appraisals.']);
                exit;
            }

            if ($appraisalData['data'][0]['status'] !== 'draft') {
                http_response_code(403);
                echo json_encode(['status' => false, 'message' => 'Forbidden: This appraisal has been submitted and can no longer be edited.']);
                exit;
            }
        }
        elseif ($user['role'] === 'school') {
            // A school can only update appraisals from their own school.
            $auth = new EmailPasswordAuth();
            $schoolUserData = $auth->getUsers(['id' => $user['id']]);
            $schoolName = $schoolUserData['data'][0]['school_name'] ?? null;

            if (!$schoolName) {
                http_response_code(403);
                echo json_encode(['status' => false, 'message' => 'Forbidden: Your account is not associated with a school.']);
                exit;
            }

            $appraisalData = $teacherAppraisals->getAppraisals(['id' => $appraisalId, 'school_name' => $schoolName]);
            if (empty($appraisalData['data'])) {
                http_response_code(404);
                echo json_encode(['status' => false, 'message' => 'Appraisal not found in your school.']);
                exit;
            }
        }

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