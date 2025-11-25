<?php
// src/LiveDarshan.php

require_once __DIR__ . '/config.php';

class LiveDarshan
{
    private $db;

    /**
     * All fields from the live_darshan table.
     * Used in getDarshans() for building filters.
     */
    private $darshanTableFields = [
        'id',
        'created_by',
        'updated_by',
        'title',
        'description',
        'stream_url',
        'status',
        'slug',
        'cover_image_url',
        'image_alt_text',
        'meta_title',
        'meta_description',
        'meta_keywords', // Assuming you added this to match your other tables
        'created_at',
        'updated_at'
    ];

    /**
     * Fields that can be set via createDarshan() or updateDarshan().
     */
    private $updatableDarshanFields = [
        'created_by',
        'updated_by',
        'title',
        'description',
        'stream_url',
        'status',
        'slug',
        'cover_image_url',
        'image_alt_text',
        'meta_title',
        'meta_description',
        'meta_keywords' // Assuming you added this
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
     * Create a new live darshan stream record
     */
    public function createDarshan($data)
    {
        $setParts = [];
        $params = [];

        if (empty($data['title']) || empty($data['slug']) || empty($data['stream_url'])) {
            http_response_code(400);
            return [
                'status' => false,
                'message' => 'title, slug, and stream_url are required.'
            ];
        }

        // Check for unique slug
        $stmtSlug = $this->db->prepare("SELECT id FROM live_darshan WHERE slug = :slug");
        $stmtSlug->execute(['slug' => $data['slug']]);
        if ($stmtSlug->fetch()) {
            http_response_code(409);
            return [
                'status' => false,
                'message' => 'Slug already exists.'
            ];
        }

        foreach ($data as $field => $value) {
            if (in_array($field, $this->updatableDarshanFields)) {
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

        $this->db->beginTransaction();
        try {
            $sql = "INSERT INTO live_darshan SET " . implode(', ', $setParts);
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $darshanId = $this->db->lastInsertId();

            $this->db->commit();
            http_response_code(201);
            return [
                'status' => true,
                'message' => 'Live Darshan created successfully.',
                'darshan_id' => (int) $darshanId
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            http_response_code(500);
            return [
                'status' => false,
                'message' => 'Create darshan failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get a paginated and filtered list of live darshans
     */
    public function getDarshans($filters = [])
    {
        $page = isset($filters['page']) && is_numeric($filters['page']) ? (int) $filters['page'] : 1;
        $limit = isset($filters['limit']) && is_numeric($filters['limit']) ? (int) $filters['limit'] : 10;
        $offset = ($page - 1) * $limit;

        // Select live_darshan (aliased as 'd') and join users
        $select = "SELECT 
                        d.*, 
                        u.name AS author_name, 
                        u.avatar AS author_avatar 
                   FROM live_darshan d
                   LEFT JOIN users u ON d.created_by = u.id";

        $countSql = "SELECT COUNT(d.id) 
                     FROM live_darshan d
                     LEFT JOIN users u ON d.created_by = u.id";

        $totalDarshansSql = "SELECT COUNT(id) FROM live_darshan";

        $whereParts = [];
        $params = [];

        // Basic field filters (e.g., status, created_by)
        foreach ($filters as $field => $value) {
            if (in_array($field, $this->darshanTableFields) && $value !== null) {
                $whereParts[] = "d.$field = :$field";
                $params[$field] = $value;
            }
        }

        // Search filter (title, description, author name)
        if (!empty($filters['search'])) {
            $whereParts[] = "(d.title LIKE :search OR d.description LIKE :search OR u.name LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $whereClause = !empty($whereParts) ? " WHERE " . implode(' AND ', $whereParts) : "";

        $countSql .= $whereClause;

        // Order by most recent
        $sql = $select . $whereClause . " ORDER BY d.created_at DESC LIMIT :limit OFFSET :offset";

        try {
            // 1. Get absolute total darshans
            $stmtTotal = $this->db->query($totalDarshansSql);
            $totalDarshans = $stmtTotal->fetchColumn();

            // 2. Get total *filtered* darshans
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
            $darshans = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'status' => true,
                'total_darshans' => (int) $totalDarshans,
                'count' => (int) $filteredCount,
                'fetched_count' => count($darshans),
                'page' => $page,
                'limit' => $limit,
                'total_pages' => (int) ceil($filteredCount / $limit),
                'data' => $darshans
            ];

        } catch (Exception $e) {
            http_response_code(500);
            return [
                'status' => false,
                'message' => 'Error fetching darshans: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update an existing live darshan record
     */
    public function updateDarshan($darshanId, $data)
    {
        $darshanSetParts = [];
        $darshanParams = [];

        if (!isset($darshanId)) {
            http_response_code(400);
            return [
                'status' => false,
                'message' => 'Darshan ID is required for update.'
            ];
        }

        foreach ($data as $field => $value) {
            if (in_array($field, $this->updatableDarshanFields)) {
                $darshanSetParts[] = "$field = :$field";
                $darshanParams[$field] = $value;
            }
        }

        if (empty($darshanSetParts)) {
            http_response_code(400);
            return [
                'status' => false,
                'message' => 'No valid fields provided for update.'
            ];
        }

        // Check for slug uniqueness if it's being updated
        if (isset($darshanParams['slug'])) {
            $stmtSlug = $this->db->prepare("SELECT id FROM live_darshan WHERE slug = :slug AND id != :id");
            $stmtSlug->execute(['slug' => $darshanParams['slug'], 'id' => $darshanId]);
            if ($stmtSlug->fetch()) {
                http_response_code(409);
                return [
                    'status' => false,
                    'message' => 'Slug already exists.'
                ];
            }
        }

        $this->db->beginTransaction();
        try {
            if (!empty($darshanSetParts)) {
                $sqlDarshan = "UPDATE live_darshan SET " . implode(', ', $darshanSetParts) . " WHERE id = :darshan_id";
                $darshanParams['darshan_id'] = $darshanId;
                $stmtDarshan = $this->db->prepare($sqlDarshan);
                $stmtDarshan->execute($darshanParams);
            }

            $this->db->commit();
            return [
                'status' => true,
                'message' => 'Live Darshan updated successfully.'
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
     * Delete a live darshan record
     */
    public function deleteDarshan($darshanId)
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("DELETE FROM live_darshan WHERE id = :darshan_id");
            $stmt->execute(['darshan_id' => $darshanId]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                $this->db->rollBack();
                return [
                    'status' => false,
                    'message' => 'Live Darshan not found.'
                ];
            }

            $this->db->commit();
            return [
                'status' => true,
                'message' => 'Live Darshan deleted successfully.'
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
