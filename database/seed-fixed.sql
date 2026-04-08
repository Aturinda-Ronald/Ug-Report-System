-- Uganda Results System - Clean Seed Data
USE uganda_results;

-- Schools
INSERT INTO schools (id, name, emis_number, address, phone, email, principal_name, school_type, level, district, region, established_year) VALUES
(1, 'Makerere College School', 'UG001', 'Plot 1, University Road, Kampala', '+256700000001', 'admin@makererecollege.ug', 'Dr. Sarah Nakamya', 'PRIVATE', 'SECONDARY', 'Kampala', 'Central', 1995),
(2, 'St. Mary\'s Secondary School Kisubi', 'UG002', 'Kisubi, Wakiso District', '+256700000002', 'admin@smss-kisubi.ug', 'Br. John Bosco', 'PRIVATE', 'SECONDARY', 'Wakiso', 'Central', 1906);

-- Users (Password: admin123 and student123)
INSERT INTO users (id, school_id, email, password_hash, first_name, last_name, role, phone, is_active) VALUES
(1, NULL, 'super@uganda-results.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 'SUPER_ADMIN', '+256700000000', TRUE),
(2, 1, 'admin@makererecollege.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mary', 'Nabwire', 'SCHOOL_ADMIN', '+256700000011', TRUE),
(3, 1, 'teacher1@makererecollege.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'James', 'Okello', 'STAFF', '+256700000012', TRUE),
(4, 2, 'admin@smss-kisubi.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Agnes', 'Namukasa', 'SCHOOL_ADMIN', '+256700000021', TRUE),
(5, 1, 'sarah.nakato@student.makererecollege.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah', 'Nakato', 'STUDENT', NULL, TRUE);

-- Academic Years
INSERT INTO academic_years (id, school_id, name, start_date, end_date, is_current) VALUES
(1, 1, '2024', '2024-02-05', '2024-11-30', TRUE),
(2, 2, '2024', '2024-02-05', '2024-11-30', TRUE);

-- Terms
INSERT INTO terms (id, school_id, academic_year_id, name, start_date, end_date, is_current) VALUES
(1, 1, 1, 'Term 1', '2024-02-05', '2024-05-17', FALSE),
(2, 1, 1, 'Term 2', '2024-05-27', '2024-08-23', FALSE),
(3, 1, 1, 'Term 3', '2024-09-02', '2024-11-30', TRUE),
(4, 2, 2, 'Term 1', '2024-02-05', '2024-05-17', FALSE),
(5, 2, 2, 'Term 2', '2024-05-27', '2024-08-23', FALSE),
(6, 2, 2, 'Term 3', '2024-09-02', '2024-11-30', TRUE);

-- Classes
INSERT INTO classes (id, school_id, name, level, year_group, class_teacher_id) VALUES
(1, 1, 'Senior 1', 'O_LEVEL', 1, 3),
(2, 1, 'Senior 2', 'O_LEVEL', 2, 3),
(3, 1, 'Senior 3', 'O_LEVEL', 3, 3),
(4, 1, 'Senior 4', 'O_LEVEL', 4, 3),
(5, 1, 'Senior 5', 'A_LEVEL', 5, 3),
(6, 1, 'Senior 6', 'A_LEVEL', 6, 3),
(7, 2, 'Senior 1', 'O_LEVEL', 1, 4),
(8, 2, 'Senior 2', 'O_LEVEL', 2, 4),
(9, 2, 'Senior 3', 'O_LEVEL', 3, 4),
(10, 2, 'Senior 4', 'O_LEVEL', 4, 4);

-- Streams
INSERT INTO streams (id, school_id, class_id, name) VALUES
(1, 1, 1, 'A'), (2, 1, 1, 'B'),
(3, 1, 2, 'A'), (4, 1, 2, 'B'),
(5, 1, 3, 'A'), (6, 1, 3, 'B'),
(7, 1, 4, 'A'), (8, 1, 4, 'B'),
(9, 1, 5, 'PCM'), (10, 1, 5, 'PCB'),
(11, 1, 6, 'PCM'), (12, 1, 6, 'PCB'),
(13, 2, 7, 'A'), (14, 2, 8, 'A'),
(15, 2, 9, 'A'), (16, 2, 10, 'A');

-- Subjects
INSERT INTO subjects (id, school_id, name, code, level, category, max_mark, pass_mark) VALUES
(1, 1, 'English Language', 'ENG', 'O_LEVEL', 'CORE', 100, 50),
(2, 1, 'Mathematics', 'MATH', 'O_LEVEL', 'CORE', 100, 50),
(3, 1, 'Physics', 'PHY', 'O_LEVEL', 'CORE', 100, 50),
(4, 1, 'Chemistry', 'CHEM', 'O_LEVEL', 'CORE', 100, 50),
(5, 1, 'Biology', 'BIO', 'O_LEVEL', 'CORE', 100, 50),
(6, 1, 'History', 'HIST', 'O_LEVEL', 'CORE', 100, 50),
(7, 1, 'Geography', 'GEOG', 'O_LEVEL', 'CORE', 100, 50),
(8, 1, 'Religious Education', 'RE', 'O_LEVEL', 'CORE', 100, 50),
(9, 1, 'Literature in English', 'LIT', 'O_LEVEL', 'ELECTIVE', 100, 50),
(10, 1, 'Fine Art', 'ART', 'O_LEVEL', 'ELECTIVE', 100, 50),
(11, 1, 'Music', 'MUS', 'O_LEVEL', 'ELECTIVE', 100, 50),
(12, 1, 'Commerce', 'COMM', 'O_LEVEL', 'ELECTIVE', 100, 50),
(13, 1, 'Entrepreneurship', 'ENT', 'O_LEVEL', 'ELECTIVE', 100, 50),
(14, 1, 'Computer Studies', 'COMP', 'O_LEVEL', 'ELECTIVE', 100, 50),
(15, 1, 'Agriculture', 'AGRIC', 'O_LEVEL', 'ELECTIVE', 100, 50),
(16, 1, 'Technical Drawing', 'TD', 'O_LEVEL', 'ELECTIVE', 100, 50),
(17, 1, 'Mathematics', 'AMATH', 'A_LEVEL', 'CORE', 100, 50),
(18, 1, 'Physics', 'APHY', 'A_LEVEL', 'CORE', 100, 50),
(19, 1, 'Chemistry', 'ACHEM', 'A_LEVEL', 'CORE', 100, 50),
(20, 1, 'Biology', 'ABIO', 'A_LEVEL', 'CORE', 100, 50),
(21, 1, 'General Paper', 'GP', 'A_LEVEL', 'CORE', 100, 50),
(22, 1, 'Computer Science', 'CS', 'A_LEVEL', 'ELECTIVE', 100, 50);

-- Copy subjects for School 2
INSERT INTO subjects (school_id, name, code, level, category, max_mark, pass_mark)
SELECT 2, name, code, level, category, max_mark, pass_mark
FROM subjects WHERE school_id = 1 AND level = 'O_LEVEL' AND category = 'CORE';

-- Grade Scales
INSERT INTO grade_scales (id, school_id, name, level, is_default) VALUES
(1, 1, 'UCE Grade Scale', 'O_LEVEL', TRUE),
(2, 1, 'UACE Grade Scale', 'A_LEVEL', TRUE),
(3, 2, 'UCE Grade Scale', 'O_LEVEL', TRUE);

-- Grade Scale Items
INSERT INTO grade_scale_items (grade_scale_id, grade_code, grade_name, min_mark, max_mark, points, interpretation, color, sort_order) VALUES
(1, 'D1', 'Distinction 1', 80, 100, 9.0, 'Excellent', '#059669', 1),
(1, 'D2', 'Distinction 2', 70, 79, 8.0, 'Very Good', '#0891b2', 2),
(1, 'C3', 'Credit 3', 65, 69, 7.0, 'Good', '#0d9488', 3),
(1, 'C4', 'Credit 4', 60, 64, 6.0, 'Good', '#10b981', 4),
(1, 'C5', 'Credit 5', 55, 59, 5.0, 'Fairly Good', '#22c55e', 5),
(1, 'C6', 'Credit 6', 50, 54, 4.0, 'Fairly Good', '#84cc16', 6),
(1, 'P7', 'Pass 7', 45, 49, 3.0, 'Pass', '#eab308', 7),
(1, 'P8', 'Pass 8', 40, 44, 2.0, 'Pass', '#f59e0b', 8),
(1, 'F9', 'Fail 9', 0, 39, 1.0, 'Fail', '#ef4444', 9),
(2, 'A', 'A Grade', 80, 100, 6.0, 'Excellent', '#059669', 1),
(2, 'B', 'B Grade', 70, 79, 5.0, 'Very Good', '#0891b2', 2),
(2, 'C', 'C Grade', 60, 69, 4.0, 'Good', '#0d9488', 3),
(2, 'D', 'D Grade', 50, 59, 3.0, 'Pass', '#22c55e', 4),
(2, 'E', 'E Grade', 45, 49, 2.0, 'Pass', '#eab308', 5),
(2, 'O', 'O Grade', 40, 44, 1.0, 'Ordinary Level', '#f59e0b', 6),
(2, 'F', 'F Grade', 0, 39, 0.0, 'Fail', '#ef4444', 7);

-- Copy grade scale for School 2
INSERT INTO grade_scale_items (grade_scale_id, grade_code, grade_name, min_mark, max_mark, points, interpretation, color, sort_order)
SELECT 3, grade_code, grade_name, min_mark, max_mark, points, interpretation, color, sort_order
FROM grade_scale_items WHERE grade_scale_id = 1;

-- Assessment Types
INSERT INTO assessment_types (id, school_id, name, code, weight, max_mark, is_exam) VALUES
(1, 1, 'Bot (Beginning of Term)', 'BOT', 30.0, 100, FALSE),
(2, 1, 'Mid Term Exam', 'MID', 30.0, 100, TRUE),
(3, 1, 'End of Term Exam', 'EOT', 40.0, 100, TRUE),
(4, 2, 'Bot (Beginning of Term)', 'BOT', 30.0, 100, FALSE),
(5, 2, 'Mid Term Exam', 'MID', 30.0, 100, TRUE),
(6, 2, 'End of Term Exam', 'EOT', 40.0, 100, TRUE);

-- Students
INSERT INTO students (id, school_id, user_id, index_no, first_name, last_name, gender, date_of_birth, class_id, stream_id, admission_date, status, guardian_name, guardian_phone) VALUES
(1, 1, 5, 'U001/2024', 'Sarah', 'Nakato', 'F', '2008-03-15', 1, 1, '2024-02-01', 'ACTIVE', 'Grace Nakato', '+256700111001'),
(2, 1, NULL, 'U002/2024', 'John', 'Mukasa', 'M', '2008-07-22', 1, 1, '2024-02-01', 'ACTIVE', 'Paul Mukasa', '+256700111002'),
(3, 1, NULL, 'U003/2024', 'Mary', 'Nakiranda', 'F', '2008-01-10', 1, 2, '2024-02-01', 'ACTIVE', 'Agnes Nakiranda', '+256700111003'),
(4, 1, NULL, 'U004/2024', 'David', 'Ssempa', 'M', '2008-11-05', 1, 2, '2024-02-01', 'ACTIVE', 'Robert Ssempa', '+256700111004'),
(5, 1, NULL, 'U005/2024', 'Grace', 'Namuli', 'F', '2007-06-18', 2, 3, '2023-02-01', 'ACTIVE', 'Ruth Namuli', '+256700111005'),
(6, 1, NULL, 'U006/2024', 'Peter', 'Kalema', 'M', '2007-09-12', 2, 3, '2023-02-01', 'ACTIVE', 'Joseph Kalema', '+256700111006'),
(7, 1, NULL, 'U007/2024', 'Rebecca', 'Aling', 'F', '2006-04-25', 3, 5, '2022-02-01', 'ACTIVE', 'Margaret Aling', '+256700111007'),
(8, 1, NULL, 'U008/2024', 'Samuel', 'Odongo', 'M', '2006-12-08', 3, 5, '2022-02-01', 'ACTIVE', 'Francis Odongo', '+256700111008'),
(9, 1, NULL, 'U009/2024', 'Esther', 'Namusoke', 'F', '2005-02-14', 4, 7, '2021-02-01', 'ACTIVE', 'Joyce Namusoke', '+256700111009'),
(10, 1, NULL, 'U010/2024', 'Moses', 'Kato', 'M', '2005-08-30', 4, 7, '2021-02-01', 'ACTIVE', 'Daniel Kato', '+256700111010'),
(11, 1, NULL, 'U011/2024', 'Lydia', 'Anyango', 'F', '2004-05-17', 5, 9, '2020-02-01', 'ACTIVE', 'Stella Anyango', '+256700111011'),
(12, 1, NULL, 'U012/2024', 'Emmanuel', 'Byaruhanga', 'M', '2004-10-03', 5, 9, '2020-02-01', 'ACTIVE', 'Charles Byaruhanga', '+256700111012'),
(13, 1, NULL, 'U013/2024', 'Faith', 'Nalwanga', 'F', '2003-07-21', 6, 11, '2019-02-01', 'ACTIVE', 'Catherine Nalwanga', '+256700111013'),
(14, 1, NULL, 'U014/2024', 'Isaac', 'Tumwebaze', 'M', '2003-12-11', 6, 11, '2019-02-01', 'ACTIVE', 'James Tumwebaze', '+256700111014'),
(15, 1, NULL, 'U015/2024', 'Priscilla', 'Nakabuye', 'F', '2008-09-26', 1, 1, '2024-02-01', 'ACTIVE', 'Mary Nakabuye', '+256700111015'),
(16, 1, NULL, 'U016/2024', 'Andrew', 'Ssekalala', 'M', '2008-04-14', 1, 2, '2024-02-01', 'ACTIVE', 'John Ssekalala', '+256700111016');

-- Student subject enrollments
INSERT INTO student_subjects (school_id, student_id, subject_id, academic_year_id) VALUES
(1, 1, 1, 1), (1, 1, 2, 1), (1, 1, 3, 1), (1, 1, 4, 1), (1, 1, 5, 1), (1, 1, 6, 1), (1, 1, 7, 1), (1, 1, 8, 1),
(1, 2, 1, 1), (1, 2, 2, 1), (1, 2, 3, 1), (1, 2, 4, 1), (1, 2, 5, 1), (1, 2, 6, 1), (1, 2, 7, 1), (1, 2, 8, 1);

-- Sample marks
INSERT INTO marks (school_id, student_id, subject_id, assessment_type_id, term_id, marks_obtained, marks_possible, percentage, grade_code, points, entered_by) VALUES
(1, 1, 1, 1, 1, 85, 100, 85.0, 'D1', 9.0, 3),
(1, 1, 2, 1, 1, 78, 100, 78.0, 'D2', 8.0, 3),
(1, 1, 3, 1, 1, 92, 100, 92.0, 'D1', 9.0, 3),
(1, 1, 4, 1, 1, 81, 100, 81.0, 'D1', 9.0, 3),
(1, 1, 5, 1, 1, 76, 100, 76.0, 'D2', 8.0, 3),
(1, 1, 6, 1, 1, 88, 100, 88.0, 'D1', 9.0, 3),
(1, 1, 7, 1, 1, 83, 100, 83.0, 'D1', 9.0, 3),
(1, 1, 8, 1, 1, 79, 100, 79.0, 'D2', 8.0, 3);

-- Sample report card
INSERT INTO report_cards (school_id, student_id, term_id, class_position, stream_position, total_marks, possible_marks, percentage, total_points, division, remarks, generated_by) VALUES
(1, 1, 1, 1, 1, 662, 800, 82.75, 68.0, 'I', 'Excellent performance. Keep up the good work!', 3);

-- Activity logs
INSERT INTO activity_logs (school_id, user_id, action, resource_type, resource_id, description, ip_address) VALUES
(1, 2, 'LOGIN', 'USER', 2, 'School admin logged in', '127.0.0.1'),
(1, 3, 'CREATE_MARKS', 'MARKS', 1, 'Added BOT marks for Sarah Nakato - English', '127.0.0.1'),
(1, 3, 'GENERATE_REPORT', 'REPORT_CARD', 1, 'Generated Term 1 report card for Sarah Nakato', '127.0.0.1');
