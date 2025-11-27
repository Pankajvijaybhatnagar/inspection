<?php
// src/TeacherAppraisals.php

require_once __DIR__ . '/config.php';

class TeacherAppraisals
{
    private $db;

    /**
     * All possible fields in the teacher_appraisals table.
     * These are used for building SELECT and WHERE clauses.
     * Note: Field names are converted to snake_case for database convention.
     */
    private $appraisalsTableFields = [
        'id', 'created_by', 'session_year', 'school_name', 'full_name', 'employee_code', 'designation', 'department', 'date_of_birth', 'gender',
        'contact_number', 'email', 'date_of_joining', 'total_experience', 'highest_qualification',
        'professional_qualification', 'additional_certifications', 'classes_subjects_taught', 'teaching_hours',
        'syllabus_completion', 'lesson_planning', 'teaching_aids', 'teaching_methods', 'student_engagement',
        'homework_management', 'remedial_classes', 'slow_learner_support', 'classroom_discipline',
        'seating_plan', 'classroom_cleanliness', 'notice_board_maintenance', 'behaviour_handling',
        'inclusiveness_special_needs', 'ptm_interactions', 'class_result', 'board_exam_performance',
        'olympiad_participation', 'outstanding_students', 'remedial_enrichment_results', 'exam_duties',
        'discipline_duties', 'event_management', 'house_club_activities', 'assembly_duties',
        'competition_training', 'committee_participation', 'trainings_attended', 'workshops_conducted',
        'courses_completed', 'academic_innovation', 'research_publications', 'areas_of_expertise',
        'leadership_qualities', 'communication_skills', 'time_management', 'team_collaboration',
        'creativity', 'technology_usage', 'skills_to_improve', 'training_required', 'weaknesses_identified',
        'support_expected', 'discipline_support', 'school_growth_contribution', 'student_relationship',
        'colleague_relationship', 'teaching_innovations', 'extra_initiatives', 'academic_goals',
        'professional_development_goals', 'student_learning_goals', 'personal_goals', 'punctuality',
        'dress_code', 'ethical_behaviour', 'confidentiality', 'school_policies_adherence',
        'teaching_quality_rating', 'classroom_management_rating', 'communication_rating', 'co_curricular_rating',
        'professional_development_rating', 'student_relationship_rating', 'declaration', 'teacher_signature',
        'declaration_date', 'hod_comments', 'vice_principal_comments', 'principal_comments', 'final_rating',
        'recommendations', 'principal_signature', 'review_date', 'status', 'created_at', 'updated_at'
    ];

    /**
     * Fields that are allowed to be updated via the createAppraisal and updateAppraisal methods.
     * This prevents mass assignment vulnerabilities.
     * 'id', 'created_at', and 'updated_at' are typically managed by the database.
     */
    private $updatableAppraisalFields = [
        'session_year', 'school_name', 'full_name', 'employee_code', 'designation', 'department', 'date_of_birth', 'gender',
        'contact_number', 'email', 'date_of_joining', 'total_experience', 'highest_qualification',
        'professional_qualification', 'additional_certifications', 'classes_subjects_taught', 'teaching_hours',
        'syllabus_completion', 'lesson_planning', 'teaching_aids', 'teaching_methods', 'student_engagement',
        'homework_management', 'remedial_classes', 'slow_learner_support', 'classroom_discipline',
        'seating_plan', 'classroom_cleanliness', 'notice_board_maintenance', 'behaviour_handling',
        'inclusiveness_special_needs', 'ptm_interactions', 'class_result', 'board_exam_performance',
        'olympiad_participation', 'outstanding_students', 'remedial_enrichment_results', 'exam_duties',
        'discipline_duties', 'event_management', 'house_club_activities', 'assembly_duties',
        'competition_training', 'committee_participation', 'trainings_attended', 'workshops_conducted',
        'courses_completed', 'academic_innovation', 'research_publications', 'areas_of_expertise',
        'leadership_qualities', 'communication_skills', 'time_management', 'team_collaboration',
        'creativity', 'technology_usage', 'skills_to_improve', 'training_required', 'weaknesses_identified',
        'support_expected', 'discipline_support', 'school_growth_contribution', 'student_relationship',
        'colleague_relationship', 'teaching_innovations', 'extra_initiatives', 'academic_goals',
        'professional_development_goals', 'student_learning_goals', 'personal_goals', 'punctuality',
        'dress_code', 'ethical_behaviour', 'confidentiality', 'school_policies_adherence',
        'teaching_quality_rating', 'classroom_management_rating', 'communication_rating', 'co_curricular_rating',
        'professional_development_rating', 'student_relationship_rating', 'declaration', 'teacher_signature',
        'declaration_date', 'hod_comments', 'vice_principal_comments', 'principal_comments', 'final_rating',
        'recommendations', 'principal_signature', 'review_date', 'status', 'created_by'
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
     * Creates a new teacher appraisal record.
     *
     * @param array $data The appraisal data.
     * @return array JSON response indicating success or failure.
     */
    public function createAppraisal($data)
    {
        $setParts = [];
        $params = [];

        // Define required fields for creating a new draft appraisal
        if (empty($data['created_by']) || empty($data['session_year'])) {
            http_response_code(400);
            return [
                'status' => false,
                'message' => 'created_by and session_year are required.'
            ];
        }

        // Check if an appraisal for this user already exists for the given session year.
        $stmtCheck = $this->db->prepare("SELECT id FROM teacher_appraisals WHERE created_by = :created_by AND session_year = :session_year");
        $stmtCheck->execute(['created_by' => $data['created_by'], 'session_year' => $data['session_year']]);
        if ($stmtCheck->fetch()) {
            http_response_code(409);
            return [
                'status' => false,
                'message' => 'An appraisal for this user and session year already exists.'
            ];
        }

        foreach ($data as $field => $value) {
            if (in_array($field, $this->updatableAppraisalFields)) {
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
            $sql = "INSERT INTO teacher_appraisals SET " . implode(', ', $setParts);
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $appraisalId = $this->db->lastInsertId();

            $this->db->commit();
            http_response_code(201);
            return [
                'status' => true,
                'message' => 'Teacher appraisal created successfully.',
                'appraisal_id' => (int) $appraisalId
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            http_response_code(500);
            return [
                'status' => false,
                'message' => 'Create appraisal failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Retrieves a list of teacher appraisals with filtering and pagination.
     *
     * @param array $filters Optional filters (e.g., employee_code, status, page, limit).
     * @return array JSON response containing appraisal data and pagination info.
     */
    public function getAppraisals($filters = [])
    {
        $page = isset($filters['page']) && is_numeric($filters['page']) ? (int) $filters['page'] : 1;
        $limit = isset($filters['limit']) && is_numeric($filters['limit']) ? (int) $filters['limit'] : 10;
        $offset = ($page - 1) * $limit;

        $select = "SELECT 
                        ta.*, 
                        u.name as creator_name,
                        ud.school_name as teacher_school_name 
                   FROM teacher_appraisals ta
                   LEFT JOIN users u ON ta.created_by = u.id
                   LEFT JOIN user_details ud ON u.id = ud.user_id";

        $countSql = "SELECT COUNT(ta.id) FROM teacher_appraisals ta";
        $totalAppraisalsSql = "SELECT COUNT(id) FROM teacher_appraisals";
        
        $whereParts = [];
        $params = [];

        foreach ($filters as $field => $value) {
            if (in_array($field, $this->appraisalsTableFields) && $value !== null) {
                $whereParts[] = "ta.$field = :$field";
                $params[$field] = $value;
            }
        }

        $whereClause = !empty($whereParts) ? " WHERE " . implode(' AND ', $whereParts) : "";
        $sql = $select . $whereClause . " ORDER BY ta.created_at DESC LIMIT :limit OFFSET :offset";
        $countSql .= $whereClause;

        try {
            $stmtTotal = $this->db->query($totalAppraisalsSql);
            $totalAppraisals = $stmtTotal->fetchColumn();

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
            $appraisals = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'status' => true,
                'total_appraisals' => (int) $totalAppraisals,
                'count' => (int) $filteredCount,
                'fetched_count' => count($appraisals),
                'page' => $page,
                'limit' => $limit,
                'total_pages' => (int) ceil($filteredCount / $limit),
                'data' => $appraisals
            ];
        } catch (Exception $e) {
            http_response_code(500);
            return [
                'status' => false,
                'message' => 'Error fetching appraisals: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Updates an existing teacher appraisal record.
     *
     * @param int $appraisalId The ID of the appraisal to update.
     * @param array $data The data to update.
     * @return array JSON response indicating success or failure.
     */
    public function updateAppraisal($appraisalId, $data)
    {
        $appraisalSetParts = [];
        $appraisalParams = [];

        foreach ($data as $field => $value) {
            if (in_array($field, $this->updatableAppraisalFields)) {
                $appraisalSetParts[] = "$field = :$field";
                $appraisalParams[$field] = $value;
            }
        }

        if (empty($appraisalSetParts)) {
            http_response_code(400);
            return [
                'status' => false,
                'message' => 'No valid fields provided for update.'
            ];
        }
        
        $this->db->beginTransaction();
        try {
            $sqlAppraisal = "UPDATE teacher_appraisals SET " . implode(', ', $appraisalSetParts) . " WHERE id = :appraisal_id";
            $appraisalParams['appraisal_id'] = $appraisalId;
            $stmtAppraisal = $this->db->prepare($sqlAppraisal);
            $stmtAppraisal->execute($appraisalParams);

            if ($stmtAppraisal->rowCount() === 0) {
                 http_response_code(404);
                 $this->db->rollBack();
                 return [
                     'status' => false,
                     'message' => 'Appraisal not found or no changes made.'
                 ];
            }

            $this->db->commit();
            return [
                'status' => true,
                'message' => 'Teacher appraisal updated successfully.'
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
     * Deletes a teacher appraisal record.
     *
     * @param int $appraisalId The ID of the appraisal to delete.
     * @return array JSON response indicating success or failure.
     */
    public function deleteAppraisal($appraisalId)
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("DELETE FROM teacher_appraisals WHERE id = :appraisal_id");
            $stmt->execute(['appraisal_id' => $appraisalId]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                $this->db->rollBack();
                return [
                    'status' => false,
                    'message' => 'Appraisal not found.'
                ];
            }

            $this->db->commit();
            return [
                'status' => true,
                'message' => 'Teacher appraisal deleted successfully.'
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