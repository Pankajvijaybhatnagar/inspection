<?php
// cors.php

// Allow from any origin (change this to a specific domain in production)
header("Access-Control-Allow-Origin: *");

// Allow credentials (cookies, HTTP auth)
header("Access-Control-Allow-Credentials: true");

// Allow the following methods
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

// Allow the following headers
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// If it's an OPTIONS request, return early with a 200 OK
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
