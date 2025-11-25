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
    role ENUM("superadmin","admin","volunteer","user") DEFAULT "user",
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
    
    -- 1. Personal Information
    full_name VARCHAR(255) NOT NULL,
    employee_code VARCHAR(100) NOT NULL UNIQUE,
    designation VARCHAR(255) NOT NULL,
    department VARCHAR(255) NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM("male", "female", "other") NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    date_of_joining DATE NOT NULL,
    total_experience INT NOT NULL,
    highest_qualification VARCHAR(255) NOT NULL,
    professional_qualification VARCHAR(255) DEFAULT NULL,
    additional_certifications TEXT DEFAULT NULL,

    -- 2. Academic Responsibilities
    classes_subjects_taught TEXT NOT NULL,
    teaching_hours INT NOT NULL,
    syllabus_completion INT NOT NULL,
    lesson_planning ENUM("regularly", "sometimes", "rarely") NOT NULL,
    teaching_aids ENUM("regularly", "sometimes", "rarely") NOT NULL,
    teaching_methods TEXT NOT NULL,
    student_engagement TEXT NOT NULL,
    homework_management ENUM("excellent", "good", "average", "needs-improvement") NOT NULL,
    remedial_classes ENUM("regularly", "sometimes", "rarely", "never") NOT NULL,
    slow_learner_support TEXT NOT NULL,

    -- 3. Classroom Management
    classroom_discipline ENUM("excellent", "good", "average", "needs-improvement") NOT NULL,
    seating_plan ENUM("regularly-updated", "sometimes-updated", "rarely-updated", "not-updated") NOT NULL,
    classroom_cleanliness ENUM("excellent", "good", "average", "needs-improvement") NOT NULL,
    notice_board_maintenance ENUM("regularly-maintained", "sometimes-maintained", "rarely-maintained", "not-maintained") NOT NULL,
    behaviour_handling TEXT NOT NULL,
    inclusiveness_special_needs TEXT NOT NULL,
    ptm_interactions TEXT NOT NULL,

    -- 4. Student Performance & Outcomes
    class_result INT NOT NULL,
    board_exam_performance TEXT NOT NULL,
    olympiad_participation TEXT NOT NULL,
    outstanding_students TEXT NOT NULL,
    remedial_enrichment_results TEXT NOT NULL,

    -- 5. Co-curricular Activities
    exam_duties ENUM("regularly-performed", "sometimes-performed", "rarely-performed", "never-performed") NOT NULL,
    discipline_duties ENUM("regularly-performed", "sometimes-performed", "rarely-performed", "never-performed") NOT NULL,
    event_management TEXT NOT NULL,
    house_club_activities TEXT NOT NULL,
    assembly_duties ENUM("regularly-performed", "sometimes-performed", "rarely-performed", "never-performed") NOT NULL,
    competition_training TEXT NOT NULL,
    committee_participation TEXT NOT NULL,

    -- 6. Professional Development
    trainings_attended TEXT NOT NULL,
    workshops_conducted TEXT NOT NULL,
    courses_completed TEXT NOT NULL,
    academic_innovation TEXT NOT NULL,
    research_publications TEXT NOT NULL,

    -- 7. Personal Strengths & Skills
    areas_of_expertise TEXT NOT NULL,
    leadership_qualities TEXT NOT NULL,
    communication_skills ENUM("excellent", "good", "average", "needs-improvement") NOT NULL,
    time_management ENUM("excellent", "good", "average", "needs-improvement") NOT NULL,
    team_collaboration ENUM("excellent", "good", "average", "needs-improvement") NOT NULL,
    creativity ENUM("excellent", "good", "average", "needs-improvement") NOT NULL,
    technology_usage ENUM("excellent", "good", "average", "needs-improvement") NOT NULL,

    -- 8. Areas of Improvement
    skills_to_improve TEXT NOT NULL,
    training_required TEXT NOT NULL,
    weaknesses_identified TEXT NOT NULL,
    support_expected TEXT NOT NULL,

    -- 9. Contribution to School
    discipline_support TEXT NOT NULL,
    school_growth_contribution TEXT NOT NULL,
    student_relationship ENUM("excellent", "good", "average", "needs-improvement") NOT NULL,
    colleague_relationship ENUM("excellent", "good", "average", "needs-improvement") NOT NULL,
    teaching_innovations TEXT NOT NULL,
    extra_initiatives TEXT NOT NULL,

    -- 10. Goals for Next Year
    academic_goals TEXT NOT NULL,
    professional_development_goals TEXT NOT NULL,
    student_learning_goals TEXT NOT NULL,
    personal_goals TEXT NOT NULL,

    -- 11. Code of Conduct Compliance
    punctuality ENUM("excellent", "good", "average", "needs-improvement") NOT NULL,
    dress_code ENUM("always-followed", "mostly-followed", "sometimes-followed", "rarely-followed") NOT NULL,
    ethical_behaviour ENUM("excellent", "good", "average", "needs-improvement") NOT NULL,
    confidentiality ENUM("always-maintained", "mostly-maintained", "sometimes-maintained", "rarely-maintained") NOT NULL,
    school_policies_adherence ENUM("always-followed", "mostly-followed", "sometimes-followed", "rarely-followed") NOT NULL,

    -- 12. Overall Self-Rating
    teaching_quality_rating INT NOT NULL,
    classroom_management_rating INT NOT NULL,
    communication_rating INT NOT NULL,
    co_curricular_rating INT NOT NULL,
    professional_development_rating INT NOT NULL,
    student_relationship_rating INT NOT NULL,

    -- 13. Teacher Declaration
    declaration BOOLEAN NOT NULL,
    teacher_signature VARCHAR(255) DEFAULT NULL, -- Path to file
    declaration_date DATE NOT NULL,

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
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
$db->createTable($createTeacherAppraisalsTableSql);


// --- Table for School Inspection Form ---
$createSchoolInspectionsTableSql = 'CREATE TABLE IF NOT EXISTS school_inspections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    created_by INT NOT NULL,

    -- 1. School Details
    school_name VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    pincode VARCHAR(10) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    website VARCHAR(255) DEFAULT NULL,
    year_established INT NOT NULL,
    school_type ENUM("residential", "non-residential", "day-boarding") NOT NULL,
    medium VARCHAR(100) NOT NULL,
    school_category ENUM("co-ed", "boys", "girls") NOT NULL,

    -- 2. Affiliation Details
    application_type ENUM("fresh", "extension", "upgradation", "switch-over") NOT NULL,
    affiliation_number VARCHAR(100) DEFAULT NULL,
    school_code VARCHAR(100) DEFAULT NULL,
    affiliation_status ENUM("provisional", "regular") NOT NULL,
    affiliation_validity DATE DEFAULT NULL,
    classes_from VARCHAR(20) DEFAULT NULL,
    classes_to VARCHAR(20) DEFAULT NULL,
    proposed_classes VARCHAR(100) DEFAULT NULL,

    -- 3. Management Details
    trust_name VARCHAR(255) NOT NULL,
    registration_number VARCHAR(100) NOT NULL,
    registration_validity DATE DEFAULT NULL,
    pan_number VARCHAR(50) DEFAULT NULL,
    registered_address TEXT NOT NULL,
    chairman_name VARCHAR(255) NOT NULL,
    manager_name VARCHAR(255) NOT NULL,
    principal_name VARCHAR(255) NOT NULL,
    principal_qualification VARCHAR(255) NOT NULL,
    principal_experience INT NOT NULL,
    audit_reports VARCHAR(255) DEFAULT NULL, -- Path to file
    moa_docs VARCHAR(255) DEFAULT NULL, -- Path to file

    -- 4. Land & Building Details
    land_area VARCHAR(100) NOT NULL,
    ownership_type ENUM("owned", "leased") NOT NULL,
    land_doc_type VARCHAR(255) NOT NULL,
    land_title ENUM("yes", "no") NOT NULL,
    land_contiguous ENUM("yes", "no") NOT NULL,
    built_up_area VARCHAR(100) NOT NULL,
    playground_area VARCHAR(100) NOT NULL,
    num_floors INT NOT NULL,
    building_safety_cert VARCHAR(255) DEFAULT NULL, -- Path to file
    fire_safety_cert VARCHAR(255) DEFAULT NULL, -- Path to file
    sanitation_cert VARCHAR(255) DEFAULT NULL, -- Path to file
    water_testing_report VARCHAR(255) DEFAULT NULL, -- Path to file
    boundary_wall ENUM("yes", "no") NOT NULL,
    hazardous_areas ENUM("yes", "no") NOT NULL,

    -- 5. Infrastructure & Facilities
    total_classrooms INT NOT NULL,
    smart_classrooms INT DEFAULT 0,
    computer_lab ENUM("yes", "no") NOT NULL,
    science_labs ENUM("yes", "no") NOT NULL,
    composite_lab ENUM("yes", "no") NOT NULL,
    math_lab ENUM("yes", "no") NOT NULL,
    library_area VARCHAR(100) DEFAULT NULL,
    num_books INT DEFAULT 0,
    digital_library ENUM("yes", "no") DEFAULT NULL,
    art_room ENUM("yes", "no") DEFAULT NULL,
    music_room ENUM("yes", "no") DEFAULT NULL,
    dance_room ENUM("yes", "no") DEFAULT NULL,
    medical_room ENUM("yes", "no") NOT NULL,
    counselling_room ENUM("yes", "no") DEFAULT NULL,
    staff_room ENUM("yes", "no") NOT NULL,
    cctv_cameras ENUM("yes", "no") NOT NULL,
    ro_water ENUM("yes", "no") NOT NULL,
    washrooms INT NOT NULL,
    lift ENUM("yes", "no") DEFAULT NULL,
    ramps ENUM("yes", "no") NOT NULL,

    -- 6. Staff Details
    total_teachers INT NOT NULL,
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
    salary_through_bank ENUM("yes", "no") NOT NULL,
    driver_verification ENUM("yes", "no") NOT NULL,
    police_verification ENUM("yes", "no") NOT NULL,

    -- 7. Student Details
    total_students INT NOT NULL,
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
    physics_lab_equipment ENUM("adequate", "inadequate", "not-available") NOT NULL,
    chemistry_lab_equipment ENUM("adequate", "inadequate", "not-available") NOT NULL,
    biology_lab_equipment ENUM("adequate", "inadequate", "not-available") NOT NULL,
    safety_equipment ENUM("adequate", "inadequate", "not-available") NOT NULL,
    chemical_storage_safety ENUM("adequate", "inadequate", "not-available") NOT NULL,
    lab_assistant_qualification VARCHAR(255) DEFAULT NULL,
    composite_lab_status ENUM("adequate", "inadequate", "not-available") NOT NULL,

    -- 10. Library School inspection
    total_books_lib INT NOT NULL,
    reference_books INT DEFAULT 0,
    magazines INT DEFAULT 0,
    e_library ENUM("yes", "no") NOT NULL,
    library_management_system ENUM("yes", "no") NOT NULL,
    issue_return_register ENUM("yes", "no") NOT NULL,

    -- 11. Safety & Security
    cctv_details TEXT NOT NULL,
    fire_extinguishers ENUM("adequate", "inadequate", "not-available") NOT NULL,
    fire_drill_records VARCHAR(255) DEFAULT NULL, -- Path to file
    disaster_management_plan VARCHAR(255) DEFAULT NULL, -- Path to file
    first_aid_kits ENUM("adequate", "inadequate", "not-available") NOT NULL,
    visitor_register ENUM("yes", "no") NOT NULL,
    police_verification_status ENUM("yes", "no") NOT NULL,
    child_protection_policy VARCHAR(255) DEFAULT NULL, -- Path to file
    complaint_box ENUM("yes", "no") NOT NULL,

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
    classroom_observation_notes TEXT NOT NULL,
    teacher_interaction_notes TEXT NOT NULL,
    student_interaction_notes TEXT NOT NULL,
    infrastructure_condition TEXT NOT NULL,
    discipline_notes TEXT NOT NULL,
    strengths TEXT NOT NULL,
    weaknesses TEXT NOT NULL,

    -- 16. Final Recommendation
    recommendation ENUM("recommended", "conditional", "not-recommended") NOT NULL,
    reasons TEXT NOT NULL,
    compliance_required TEXT NOT NULL,
    inspector_name VARCHAR(255) NOT NULL,
    inspector_designation VARCHAR(255) NOT NULL,
    inspector_signature VARCHAR(255) DEFAULT NULL, -- Path to file
    inspection_date DATE NOT NULL,

    -- Status and Timestamps
    status ENUM("draft", "submitted", "inspected", "approved", "rejected") NOT NULL DEFAULT "draft",
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
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


