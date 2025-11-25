<?php

require_once __DIR__ . '/../src/middlewares/cors.php';
require_once __DIR__ . '/../src/Schools.php';

header('Content-Type: application/json');

// --- NO AUTH ---
// This is a fully public endpoint.

$method = $_SERVER['REQUEST_METHOD'];
$schools = new Schools();

switch ($method) {

    // ---------------------------------------------------------
    // 1️⃣ PUBLIC GET — SCHOOL LIST
    // ---------------------------------------------------------
    case 'GET':

        $filters = $_GET;

        // Force only "active" schools to appear in public list
        $filters['status'] = 'active';

        // Helper: For dropdown list → /v1/public/schools.php?dropdown=true
        if (isset($filters['dropdown']) && $filters['dropdown'] === 'true') {
            echo json_encode($schools->getActiveSchools());
            exit;
        }
        unset($filters['dropdown']);

        // Helper: public search → /v1/public/schools.php?search=delhi
        // Already handled by getSchools()

        $result = $schools->getSchools($filters);
        echo json_encode($result);
        break;

    // ---------------------------------------------------------
    // ❌ DISALLOW OTHER METHODS
    // ---------------------------------------------------------
    case 'POST':
    case 'PUT':
    case 'DELETE':
    default:
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
        break;
}

