<?php
require_once __DIR__ . '/../../src/middlewares/cors.php';
require_once __DIR__ . '/../../src/Inquiries.php';
require_once __DIR__ . '/../../src/middlewares/auth.php';

header('Content-Type: application/json');

$user = authenticate();
$inquiries = new Inquiries();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $filters = $_GET;
        $result = $inquiries->getInquiries($filters);
        echo json_encode($result);
        break;

    case 'PUT':
        $input = json_decode(file_get_contents("php://input"), true);
        $inquiryId = isset($input["id"]) ? $input["id"] : null;
        if (empty($input['type'])) {
            http_response_code(400);
            echo json_encode(['status'=>false,'message'=>'Type is required.']);
            exit;
        }
        $input['updated_by'] = $user['id'];
        $result = $inquiries->updateInquiry($inquiryId, $input);
        echo json_encode($result);
        break;

    case 'DELETE':
        $input = json_decode(file_get_contents("php://input"), true);
        $inquiryId = isset($input["id"]) ? $input["id"] : null;
        $result = $inquiries->deleteInquiry($inquiryId);
        echo json_encode($result);
        break;

    default:
        http_response_code(405);
        echo json_encode(["error"=>"Method not allowed"]);
        break;
}