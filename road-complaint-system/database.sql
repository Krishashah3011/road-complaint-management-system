-- ============================================================
-- Road / Pathway Complaint and Resolution Tracking System
-- Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS road_complaint_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE road_complaint_db;

-- ============================================================
-- ROLES TABLE
-- ============================================================
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO roles (name, description) VALUES
('admin', 'System Administrator / Supervisor'),
('staff', 'Field Staff / Resolver'),
('user', 'Complainant / Citizen');

-- ============================================================
-- USERS TABLE
-- ============================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role_id INT NOT NULL DEFAULT 3,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
);

-- Default admin password: Admin@123
INSERT INTO users (name, email, password, phone, role_id) VALUES
('System Admin', 'admin@roadcomplaint.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '9000000001', 1),
('Ravi Patel', 'staff@roadcomplaint.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '9000000002', 2),
('Amit Shah', 'user@roadcomplaint.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '9000000003', 3);

-- ============================================================
-- CATEGORIES TABLE (Road-related)
-- ============================================================
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    icon VARCHAR(50) DEFAULT 'fa-road',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO categories (name, description, icon) VALUES
('Pothole', 'Deep holes or depressions in road surface', 'fa-circle-exclamation'),
('Road Crack', 'Visible cracks or fractures in road', 'fa-road-barrier'),
('Waterlogging', 'Water stagnation on road surface', 'fa-water'),
('Broken Pavement', 'Damaged footpath or pavement tiles', 'fa-person-walking'),
('Damaged Divider', 'Broken or missing road dividers', 'fa-car-crash'),
('Street Light on Road', 'Fallen or damaged street light on road', 'fa-lightbulb'),
('Debris/Garbage on Road', 'Construction debris or garbage blocking road', 'fa-trash'),
('Faded Road Markings', 'Unclear or faded lane markings/zebra crossings', 'fa-strikethrough'),
('Missing Manhole Cover', 'Open or missing manhole on road', 'fa-circle-dot'),
('Road Subsidence', 'Road sinking or settling unevenly', 'fa-arrow-down');

-- ============================================================
-- AREA MASTER TABLE (Ward → Area → Spot)
-- ============================================================
CREATE TABLE wards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    ward_no VARCHAR(20) NOT NULL,
    is_active TINYINT(1) DEFAULT 1
);

CREATE TABLE areas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ward_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (ward_id) REFERENCES wards(id) ON DELETE CASCADE
);

CREATE TABLE spots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    area_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    landmark VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE CASCADE
);

-- Sample Ward Data (Bhavnagar style)
INSERT INTO wards (name, ward_no) VALUES
('Ward 1 - Waghawadi', 'W01'),
('Ward 2 - Kumbharwada', 'W02'),
('Ward 3 - Nilambag', 'W03'),
('Ward 4 - Crescent Circle', 'W04'),
('Ward 5 - Takhteshwar', 'W05');

INSERT INTO areas (ward_id, name) VALUES
(1, 'Waghawadi Road'),
(1, 'Atabhai Circle'),
(1, 'Saru Section'),
(2, 'Kumbharwada Main Road'),
(2, 'Ganga Jamna Area'),
(3, 'Nilambag Palace Road'),
(3, 'ST Workshop Area'),
(4, 'Crescent Circle Main'),
(4, 'Ghogha Road'),
(5, 'Takhteshwar Road'),
(5, 'RC Technical Road');

INSERT INTO spots (area_id, name, landmark) VALUES
(1, 'Near Waghawadi Bus Stop', 'Opposite Old Bus Stand'),
(1, 'Waghawadi School Junction', 'Near Government School'),
(2, 'Atabhai Circle North Side', 'Near Pan Shop'),
(2, 'Atabhai Chowk', 'Main Crossroad'),
(3, 'Saru Section Entry', 'Near Gate No 1'),
(4, 'Kumbharwada Main Junction', 'Near Temple'),
(4, 'Near Kumbharwada Park', 'Opposite Children Park'),
(5, 'Ganga Jamna Cross Road', 'Near Water Tank'),
(6, 'Nilambag Palace Gate', 'Front Gate Area'),
(6, 'Palace Road Middle', '100m from Palace'),
(7, 'ST Workshop Gate', 'Opposite Workshop'),
(8, 'Crescent Circle Roundabout', 'Main Circle'),
(9, 'Ghogha Road Km 1', 'Near Filling Station'),
(9, 'Ghogha Road Km 3', 'Near Industrial Area'),
(10, 'Takhteshwar Hill Road', 'Near Temple Entrance'),
(11, 'RC Technical College Gate', 'College Main Gate');

-- ============================================================
-- COMPLAINTS TABLE
-- ============================================================
CREATE TABLE complaints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_no VARCHAR(20) NOT NULL UNIQUE,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    category_id INT NOT NULL,
    ward_id INT NOT NULL,
    area_id INT NOT NULL,
    spot_id INT NOT NULL,
    priority ENUM('low','medium','high','critical') DEFAULT 'medium',
    status ENUM('submitted','verified','assigned','in_progress','resolved','closed','reopened','escalated') DEFAULT 'submitted',
    is_repeated TINYINT(1) DEFAULT 0,
    repeated_ref_id INT DEFAULT NULL,
    submitted_by INT NOT NULL,
    assigned_to INT DEFAULT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    first_response_at TIMESTAMP NULL,
    resolved_at TIMESTAMP NULL,
    closed_at TIMESTAMP NULL,
    sla_response_breach TINYINT(1) DEFAULT 0,
    sla_resolution_breach TINYINT(1) DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (ward_id) REFERENCES wards(id),
    FOREIGN KEY (area_id) REFERENCES areas(id),
    FOREIGN KEY (spot_id) REFERENCES spots(id),
    FOREIGN KEY (submitted_by) REFERENCES users(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id)
);

-- ============================================================
-- COMPLAINT HISTORY / TIMELINE TABLE
-- ============================================================
CREATE TABLE complaint_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50) NOT NULL,
    remark TEXT,
    action_by INT NOT NULL,
    action_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
    FOREIGN KEY (action_by) REFERENCES users(id)
);

-- ============================================================
-- ASSIGNMENTS TABLE
-- ============================================================
CREATE TABLE assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    assigned_to INT NOT NULL,
    assigned_by INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    note TEXT,
    is_current TINYINT(1) DEFAULT 1,
    FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id),
    FOREIGN KEY (assigned_by) REFERENCES users(id)
);

-- ============================================================
-- ATTACHMENTS TABLE
-- ============================================================
CREATE TABLE attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50),
    file_size INT,
    upload_type ENUM('complaint_proof','resolution_proof') DEFAULT 'complaint_proof',
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

-- ============================================================
-- USER PREFERENCES (Cookies supplement in DB)
-- ============================================================
CREATE TABLE user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    theme VARCHAR(20) DEFAULT 'light',
    default_ward_id INT DEFAULT NULL,
    notifications_enabled TINYINT(1) DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- INDEXES FOR PERFORMANCE
-- ============================================================
CREATE INDEX idx_complaints_status ON complaints(status);
CREATE INDEX idx_complaints_area ON complaints(area_id, category_id);
CREATE INDEX idx_complaints_submitted_by ON complaints(submitted_by);
CREATE INDEX idx_complaints_assigned_to ON complaints(assigned_to);
CREATE INDEX idx_complaint_history_complaint ON complaint_history(complaint_id);
CREATE INDEX idx_complaints_submitted_at ON complaints(submitted_at);
