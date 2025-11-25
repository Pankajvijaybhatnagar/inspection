<?php

require_once __DIR__ . '/config.php';            // Load Config class
require_once __DIR__ . '/../vendor/autoload.php'; // Google SDK & JWT
require_once __DIR__ . '/Jwt.php';               // JWT class

class GoogleAuth
{
    private $client;
    private $db;

    public function __construct()
    {
        $config = new Config(); // Load .env and DB
        $this->db = $this->getDBFromConfig($config);

        JWT::init(); // Initialize JWT secrets from env

        $this->client = new Google_Client();
        $this->client->setClientId(getenv('GOOGLE_CLIENT_ID'));
    }

    private function getDBFromConfig($config)
    {
        $reflection = new ReflectionClass($config);
        $property = $reflection->getProperty('db');
        $property->setAccessible(true);
        return $property->getValue($config);
    }

    public function verifyAndLogin($idToken)
    {
        $payload = $this->client->verifyIdToken($idToken);

        if (!$payload) {
            return [
                'status' => false,
                'message' => 'Invalid ID token',
            ];
        }

        $googleId = $payload['sub'];
        $name     = $payload['name'];
        $email    = $payload['email'];
        $picture  = $payload['picture'] ?? null;

        // Check if user exists
        $stmt = $this->db->prepare("SELECT * FROM users WHERE google_id = :google_id OR email = :email");
        $stmt->execute(['google_id' => $googleId, 'email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // Create new user
            $username = explode('@', $email)[0];
            $stmt = $this->db->prepare("INSERT INTO users (google_id, name, email, avatar, username, is_verified) VALUES (:google_id, :name, :email, :avatar, :username, :is_verified)");
            $stmt->execute([
                'google_id'   => $googleId,
                'name'        => $name,
                'email'       => $email,
                'avatar'      => $picture,
                'username'    => $username,
                'is_verified' => true
            ]);
        } else {
            // User exists, check if they are verified
            if (!$user['is_verified']) {
                $updateStmt = $this->db->prepare("UPDATE users SET is_verified = :is_verified WHERE id = :id");
                $updateStmt->execute(['is_verified' => true, 'id' => $user['id']]);
            }
        }

        // Fetch user again after insert (or original)
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // JWT Payload (can include more fields if needed)
        $tokenPayload = [
            'sub'   => $user['id'],
            'id'   => $user['id'],
            'email' => $user['email'],
            'role'  => $user['role'] ?? 'user',
            'name'  => $user['name']
        ];

        $accessToken  = JWT::createAccessToken($tokenPayload);
        $refreshToken = JWT::createRefreshToken(['sub' => $user['id']]);

        return [
            'status' => true,
            'user' => [
                'id'      => $user['id'],
                'name'    => $user['name'],
                'email'   => $user['email'],
                'avatar'  => $user['avatar'],
                'role'    => $user['role'] ?? 'user'
            ],
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken
        ];
    }
}