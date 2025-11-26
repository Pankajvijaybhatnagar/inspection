<?php
// src/SchoolInspections.php

require_once __DIR__ . '/config.php';

class SchoolInspections
{
    private $db;

    /**
     * All possible fields in the school_inspections table.
     * These are used for building SELECT and WHERE clauses.
     * Note: Field names are converted to snake_case for database convention.
     */
    private $inspectionsTableFields = [
        'id', 'created_by', 'session_year', 'school_name', 'address', 'city', 'state', 'pincode', 'email', 'phone', 'website',
        'year_established', 'school_type', 'medium', 'school_category', 'application_type',
        'affiliation_number', 'school_code', 'affiliation_status', 'affiliation_validity',
        'classes_from', 'classes_to', 'proposed_classes', 'trust_name', 'registration_number',
        'registration_validity', 'pan_number', 'registered_address', 'chairman_name', 'manager_name',
        'principal_name', 'principal_qualification', 'principal_experience', 'land_area',
        'ownership_type', 'land_doc_type', 'land_title', 'land_contiguous', 'built_up_area',
        'playground_area', 'num_floors', 'total_classrooms', 'smart_classrooms', 'computer_lab',
        'science_labs', 'composite_lab', 'math_lab', 'library_area', 'num_books', 'digital_library',
        'art_room', 'music_room', 'dance_room', 'medical_room', 'counselling_room', 'staff_room',
        'cctv_cameras', 'ro_water', 'washrooms', 'lift', 'ramps', 'total_teachers', 'pgt_count',
        'tgt_count', 'prt_count', 'special_educator', 'counsellor', 'admin_staff', 'accountant',
        'lab_assistants', 'library_assistant', 'support_staff', 'total_students', 'boys_count',
        'girls_count', 'section_per_class', 'student_teacher_ratio', 'special_needs_students',
        'house_system', 'physics_lab_equipment', 'chemistry_lab_equipment', 'biology_lab_equipment',
        'safety_equipment', 'chemical_storage_safety', 'lab_assistant_qualification',
        'composite_lab_status', 'total_books_lib', 'reference_books', 'magazines', 'e_library',
        'library_management_system', 'issue_return_register', 'cctv_details', 'fire_extinguishers',
        'first_aid_kits', 'visitor_register', 'police_verification_status', 'complaint_box',
        'num_buses', 'driver_license_verification', 'driver_police_verification', 'gps_installed',
        'cctv_in_bus', 'first_aid_box_in_bus', 'fire_extinguisher_in_bus', 'speed_governor',
        'classroom_observation_notes', 'teacher_interaction_notes', 'student_interaction_notes',
        'infrastructure_condition', 'discipline_notes', 'strengths', 'weaknesses', 'recommendation',
        'reasons', 'compliance_required', 'inspector_name', 'inspector_designation',
        'status', 'created_at', 'updated_at'
    ];

    /**
     * Fields that are allowed to be updated via the createInspection and updateInspection methods.
     * This prevents mass assignment vulnerabilities.
     * 'id', 'created_at', and 'updated_at' are typically managed by the database.
     * File fields are handled separately and not included here.
     */
    private $updatableInspectionFields = [
        'session_year', 'school_name', 'address', 'city', 'state', 'pincode', 'email', 'phone', 'website',
        'year_established', 'school_type', 'medium', 'school_category', 'application_type',
        'affiliation_number', 'school_code', 'affiliation_status', 'affiliation_validity',
        'classes_from', 'classes_to', 'proposed_classes', 'trust_name', 'registration_number',
        'registration_validity', 'pan_number', 'registered_address', 'chairman_name', 'manager_name',
        'principal_name', 'principal_qualification', 'principal_experience', 'land_area',
        'ownership_type', 'land_doc_type', 'land_title', 'land_contiguous', 'built_up_area',
        'playground_area', 'num_floors', 'total_classrooms', 'smart_classrooms', 'computer_lab',
        'science_labs', 'composite_lab', 'math_lab', 'library_area', 'num_books', 'digital_library',
        'art_room', 'music_room', 'dance_room', 'medical_room', 'counselling_room', 'staff_room',
        'cctv_cameras', 'ro_water', 'washrooms', 'lift', 'ramps', 'total_teachers', 'pgt_count',
        'tgt_count', 'prt_count', 'special_educator', 'counsellor', 'admin_staff', 'accountant',
        'lab_assistants', 'library_assistant', 'support_staff', 'total_students', 'boys_count',
        'girls_count', 'section_per_class', 'student_teacher_ratio', 'special_needs_students',
        'house_system', 'physics_lab_equipment', 'chemistry_lab_equipment', 'biology_lab_equipment',
        'safety_equipment', 'chemical_storage_safety', 'lab_assistant_qualification',
        'composite_lab_status', 'total_books_lib', 'reference_books', 'magazines', 'e_library',
        'library_management_system', 'issue_return_register', 'cctv_details', 'fire_extinguishers',
        'first_aid_kits', 'visitor_register', 'police_verification_status', 'complaint_box',
        'num_buses', 'driver_license_verification', 'driver_police_verification', 'gps_installed',
        'cctv_in_bus', 'first_aid_box_in_bus', 'fire_extinguisher_in_bus', 'speed_governor',
        'classroom_observation_notes', 'teacher_interaction_notes', 'student_interaction_notes',
        'infrastructure_condition', 'discipline_notes', 'strengths', 'weaknesses', 'recommendation',
        'reasons', 'compliance_required', 'inspector_name', 'inspector_designation', 'status'
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
     * Creates a new school inspection record.
     * Note: File handling is omitted for brevity but would be implemented here.
     *
     * @param array $data The inspection data.
     * @return array JSON response indicating success or failure.
     */
    public function createInspection($data)
    {
        $setParts = [];
        $params = [];

        // Define required fields for creating a new draft inspection
        if (empty($data['school_name']) || empty($data['created_by']) || empty($data['session_year'])) {
            http_response_code(400);
            return [
                'status' => false,
                'message' => 'school_name, created_by, and session_year are required.'
            ];
        }

        // Check if an inspection for this school already exists for the given session year.
        $stmtCheck = $this->db->prepare("SELECT id FROM school_inspections WHERE school_name = :school_name AND session_year = :session_year");
        $stmtCheck->execute(['school_name' => $data['school_name'], 'session_year' => $data['session_year']]);
        if ($stmtCheck->fetch()) {
            http_response_code(409);
            return [
                'status' => false,
                'message' => 'An inspection for this school and session year already exists.'
            ];
        }

        foreach ($data as $field => $value) {
            if (in_array($field, $this->updatableInspectionFields)) {
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
            $sql = "INSERT INTO school_inspections SET " . implode(', ', $setParts);
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $inspectionId = $this->db->lastInsertId();

            // File handling logic would go here
            // e.g., upload 'auditReports', 'moaDocs', etc.

            $this->db->commit();
            http_response_code(201);
            return [
                'status' => true,
                'message' => 'School inspection created successfully.',
                'inspection_id' => (int) $inspectionId
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            http_response_code(500);
            return [
                'status' => false,
                'message' => 'Create inspection failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Retrieves a list of school inspections with filtering and pagination.
     *
     * @param array $filters Optional filters (e.g., school_name, city, state, status, page, limit).
     * @return array JSON response containing inspection data and pagination info.
     */
    public function getInspections($filters = [])
    {
        $page = isset($filters['page']) && is_numeric($filters['page']) ? (int) $filters['page'] : 1;
        $limit = isset($filters['limit']) && is_numeric($filters['limit']) ? (int) $filters['limit'] : 10;
        $offset = ($page - 1) * $limit;

        $select = "SELECT si.* FROM school_inspections si";
        $countSql = "SELECT COUNT(si.id) FROM school_inspections si";
        
        $whereParts = [];
        $params = [];

        foreach ($filters as $field => $value) {
            if (in_array($field, $this->inspectionsTableFields) && $value !== null) {
                $whereParts[] = "si.$field = :$field";
                $params[$field] = $value;
            }
        }

        if (!empty($filters['search'])) {
            $whereParts[] = "(si.school_name LIKE :search OR si.inspector_name LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $whereClause = !empty($whereParts) ? " WHERE " . implode(' AND ', $whereParts) : "";
        $sql = $select . $whereClause . " ORDER BY si.created_at DESC LIMIT :limit OFFSET :offset";
        $countSql .= $whereClause;

        try {
            $stmtFilteredCount = $this->db->prepare($countSql);
            $stmtFilteredCount->execute($params);
            $filteredCount = $stmtFilteredCount->fetchColumn();

            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
            $stmt->execute();
            $inspections = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'status' => true,
                'count' => (int) $filteredCount,
                'fetched_count' => count($inspections),
                'page' => $page,
                'limit' => $limit,
                'total_pages' => (int) ceil($filteredCount / $limit),
                'data' => $inspections
            ];
        } catch (Exception $e) {
            http_response_code(500);
            return [
                'status' => false,
                'message' => 'Error fetching inspections: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Updates an existing school inspection record.
     * Note: File handling is omitted for brevity.
     *
     * @param int $inspectionId The ID of the inspection to update.
     * @param array $data The data to update.
     * @return array JSON response indicating success or failure.
     */
    public function updateInspection($inspectionId, $data)
    {
        $inspectionSetParts = [];
        $inspectionParams = [];

        foreach ($data as $field => $value) {
            if (in_array($field, $this->updatableInspectionFields)) {
                $inspectionSetParts[] = "$field = :$field";
                $inspectionParams[$field] = $value;
            }
        }

        if (empty($inspectionSetParts)) {
            http_response_code(400);
            return [
                'status' => false,
                'message' => 'No valid fields provided for update.'
            ];
        }
        
        $this->db->beginTransaction();
        try {
            $sqlInspection = "UPDATE school_inspections SET " . implode(', ', $inspectionSetParts) . " WHERE id = :inspection_id";
            $inspectionParams['inspection_id'] = $inspectionId;
            $stmtInspection = $this->db->prepare($sqlInspection);
            $stmtInspection->execute($inspectionParams);

            if ($stmtInspection->rowCount() === 0) {
                 http_response_code(404);
                 $this->db->rollBack();
                 return [
                     'status' => false,
                     'message' => 'Inspection not found or no changes made.'
                 ];
            }

            // File handling logic would go here

            $this->db->commit();
            return [
                'status' => true,
                'message' => 'School inspection updated successfully.'
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
     * Deletes a school inspection record.
     *
     * @param int $inspectionId The ID of the inspection to delete.
     * @return array JSON response indicating success or failure.
     */
    public function deleteInspection($inspectionId)
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("DELETE FROM school_inspections WHERE id = :inspection_id");
            $stmt->execute(['inspection_id' => $inspectionId]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                $this->db->rollBack();
                return [
                    'status' => false,
                    'message' => 'Inspection not found.'
                ];
            }

            // Logic to delete associated files would go here

            $this->db->commit();
            return [
                'status' => true,
                'message' => 'School inspection deleted successfully.'
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