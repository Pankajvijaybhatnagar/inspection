<?php

require_once __DIR__ . '/../../src/middlewares/cors.php';
require_once __DIR__ . '/../../src/EmailPasswordAuth.php' ;
require_once __DIR__ . '/../../src/middlewares/auth.php';

header('Content-Type: application/json');

$user = authenticate();
//print_r($user);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method){
    case 'POST':
        // POST case: Create a new user data is name email password
        $input = json_decode(file_get_contents("php://input"), true);
        $email = isset($input["email"]) ? $input["email"] : null;
        $password = isset($input["password"]) ? $input["password"] : null;
        $name = isset($input["name"]) ? $input["name"] : null;

        $auth = new EmailPasswordAuth();
        $result = $auth->register($name, $email, $password);
        echo json_encode($result);
        break;
    
    case 'GET':
        // GET case: Return all users
        $auth = new EmailPasswordAuth();
        $filters = $_GET;

        $result = $auth->getUsers($filters);
        echo json_encode($result);
        break;

    case 'PUT':
        // PUT case: Update user data
        $input = json_decode(file_get_contents("php://input"), true);
        $userId = isset($input["id"]) ? $input["id"] : null;

        $auth = new EmailPasswordAuth();
        $result = $auth->updateUser($userId, $input);
        echo json_encode($result);
        break;

    case 'DELETE':
        // DELETE case: Delete a user
        $input = json_decode(file_get_contents("php://input"), true);
        $userId = isset($input["id"]) ? $input["id"] : null;

        $auth = new EmailPasswordAuth();
        $result = $auth->deleteUser($userId);
        echo json_encode($result);
        break;
        

    default:
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);;
        break;
}