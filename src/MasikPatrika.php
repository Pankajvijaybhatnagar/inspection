<?php
// src/MasikPatrika.php

require_once __DIR__ . '/config.php';

class MasikPatrika
{
    private $db;

    // All fields from your new 'masik_patrika' table
    private $patrikaTableFields = [
        'id',
        'user_id',
        'title',
        'issue_date',
        'description',
        'cover_image_url',
        'pdf_url',
        'status',
        'slug',
        'image_alt_text',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
        'created_at',
        'updated_at'
    ];

    // Fields that can be set during creation or update
    private $updatablePatrikaFields = [
        'user_id',
        'title',
        'issue_date',
        'description',
        'cover_image_url',
        'pdf_url',
        'status',
        'slug',
        'image_alt_text',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
    ];

    public function __construct()
    {
        $config = new Config();
        $this->db = $this->getDBFromConfig($config);
    }

    private function getDBFromConfig($config)
    {
        $reflection = new ReflectionClass($config);
        $property = $reflection->getProperty('db');
        $property->setAccessible(true);
        return $property->getValue($config);
    }

    /**
     * Create a new Masik Patrika entry
     */
    public function createPatrika($data)
    {
        $setParts = [];
        $params = [];

        // Validation for required fields
        if (empty($data['title']) || empty($data['slug']) || empty($data['user_id'])) {
            http_response_code(400);
            return [
                'status' => false,
                'message' => 'title, slug, and user_id are required.'
            ];
        }

        // Check for unique slug
        $stmtSlug = $this->db->prepare("SELECT id FROM masik_patrika WHERE slug = :slug");
        $stmtSlug->execute(['slug' => $data['slug']]);
        if ($stmtSlug->fetch()) {
            http_response_code(409);
            return [
                'status' => false,
                'message' => 'Slug already exists.'
            ];
        }

        foreach ($data as $field => $value) {
            if (in_array($field, $this->updatablePatrikaFields)) {
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
            $sql = "INSERT INTO masik_patrika SET " . implode(', ', $setParts);
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $patrikaId = $this->db->lastInsertId();

            http_response_code(201);
            return [
                'status' => true,
                'message' => 'Masik Patrika created successfully.',
                'patrika_id' => (int) $patrikaId
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
     * Get a paginated list of Masik Patrika entries
     */
    public function getPatrika($filters = [])
    {
        $page = isset($filters['page']) && is_numeric($filters['page']) ? (int) $filters['page'] : 1;
        $limit = isset($filters['limit']) && is_numeric($filters['limit']) ? (int) $filters['limit'] : 10;
        $offset = ($page - 1) * $limit;

        $select = "SELECT 
                        mp.*, 
                        u.name AS author_name, 
                        u.avatar AS author_avatar 
                   FROM masik_patrika mp
                   LEFT JOIN users u ON mp.user_id = u.id";

        $countSql = "SELECT COUNT(mp.id) 
                     FROM masik_patrika mp
                     LEFT JOIN users u ON mp.user_id = u.id";
        
        $totalEntriesSql = "SELECT COUNT(id) FROM masik_patrika";

        $whereParts = [];
        $params = [];

        // Basic field filters
        foreach ($filters as $field => $value) {
            if (in_array($field, $this->patrikaTableFields) && $value !== null && $value !== '' && $field !== 'page' ) {
                $whereParts[] = "mp.$field = :$field";
                $params[$field] = $value;
            }
        }

        // Search filter (checks title, description, and author's name)
        if (!empty($filters['search'])) {
            $whereParts[] = "(mp.title LIKE :search OR mp.description LIKE :search )";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $whereClause = !empty($whereParts) ? " WHERE " . implode(' AND ', $whereParts) : "";
        
        $countSql .= $whereClause;

        // Order by issue_date (newest first)
        $sql = $select . $whereClause . " ORDER BY mp.issue_date DESC, mp.created_at DESC LIMIT :limit OFFSET :offset";

        try {
            // 1. Get absolute total
            $stmtTotal = $this->db->query($totalEntriesSql);
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
            $patrikaEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'status' => true,
                'total_entries' => (int) $totalEntries,
                'count' => (int) $filteredCount,
                'fetched_count' => count($patrikaEntries),
                'page' => $page,
                'limit' => $limit,
                'total_pages' => (int) ceil($filteredCount / $limit),
                'data' => $patrikaEntries
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
     * Check if a user is the owner of a Masik Patrika entry.
     */
    public function checkOwnership($patrikaId, $userId)
    {
        if (empty($patrikaId) || empty($userId)) {
            return false;
        }
        
        try {
            $stmt = $this->db->prepare("SELECT id FROM masik_patrika WHERE id = :patrika_id AND user_id = :user_id");
            $stmt->execute(['patrika_id' => $patrikaId, 'user_id' => $userId]);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Update a Masik Patrika entry
     */
    public function updatePatrika($patrikaId, $data)
    {
        $setParts = [];
        $params = [];

        if(empty($patrikaId)) {
            http_response_code(400);
            return [
                'status' => false,
                'message' => 'Patrika ID is required for update.'
            ];
        }

        // Check for slug uniqueness if it's being updated
        if (isset($data['slug'])) {
            $stmtSlug = $this->db->prepare("SELECT id FROM masik_patrika WHERE slug = :slug AND id != :id");
            $stmtSlug->execute(['slug' => $data['slug'], 'id' => $patrikaId]);
            if ($stmtSlug->fetch()) {
                http_response_code(409);
                return [
                    'status' => false,
                    'message' => 'Slug already exists.'
                ];
            }
        }

        foreach ($data as $field => $value) {
            if (in_array($field, $this->updatablePatrikaFields)) {
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
            $sql = "UPDATE masik_patrika SET " . implode(', ', $setParts) . " WHERE id = :patrika_id";
            $params['patrika_id'] = $patrikaId;
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return [
                'status' => true,
                'message' => 'Masik Patrika updated successfully.'
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
     * Delete a Masik Patrika entry
     */
    public function deletePatrika($patrikaId)
    {
        if(empty($patrikaId)) {
            http_response_code(400);
            return [
                'status' => false,
                'message' => 'Patrika ID is required for deletion.'
            ];
        }

        try {
            $stmt = $this->db->prepare("DELETE FROM masik_patrika WHERE id = :patrika_id");
            $stmt->execute(['patrika_id' => $patrikaId]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                return [
                    'status' => false,
                    'message' => 'Entry not found.'
                ];
            }

            return [
                'status' => true,
                'message' => 'Masik Patrika deleted successfully.'
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