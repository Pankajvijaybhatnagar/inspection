<?php

require_once __DIR__ . '/../src/middlewares/cors.php';
require_once __DIR__ . '/../src/MasikPatrika.php'; // We need the MasikPatrika class

header('Content-Type: application/json');

// --- NO AUTHENTICATION ---
// We do not call authenticate() here. This is a public endpoint.

$method = $_SERVER['REQUEST_METHOD'];
$patrika = new MasikPatrika(); // Instantiate the MasikPatrika class

switch ($method) {
    case 'GET':
        // GET case: Return public patrika entries with filters
        $filters = $_GET;

        // --- SECURITY ---
        // Force the status to 'published' to prevent drafts/archived
        // entries from leaking to the public API.
        $filters['status'] = 'published';

        // Add a helper filter for the "latest" issue
        // e.g., /api/v1/public-masik-patrika/?latest=true
        if (isset($filters['latest']) && $filters['latest'] === 'true') {
            // Your getPatrika() class already sorts by issue_date DESC,
            // so we just set the limit to 1 to get the latest one.
            $filters['limit'] = 1;
        }
        unset($filters['latest']); // Clean up the helper filter

        // The getPatrika() function already supports all other
        // public-safe filters like:
        // - page
        // - limit
        // - search
        // - slug (for getting a single entry by its slug)
        
        $result = $patrika->getPatrika($filters);
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