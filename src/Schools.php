<?php
// src/Schools.php

require_once __DIR__ . '/config.php';

class Schools
{
    private $db;

    private $schoolFields = [
        'id', 'school_name', 'school_code', 'status', 'created_at'
    ];

    private $updatableFields = [
        'school_name', 'school_code', 'status'
    ];

    public function __construct()
    {
        $config = new Config();
        // The Config class has a public getDB() method, so reflection is not needed.
        $this->db = $config->getDB();
    }


    // ---------------------------------------------------------
    // 1️⃣ CREATE SCHOOL
    // ---------------------------------------------------------
    public function createSchool($data)
    {
        if (empty($data['school_name'])) {
            http_response_code(400);
            return [
                'status' => false,
                'message' => 'school_name is required.'
            ];
        }

        // Check duplicate school name
        $stmt = $this->db->prepare("SELECT id FROM school_list WHERE school_name = :name");
        $stmt->execute(['name' => $data['school_name']]);
        if ($stmt->fetch()) {
            http_response_code(409);
            return [
                'status' => false,
                'message' => 'School name already exists.'
            ];
        }

        $sql = "INSERT INTO school_list (school_name, school_code) 
                VALUES (:school_name, :school_code)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'school_name' => $data['school_name'],
            'school_code' => $data['school_code'] ?? null
        ]);

        http_response_code(201);
        return [
            'status' => true,
            'message' => 'School created successfully.',
            'id' => (int) $this->db->lastInsertId()
        ];
    }

    // ---------------------------------------------------------
    // 2️⃣ GET SCHOOL LIST (with optional filters)
    // ---------------------------------------------------------
    public function getSchools($filters = [])
    {
        $sql = "SELECT * FROM school_list";
        $where = [];
        $params = [];

        if (!empty($filters['id'])) {
            $where[] = "id = :id";
            $params['id'] = $filters['id'];
        }

        if (!empty($filters['status'])) {
            $where[] = "status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $where[] = "school_name LIKE :search";
            $params['search'] = "%{$filters['search']}%";
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        // If fetching by a single ID, ordering is not necessary.
        if (empty($filters['id'])) {
            $sql .= " ORDER BY school_name ASC";
        }

        

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'status' => true,
                'count' => count($schools),
                'data' => $schools
            ];
        } catch (Exception $e) {
            http_response_code(500);
            return [
                'status' => false,
                'message' => 'Error fetching schools: ' . $e->getMessage()
            ];
        }
    }

    // ---------------------------------------------------------
    // 3️⃣ GET ONLY ACTIVE SCHOOLS FOR DROPDOWN
    // ---------------------------------------------------------
    public function getActiveSchools()
    {
        try {
            $stmt = $this->db->query("SELECT id, school_name FROM school_list WHERE status = 'active' ORDER BY school_name ASC");
            $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'status' => true,
                'data' => $schools
            ];
        } catch (Exception $e) {
            http_response_code(500);
            return [
                'status' => false,
                'message' => 'Error fetching active schools: ' . $e->getMessage()
            ];
        }
    }

    // ---------------------------------------------------------
    // 4️⃣ UPDATE SCHOOL
    // ---------------------------------------------------------
    public function updateSchool($schoolId, $data)
    {
        $setParts = [];
        $params = [];

        foreach ($data as $field => $value) {
            if (in_array($field, $this->updatableFields)) {
                $setParts[] = "$field = :$field";
                $params[$field] = $value;
            }
        }

        if (empty($setParts)) {
            http_response_code(400);
            return [
                'status' => false,
                'message' => 'No valid fields for update.'
            ];
        }

        $params['id'] = $schoolId;

        $sql = "UPDATE school_list SET " . implode(", ", $setParts) . " WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            if ($stmt->rowCount() > 0) {
                return [
                    'status' => true,
                    'message' => 'School updated successfully.'
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'School not found or no changes were made.'
                ];
            }
        } catch (Exception $e) {
            http_response_code(500);
            return [
                'status' => false,
                'message' => 'Update failed: ' . $e->getMessage()
            ];
        }
    }

    // ---------------------------------------------------------
    // 5️⃣ DELETE SCHOOL
    // ---------------------------------------------------------
    public function deleteSchool($schoolId)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM school_list WHERE id = :id");
            $stmt->execute(['id' => $schoolId]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                return [
                    'status' => false,
                    'message' => 'School not found.'
                ];
            }

            return [
                'status' => true,
                'message' => 'School deleted successfully.'
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
