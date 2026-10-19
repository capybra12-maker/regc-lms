-- ============================================================
-- REGC LMS — Safe Migration Script
-- Run this on an EXISTING installation to add new tables/columns
-- without dropping any existing data.
-- ============================================================

USE regc_lms;

-- Add freight_amount to shipments (if missing)
ALTER TABLE shipments
    ADD COLUMN IF NOT EXISTS freight_amount DECIMAL(12,2) NULL AFTER notes;

-- Settings table
CREATE TABLE IF NOT EXISTS settings (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key   VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT         NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Diesel transactions table
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

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    BIGINT UNSIGNED NULL,
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

-- Add notes column to proof_of_delivery (if missing)
ALTER TABLE proof_of_delivery
    ADD COLUMN IF NOT EXISTS notes TEXT NULL AFTER received_by;

-- Default settings
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('company_name',        'REGC Trucking Services'),
('company_address',     'Tagoloan, Misamis Oriental, Philippines'),
('company_phone',       '+63 (083) 000-0000'),
('company_email',       'info@regctrucking.com'),
('company_tagline',     'Track. Deliver. Manage. Efficiently.'),
('fuel_baseline_liters','15'),
('fuel_price_per_liter','65.00'),
('app_version',         '2.0.0');

