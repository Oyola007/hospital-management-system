-- =========================================================
-- Hospital Management System - Database Schema
-- =========================================================

CREATE DATABASE IF NOT EXISTS hms_db;
USE hms_db;

-- ---------------------------------------------------------
-- USERS (all roles login through this table)
-- ---------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin','doctor','receptionist','patient') NOT NULL,
    status ENUM('active','inactive') DEFAULT 'active',
    profile_photo VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------------
-- DEPARTMENTS
-- ---------------------------------------------------------
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT
);

-- ---------------------------------------------------------
-- DOCTORS (extends users)
-- ---------------------------------------------------------
CREATE TABLE doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    department_id INT,
    specialization VARCHAR(150),
    license_no VARCHAR(100),
    consultation_fee DECIMAL(10,2) DEFAULT 0,
    bio TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

-- ---------------------------------------------------------
-- RECEPTIONISTS (extends users)
-- ---------------------------------------------------------
CREATE TABLE receptionists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    shift VARCHAR(50),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------
-- PATIENTS (extends users)
-- ---------------------------------------------------------
CREATE TABLE patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    dob DATE,
    gender ENUM('male','female','other'),
    address VARCHAR(255),
    blood_group VARCHAR(5),
    emergency_contact_name VARCHAR(150),
    emergency_contact_phone VARCHAR(20),
    national_id VARCHAR(50),
    registered_by INT NULL COMMENT 'receptionist user_id who registered patient at desk',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------
-- APPOINTMENTS
-- ---------------------------------------------------------
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    reason VARCHAR(255),
    status ENUM('pending','confirmed','completed','cancelled') DEFAULT 'pending',
    booked_by ENUM('patient','receptionist') DEFAULT 'patient',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------
-- CONSULTATIONS (doctor visit record, linked to an appointment)
-- ---------------------------------------------------------
CREATE TABLE consultations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    symptoms TEXT,
    diagnosis TEXT,
    notes TEXT,
    consultation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------
-- PRESCRIPTIONS
-- ---------------------------------------------------------
CREATE TABLE prescriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consultation_id INT NOT NULL,
    medicine_name VARCHAR(150) NOT NULL,
    dosage VARCHAR(100),
    frequency VARCHAR(100),
    duration VARCHAR(100),
    instructions VARCHAR(255),
    status ENUM('pending','dispensed') DEFAULT 'pending',
    FOREIGN KEY (consultation_id) REFERENCES consultations(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------
-- WARDS & BEDS
-- ---------------------------------------------------------
CREATE TABLE wards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    ward_type ENUM('general','private','icu','maternity','pediatric') DEFAULT 'general',
    total_beds INT DEFAULT 0
);

CREATE TABLE beds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ward_id INT NOT NULL,
    bed_number VARCHAR(20) NOT NULL,
    status ENUM('available','occupied','maintenance') DEFAULT 'available',
    FOREIGN KEY (ward_id) REFERENCES wards(id) ON DELETE CASCADE
);

CREATE TABLE admissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    bed_id INT NOT NULL,
    doctor_id INT NOT NULL,
    admitted_by INT NULL COMMENT 'receptionist/admin user_id',
    reason VARCHAR(255),
    admit_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    discharge_date DATETIME NULL,
    status ENUM('admitted','discharged') DEFAULT 'admitted',
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (bed_id) REFERENCES beds(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------
-- LABORATORY
-- ---------------------------------------------------------
CREATE TABLE lab_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_name VARCHAR(150) NOT NULL,
    price DECIMAL(10,2) DEFAULT 0
);

CREATE TABLE lab_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    test_id INT NOT NULL,
    status ENUM('requested','sample_collected','completed') DEFAULT 'requested',
    result TEXT,
    result_file VARCHAR(255),
    requested_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    result_date TIMESTAMP NULL,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (test_id) REFERENCES lab_tests(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------
-- PHARMACY
-- ---------------------------------------------------------
CREATE TABLE pharmacy_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medicine_name VARCHAR(150) NOT NULL,
    category VARCHAR(100),
    unit VARCHAR(50) DEFAULT 'tablets',
    stock_qty INT DEFAULT 0,
    reorder_level INT DEFAULT 10,
    unit_price DECIMAL(10,2) DEFAULT 0,
    expiry_date DATE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE pharmacy_dispense (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_id INT NOT NULL,
    inventory_id INT NOT NULL,
    quantity INT NOT NULL,
    dispensed_by INT NOT NULL COMMENT 'user_id',
    dispensed_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE CASCADE,
    FOREIGN KEY (inventory_id) REFERENCES pharmacy_inventory(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------
-- BILLING
-- ---------------------------------------------------------
CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    invoice_number VARCHAR(50) UNIQUE,
    total_amount DECIMAL(10,2) DEFAULT 0,
    amount_paid DECIMAL(10,2) DEFAULT 0,
    status ENUM('unpaid','partial','paid') DEFAULT 'unpaid',
    created_by INT NULL COMMENT 'user_id who created invoice',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

CREATE TABLE invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    category ENUM('consultation','lab','pharmacy','admission','other') DEFAULT 'other',
    amount DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
);

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    method ENUM('cash','mpesa','card','insurance') DEFAULT 'cash',
    reference_no VARCHAR(100),
    paid_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    received_by INT NULL COMMENT 'user_id',
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------
-- SEED DATA
-- ---------------------------------------------------------
INSERT INTO departments (name, description) VALUES
('General Medicine', 'General outpatient care'),
('Pediatrics', 'Child healthcare'),
('Surgery', 'Surgical procedures'),
('Maternity', 'Maternal and newborn care'),
('Laboratory', 'Diagnostic testing');

INSERT INTO wards (name, ward_type, total_beds) VALUES
('General Ward A', 'general', 10),
('ICU', 'icu', 4),
('Maternity Ward', 'maternity', 6),
('Private Wing', 'private', 5);

-- Default admin account (password: Admin@123 - CHANGE AFTER FIRST LOGIN)
-- Hash generated with PHP password_hash('Admin@123', PASSWORD_DEFAULT)
INSERT INTO users (full_name, email, password, phone, role) VALUES
('System Admin', 'admin@hms.local', '$2b$10$2Wik3GBhh64Z/g/GKBK7neerAJ/YTBQnPViOz5nNCY7b7.Pvn3sJS', '0700000000', 'admin');
