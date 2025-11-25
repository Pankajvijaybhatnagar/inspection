<?php
// /v1/admin/events.php

require_once __DIR__ . '/../../src/middlewares/cors.php';
require_once __DIR__ . '/../../src/Events.php'; // Changed to Events.php
require_once __DIR__ . '/../../src/middlewares/auth.php';

header('Content-Type: application/json');

$user = authenticate(); // Protects all event endpoints

$method = $_SERVER['REQUEST_METHOD'];
$events = new Events(); // Instantiate the Events class

switch ($method) {
    case 'POST':
        // POST case: Create a new event
        $input = json_decode(file_get_contents("php://input"), true);
        $input['created_by'] = $user['id']; 
        // Separate category_ids from the main data, as expected by createEvent()
        $categoryIds = isset($input["category_ids"]) ? $input["category_ids"] : [];
        unset($input["category_ids"]); // Remove it from the main data array

        $result = $events->createEvent($input, $categoryIds);
        echo json_encode($result);
        break;

    case 'GET':
        // GET case: Return all events with filters
        $filters = $_GET;
        $result = $events->getEvents($filters);
        echo json_encode($result);
        break;

    case 'PUT':
        // PUT case: Update event data
        $input = json_decode(file_get_contents("php://input"), true);
        $eventId = isset($input["id"]) ? $input["id"] : null;

        // updateEvent() is designed to handle category_ids from within the $input array
        $result = $events->updateEvent($eventId, $input);
        echo json_encode($result);
        break;

    case 'DELETE':
        // DELETE case: Delete an event
        $input = json_decode(file_get_contents("php://input"), true);
        $eventId = isset($input["id"]) ? $input["id"] : null;

        $result = $events->deleteEvent($eventId);
        echo json_encode($result);
        break;

    default:
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
        break;
}