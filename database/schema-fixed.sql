-- Uganda Results System - Clean Database Schema
SET FOREIGN_KEY_CHECKS = 0;
DROP DATABASE IF EXISTS uganda_results;
CREATE DATABASE uganda_results CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE uganda_results;

-- Schools
CREATE TABLE schools (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    emis_number VARCHAR(20) UNIQUE,
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(255),
    website VARCHAR(255),
    logo_url VARCHAR(500),
    principal_name VARCHAR(255),
    school_type ENUM('GOVERNMENT', 'PRIVATE', 'COMMUNITY') DEFAULT 'GOVERNMENT',
    level ENUM('SECONDARY', 'PRIMARY', 'BOTH') DEFAULT 'SECONDARY',
    district VARCHAR(100),
    region VARCHAR(100),
    established_year YEAR,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Users
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role ENUM('SUPER_ADMIN', 'SCHOOL_ADMIN', 'STAFF', 'STUDENT') NOT NULL,
    phone VARCHAR(20),
    avatar_url VARCHAR(500),
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    password_reset_token VARCHAR(255),
    password_reset_expires TIMESTAMP NULL,
    email_verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Academic years
CREATE TABLE academic_years (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_current BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Terms
CREATE TABLE terms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    academic_year_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_current BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Classes
CREATE TABLE classes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    level ENUM('O_LEVEL', 'A_LEVEL') NOT NULL,
    year_group INT NOT NULL,
    class_teacher_id INT,
    capacity INT DEFAULT 40,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Streams
CREATE TABLE streams (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    class_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    capacity INT DEFAULT 40,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Subjects
CREATE TABLE subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) NOT NULL,
    level ENUM('O_LEVEL', 'A_LEVEL', 'BOTH') NOT NULL,
    category ENUM('CORE', 'ELECTIVE', 'SUBSIDIARY') DEFAULT 'CORE',
    is_practical BOOLEAN DEFAULT FALSE,
    max_mark DECIMAL(5,2) DEFAULT 100.00,
    pass_mark DECIMAL(5,2) DEFAULT 50.00,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Grade scales
CREATE TABLE grade_scales (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    level ENUM('O_LEVEL', 'A_LEVEL') NOT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Grade scale items
CREATE TABLE grade_scale_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    grade_scale_id INT NOT NULL,
    grade_code VARCHAR(10) NOT NULL,
    grade_name VARCHAR(50) NOT NULL,
    min_mark DECIMAL(5,2) NOT NULL,
    max_mark DECIMAL(5,2) NOT NULL,
    points DECIMAL(3,1) NOT NULL,
    interpretation TEXT,
    color VARCHAR(7) DEFAULT '#6b7280',
    sort_order INT DEFAULT 0
);

-- Students
CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    user_id INT,
    index_no VARCHAR(50) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    other_names VARCHAR(100),
    gender ENUM('M', 'F') NOT NULL,
    date_of_birth DATE,
    class_id INT,
    stream_id INT,
    admission_date DATE,
    graduation_date DATE,
    status ENUM('ACTIVE', 'GRADUATED', 'TRANSFERRED', 'DROPPED') DEFAULT 'ACTIVE',
    guardian_name VARCHAR(200),
    guardian_phone VARCHAR(20),
    guardian_email VARCHAR(255),
    address TEXT,
    photo_url VARCHAR(500),
    medical_info TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Student subjects
CREATE TABLE student_subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    academic_year_id INT NOT NULL,
    enrollment_date DATE,
    is_active BOOLEAN DEFAULT TRUE
);

-- Assessment types
CREATE TABLE assessment_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) NOT NULL,
    weight DECIMAL(5,2) NOT NULL,
    max_mark DECIMAL(5,2) DEFAULT 100.00,
    is_exam BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Marks
CREATE TABLE marks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    assessment_type_id INT NOT NULL,
    term_id INT NOT NULL,
    marks_obtained DECIMAL(5,2),
    marks_possible DECIMAL(5,2) DEFAULT 100.00,
    percentage DECIMAL(5,2),
    grade_code VARCHAR(10),
    points DECIMAL(3,1),
    remarks TEXT,
    entered_by INT,
    entered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Report cards
CREATE TABLE report_cards (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    student_id INT NOT NULL,
    term_id INT NOT NULL,
    class_position INT,
    stream_position INT,
    total_marks DECIMAL(8,2),
    possible_marks DECIMAL(8,2),
    percentage DECIMAL(5,2),
    total_points DECIMAL(6,1),
    division VARCHAR(10),
    remarks TEXT,
    next_term_begins DATE,
    generated_by INT,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    pdf_url VARCHAR(500),
    is_published BOOLEAN DEFAULT FALSE,
    published_at TIMESTAMP NULL
);

-- Activity logs
CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    resource_type VARCHAR(50),
    resource_id INT,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User sessions
CREATE TABLE user_sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    payload TEXT,
    last_activity INT
);

SET FOREIGN_KEY_CHECKS = 1;
