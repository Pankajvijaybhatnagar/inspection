<?php

require_once __DIR__ . '/../src/middlewares/cors.php';
// NO authentication is required for this public route

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// This endpoint is GET ONLY
if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

// --- Setup Paths ---
$baseUploadDir = __DIR__ . '/../uploads/'; // Root of all uploads
$galleryBasePath = 'gallery'; // The main "gallery" folder
$fullGalleryPath = $baseUploadDir . $galleryBasePath;
$baseURL = "https://" . $_SERVER['HTTP_HOST'] . "/uploads/gallery/";

// --- Helper function to sanitize folder names ---
function sanitize_filename($string) {
    // Remove ".." to prevent "directory traversal"
    $string = str_replace('..', '', $string);
    // Remove other problematic characters
    return preg_replace("/[^a-zA-Z0-9_-]/", "_", $string);
}

// --- Pagination Parameters ---
$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) && $_GET['limit'] > 0 ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;

try {
    $folder = isset($_GET['folder']) ? sanitize_filename($_GET['folder']) : null;

    if ($folder === null) {
        // --- 1. List all folders (Paginated) ---
        // (This part is unchanged)
        $allDirectories = glob($fullGalleryPath . '/*', GLOB_ONLYDIR);
        $totalItems = count($allDirectories);
        $totalPages = (int)ceil($totalItems / $limit);

        // Get the "page" of directories
        $paginatedDirs = array_slice($allDirectories, $offset, $limit);
        
        $folderList = [];
        foreach ($paginatedDirs as $dir) {
            $folderName = basename($dir);
            
            // Find the first image in the folder to use as a thumbnail
            $firstImage = glob($dir . '/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
            $thumbnailUrl = null;
            if (!empty($firstImage)) {
                $thumbnailUrl = $baseURL . $folderName . '/' . basename($firstImage[0]);
            }

            $folderList[] = [
                'name' => $folderName,
                'thumbnail' => $thumbnailUrl
            ];
        }

        echo json_encode([
            'status' => true,
            'pagination' => [
                'total_items' => $totalItems,
                'total_pages' => $totalPages,
                'current_page' => $page,
                'limit' => $limit
            ],
            'folders' => $folderList
        ]);

    } else {
        // --- 2. List all files in a folder (Paginated) ---
        // (This part is MODIFIED)
        $folderPath = $fullGalleryPath . '/' . $folder;
        if (!is_dir($folderPath)) {
            http_response_code(404);
            echo json_encode(['status' => false, 'message' => 'Folder not found.']);
            exit;
        }
        
        // Get all files
        $allFiles = glob($folderPath . '/*.{jpg,jpeg,png,gif,pdf,doc,docx}', GLOB_BRACE);
        $totalItems = count($allFiles);
        $totalPages = (int)ceil($totalItems / $limit);
        
        // Get the "page" of files
        $paginatedFiles = array_slice($allFiles, $offset, $limit);
        
        $fileList = [];
        foreach ($paginatedFiles as $file) {
            $filename = basename($file);
            $width = null;
            $height = null;

            // --- START OF MODIFICATION ---
            // Try to get image dimensions.
            // We use '@' to suppress warnings for non-image files (like pdf, docx)
            $imageInfo = @getimagesize($file);

            if ($imageInfo !== false) {
                // It's an image, so we add width and height
                $width = $imageInfo[0];
                $height = $imageInfo[1];
            }
            // --- END OF MODIFICATION ---

            $fileList[] = [
                'filename' => $filename,
                'url' => $baseURL . $folder . '/' . $filename,
                'width' => $width,   // <-- ADDED
                'height' => $height  // <-- ADDED
            ];
        }

        echo json_encode([
            'status' => true,
            'folder' => $folder,
            'pagination' => [
                'total_items' => $totalItems,
                'total_pages' => $totalPages,
                'current_page' => $page,
                'limit' => $limit
            ],
            'files' => $fileList
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}