-- A2C (Airtime2Cash) Settings - Phone numbers and rates per network
CREATE TABLE IF NOT EXISTS a2c_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    network VARCHAR(50) NOT NULL UNIQUE,
    phone_number VARCHAR(20) NOT NULL,
    whatsapp_number VARCHAR(20),
    contact_phone VARCHAR(20),
    rate DECIMAL(5, 2) NOT NULL COMMENT 'Exchange rate (e.g., 0.85 means ₦100 airtime = ₦85 cash)',
    min_amount DECIMAL(10, 2) NOT NULL DEFAULT 500,
    max_amount DECIMAL(10, 2) NOT NULL DEFAULT 50000,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- A2C Requests - Pending airtime2cash conversions awaiting admin approval
CREATE TABLE IF NOT EXISTS a2c_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    network VARCHAR(50) NOT NULL,
    sender_phone VARCHAR(20) NOT NULL,
    airtime_amount DECIMAL(10, 2) NOT NULL,
    cash_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    admin_notes TEXT,
    reference VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES subscribers(sId),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Insert sample data
INSERT INTO a2c_settings (network, phone_number, whatsapp_number, contact_phone, rate, min_amount, max_amount, is_active) VALUES
('mtn', '08012345678', '08012345678', '08012345678', 0.85, 500, 50000, 1),
('airtel', '08012345679', '08012345679', '08012345679', 0.85, 500, 50000, 1),
('glo', '08012345680', '08012345680', '08012345680', 0.85, 500, 50000, 1),
('9mobile', '08012345681', '08012345681', '08012345681', 0.85, 500, 50000, 1)
ON DUPLICATE KEY UPDATE phone_number=VALUES(phone_number), whatsapp_number=VALUES(whatsapp_number), contact_phone=VALUES(contact_phone), rate=VALUES(rate), is_active=VALUES(is_active);
