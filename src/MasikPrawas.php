<?php
// src/MasikPrawas.php

require_once __DIR__ . '/config.php';

class MasikPrawas
{
    private $db;

    // Fields from your 'masik_prawas' table
    private $prawasTableFields = [
        'id',
        'user_id',
        'title',
        'description',
        'cover_image_url',
        'created_at',
        'updated_at'
    ];

    // Fields that can be set during creation or update
    private $updatablePrawasFields = [
        'user_id',
        'title',
        'description',
        'cover_image_url',
    ];

    public function __construct()
    {
        $config = new Config();
        $this->db = $this->getDBFromConfig($config);
    }

    // Copied from your Events class to get the private $db property
    private function getDBFromConfig($config)
    {
        $reflection = new ReflectionClass($config);
        $property = $reflection->getProperty('db');
        $property->setAccessible(true);
        return $property->getValue($config);
    }

    /**
     * Create a new Masik Prawas entry
     */
    public function createPrawas($data)
    {
        $setParts = [];
        $params = [];

        // Validation for required fields
        if (empty($data['title']) || empty($data['user_id'])) {
            http_response_code(400);
            return [
                'status' => false,
                'message' => 'title and user_id are required.'
            ];
        }

        foreach ($data as $field => $value) {
            if (in_array($field, $this->updatablePrawasFields)) {
                $setParts[] = "$field = :$field";
                $params[$field] = $value;
            }
        }

        if (empty($setParts)) {
            http_response_code(400);
            return [
                'status' => false,
                'message' => 'No valid fields provided for creation.'
            ];
        }

        try {
            $sql = "INSERT INTO masik_prawas SET " . implode(', ', $setParts);
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $prawasId = $this->db->lastInsertId();

            http_response_code(201);
            return [
                'status' => true,
                'message' => 'Masik Prawas created successfully.',
                'prawas_id' => (int) $prawasId
            ];
        } catch (Exception $e) {
            http_response_code(500);
            return [
                'status' => false,
                'message' => 'Create failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get a paginated list of Masik Prawas entries
     */
    public function getPrawas($filters = [])
    {
        $page = isset($filters['page']) && is_numeric($filters['page']) ? (int) $filters['page'] : 1;
        $limit = isset($filters['limit']) && is_numeric($filters['limit']) ? (int) $filters['limit'] : 10;
        $offset = ($page - 1) * $limit;

        // Select from masik_prawas (aliased as 'mp') and JOIN users
        $select = "SELECT 
                        mp.*, 
                        u.name AS author_name, 
                        u.avatar AS author_avatar 
                   FROM masik_prawas mp
                   LEFT JOIN users u ON mp.user_id = u.id";

        $countSql = "SELECT COUNT(mp.id) 
                     FROM masik_prawas mp
                     LEFT JOIN users u ON mp.user_id = u.id";
        
        $totalEventsSql = "SELECT COUNT(id) FROM masik_prawas";

        $whereParts = [];
        $params = [];

        // Basic field filters
        foreach ($filters as $field => $value) {
            if (in_array($field, $this->prawasTableFields) && $value !== null) {
                $whereParts[] = "mp.$field = :$field";
                $params[$field] = $value;
            }
        }

        // Search filter (checks title, description, and author's name)
        if (!empty($filters['search'])) {
            $whereParts[] = "(mp.title LIKE :search OR mp.description LIKE :search OR u.name LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $whereClause = !empty($whereParts) ? " WHERE " . implode(' AND ', $whereParts) : "";
        
        $countSql .= $whereClause;

        // Order by last uploaded (created_at descending) as requested
        $sql = $select . $whereClause . " ORDER BY mp.created_at DESC LIMIT :limit OFFSET :offset";

        try {
            // 1. Get absolute total
            $stmtTotal = $this->db->query($totalEventsSql);
            $totalEntries = $stmtTotal->fetchColumn();

            // 2. Get total *filtered*
            $stmtFilteredCount = $this->db->prepare($countSql);
            $stmtFilteredCount->execute($params);
            $filteredCount = $stmtFilteredCount->fetchColumn();

            // 3. Get the paginated data
            $stmt = $this->db->prepare($sql);

            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);

            $stmt->execute();
            $prawasEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'status' => true,
                'total_entries' => (int) $totalEntries,
                'count' => (int) $filteredCount,
                'fetched_count' => count($prawasEntries),
                'page' => $page,
                'limit' => $limit,
                'total_pages' => (int) ceil($filteredCount / $limit),
                'data' => $prawasEntries
            ];
            
        } catch (Exception $e) {
            http_response_code(500);
            return [
                'status' => false,
                'message' => 'Error fetching entries: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update a Masik Prawas entry
     */
    public function updatePrawas($prawasId, $data)
    {
        $setParts = [];
        $params = [];

        if(empty($prawasId)) {
            http_response_code(400);
            return [
                'status' => false,
                'message' => 'Prawas ID is required for update.'
            ];
        }

        foreach ($data as $field => $value) {
            if (in_array($field, $this->updatablePrawasFields)) {
                // Don't allow user_id to be updated this way
                if ($field === 'user_id') continue; 
                
                $setParts[] = "$field = :$field";
                $params[$field] = $value;
            }
        }

        if (empty($setParts)) {
            http_response_code(400);
            return [
                'status' => false,
                'message' => 'No valid fields provided for update.'
            ];
        }

        try {
            $sql = "UPDATE masik_prawas SET " . implode(', ', $setParts) . " WHERE id = :prawas_id";
            $params['prawas_id'] = $prawasId;
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return [
                'status' => true,
                'message' => 'Masik Prawas updated successfully.'
            ];
        } catch (Exception $e) {
            http_response_code(500);
            return [
                'status' => false,
                'message' => 'Update failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete a Masik Prawas entry
     */
    public function deletePrawas($prawasId)
    {
        if(empty($prawasId)) {
            http_response_code(400);
            return [
                'status' => false,
                'message' => 'Prawas ID is required for deletion.'
            ];
        }

        try {
            $stmt = $this->db->prepare("DELETE FROM masik_prawas WHERE id = :prawas_id");
            $stmt->execute(['prawas_id' => $prawasId]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                return [
                    'status' => false,
                    'message' => 'Entry not found.'
                ];
            }

            return [
                'status' => true,
                'message' => 'Masik Prawas deleted successfully.'
            ];
        } catch (Exception $e) {
            http_response_code(500);
            return [
                'status' => false,
                'message' => 'Delete failed: ' . $e->getMessage()
            ];
        }
    }
}