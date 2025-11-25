<?php
// src/EmailPasswordAuth.php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Jwt.php';

class EmailPasswordAuth
{
    private $db;

    /**
     * Define the valid fields for the tables.
     * This helps in building dynamic and secure queries.
     */
    private $usersTableFields = [
        'id',
        'username',
        'google_id',
        'email',
        'password',
        'name',
        'role',
        'access',
        'avatar',
        'points',
        'is_verified',
        'created_at'
    ];

    private $detailsTableFields = [
        'user_id',
        'address',
        'city',
        'district',
        'state',
        'country',
        'phone',
        'dob'
    ];

    /**
     * Define fields that are safely updatable by a user.
     * Excludes sensitive fields like id, email, password, role, etc.
     * which should have their own specific methods.
     */
    private $updatableUserFields = [
        'username',
        'name',
        'access',
        'avatar',
        'points',
        'role',
        'is_verified',
        'email',
        'password'
    ];

    private $updatableUserDetailsFields = [
        'address',
        'city',
        'district',
        'state',
        'country',
        'phone',
        'dob'
    ];


    public function __construct()
    {
        $config = new Config();
        $this->db = $this->getDBFromConfig($config);

        JWT::init(); // Initialize JWT secrets
    }

    private function getDBFromConfig($config)
    {
        $reflection = new ReflectionClass($config);
        $property = $reflection->getProperty('db');
        $property->setAccessible(true);
        return $property->getValue($config);
    }

    public function login($email, $password)
    {
        if (empty($email) || empty($password)) {
            http_response_code(401);
            return [
                'status' => false,
                'message' => 'Email and password are required'
            ];
        }

        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            http_response_code(401);

            return [
                'status' => false,
                'message' => 'Invalid email or password'
            ];
        }

        //check user is verified
        if (!$user['is_verified']) {
            http_response_code(403);
            return [
                'status' => false,
                'message' => 'Email not verified. Please verify your email before logging in.'
            ];
        }

        // JWT Payload
        $tokenPayload = [
            'sub' => $user['id'],
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'] ?? 'user',
            'name' => $user['name'],
            'access' => $user['access'] ?? '',
        ];

        $accessToken = JWT::createAccessToken($tokenPayload);
        $refreshToken = JWT::createRefreshToken(['sub' => $user['id']]);

        $options = [
            'httpOnly' => true,
            'secure' => true,
        ];

        setcookie("access_token", $accessToken, $options);
        setcookie("refresh_token", $refreshToken, $options);
        return [
            'status' => true,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'avatar' => $user['avatar'],
                'role' => $user['role'] ?? 'user'
            ],
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken
        ];
    }

    public function register($name, $email, $password)
    {
        if (empty($name) || empty($email) || empty($password)) {
            http_response_code(400);
            return [
                'status' => false,
                'message' => 'name, email, and password are required.'
            ];
        }

        // Check if email already exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            http_response_code(409);
            return [
                'status' => false,
                'message' => 'Email is already registered.'
            ];
        }

        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Insert new user
        $stmt = $this->db->prepare("INSERT INTO users (name,username, email, password) VALUES (:name,:username, :email, :password)");
        $stmt->execute([
            'name' => $name,
            'username' => explode('@', $email)[0] . rand(1000, 9999),
            'email' => $email,
            'password' => $hashedPassword
        ]);

        http_response_code(201);
        return [
            'status' => true,
            'message' => 'User registered successfully.'
        ];
    }

    // --- NEW FUNCTIONS ADDED BELOW ---

    /**
     * Get users with dynamic filtering.
     *
     * @param array $filters Associative array of filters (e.g., ['role' => 'user', 'city' => 'New York'])
     * @return array
     */
    public function getUsers($filters = [])
    {
        // Pagination defaults
        $page = isset($filters['page']) && is_numeric($filters['page']) ? (int) $filters['page'] : 1;
        $limit = isset($filters['limit']) && is_numeric($filters['limit']) ? (int) $filters['limit'] : 10;
        $offset = ($page - 1) * $limit;

        // Base SQL for data
        $sql = "SELECT 
                    u.id, u.username, u.google_id, u.email, u.name, u.role, 
                    u.access, u.avatar, u.points, u.is_verified, u.created_at,
                    ud.address, ud.city, ud.district, ud.state, 
                    ud.country, ud.phone, ud.dob
                FROM users u 
                LEFT JOIN user_details ud ON u.id = ud.user_id";

        // Base SQL for counting filtered records
        $countSql = "SELECT COUNT(DISTINCT u.id) 
                     FROM users u
                     LEFT JOIN user_details ud ON u.id = ud.user_id";

        // SQL for absolute total users (no filters)
        $totalUsersSql = "SELECT COUNT(id) FROM users";

        $whereParts = [];
        $params = [];

        $validAllFields = array_merge($this->usersTableFields, $this->detailsTableFields);

        foreach ($filters as $field => $value) {
            // Only add to WHERE clause if it's a valid field, value is not null,
            // and it's not a pagination field.
            if (in_array($field, $validAllFields) && $value !== null) {
                // Use table alias 'u' or 'ud' if field is not unique
                $alias = in_array($field, $this->detailsTableFields) ? 'ud' : 'u';

                // Handle 'id' ambiguity
                if ($field === 'id')
                    $alias = 'u';
                if ($field === 'user_id')
                    $alias = 'ud';

                $whereParts[] = "$alias.$field = :$field";
                $params[$field] = $value;
            }
        }

        if (!empty($whereParts)) {
            $whereClause = " WHERE " . implode(' AND ', $whereParts);
            $sql .= $whereClause;
            $countSql .= $whereClause; // Add filter to filtered count query
        }

        // Add pagination to the main data query
        $sql .= " ORDER BY u.id DESC LIMIT :limit OFFSET :offset";

        try {
            // 1. Get the *absolute* total number of users in the DB
            $stmtTotal = $this->db->query($totalUsersSql);
            $totalUsers = $stmtTotal->fetchColumn();

            // 2. Get the total count of *filtered* users
            $stmtFilteredCount = $this->db->prepare($countSql);
            $stmtFilteredCount->execute($params); // Use the filter params
            $filteredCount = $stmtFilteredCount->fetchColumn();

            // 3. Get the paginated data
            $stmt = $this->db->prepare($sql);

            // Bind filter params
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }

            // Bind pagination params
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);

            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // This is the count for the *current page*
            $fetchedCount = count($users);

            return [
                'status' => true,
                'total_users' => (int) $totalUsers,      // Absolute total users in DB
                'count' => (int) $filteredCount,       // Total users matching filter
                'fetched_count' => $fetchedCount,      // Users returned on this page
                'page' => (int) $page,
                'limit' => (int) $limit,
                'total_pages' => (int) ceil($filteredCount / $limit), // Pages based on filtered count
                'data' => $users
            ];

        } catch (Exception $e) {
            http_response_code(500);
            return [
                'status' => false,
                'message' => 'Error fetching users: ' . $e->getMessage()
            ];
        }
    }



    /**
     * Update a user's data dynamically.
     *
     * @param int $userId The ID of the user to update.
     * @param array $data Associative array of data to update (e.g., ['name' => 'New Name', 'city' => 'New City'])
     * @return array
     */
    public function updateUser($userId, $data)
    {
        $userSetParts = [];
        $userDetailsSetParts = [];
        $userParams = [];
        $userDetailsParams = [];

        // Loop through the provided data and sort it into an update for
        // 'users' table or 'user_details' table
        foreach ($data as $field => $value) {
            // We check against the "updatable" lists for security
            if (in_array($field, $this->updatableUserFields)) {
                $userSetParts[] = "$field = :$field";
                $userParams[$field] = $value;

                // If the field is 'access' and the value is an array, encode it to JSON.
                if ($field === 'access' && is_array($value)) {
                    $userParams[$field] = json_encode($value);
                }

            } elseif (in_array($field, $this->updatableUserDetailsFields)) {
                $userDetailsSetParts[] = "$field = :$field";
                $userDetailsParams[$field] = $value;
            }
        }

        if (empty($userSetParts) && empty($userDetailsSetParts)) {
            http_response_code(400);
            return [
                'status' => false,
                'message' => 'No valid fields provided for update.'
            ];
        }

        $this->db->beginTransaction();

        try {
            // 1. Update 'users' table if there is data for it
            if (!empty($userSetParts)) {
                $sqlUser = "UPDATE users SET " . implode(', ', $userSetParts) . " WHERE id = :user_id";
                $userParams['user_id'] = $userId;

                $stmtUser = $this->db->prepare($sqlUser);
                $stmtUser->execute($userParams);
            }

            // 2. Update 'user_details' table if there is data for it
            if (!empty($userDetailsSetParts)) {
                // We must check if a row exists first, because the register function
                // does not create one.
                $stmtCheck = $this->db->prepare("SELECT user_id FROM user_details WHERE user_id = :user_id");
                $stmtCheck->execute(['user_id' => $userId]);

                $userDetailsParams['user_id'] = $userId;

                if ($stmtCheck->fetch()) {
                    // Row exists, so UPDATE
                    $sqlDetails = "UPDATE user_details SET " . implode(', ', $userDetailsSetParts) . " WHERE user_id = :user_id";
                    $stmtDetails = $this->db->prepare($sqlDetails);
                    $stmtDetails->execute($userDetailsParams);
                } else {
                    // Row does not exist, so INSERT
                    $fields = implode(', ', array_keys($userDetailsParams));
                    $placeholders = ':' . implode(', :', array_keys($userDetailsParams));

                    $sqlDetails = "INSERT INTO user_details ($fields) VALUES ($placeholders)";
                    $stmtDetails = $this->db->prepare($sqlDetails);
                    $stmtDetails->execute($userDetailsParams);
                }
            }

            $this->db->commit();

            return [
                'status' => true,
                'message' => 'User updated successfully.'
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            http_response_code(500);
            return [
                'status' => false,
                'message' => 'Update failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete a user.
     * Relies on ON DELETE CASCADE set in the database schema.
     *
     * @param int $userId The ID of the user to delete.
     * @return array
     */
    public function deleteUser($userId)
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = :user_id");
            $stmt->execute(['user_id' => $userId]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                $this->db->rollBack();
                return [
                    'status' => false,
                    'message' => 'User not found.'
                ];
            }

            $this->db->commit();
            return [
                'status' => true,
                'message' => 'User deleted successfully.'
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            http_response_code(500);
            return [
                'status' => false,
                'message' => 'Delete failed: ' . $e->getMessage()
            ];
        }
    }
}