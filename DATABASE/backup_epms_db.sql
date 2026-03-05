-- Create the database
CREATE DATABASE IF NOT EXISTS epms_db;
USE epms_db;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'president', 'department_head', 'regular_employee', 'user') NOT NULL DEFAULT 'user',
    department_id INT,
    avatar VARCHAR(255) DEFAULT NULL,
    remember_token VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create departments table
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    head_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (head_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Create programs table
CREATE TABLE IF NOT EXISTS programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    department_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
);

-- Add foreign key constraint to users table
ALTER TABLE users
ADD CONSTRAINT fk_users_department
FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL;

-- Create records table
CREATE TABLE IF NOT EXISTS records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    form_type ENUM('DPCR', 'IPCR', 'IDP') NOT NULL,
    period VARCHAR(255) NOT NULL,
    content LONGTEXT,
    status ENUM('Draft', 'Pending', 'Approved', 'For Revision') NOT NULL DEFAULT 'Draft',
    date_submitted TIMESTAMP NULL,
    reviewed_by INT,
    date_reviewed TIMESTAMP NULL,
    comments TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Create DPCR entries table
CREATE TABLE IF NOT EXISTS dpcr_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_id INT NOT NULL,
    major_output TEXT NOT NULL,
    success_indicators TEXT NOT NULL,
    budget DECIMAL(15, 2),
    accountable TEXT,
    accomplishments TEXT,
    category ENUM('Strategic', 'Core', 'Support') NOT NULL DEFAULT 'Core',
    q1_rating INT,
    q2_rating INT,
    q3_rating INT,
    q4_rating INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (record_id) REFERENCES records(id) ON DELETE CASCADE
);

-- Create IPCR entries table
CREATE TABLE IF NOT EXISTS ipcr_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_id INT NOT NULL,
    major_output TEXT NOT NULL,
    success_indicators TEXT NOT NULL,
    actual_accomplishments TEXT,
    q_rating INT,
    e_rating INT,
    t_rating INT,
    final_rating INT,
    remarks TEXT,
    category ENUM('Strategic', 'Core', 'Support') NOT NULL DEFAULT 'Core',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (record_id) REFERENCES records(id) ON DELETE CASCADE
);

-- Create IDP entries table
CREATE TABLE IF NOT EXISTS idp_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_id INT NOT NULL,
    development_needs TEXT NOT NULL,
    development_interventions TEXT NOT NULL,
    target_competency_level INT,
    success_indicators TEXT NOT NULL,
    timeline_start DATE,
    timeline_end DATE,
    resources_needed TEXT,
    status ENUM('Not Started', 'In Progress', 'Completed') DEFAULT 'Not Started',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (record_id) REFERENCES records(id) ON DELETE CASCADE
);

-- Insert sample data for testing
-- Insert departments
INSERT INTO departments (name, description) VALUES 
('ICSLIS - Institute of Computing Studies and Library Information Sciences', 'Department for computing and library science programs'),
('Human Resources', 'Manages employee relations and workforce planning'),
('Finance', 'Handles financial operations and budgeting'),
('Academic Affairs', 'Oversees academic programs and policies'),
('Student Services', 'Provides support services for students');

-- Insert programs under ICSLIS
INSERT INTO programs (name, department_id) VALUES
('BSCS - Bachelor of Science in Computer Science', 1),
('BSIS - Bachelor of Science in Information Systems', 1),
('BLIS - Bachelor of Library and Information Science', 1);

-- Insert sample users with different roles
INSERT INTO users (name, email, password, role, department_id) 
VALUES 
('Admin Santos', 'asantos@gmail.com', '$2y$10$5M1sLMEQfuw9A4Xmn0n8g.YUEnfGBEw0W3Pn8KG2v7tCgY6l9A5Ea', 'admin', 1),
('Arnie Santos', 'arniesantos@cca.edu.ph', '$2y$10$5M1sLMEQfuw9A4Xmn0n8g.YUEnfGBEw0W3Pn8KG2v7tCgY6l9A5Ea', 'regular_employee', 1),
('Dean Smith', 'deansmith@cca.edu.ph', '$2y$10$5M1sLMEQfuw9A4Xmn0n8g.YUEnfGBEw0W3Pn8KG2v7tCgY6l9A5Ea', 'department_head', 1),
('Mark Lapid', 'marklapid@cca.edu.ph', '$2y$10$5M1sLMEQfuw9A4Xmn0n8g.YUEnfGBEw0W3Pn8KG2v7tCgY6l9A5Ea', 'president', 3),
('HR Manager', 'hrmanager@cca.edu.ph', '$2y$10$5M1sLMEQfuw9A4Xmn0n8g.YUEnfGBEw0W3Pn8KG2v7tCgY6l9A5Ea', 'department_head', 2),
('Faculty Member', 'faculty@cca.edu.ph', '$2y$10$5M1sLMEQfuw9A4Xmn0n8g.YUEnfGBEw0W3Pn8KG2v7tCgY6l9A5Ea', 'regular_employee', 1);

-- Set department heads
UPDATE departments SET head_id = 3 WHERE id = 1; -- Dean Smith as head of ICSLIS
UPDATE departments SET head_id = 5 WHERE id = 2; -- HR Manager as head of HR
UPDATE departments SET head_id = 4 WHERE id = 3; -- Mark Lapid (President) also oversees Finance

-- Insert sample records
INSERT INTO records (user_id, form_type, period, status, date_submitted) 
VALUES 
(2, 'DPCR', 'Q1 2023', 'Approved', '2023-03-15 10:00:00'),
(2, 'IPCR', 'Q1 2023', 'Approved', '2023-03-20 11:30:00'),
(3, 'DPCR', 'Q1 2023', 'Pending', '2023-03-25 09:15:00'),
(3, 'IDP', 'Annual 2023', 'For Revision', '2023-01-10 14:20:00'),
(6, 'IPCR', 'Q2 2023', 'Pending', '2023-06-10 16:45:00'),
(2, 'IPCR', 'Q3 2023', 'Draft', NULL);

-- Insert sample DPCR entries
INSERT INTO dpcr_entries (record_id, major_output, success_indicators, budget, accountable, accomplishments, category, q1_rating, q2_rating, q3_rating, q4_rating) 
VALUES 
(1, 'Curriculum Development', 'Update CS curriculum by Q2', 50000.00, 'ICSLIS Department', 'Curriculum updated ahead of schedule', 'Strategic', 5, 0, 0, 0),
(1, 'Faculty Training Program', 'Train faculty on new technologies', 75000.00, 'ICSLIS Department', 'Training completed with 95% attendance', 'Core', 4, 0, 0, 0),
(3, 'Research Publication', 'Publish 5 research papers', 25000.00, 'ICSLIS Department', 'Research in progress', 'Core', 0, 0, 0, 0); 

-- Insert sample IPCR entries
INSERT INTO ipcr_entries (record_id, major_output, success_indicators, actual_accomplishments, category, q_rating, e_rating, t_rating, final_rating)
VALUES
(2, 'Course Materials Development', 'Develop 3 new lab exercises', 'Developed 4 new lab exercises with comprehensive guides', 'Core', 4, 5, 4, 4),
(2, 'Student Mentorship', 'Mentor at least 5 students', 'Mentored 7 students, with 2 winning in competitions', 'Strategic', 5, 5, 5, 5),
(5, 'Technology Workshop', 'Conduct 2 workshops for students', 'Conducted 1 workshop with 30 attendees', 'Support', 3, 4, 3, 3);

-- Insert sample IDP entries
INSERT INTO idp_entries (record_id, development_needs, development_interventions, target_competency_level, success_indicators, timeline_start, timeline_end, resources_needed, status)
VALUES
(4, 'Data Analytics Skills', 'Attend Data Science Workshop', 4, 'Complete certification and apply skills in department reporting', '2023-02-01', '2023-06-30', 'Training budget, software licenses', 'In Progress'),
(4, 'Leadership Skills', 'Leadership Mentoring Program', 3, 'Successfully lead at least one department initiative', '2023-03-15', '2023-12-15', 'Mentorship sessions, leadership books', 'Not Started'); 