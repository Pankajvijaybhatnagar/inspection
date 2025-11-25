<?php
// src/middlewares/auth.php

require_once __DIR__ . '/../Jwt.php'; // Adjusted path for your structure

// Initialize JWT system
JWT::init();

function authenticate()
{
    header('Content-Type: application/json');

    $token = $_COOKIE['access_token'] ?? null;

    if (!$token) {

        // Get Authorization header
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            http_response_code(401);
            echo json_encode(['error' => 'Authorization header missing or malformed']);
            exit;
        }

        // Extract token from "Bearer <token>"
        $token = trim(str_replace('Bearer', '', $authHeader));

    }
    // Validate token
    $user = JWT::verifyAccessToken($token);

    if (!$user || !isset($user->sub)) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid or expired token']);
        exit;
    }

    // Fetch user from database
    require_once __DIR__ . '/../config.php';
    $config = new Config();
    $reflection = new ReflectionClass($config);
    $property = $reflection->getProperty('db');
    $property->setAccessible(true);
    $db = $property->getValue($config);
    $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute(['id' => $user->sub]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    // Inject user into global request for downstream usage
    $_POST['user'] = (array) $user;
    $_REQUEST['user'] = (array) $user;

    // Optional: return the user object
    return $user;
}

function isAdmin()
{
    if (!isset($_POST['user']) || !isset($_POST['user']['role']) || $_POST['user']['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied: Admins only']);
        exit;
    }
}
