-- ============================================================
-- REGC Fleet Management & Shipment Tracking
-- Full Database Schema  |  MySQL 8+ / MariaDB 10.5+
-- Compatible with local XAMPP and live hosting (InfinityFree, cPanel)
-- ============================================================

-- On shared hosting (InfinityFree), your database is already created.
-- In phpMyAdmin, simply select your database and import this file.
-- For local manual setup:
-- CREATE DATABASE IF NOT EXISTS regc_lms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE regc_lms;

-- ------------------------------------------------------------
-- 1. USERS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name    VARCHAR(150)  NOT NULL,
    email        VARCHAR(190)  NOT NULL UNIQUE,
    password     VARCHAR(255)  NOT NULL,
    role         ENUM('administrator','driver','warehouse') NOT NULL DEFAULT 'warehouse',
    status       ENUM('active','inactive') NOT NULL DEFAULT 'active',
    last_login_at DATETIME NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role   (role),
    INDEX idx_users_status (status)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 2. SETTINGS  (key-value store)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key   VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT         NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 3. CUSTOMERS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS customers (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_name   VARCHAR(180) NULL,
    contact_person VARCHAR(150) NOT NULL,
    contact_number VARCHAR(50)  NULL,
    email          VARCHAR(190) NULL,
    address        TEXT         NULL,
    status         ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customer_name  (contact_person),
    INDEX idx_company_name   (company_name)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 4. DRIVERS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS drivers (
    id                       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id                  BIGINT UNSIGNED NULL,
    full_name                VARCHAR(150) NOT NULL,
    contact_number           VARCHAR(50)  NULL,
    license_number           VARCHAR(100) NOT NULL UNIQUE,
    license_expiration       DATE         NULL,
    emergency_contact        VARCHAR(150) NULL,
    emergency_contact_number VARCHAR(50)  NULL,
    status ENUM('available','assigned','on_delivery','off_duty') NOT NULL DEFAULT 'available',
    gps_lat                  DECIMAL(10,8) NULL,
    gps_lng                  DECIMAL(11,8) NULL,
    gps_updated_at           DATETIME     NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_driver_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_driver_status (status)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 5. TRUCKS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS trucks (
    id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plate_number            VARCHAR(50)  NOT NULL UNIQUE,
    truck_model             VARCHAR(150) NULL,
    truck_type              VARCHAR(100) NULL,
    capacity_kg             DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM('available','assigned','in_transit','maintenance','unavailable') NOT NULL DEFAULT 'available',
    assigned_driver_id      BIGINT UNSIGNED NULL,
    registration_number     VARCHAR(100) NULL,
    registration_expiration DATE         NULL,
    notes                   TEXT         NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_truck_driver
        FOREIGN KEY (assigned_driver_id) REFERENCES drivers(id) ON DELETE SET NULL,
    INDEX idx_truck_status (status),
    INDEX idx_truck_driver (assigned_driver_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 6. SHIPMENTS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS shipments (
    id                     BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tracking_number        VARCHAR(30)   NULL UNIQUE,
    customer_id            BIGINT UNSIGNED NOT NULL,
    cargo_type             VARCHAR(100)  NOT NULL,
    cargo_description      TEXT          NULL,
    cargo_weight_kg        DECIMAL(12,2) NOT NULL,
    pickup_location        VARCHAR(255)  NOT NULL,
    destination            VARCHAR(255)  NOT NULL,
    pickup_lat             DECIMAL(10,8) NULL,
    pickup_lng             DECIMAL(11,8) NULL,
    destination_lat        DECIMAL(10,8) NULL,
    destination_lng        DECIMAL(11,8) NULL,
    shipment_date          DATETIME      NULL,
    expected_delivery_date DATETIME      NULL,
    truck_id               BIGINT UNSIGNED NULL,
    driver_id              BIGINT UNSIGNED NULL,
    current_status ENUM('pending','assigned','pickup','in_transit','arrived','delivered') NOT NULL DEFAULT 'pending',
    freight_amount         DECIMAL(12,2) NULL,
    notes                  TEXT          NULL,
    created_by             BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_shipment_customer FOREIGN KEY (customer_id) REFERENCES customers(id),
    CONSTRAINT fk_shipment_truck    FOREIGN KEY (truck_id)    REFERENCES trucks(id)    ON DELETE SET NULL,
    CONSTRAINT fk_shipment_driver   FOREIGN KEY (driver_id)   REFERENCES drivers(id)   ON DELETE SET NULL,
    CONSTRAINT fk_shipment_creator  FOREIGN KEY (created_by)  REFERENCES users(id),
    INDEX idx_tracking_number  (tracking_number),
    INDEX idx_shipment_status  (current_status),
    INDEX idx_shipment_driver  (driver_id),
    INDEX idx_shipment_truck   (truck_id),
    INDEX idx_shipment_customer(customer_id),
    INDEX idx_shipment_date    (shipment_date)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 7. SHIPMENT STATUS HISTORY
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS shipment_status_history (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shipment_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending','assigned','pickup','in_transit','arrived','delivered') NOT NULL,
    updated_by  BIGINT UNSIGNED NOT NULL,
    remarks     TEXT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_history_shipment FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE,
    CONSTRAINT fk_history_user     FOREIGN KEY (updated_by)  REFERENCES users(id),
    INDEX idx_history_shipment (shipment_id),
    INDEX idx_history_status   (status)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 8. DELIVERY SCHEDULES
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS delivery_schedules (
    id                        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shipment_id               BIGINT UNSIGNED NOT NULL,
    truck_id                  BIGINT UNSIGNED NOT NULL,
    driver_id                 BIGINT UNSIGNED NOT NULL,
    pickup_datetime           DATETIME NOT NULL,
    expected_delivery_datetime DATETIME NULL,
    notes                     TEXT NULL,
    created_by                BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_schedule_shipment FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE,
    CONSTRAINT fk_schedule_truck    FOREIGN KEY (truck_id)    REFERENCES trucks(id),
    CONSTRAINT fk_schedule_driver   FOREIGN KEY (driver_id)   REFERENCES drivers(id),
    CONSTRAINT fk_schedule_creator  FOREIGN KEY (created_by)  REFERENCES users(id),
    INDEX idx_schedule_pickup (pickup_datetime)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 9. PROOF OF DELIVERY
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS proof_of_delivery (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shipment_id      BIGINT UNSIGNED NOT NULL,
    delivery_receipt VARCHAR(255) NULL,
    proof_image      VARCHAR(255) NULL,
    signature_file   VARCHAR(255) NULL,
    received_by      VARCHAR(150) NULL,
    notes            TEXT         NULL,
    uploaded_by      BIGINT UNSIGNED NOT NULL,
    uploaded_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pod_shipment FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE,
    CONSTRAINT fk_pod_user     FOREIGN KEY (uploaded_by)  REFERENCES users(id),
    INDEX idx_pod_shipment (shipment_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 10. DIESEL / FUEL TRANSACTIONS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS diesel_transactions (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shipment_id      BIGINT UNSIGNED NULL,
    truck_id         BIGINT UNSIGNED NOT NULL,
    driver_id        BIGINT UNSIGNED NULL,
    transaction_date DATE          NOT NULL,
    liters           DECIMAL(10,2) NOT NULL,
    price_per_liter  DECIMAL(8,2)  NOT NULL,
    total_cost       DECIMAL(12,2) NOT NULL,
    trip_distance_km DECIMAL(10,2) NULL,
    notes            TEXT          NULL,
    recorded_by      BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_diesel_shipment FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE SET NULL,
    CONSTRAINT fk_diesel_truck    FOREIGN KEY (truck_id)    REFERENCES trucks(id),
    CONSTRAINT fk_diesel_driver   FOREIGN KEY (driver_id)   REFERENCES drivers(id)   ON DELETE SET NULL,
    CONSTRAINT fk_diesel_recorder FOREIGN KEY (recorded_by) REFERENCES users(id),
    INDEX idx_diesel_truck    (truck_id),
    INDEX idx_diesel_shipment (shipment_id),
    INDEX idx_diesel_date     (transaction_date)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 11. NOTIFICATIONS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    BIGINT UNSIGNED NULL  COMMENT 'NULL = broadcast by role',
    role       ENUM('administrator','driver','warehouse') NULL,
    title      VARCHAR(255) NOT NULL,
    message    TEXT         NOT NULL,
    type       ENUM('info','success','warning','danger') NOT NULL DEFAULT 'info',
    link       VARCHAR(500) NULL,
    is_read    TINYINT(1)   NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_notif_user (user_id),
    INDEX idx_notif_role (role),
    INDEX idx_notif_read (is_read)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 12. SYSTEM LOGS (ACTIVITY LOG)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS system_logs (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     BIGINT UNSIGNED NULL,
    action      VARCHAR(150) NOT NULL,
    module      VARCHAR(100) NULL,
    description TEXT         NULL,
    ip_address  VARCHAR(45)  NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_log_user    (user_id),
    INDEX idx_log_module  (module),
    INDEX idx_log_created (created_at)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- DEFAULT INITIAL USERS (For fresh install & live hosting)
-- ------------------------------------------------------------
INSERT IGNORE INTO users (id, full_name, email, password, role, status) VALUES
(1, 'Administrator', 'admin@regc.com', '$2y$10$mvKBCHiqc.W.g3fDwFf7J.5HaZiCYxEoRZjPfPcsvvJm4Cfi3B9vi', 'administrator', 'active'),
(2, 'Juan Dela Cruz', 'driver1@regc.com', '$2y$10$l/11E6a9aC6N78.h0r6lVeiJc3B7WwzKzX68r2gP6rPZ.y.4cWJTC', 'driver', 'active'),
(3, 'Maria Reyes',    'warehouse@regc.com', '$2y$10$83638J/3hZf4cW8Qyv/U4uvBkWn4vM7QcWp0m1V4uQj.X1l5nK8m.', 'warehouse', 'active');

-- ------------------------------------------------------------
-- DEFAULT SETTINGS
-- ------------------------------------------------------------
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('company_name',       'REGC Fleet Management & Shipment Tracking'),
('company_address',    'General Santos City, South Cotabato, Philippines'),
('company_phone',      '+63 (083) 552-0000'),
('company_email',      'dispatch@regctrucking.com'),
('company_tagline',    'Track. Deliver. Manage. Efficiently.'),
('fuel_baseline_liters','15'),
('fuel_price_per_liter','65.00'),
('app_version',        '2.1.0');
