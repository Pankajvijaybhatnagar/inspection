<?php
// src/Events.php

require_once __DIR__ . '/config.php';

class Events
{
    private $db;

    private $eventsTableFields = [
        'id',
        'created_by',
        'title',
        'slug',
        'description',
        'excerpt',
        'cover_image_url',
        'image_alt_text',
        'start_date',
        'start_time',
        'end_date',
        'end_time',
        'location_name',
        'location_address',
        'location_map_url',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
        'status',
        'created_at',
        'updated_at'
    ];

    private $updatableEventFields = [
        'title',
        'slug',
        'description',
        'excerpt',
        'cover_image_url',
        'image_alt_text',
        'start_date',
        'start_time',
        'end_date',
        'end_time',
        'location_name',
        'location_address',
        'location_map_url',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
        'status',
        'created_by'
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

    public function createEvent($data, $categoryIds = [])
    {
        $setParts = [];
        $params = [];

        if (empty($data['title']) || empty($data['slug']) || empty($data['start_date']) || empty($data['end_date'])) {
            http_response_code(400);
            return [
                'status' => false,
                'message' => 'title, slug, start_date, and end_date are required.'
            ];
        }

        // Check for unique slug
        $stmtSlug = $this->db->prepare("SELECT id FROM events WHERE slug = :slug");
        $stmtSlug->execute(['slug' => $data['slug']]);
        if ($stmtSlug->fetch()) {
            http_response_code(409);
            return [
                'status' => false,
                'message' => 'Slug already exists.'
            ];
        }

        foreach ($data as $field => $value) {
            if (in_array($field, $this->updatableEventFields)) {
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
            $sql = "INSERT INTO events SET " . implode(', ', $setParts);
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $eventId = $this->db->lastInsertId();

            if (!empty($categoryIds)) {
                $stmtCat = $this->db->prepare("INSERT INTO event_category_pivot (event_id, category_id) VALUES (:event_id, :category_id)");
                foreach ($categoryIds as $catId) {
                    $stmtCat->execute(['event_id' => $eventId, 'category_id' => $catId]);
                }
            }

            $this->db->commit();
            http_response_code(201);
            return [
                'status' => true,
                'message' => 'Event created successfully.',
                'event_id' => (int) $eventId
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            http_response_code(500);
            return [
                'status' => false,
                'message' => 'Create event failed: ' . $e->getMessage()
            ];
        }
    }

public function getEvents($filters = [])
    {
        $page = isset($filters['page']) && is_numeric($filters['page']) ? (int) $filters['page'] : 1;
        $limit = isset($filters['limit']) && is_numeric($filters['limit']) ? (int) $filters['limit'] : 10;
        $offset = ($page - 1) * $limit;

        // MODIFIED: Added u.name and u.avatar, and the LEFT JOIN to users
        $select = "SELECT 
                        e.*, 
                        u.name AS author_name, 
                        u.avatar AS author_avatar 
                   FROM events e
                   LEFT JOIN users u ON e.created_by = u.id"; // Join users table

        // MODIFIED: Added join for filter consistency
        $countSql = "SELECT COUNT(e.id) 
                     FROM events e
                     LEFT JOIN users u ON e.created_by = u.id";
        
        // This is still the fastest way to get the absolute total
        $totalEventsSql = "SELECT COUNT(id) FROM events";

        $whereParts = [];
        $params = [];

        // Basic field filters
        foreach ($filters as $field => $value) {
            if (in_array($field, $this->eventsTableFields) && $value !== null) {
                $whereParts[] = "e.$field = :$field";
                $params[$field] = $value;
            }
        }

        // Date range filters
        if (!empty($filters['start_date_after'])) {
            $whereParts[] = "e.start_date >= :start_date_after";
            $params['start_date_after'] = $filters['start_date_after'];
        }
        if (!empty($filters['end_date_before'])) {
            $whereParts[] = "e.end_date <= :end_date_before";
            $params['end_date_before'] = $filters['end_date_before'];
        }

        // MODIFIED: Search filter now also checks the author's name (u.name)
        if (!empty($filters['search'])) {
            $whereParts[] = "(e.title LIKE :search OR e.description LIKE :search OR e.location_name LIKE :search OR u.name LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $whereClause = !empty($whereParts) ? " WHERE " . implode(' AND ', $whereParts) : "";
        
        $countSql .= $whereClause;

        // NOTE: No GROUP BY is needed here because it's a one-to-one join
        // (one event has one author), so no duplicate rows are created.
        $sql = $select . $whereClause . " ORDER BY e.start_date DESC LIMIT :limit OFFSET :offset";

        try {
            // 1. Get absolute total events
            $stmtTotal = $this->db->query($totalEventsSql);
            $totalEvents = $stmtTotal->fetchColumn();

            // 2. Get total *filtered* events
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
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // The response is the same, but the 'data' array
            // will now contain 'author_name' and 'author_avatar' for each event.
            return [
                'status' => true,
                'total_events' => (int) $totalEvents,
                'count' => (int) $filteredCount,
                'fetched_count' => count($events),
                'page' => $page,
                'limit' => $limit,
                'total_pages' => (int) ceil($filteredCount / $limit),
                'data' => $events
            ];
            
        } catch (Exception $e) {
            http_response_code(500);
            return [
                'status' => false,
                'message' => 'Error fetching events: ' . $e->getMessage()
            ];
        }
    }

    public function updateEvent($eventId, $data)
    {
        $eventSetParts = [];
        $eventParams = [];
        $categoryIds = null;

        if(!isset($eventId)) {
            http_response_code(400);
            return [
                'status' => false,
                'message' => 'Event ID is required for update.'
            ];
        }



        foreach ($data as $field => $value) {
            if (in_array($field, $this->updatableEventFields)) {
                $eventSetParts[] = "$field = :$field";
                $eventParams[$field] = $value;
            } elseif ($field === 'category_ids' && is_array($value)) {
                $categoryIds = $value;
            }
        }

        if (empty($eventSetParts) && $categoryIds === null) {
            http_response_code(400);
            return [
                'status' => false,
                'message' => 'No valid fields provided for update.'
            ];
        }

        // Check for slug uniqueness if it's being updated
        if (isset($eventParams['slug'])) {
            $stmtSlug = $this->db->prepare("SELECT id FROM events WHERE slug = :slug AND id != :id");
            $stmtSlug->execute(['slug' => $eventParams['slug'], 'id' => $eventId]);
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
            if (!empty($eventSetParts)) {
                $sqlEvent = "UPDATE events SET " . implode(', ', $eventSetParts) . " WHERE id = :event_id";
                $eventParams['event_id'] = $eventId;
                $stmtEvent = $this->db->prepare($sqlEvent);
                $stmtEvent->execute($eventParams);
            }

            if ($categoryIds !== null) {
                $stmtDel = $this->db->prepare("DELETE FROM event_category_pivot WHERE event_id = :event_id");
                $stmtDel->execute(['event_id' => $eventId]);

                if (!empty($categoryIds)) {
                    $stmtCat = $this->db->prepare("INSERT INTO event_category_pivot (event_id, category_id) VALUES (:event_id, :category_id)");
                    foreach ($categoryIds as $catId) {
                        $stmtCat->execute(['event_id' => $eventId, 'category_id' => $catId]);
                    }
                }
            }

            $this->db->commit();
            return [
                'status' => true,
                'message' => 'Event updated successfully.'
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

    public function deleteEvent($eventId)
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("DELETE FROM events WHERE id = :event_id");
            $stmt->execute(['event_id' => $eventId]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                $this->db->rollBack();
                return [
                    'status' => false,
                    'message' => 'Event not found.'
                ];
            }

            $this->db->commit();
            return [
                'status' => true,
                'message' => 'Event deleted successfully.'
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