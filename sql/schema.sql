-- StudentHub Portal Relational Database Schema
-- Normalized up to 3NF

CREATE DATABASE IF NOT EXISTS studenthub_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE studenthub_db;

-- 1. Users Table (Authentication and Role-Based Access)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'student') NOT NULL DEFAULT 'student',
    is_active TINYINT NOT NULL DEFAULT 1,
    remember_token_hash VARCHAR(64) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Students Table (Academic Profile Information)
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    enrollment_no VARCHAR(20) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    mobile VARCHAR(15) NOT NULL,
    course VARCHAR(100) NOT NULL,
    year VARCHAR(50) NOT NULL,
    gender VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_students_users FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Events Table (Campus Events and Poster Directory)
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    venue VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    poster_path VARCHAR(255) DEFAULT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_events_users FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Registrations Table (Student Event Enrollment)
CREATE TABLE IF NOT EXISTS registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    event_id INT NOT NULL,
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('confirmed', 'attended', 'cancelled') DEFAULT 'confirmed',
    CONSTRAINT unique_student_event UNIQUE (student_id, event_id),
    CONSTRAINT fk_reg_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_reg_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Audit Logs Table (Operational Audit Trail)
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action_name VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Clean existing data for reproducible seeding
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE registrations;
TRUNCATE TABLE audit_logs;
TRUNCATE TABLE events;
TRUNCATE TABLE students;
TRUNCATE TABLE users;
SET FOREIGN_KEY_CHECKS = 1;

-- Seed Users: Admin (password: admin123) and Student (password: student123)
-- bcrypt hashes generated via password_hash('...', PASSWORD_DEFAULT)
INSERT INTO users (id, username, email, password_hash, role, is_active) VALUES
(1, 'admin', 'admin@charusat.edu.in', '$2y$10$wO3tQvE6QG1zCq.7b51e0.P4kG5oHw1v9lXyP0xQ3q1k4WzZl.7v2', 'admin', 1),
(2, 'candidate99', 'student@charusat.edu.in', '$2y$10$mRz5rB7uX2mE8q7GgqQz.uL9hUvP7r0lQ9fK2k8P6zS2rT1v8v4W2', 'student', 1);

-- Seed 21 Student Records (STU2026001 to STU2026021)
INSERT INTO students (id, user_id, enrollment_no, full_name, email, mobile, course, year, gender) VALUES
(1, 2, 'STU2026001', 'Student 01', 'student@charusat.edu.in', '9800000001', 'B.Tech Computer Engineering', 'Second Year', 'Male'),
(2, NULL, 'STU2026002', 'Student 02', 'student02@charusat.edu.in', '9800000002', 'B.Tech IT', 'First Year', 'Female'),
(3, NULL, 'STU2026003', 'Student 03', 'student03@charusat.edu.in', '9800000003', 'B.Tech Computer Engineering', 'Third Year', 'Male'),
(4, NULL, 'STU2026004', 'Student 04', 'student04@charusat.edu.in', '9800000004', 'BCA', 'First Year', 'Female'),
(5, NULL, 'STU2026005', 'Student 05', 'student05@charusat.edu.in', '9800000005', 'B.Tech IT', 'Second Year', 'Male'),
(6, NULL, 'STU2026006', 'Student 06', 'student06@charusat.edu.in', '9800000006', 'B.Tech Computer Engineering', 'Fourth Year', 'Female'),
(7, NULL, 'STU2026007', 'Student 07', 'student07@charusat.edu.in', '9800000007', 'B.Tech IT', 'Third Year', 'Male'),
(8, NULL, 'STU2026008', 'Student 08', 'student08@charusat.edu.in', '9800000008', 'BCA', 'First Year', 'Female'),
(9, NULL, 'STU2026009', 'Student 09', 'student09@charusat.edu.in', '9800000009', 'BCA', 'Second Year', 'Male'),
(10, NULL, 'STU2026010', 'Student 10', 'student10@charusat.edu.in', '9800000010', 'BCA', 'Third Year', 'Female'),
(11, NULL, 'STU2026011', 'Student 11', 'student11@charusat.edu.in', '9800000011', 'B.Tech Computer Engineering', 'Third Year', 'Male'),
(12, NULL, 'STU2026012', 'Student 12', 'student12@charusat.edu.in', '9800000012', 'B.Tech Computer Engineering', 'Fourth Year', 'Female'),
(13, NULL, 'STU2026013', 'Student 13', 'student13@charusat.edu.in', '9800000013', 'B.Tech Computer Engineering', 'Second Year', 'Male'),
(14, NULL, 'STU2026014', 'Student 14', 'student14@charusat.edu.in', '9800000014', 'B.Tech IT', 'Fourth Year', 'Female'),
(15, NULL, 'STU2026015', 'Student 15', 'student15@charusat.edu.in', '9800000015', 'BCA', 'Second Year', 'Male'),
(16, NULL, 'STU2026016', 'Student 16', 'student16@charusat.edu.in', '9800000016', 'B.Tech Computer Engineering', 'First Year', 'Female'),
(17, NULL, 'STU2026017', 'Student 17', 'student17@charusat.edu.in', '9800000017', 'B.Tech Computer Engineering', 'Third Year', 'Male'),
(18, NULL, 'STU2026018', 'Student 18', 'student18@charusat.edu.in', '9800000018', 'B.Tech IT', 'Third Year', 'Female'),
(19, NULL, 'STU2026019', 'Student 19', 'student19@charusat.edu.in', '9800000019', 'BCA', 'Third Year', 'Male'),
(20, NULL, 'STU2026020', 'Student 20', 'student20@charusat.edu.in', '9800000020', 'B.Tech Computer Engineering', 'Second Year', 'Female'),
(21, NULL, 'STU2026021', 'Student 21', 'student21@charusat.edu.in', '9800000021', 'B.Tech IT', 'First Year', 'Male');

-- Seed 8 Campus Events
INSERT INTO events (id, title, description, event_date, venue, category, poster_path, created_by) VALUES
(1, 'Web Development Workshop', 'Hands-on practical workshop covering responsive web design, PHP backend APIs, and database connectivity.', '2026-07-25', 'Lab 304, IT Building', 'Workshop', 'uploads/posters/poster_webdev.png', 1),
(2, '24-Hour Code Hackathon', 'Collaborative sprint where student developers architect, build, and deploy innovative full-stack solutions.', '2026-08-07', 'CSPIT Computer Center', 'Competition', 'uploads/posters/poster_hackathon.png', 1),
(3, 'Social Outreach & Tree Plantation', 'NSS student-driven social initiative supporting campus sustainability, green canopy expansion, and ecology.', '2026-08-12', 'Campus Botanical Park', 'Social', 'uploads/posters/poster_social.png', 1),
(4, 'Cyber Security Awareness Session', 'Interactive awareness workshop on secure credentials, CSRF/XSS defense, and cloud authentication.', '2026-08-18', 'Seminar Hall 1', 'Academic', 'uploads/posters/poster_cyber.png', 1),
(5, 'AI and Cloud Computing Seminar', 'Expert session by industry leaders on modern machine learning deployments, microservices, and DevOps.', '2026-08-30', 'Auditorium Hall B', 'Seminar', 'uploads/posters/poster_ai.png', 1),
(6, 'Inter-College Badminton Tournament', 'Annual inter-department sports championship promoting athletics, fitness, and team camaraderie.', '2026-09-02', 'Sports Complex', 'Sports', 'uploads/posters/poster_badminton.png', 1),
(7, 'CHARUSAT TechFest 2026', 'Annual state-level technical symposium with competitive robotics, coding contests, and paper presentations.', '2026-09-15', 'Central Campus Auditorium', 'Competition', 'uploads/posters/poster_techfest.png', 1),
(8, 'Spoural Cultural Night', 'Annual university cultural celebration featuring music, classical dance, theatrical drama, and art.', '2026-10-10', 'University Cricket Grounds', 'Cultural', 'uploads/posters/poster_cultural.png', 1);

-- Seed 7 Event Registrations
INSERT INTO registrations (id, student_id, event_id, status) VALUES
(1, 1, 1, 'confirmed'),
(2, 1, 2, 'confirmed'),
(3, 3, 2, 'confirmed'),
(4, 5, 5, 'confirmed'),
(5, 7, 7, 'confirmed'),
(6, 9, 6, 'confirmed'),
(7, 11, 4, 'confirmed');

-- Seed System Audit Logs
INSERT INTO audit_logs (id, user_id, action_name, entity_type, entity_id, details) VALUES
(1, 1, 'USER_LOGIN', 'users', 1, 'Admin user admin logged in successfully from 127.0.0.1.'),
(2, 1, 'SEED_DATABASE', 'system', 0, 'Database initialized with 21 students and 8 campus events.'),
(3, 1, 'CREATE_EVENT', 'events', 1, 'Created event: Web Development Workshop.'),
(4, 1, 'CREATE_EVENT', 'events', 2, 'Created event: 24-Hour Code Hackathon.'),
(5, 2, 'USER_LOGIN', 'users', 2, 'Student STU2026001 authenticated into portal dashboard.');
