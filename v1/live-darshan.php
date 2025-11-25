<?php
// /api/v1/public-darshan/index.php (example path)

require_once __DIR__ . '/../src/middlewares/cors.php';
require_once __DIR__ . '/../src/LiveDarshan.php'; // We need the LiveDarshan class

header('Content-Type: application/json');

// --- NO AUTHENTICATION ---
// We do not call authenticate() here. This is a public endpoint.

$method = $_SERVER['REQUEST_METHOD'];
$darshan = new LiveDarshan(); // Instantiate the LiveDarshan class

switch ($method) {
    case 'GET':
        // GET case: Return public darshans with filters
        $filters = $_GET;

        // --- SECURITY ---
        // By default, force the status to 'live'
        $filters['status'] = 'live';

        // Add a helper filter for "upcoming" streams
        // e.g., /api/v1/public-darshan/?show=upcoming
        if (isset($filters['show']) && $filters['show'] === 'upcoming') {
            $filters['status'] = 'upcoming'; // Switch filter to upcoming
        }
        unset($filters['show']); // Clean up the helper filter

        // The 'archived' status is never exposed because it's not a default
        // and there is no helper filter to access it.

        // The getDarshans() function already supports all other
        // public-safe filters like:
        // - page
        // - limit
        // - search
        // - slug (for getting a single darshan by its slug)

        $result = $darshan->getDarshans($filters);
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
