<?php

require_once __DIR__ . '/../../src/middlewares/cors.php';
require_once __DIR__ . '/../../src/Blogs.php'; // Changed to Blogs.php
require_once __DIR__ . '/../../src/middlewares/auth.php';

header('Content-Type: application/json');

$user = authenticate(); // Protects all blog endpoints
$blogs = new Blogs(); // Instantiate the Blogs class

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        // POST case: Create a new blog post
        $input = json_decode(file_get_contents("php://input"), true);
        
        $categoryIds = isset($input["category_ids"]) ? $input["category_ids"] : [];
        unset($input["category_ids"]); 

        // Set the author from the authenticated user
        $input['created_by'] = $user['id'];

        $result = $blogs->createBlog($input, $categoryIds);
        echo json_encode($result);
        break;

    case 'GET':
        // GET case: Return all blog posts with filters
        $filters = $_GET;
        $result = $blogs->getBlogs($filters);
        echo json_encode($result);
        break;

    case 'PUT':
        // PUT case: Update blog post data
        $input = json_decode(file_get_contents("php://input"), true);
        $blogId = isset($input["id"]) ? $input["id"] : null;

        // Add logic here to check if $user['id'] is the author or an admin
        // For now, we allow the update to proceed
        
        $result = $blogs->updateBlog($blogId, $input);
        echo json_encode($result);
        break;

    case 'DELETE':
        // DELETE case: Delete a blog post
        $input = json_decode(file_get_contents("php://input"), true);
        $blogId = isset($input["id"]) ? $input["id"] : null;
        
        // Add logic here to check if $user['id'] is the author or an admin

        $result = $blogs->deleteBlog($blogId);
        echo json_encode($result);
        break;

    default:
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
        break;
}