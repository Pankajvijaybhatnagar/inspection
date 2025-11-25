<?php
//    src/upload/FileUpload.php


function upload($file, $uploadFolder = '')
{
    $uploadDir = __DIR__ . "/../../uploads/" . $uploadFolder . "/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Check if file was uploaded without errors
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ["status" => false, "message" => "File upload error."];
    }

    // Allowed file extensions (you can adjust as needed)
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];

    // Get file info
    $originalName = basename($file['name']);
    $fileTmpPath = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileType = mime_content_type($fileTmpPath);

    // Extract extension and sanitize filename
    $fileExt = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($fileExt, $allowedExtensions)) {
        return ["status" => false, "message" => "Invalid file type."];
    }

    // Sanitize file name (remove unwanted characters)
    $baseName = preg_replace("/[^a-zA-Z0-9_-]/", "_", pathinfo($originalName, PATHINFO_FILENAME));

    // Generate unique file name
    $newFileName = $baseName . "_" . uniqid() . "." . $fileExt;

    $destPath = $uploadDir . $newFileName;

    // Move file to destination
    if (move_uploaded_file($fileTmpPath, $destPath)) {
        return ["status" => true, "message" => "File uploaded successfully.", "filename" => $newFileName];
    } else {
        return ["status" => false, "message" => "Failed to move uploaded file."];
    }


}




function removeFile($filename, $uploadFolder = '')
{
    $uploadDir = __DIR__ . "/../../uploads/" . $uploadFolder . "/";


    $filePath = $uploadDir . $filename;

    if (file_exists($filePath)) {
        if (unlink($filePath)) {
            return ["status" => true, "message" => "File removed successfully."];
        } else {
            return ["status" => false, "message" => "Failed to remove file."];
        }
    } else {
        return ["status" => false, "message" => "File does not exist."];
    }
}
