<?php

require_once __DIR__ . '/../src/middlewares/cors.php';
require_once __DIR__ . '/../src/Events.php'; // We still need the Events class

header('Content-Type: application/json');

// --- NO AUTHENTICATION ---
// We do not call authenticate() here. This is a public endpoint.

$method = $_SERVER['REQUEST_METHOD'];
$events = new Events(); // Instantiate the Events class

switch ($method) {
    case 'GET':
        // GET case: Return public events with filters
        $filters = $_GET;

        // --- SECURITY ---
        // Force the status to 'published' to prevent drafts from leaking
        $filters['status'] = 'published';

        // Add a helper filter for "upcoming" events
        // e.g., /api/v1/public-events/?upcoming=true
        if (isset($filters['upcoming']) && $filters['upcoming'] === 'true') {
            $filters['start_date_after'] = date('Y-m-d'); // Set to today
        }
        unset($filters['upcoming']); // Clean up the helper filter

        // The getEvents() function already supports all other
        // public-safe filters like:
        // - page
        // - limit
        // - search
        // - category_id
        // - category_slug
        // - slug (for getting a single event by its slug)
        
        $result = $events->getEvents($filters);
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