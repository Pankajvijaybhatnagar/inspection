<?php
// v1/admin/upload.php

require_once __DIR__ . '/../../src/middlewares/cors.php';
require_once __DIR__ . '/../../src/middlewares/auth.php';
require_once __DIR__ . '/../../src/upload/FileUpload.php';

header('Content-Type: application/json');

// Authenticate admin
$user = authenticate();

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}

// Read POST data
$folderData = isset($_POST['folder']) ? trim($_POST['folder']) : null;
$oldFile = isset($_POST['old_file']) ? trim($_POST['old_file']) : null;

// Validate folder
if (empty($folderData)) {
    http_response_code(400);
    echo json_encode(["status" => false, "message" => "Missing 'folder' in request."]);
    exit;
}

// Allowed folders for admin uploads
$allowedFolders = [
    "events",
    "blogs",
    "patrika",
    "masikprawas",
    "others"
];

// Folder validation
if (!in_array($folderData, $allowedFolders)) {
    http_response_code(400);
    echo json_encode(["status" => false, "message" => "Invalid folder name."]);
    exit;
}

// Ensure file exists
if (!isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(["status" => false, "message" => "No file uploaded."]);
    exit;
}

// ------------------------------
// 1. FIRST TRY TO UPLOAD NEW FILE
// ------------------------------
$uploadFolder = $folderData;
$uploadResult = upload($_FILES['file'], $uploadFolder);

// If upload failed → DO NOT delete old file
if (!$uploadResult['status']) {
    echo json_encode([
        "status" => false,
        "message" => $uploadResult['message']
    ]);
    exit;
}

// ------------------------------
// 2. NEW FILE SUCCESS → DELETE OLD FILE (IGNORE ERRORS)
// ------------------------------
if (!empty($oldFile)) {
    removeFile($oldFile, $uploadFolder);
}

// ------------------------------
// 3. RETURN SUCCESS RESPONSE
// ------------------------------
echo json_encode([
    "status" => true,
    "message" => "File uploaded successfully.",
    "uploaded_file" => $uploadResult['filename'],
    "folder" => $folderData
]);
exit;
