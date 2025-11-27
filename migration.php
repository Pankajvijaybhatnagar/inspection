<?php
require_once __DIR__ . '/src/config.php';
$db = new Config();

$createUserTableSql = 'CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    google_id VARCHAR(50) DEFAULT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) DEFAULT NULL,
    name VARCHAR(50) NOT NULL,
    role ENUM("superadmin", "school", "teacher") DEFAULT "teacher",
    access JSON DEFAULT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    points INT DEFAULT 0,
    is_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
$db->createTable($createUserTableSql);

$createUserDetailsTableSql = 'CREATE TABLE IF NOT EXISTS user_details (
    user_id INT NOT NULL,
    address VARCHAR(255) DEFAULT NULL,
    school_name VARCHAR(255) DEFAULT NULL,
    employee_id VARCHAR(100) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    district VARCHAR(100) DEFAULT NULL,
    state VARCHAR(100) DEFAULT NULL,
    country VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    dob DATE DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
$db->createTable($createUserDetailsTableSql);

$createSessionsTableSql = 'CREATE TABLE IF NOT EXISTS sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    refresh_token TEXT NOT NULL,
    expires_at DATETIME NOT NULL,
    ip_address VARCHAR(255),
    user_agent TEXT,
    device_info TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
$db->createTable($createSessionsTableSql);

$createActivityLogTableSql = 'CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT DEFAULT NULL,
    points_earned INT DEFAULT 0,
    points_spent INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
$db->createTable($createActivityLogTableSql);

$createOTPTableSql = 'CREATE TABLE IF NOT EXISTS otps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    otp_code VARCHAR(10) NOT NULL,
    expires_at DATETIME NOT NULL,
    is_used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
$db->createTable($createOTPTableSql);









// End of migration.php



// --- Table for Teacher Self-Appraisal Form ---
$createTeacherAppraisalsTableSql = 'CREATE TABLE IF NOT EXISTS teacher_appraisals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    created_by INT NOT NULL,
    session_year VARCHAR(20) DEFAULT NULL,
    school_name VARCHAR(255) DEFAULT NULL,
    
    -- 1. Personal Information
    full_name VARCHAR(255) DEFAULT NULL,
    employee_code VARCHAR(100) DEFAULT NULL,
    designation VARCHAR(255) DEFAULT NULL,
    department VARCHAR(255) DEFAULT NULL,
    date_of_birth DATE DEFAULT NULL,
    gender ENUM("male", "female", "other") DEFAULT NULL,
    contact_number VARCHAR(20) DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    date_of_joining DATE DEFAULT NULL,
    total_experience INT DEFAULT NULL,
    highest_qualification VARCHAR(255) NOT NULL,
    professional_qualification VARCHAR(255) DEFAULT NULL,
    additional_certifications TEXT DEFAULT NULL,

    -- 2. Academic Responsibilities
    classes_subjects_taught TEXT DEFAULT NULL,
    teaching_hours INT DEFAULT NULL,
    syllabus_completion INT DEFAULT NULL,
    lesson_planning ENUM("regularly", "sometimes", "rarely") DEFAULT NULL,
    teaching_aids ENUM("regularly", "sometimes", "rarely") DEFAULT NULL,
    teaching_methods TEXT DEFAULT NULL,
    student_engagement TEXT DEFAULT NULL,
    homework_management ENUM("excellent", "good", "average", "needs-improvement") DEFAULT NULL,
    remedial_classes ENUM("regularly", "sometimes", "rarely", "never") DEFAULT NULL,
    slow_learner_support TEXT DEFAULT NULL,

    -- 3. Classroom Management
    classroom_discipline ENUM("excellent", "good", "average", "needs-improvement") DEFAULT NULL,
    seating_plan ENUM("regularly-updated", "sometimes-updated", "rarely-updated", "not-updated") DEFAULT NULL,
    classroom_cleanliness ENUM("excellent", "good", "average", "needs-improvement") DEFAULT NULL,
    notice_board_maintenance ENUM("regularly-maintained", "sometimes-maintained", "rarely-maintained", "not-maintained") DEFAULT NULL,
    behaviour_handling TEXT DEFAULT NULL,
    inclusiveness_special_needs TEXT DEFAULT NULL,
    ptm_interactions TEXT DEFAULT NULL,

    -- 4. Student Performance & Outcomes
    class_result INT DEFAULT NULL,
    board_exam_performance TEXT DEFAULT NULL,
    olympiad_participation TEXT DEFAULT NULL,
    outstanding_students TEXT DEFAULT NULL,
    remedial_enrichment_results TEXT DEFAULT NULL,

    -- 5. Co-curricular Activities
    exam_duties ENUM("regularly-performed", "sometimes-performed", "rarely-performed", "never-performed") DEFAULT NULL,
    discipline_duties ENUM("regularly-performed", "sometimes-performed", "rarely-performed", "never-performed") DEFAULT NULL,
    event_management TEXT DEFAULT NULL,
    house_club_activities TEXT DEFAULT NULL,
    assembly_duties ENUM("regularly-performed", "sometimes-performed", "rarely-performed", "never-performed") DEFAULT NULL,
    competition_training TEXT DEFAULT NULL,
    committee_participation TEXT DEFAULT NULL,

    -- 6. Professional Development
    trainings_attended TEXT DEFAULT NULL,
    workshops_conducted TEXT DEFAULT NULL,
    courses_completed TEXT DEFAULT NULL,
    academic_innovation TEXT DEFAULT NULL,
    research_publications TEXT DEFAULT NULL,

    -- 7. Personal Strengths & Skills
    areas_of_expertise TEXT DEFAULT NULL,
    leadership_qualities TEXT DEFAULT NULL,
    communication_skills ENUM("excellent", "good", "average", "needs-improvement") DEFAULT NULL,
    time_management ENUM("excellent", "good", "average", "needs-improvement") DEFAULT NULL,
    team_collaboration ENUM("excellent", "good", "average", "needs-improvement") DEFAULT NULL,
    creativity ENUM("excellent", "good", "average", "needs-improvement") DEFAULT NULL,
    technology_usage ENUM("excellent", "good", "average", "needs-improvement") DEFAULT NULL,

    -- 8. Areas of Improvement
    skills_to_improve TEXT NOT NULL,
    training_required TEXT NOT NULL,
    weaknesses_identified TEXT NOT NULL,
    support_expected TEXT NOT NULL,

    -- 9. Contribution to School
    discipline_support TEXT DEFAULT NULL,
    school_growth_contribution TEXT DEFAULT NULL,
    student_relationship ENUM("excellent", "good", "average", "needs-improvement") DEFAULT NULL,
    colleague_relationship ENUM("excellent", "good", "average", "needs-improvement") DEFAULT NULL,
    teaching_innovations TEXT DEFAULT NULL,
    extra_initiatives TEXT DEFAULT NULL,

    -- 10. Goals for Next Year
    academic_goals TEXT DEFAULT NULL,
    professional_development_goals TEXT DEFAULT NULL,
    student_learning_goals TEXT DEFAULT NULL,
    personal_goals TEXT DEFAULT NULL,

    -- 11. Code of Conduct Compliance
    punctuality ENUM("excellent", "good", "average", "needs-improvement") DEFAULT NULL,
    dress_code ENUM("always-followed", "mostly-followed", "sometimes-followed", "rarely-followed") DEFAULT NULL,
    ethical_behaviour ENUM("excellent", "good", "average", "needs-improvement") DEFAULT NULL,
    confidentiality ENUM("always-maintained", "mostly-maintained", "sometimes-maintained", "rarely-maintained") DEFAULT NULL,
    school_policies_adherence ENUM("always-followed", "mostly-followed", "sometimes-followed", "rarely-followed") DEFAULT NULL,

    -- 12. Overall Self-Rating
    teaching_quality_rating INT DEFAULT NULL,
    classroom_management_rating INT DEFAULT NULL,
    communication_rating INT DEFAULT NULL,
    co_curricular_rating INT DEFAULT NULL,
    professional_development_rating INT DEFAULT NULL,
    student_relationship_rating INT DEFAULT NULL,

    -- 13. Teacher Declaration
    declaration BOOLEAN DEFAULT NULL,
    teacher_signature VARCHAR(255) DEFAULT NULL, -- Path to file
    declaration_date DATE DEFAULT NULL,

    -- 14. Review Section
    hod_comments TEXT DEFAULT NULL,
    vice_principal_comments TEXT DEFAULT NULL,
    principal_comments TEXT DEFAULT NULL,
    final_rating ENUM("outstanding", "excellent", "good", "satisfactory", "needs-improvement") DEFAULT NULL,
    recommendations TEXT DEFAULT NULL,
    principal_signature VARCHAR(255) DEFAULT NULL, -- Path to file
    review_date DATE DEFAULT NULL,

    -- Status and Timestamps
    status ENUM("draft", "submitted", "reviewed", "approved", "rejected") NOT NULL DEFAULT "draft",
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY `unique_appraisal_per_session` (`employee_code`, `session_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
$db->createTable($createTeacherAppraisalsTableSql);


// --- Table for School Inspection Form ---
$createSchoolInspectionsTableSql = 'CREATE TABLE IF NOT EXISTS school_inspections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    created_by INT NOT NULL,
    session_year VARCHAR(20) DEFAULT NULL,

    -- 1. School Details
    school_name VARCHAR(255) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    state VARCHAR(100) DEFAULT NULL,
    pincode VARCHAR(10) DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    website VARCHAR(255) DEFAULT NULL,
    year_established INT DEFAULT NULL,
    school_type ENUM("residential", "non-residential", "day-boarding") DEFAULT NULL,
    medium VARCHAR(100) DEFAULT NULL,
    school_category ENUM("co-ed", "boys", "girls") DEFAULT NULL,

    -- 2. Affiliation Details
    application_type ENUM("fresh", "extension", "upgradation", "switch-over") DEFAULT NULL,
    affiliation_number VARCHAR(100) DEFAULT NULL,
    school_code VARCHAR(100) DEFAULT NULL,
    affiliation_status ENUM("provisional", "regular") DEFAULT NULL,
    affiliation_validity DATE DEFAULT NULL,
    classes_from VARCHAR(20) DEFAULT NULL,
    classes_to VARCHAR(20) DEFAULT NULL,
    proposed_classes VARCHAR(100) DEFAULT NULL,

    -- 3. Management Details
    trust_name VARCHAR(255) DEFAULT NULL,
    registration_number VARCHAR(100) DEFAULT NULL,
    registration_validity DATE DEFAULT NULL,
    pan_number VARCHAR(50) DEFAULT NULL,
    registered_address TEXT DEFAULT NULL,
    chairman_name VARCHAR(255) DEFAULT NULL,
    manager_name VARCHAR(255) DEFAULT NULL,
    principal_name VARCHAR(255) DEFAULT NULL,
    principal_qualification VARCHAR(255) DEFAULT NULL,
    principal_experience INT DEFAULT NULL,
    audit_reports VARCHAR(255) DEFAULT NULL, -- Path to file
    moa_docs VARCHAR(255) DEFAULT NULL, -- Path to file

    -- 4. Land & Building Details
    land_area VARCHAR(100) NOT NULL,
    ownership_type ENUM("owned", "leased") NOT NULL,
    land_doc_type VARCHAR(255) NOT NULL,
    land_title ENUM("yes", "no") NOT NULL,
    land_contiguous ENUM("yes", "no") NOT NULL,
    built_up_area VARCHAR(100) NOT NULL,
    playground_area VARCHAR(100) DEFAULT NULL,
    num_floors INT DEFAULT NULL,
    building_safety_cert VARCHAR(255) DEFAULT NULL, -- Path to file
    fire_safety_cert VARCHAR(255) DEFAULT NULL, -- Path to file
    sanitation_cert VARCHAR(255) DEFAULT NULL, -- Path to file
    water_testing_report VARCHAR(255) DEFAULT NULL, -- Path to file
    boundary_wall ENUM("yes", "no") DEFAULT NULL,
    hazardous_areas ENUM("yes", "no") DEFAULT NULL,

    -- 5. Infrastructure & Facilities
    total_classrooms INT DEFAULT NULL,
    smart_classrooms INT DEFAULT 0,
    computer_lab ENUM("yes", "no") NOT NULL,
    science_labs ENUM("yes", "no") NOT NULL,
    composite_lab ENUM("yes", "no") DEFAULT NULL,
    math_lab ENUM("yes", "no") NOT NULL,
    library_area VARCHAR(100) DEFAULT NULL,
    num_books INT DEFAULT 0,
    digital_library ENUM("yes", "no") DEFAULT NULL,
    art_room ENUM("yes", "no") DEFAULT NULL,
    music_room ENUM("yes", "no") DEFAULT NULL,
    dance_room ENUM("yes", "no") DEFAULT NULL,
    medical_room ENUM("yes", "no") DEFAULT NULL,
    counselling_room ENUM("yes", "no") DEFAULT NULL,
    staff_room ENUM("yes", "no") DEFAULT NULL,
    cctv_cameras ENUM("yes", "no") DEFAULT NULL,
    ro_water ENUM("yes", "no") DEFAULT NULL,
    washrooms INT DEFAULT NULL,
    lift ENUM("yes", "no") DEFAULT NULL,
    ramps ENUM("yes", "no") DEFAULT NULL,

    -- 6. Staff Details
    total_teachers INT DEFAULT NULL,
    pgt_count INT DEFAULT 0,
    tgt_count INT DEFAULT 0,
    prt_count INT DEFAULT 0,
    special_educator INT DEFAULT 0,
    counsellor INT DEFAULT 0,
    admin_staff INT DEFAULT 0,
    accountant INT DEFAULT 0,
    lab_assistants INT DEFAULT 0,
    library_assistant INT DEFAULT 0,
    support_staff INT DEFAULT 0,
    teacher_qualification_records VARCHAR(255) DEFAULT NULL, -- Path to file
    appointment_letters VARCHAR(255) DEFAULT NULL, -- Path to file
    salary_slips VARCHAR(255) DEFAULT NULL, -- Path to file
    salary_through_bank ENUM("yes", "no") DEFAULT NULL,
    driver_verification ENUM("yes", "no") DEFAULT NULL,
    police_verification ENUM("yes", "no") DEFAULT NULL,

    -- 7. Student Details
    total_students INT DEFAULT NULL,
    boys_count INT DEFAULT 0,
    girls_count INT DEFAULT 0,
    class_wise_enrollment VARCHAR(255) DEFAULT NULL, -- Path to file
    section_per_class INT DEFAULT 0,
    student_teacher_ratio VARCHAR(20) DEFAULT NULL,
    special_needs_students INT DEFAULT 0,
    attendance_records VARCHAR(255) DEFAULT NULL, -- Path to file
    discipline_records VARCHAR(255) DEFAULT NULL, -- Path to file

    -- 8. Academic Details
    school_timetable VARCHAR(255) DEFAULT NULL, -- Path to file
    lesson_plans VARCHAR(255) DEFAULT NULL, -- Path to file
    teaching_aids VARCHAR(255) DEFAULT NULL, -- Path to file
    classroom_observation VARCHAR(255) DEFAULT NULL, -- Path to file
    homework_policy VARCHAR(255) DEFAULT NULL, -- Path to file
    internal_assessment VARCHAR(255) DEFAULT NULL, -- Path to file
    result_analysis VARCHAR(255) DEFAULT NULL, -- Path to file
    olympiads_participation VARCHAR(255) DEFAULT NULL, -- Path to file
    house_system ENUM("yes", "no") DEFAULT NULL,
    co_curricular_activities VARCHAR(255) DEFAULT NULL, -- Path to file

    -- 9. Laboratory School inspection
    physics_lab_equipment ENUM("adequate", "inadequate", "not-available") DEFAULT NULL,
    chemistry_lab_equipment ENUM("adequate", "inadequate", "not-available") DEFAULT NULL,
    biology_lab_equipment ENUM("adequate", "inadequate", "not-available") DEFAULT NULL,
    safety_equipment ENUM("adequate", "inadequate", "not-available") DEFAULT NULL,
    chemical_storage_safety ENUM("adequate", "inadequate", "not-available") DEFAULT NULL,
    lab_assistant_qualification VARCHAR(255) DEFAULT NULL,
    composite_lab_status ENUM("adequate", "inadequate", "not-available") DEFAULT NULL,

    -- 10. Library School inspection
    total_books_lib INT DEFAULT NULL,
    reference_books INT DEFAULT 0,
    magazines INT DEFAULT 0,
    e_library ENUM("yes", "no") DEFAULT NULL,
    library_management_system ENUM("yes", "no") DEFAULT NULL,
    issue_return_register ENUM("yes", "no") DEFAULT NULL,

    -- 11. Safety & Security
    cctv_details TEXT DEFAULT NULL,
    fire_extinguishers ENUM("adequate", "inadequate", "not-available") DEFAULT NULL,
    fire_drill_records VARCHAR(255) DEFAULT NULL, -- Path to file
    disaster_management_plan VARCHAR(255) DEFAULT NULL, -- Path to file
    first_aid_kits ENUM("adequate", "inadequate", "not-available") DEFAULT NULL,
    visitor_register ENUM("yes", "no") DEFAULT NULL,
    police_verification_status ENUM("yes", "no") DEFAULT NULL,
    child_protection_policy VARCHAR(255) DEFAULT NULL, -- Path to file
    complaint_box ENUM("yes", "no") DEFAULT NULL,

    -- 12. Transport Details
    num_buses INT DEFAULT 0,
    driver_license_verification ENUM("yes", "no") DEFAULT NULL,
    driver_police_verification ENUM("yes", "no") DEFAULT NULL,
    gps_installed ENUM("yes", "no") DEFAULT NULL,
    cctv_in_bus ENUM("yes", "no") DEFAULT NULL,
    first_aid_box_in_bus ENUM("yes", "no") DEFAULT NULL,
    fire_extinguisher_in_bus ENUM("yes", "no") DEFAULT NULL,
    speed_governor ENUM("yes", "no") DEFAULT NULL,
    rto_documents VARCHAR(255) DEFAULT NULL, -- Path to file

    -- 13. Mandatory Certificates
    building_safety_certificate VARCHAR(255) DEFAULT NULL, -- Path to file
    fire_safety_certificate VARCHAR(255) DEFAULT NULL, -- Path to file
    sanitation_certificate VARCHAR(255) DEFAULT NULL, -- Path to file
    water_testing_certificate VARCHAR(255) DEFAULT NULL, -- Path to file
    deo_certificate VARCHAR(255) DEFAULT NULL, -- Path to file
    land_documents VARCHAR(255) DEFAULT NULL, -- Path to file
    affidavit_of_trust VARCHAR(255) DEFAULT NULL, -- Path to file
    health_safety_audit_report VARCHAR(255) DEFAULT NULL, -- Path to file

    -- 14. Financial Information
    fee_structure VARCHAR(255) DEFAULT NULL, -- Path to file
    staff_salary_statement VARCHAR(255) DEFAULT NULL, -- Path to file
    epf_esi_details VARCHAR(255) DEFAULT NULL, -- Path to file
    audited_report VARCHAR(255) DEFAULT NULL, -- Path to file
    bank_statements VARCHAR(255) DEFAULT NULL, -- Path to file

    -- 15. Inspector Observations
    classroom_observation_notes TEXT DEFAULT NULL,
    teacher_interaction_notes TEXT DEFAULT NULL,
    student_interaction_notes TEXT DEFAULT NULL,
    infrastructure_condition TEXT DEFAULT NULL,
    discipline_notes TEXT DEFAULT NULL,
    strengths TEXT DEFAULT NULL,
    weaknesses TEXT DEFAULT NULL,

    -- 16. Final Recommendation
    recommendation ENUM("recommended", "conditional", "not-recommended") DEFAULT NULL,
    reasons TEXT DEFAULT NULL,
    compliance_required TEXT DEFAULT NULL,
    inspector_name VARCHAR(255) DEFAULT NULL,
    inspector_designation VARCHAR(255) DEFAULT NULL,
    inspector_signature VARCHAR(255) DEFAULT NULL, -- Path to file
    inspection_date DATE DEFAULT NULL,

    -- Status and Timestamps
    status ENUM("draft", "submitted", "inspected", "approved", "rejected") NOT NULL DEFAULT "draft",
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY `unique_inspection_per_session` (`school_name`, `session_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
$db->createTable($createSchoolInspectionsTableSql);

// --- Minimal School List Table for Dropdown ---
$createSchoolListTableSql = 'CREATE TABLE IF NOT EXISTS school_list (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_name VARCHAR(255) NOT NULL,
    school_code VARCHAR(100) DEFAULT NULL,
    status ENUM("active", "inactive") NOT NULL DEFAULT "active",
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';

$db->createTable($createSchoolListTableSql);

$alterSchoolInspectionsAddSession = 'ALTER TABLE school_inspections
    ADD COLUMN session_year VARCHAR(20) NOT NULL AFTER created_by';
$db->alterTable('school_inspections',$alterSchoolInspectionsAddSession);

$alterUserDetailsAddSchool = 'ALTER TABLE user_details
    ADD COLUMN school_name VARCHAR(255) DEFAULT NULL AFTER address';
$db->alterTable('user_details', $alterUserDetailsAddSchool);

$alterUserDetailsAddEmployeeId = 'ALTER TABLE user_details
    ADD COLUMN employee_id VARCHAR(100) DEFAULT NULL AFTER school_name';
$db->alterTable('user_details', $alterUserDetailsAddEmployeeId);

$alterTeacherAppraisalsAddSchool = 'ALTER TABLE teacher_appraisals
    ADD COLUMN school_name VARCHAR(255) DEFAULT NULL AFTER session_year';
$db->alterTable('teacher_appraisals', $alterTeacherAppraisalsAddSchool);