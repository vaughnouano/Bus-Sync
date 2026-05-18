-- ============================================================
--  DATABASE: bus_booking_system
--  Generated from ERD
-- ============================================================

CREATE DATABASE IF NOT EXISTS OUANO_SIBI
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE OUANO_SIBI;

-- ============================================================
-- TABLE: user
-- Base table for STUDENT and DRIVER (IS A relationship)
-- ============================================================
CREATE TABLE `user` (
    user_id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name      VARCHAR(50)  NOT NULL,
    last_name       VARCHAR(50)  NOT NULL,
    email           VARCHAR(100) NOT NULL UNIQUE,
    user_type       ENUM('student','driver','admin') NOT NULL,
    phone_number    VARCHAR(20),
    date_of_birth   DATE,
    address         TEXT,
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login      DATETIME
);

-- ============================================================
-- TABLE: admin
-- ============================================================
CREATE TABLE `admin` (
    admin_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(100) NOT NULL UNIQUE,
    department  VARCHAR(100)
);

-- ============================================================
-- TABLE: audit_log
-- Admin "Performs" audit logs
-- ============================================================
CREATE TABLE `audit_log` (
    log_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    timestamp   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    action      TEXT         NOT NULL,
    admin_id    INT UNSIGNED NOT NULL,
    CONSTRAINT fk_auditlog_admin
        FOREIGN KEY (admin_id) REFERENCES `admin` (admin_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

-- ============================================================
-- TABLE: student
-- IS A user
-- ============================================================
CREATE TABLE `student` (
    student_id              INT UNSIGNED PRIMARY KEY,   -- same as user_id
    grade_level             VARCHAR(20),
    is_blocked              TINYINT(1)   NOT NULL DEFAULT 0,
    block_expiry            DATE,
    no_show_count           INT UNSIGNED NOT NULL DEFAULT 0,
    emergency_contact_name  VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    medical_notes           TEXT,
    CONSTRAINT fk_student_user
        FOREIGN KEY (student_id) REFERENCES `user` (user_id)
        ON UPDATE CASCADE ON DELETE CASCADE
);

-- ============================================================
-- TABLE: driver
-- IS A user
-- ============================================================
CREATE TABLE `driver` (
    driver_id           INT UNSIGNED PRIMARY KEY,   -- same as user_id
    license_number      VARCHAR(50)  NOT NULL UNIQUE,
    hire_date           DATE         NOT NULL,
    employment_status   ENUM('active','inactive','on_leave') NOT NULL DEFAULT 'active',
    CONSTRAINT fk_driver_user
        FOREIGN KEY (driver_id) REFERENCES `user` (user_id)
        ON UPDATE CASCADE ON DELETE CASCADE
);

-- ============================================================
-- TABLE: bus
-- ============================================================
CREATE TABLE `bus` (
    bus_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bus_number  VARCHAR(20)  NOT NULL UNIQUE,
    capacity    SMALLINT UNSIGNED NOT NULL
);

-- ============================================================
-- TABLE: trip
-- Driver "Drives" a Trip; Bus "Operates" a Trip
-- ============================================================
CREATE TABLE `trip` (
    trip_id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    departure       DATETIME     NOT NULL,
    available_seats SMALLINT UNSIGNED NOT NULL,
    driver_id       INT UNSIGNED NOT NULL,
    bus_id          INT UNSIGNED NOT NULL,
    CONSTRAINT fk_trip_driver
        FOREIGN KEY (driver_id) REFERENCES `driver` (driver_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_trip_bus
        FOREIGN KEY (bus_id) REFERENCES `bus` (bus_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

-- ============================================================
-- TABLE: booking
-- Student books a Trip ("Contains" relationship)
-- ============================================================
CREATE TABLE `booking` (
    booking_id  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    timestamp   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    no_show_flag TINYINT(1)  NOT NULL DEFAULT 0,
    student_id  INT UNSIGNED NOT NULL,
    guardian_id INT UNSIGNED,           -- optional guardian (also a user)
    trip_id     INT UNSIGNED NOT NULL,
    CONSTRAINT fk_booking_student
        FOREIGN KEY (student_id) REFERENCES `student` (student_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_booking_guardian
        FOREIGN KEY (guardian_id) REFERENCES `user` (user_id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_booking_trip
        FOREIGN KEY (trip_id) REFERENCES `trip` (trip_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

-- ============================================================
-- TABLE: noshow_record
-- Student "Accumulates" no-show records
-- Booking "Generates" a no-show record
-- ============================================================
CREATE TABLE `noshow_record` (
    record_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    recorded_date   DATE         NOT NULL,
    student_id      INT UNSIGNED NOT NULL,
    booking_id      INT UNSIGNED NOT NULL,
    CONSTRAINT fk_noshow_student
        FOREIGN KEY (student_id) REFERENCES `student` (student_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_noshow_booking
        FOREIGN KEY (booking_id) REFERENCES `booking` (booking_id)
        ON UPDATE CASCADE ON DELETE CASCADE
);

-- ============================================================
-- END OF SCRIPT
-- ============================================================
