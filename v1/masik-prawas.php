<?php

require_once __DIR__ . '/../src/middlewares/cors.php';
require_once __DIR__ . '/../src/MasikPrawas.php'; // We need the MasikPrawas class

header('Content-Type: application/json');

// --- NO AUTHENTICATION ---
// We do not call authenticate() here. This is a public endpoint.

$method = $_SERVER['REQUEST_METHOD'];
$prawas = new MasikPrawas(); // Instantiate the MasikPrawas class

switch ($method) {
    case 'GET':
        // GET case: Return public Masik Prawas entries with filters
        $filters = $_GET;

        // --- SECURITY NOTE ---
        // Your 'masik_prawas' table (from our previous conversation)
        // does not have a 'status' field (e.g., "published", "draft").
        // Therefore, all entries are considered public by this endpoint.
        
        // If you need to hide drafts, you must first add a 'status'
        // column to your 'masik_prawas' table and then update the
        // 'MasikPrawas.php' class to filter by it. You would then add:
        // $filters['status'] = 'published';
        // (Just like your events example)

        // The getPrawas() function already supports public-safe filters:
        // - page
        // - limit
        // - search
        
        $result = $prawas->getPrawas($filters);
        echo json_encode($result);
        break;

    // All other methods are forbidden for this public endpoint
    case 'POST':
    case 'PUT':
    case 'DELETE':
    default:
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
        break;
}