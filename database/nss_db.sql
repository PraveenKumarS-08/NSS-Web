-- Create and use database
CREATE DATABASE IF NOT EXISTS nss_tngptc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nss_tngptc;

-- Users table (admin, volunteer, alumni)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','volunteer','alumni') NOT NULL DEFAULT 'volunteer',
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    profile_photo VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Volunteers profile
CREATE TABLE volunteers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    department VARCHAR(100) NOT NULL,
    year ENUM('I','II','III') NOT NULL,
    blood_group ENUM('A+','A-','B+','B-','O+','O-','AB+','AB-') NOT NULL,
    mobile VARCHAR(15) NOT NULL,
    register_number VARCHAR(50) NOT NULL UNIQUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Alumni profile
CREATE TABLE alumni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    batch_year VARCHAR(10) NOT NULL,
    current_position VARCHAR(100),
    company VARCHAR(100),
    linkedin_url VARCHAR(255),
    mobile VARCHAR(15),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Events
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    event_date DATETIME NOT NULL,
    end_date DATETIME DEFAULT NULL,
    location VARCHAR(200),
    image VARCHAR(255) DEFAULT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    category VARCHAR(100) DEFAULT 'General',
    max_participants INT DEFAULT 0,
    status ENUM('upcoming','ongoing','completed','postponed','cancelled') DEFAULT 'upcoming',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Gallery
CREATE TABLE gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200),
    image VARCHAR(255) DEFAULT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    category VARCHAR(100) DEFAULT 'General',
    year INT DEFAULT 2026,
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Event registrations
CREATE TABLE event_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    student_mobile VARCHAR(20) DEFAULT NULL,
    parent_mobile VARCHAR(20) DEFAULT NULL,
    age INT DEFAULT NULL,
    year VARCHAR(10) DEFAULT NULL,
    department VARCHAR(100) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    remarks TEXT DEFAULT NULL,
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_reg (event_id, user_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Attendance
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_id INT DEFAULT NULL,
    type VARCHAR(50) DEFAULT 'event',
    attendance_date DATE DEFAULT NULL,
    category VARCHAR(100) DEFAULT 'Regular',
    description VARCHAR(255) DEFAULT NULL,
    hours DECIMAL(4,1) DEFAULT 0,
    marked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL
);

-- Announcements
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    target_role ENUM('all','volunteer','alumni') DEFAULT 'all',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Contact messages
CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200),
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Hero slides for homepage carousel
CREATE TABLE hero_slides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image_path VARCHAR(255) NOT NULL,
    title VARCHAR(200) DEFAULT NULL,
    caption VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin accounts (password stored as plaintext per college admin requirement)
INSERT INTO users (name, email, password, role, status) VALUES
('NSS Web Admin (Santosh N)', 'admin@tngptcmadurai.com', 'NSS@Admin2024', 'admin', 'approved');

-- Sample events
INSERT INTO events (title, description, event_date, location, category, status, created_by) VALUES
('Blood Donation Camp 2026', 'Annual blood donation camp organized by NSS Unit, TNGPTC Madurai. All students and faculty are welcome to donate and save lives.', '2026-08-15 09:00:00', 'College Premises, Madurai-11', 'Health', 'upcoming', 1),
('Tree Plantation Drive', 'Planting 500 saplings in and around the campus as part of Green India Mission. NSS volunteers will participate actively.', '2026-08-22 08:00:00', 'College Campus', 'Environment', 'upcoming', 1),
('Village Adoption Program - Phase III', 'NSS volunteers serving the adopted village - community development, health awareness, and literacy programs.', '2026-09-05 07:00:00', 'Alanganallur Village, Madurai', 'Community', 'upcoming', 1),
('Swachh Bharat Cleanliness Drive', 'Campus and surrounding areas cleanliness drive held successfully with 120+ volunteers.', '2026-07-14 07:00:00', 'College & Surroundings', 'Environment', 'completed', 1),
('National Youth Day Celebration', 'Celebrated National Youth Day with cultural programs, speeches, and NSS oath ceremony.', '2026-01-12 10:00:00', 'College Auditorium', 'Cultural', 'completed', 1);

-- Sample announcements
INSERT INTO announcements (title, content, target_role, created_by) VALUES
('NSS Volunteer Registration Open for 2026-27', 'Applications are now open for NSS volunteers for the academic year 2026-27. All interested students can register through the portal.', 'all', 1),
('Special Camp Dates Announced', 'The annual 7-day special camp will be held from October 10-16, 2026. All registered volunteers must attend.', 'volunteer', 1);

-- Site Settings Table for Dynamic Homepage Counters
CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT
);

INSERT INTO site_settings (setting_key, setting_value) VALUES
('stat_1_label', 'ACTIVE VOLUNTEERS'),
('stat_1_val', ''),
('stat_2_label', 'CAMPS & DRIVES'),
('stat_2_val', ''),
('stat_3_label', 'YEARS OF SERVICE'),
('stat_3_val', '75+'),
('stat_4_label', 'ALUMNI NETWORK'),
('stat_4_val', '')
ON DUPLICATE KEY UPDATE setting_key=setting_key;

-- User Activity Logs (audit trail)
CREATE TABLE IF NOT EXISTS user_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_name VARCHAR(100) NOT NULL,
    user_role VARCHAR(50) DEFAULT 'volunteer',
    action_type VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    ip_address VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- System event for preserving attendance hours of deleted events
INSERT INTO events (id, title, description, event_date, location, category, status, created_by) VALUES
(99, 'Regular NSS Parade & Drill Activities', 'System event - hours re-assigned from completed/deleted events are preserved here.', '2026-01-01 00:00:00', 'College Campus', 'General', 'ongoing', 1)
ON DUPLICATE KEY UPDATE title=title;

