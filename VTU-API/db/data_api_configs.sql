-- Drop existing table if it exists
DROP TABLE IF EXISTS dataapiconfigs;

-- Create dataapiconfigs table
CREATE TABLE dataapiconfigs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    value TEXT NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert MTN configurations
INSERT INTO dataapiconfigs (name, value, description) VALUES
-- MTN SME configurations
('mtnSMEApi', 'your_api_key_here', 'API Key for MTN SME Data'),
('mtnSMEProvider', 'https://api.example.com/mtn/sme', 'Provider URL for MTN SME Data'),

-- MTN Corporate configurations
('mtnCorporateApi', 'your_api_key_here', 'API Key for MTN Corporate Data'),
('mtnCorporateProvider', 'https://api.example.com/mtn/corporate', 'Provider URL for MTN Corporate Data'),

-- MTN Gifting configurations
('mtnGiftingApi', 'your_api_key_here', 'API Key for MTN Gifting Data'),
('mtnGiftingProvider', 'https://api.example.com/mtn/gifting', 'Provider URL for MTN Gifting Data'),

-- Airtel configurations
('airtelSMEApi', 'your_api_key_here', 'API Key for Airtel SME Data'),
('airtelSMEProvider', 'https://api.example.com/airtel/sme', 'Provider URL for Airtel SME Data'),
('airtelCorporateApi', 'your_api_key_here', 'API Key for Airtel Corporate Data'),
('airtelCorporateProvider', 'https://api.example.com/airtel/corporate', 'Provider URL for Airtel Corporate Data'),
('airtelGiftingApi', 'your_api_key_here', 'API Key for Airtel Gifting Data'),
('airtelGiftingProvider', 'https://api.example.com/airtel/gifting', 'Provider URL for Airtel Gifting Data'),

-- Glo configurations
('gloSMEApi', 'your_api_key_here', 'API Key for Glo SME Data'),
('gloSMEProvider', 'https://api.example.com/glo/sme', 'Provider URL for Glo SME Data'),
('gloCorporateApi', 'your_api_key_here', 'API Key for Glo Corporate Data'),
('gloCorporateProvider', 'https://api.example.com/glo/corporate', 'Provider URL for Glo Corporate Data'),
('gloGiftingApi', 'your_api_key_here', 'API Key for Glo Gifting Data'),
('gloGiftingProvider', 'https://api.example.com/glo/gifting', 'Provider URL for Glo Gifting Data'),

-- 9mobile configurations
('9mobileSMEApi', 'your_api_key_here', 'API Key for 9mobile SME Data'),
('9mobileSMEProvider', 'https://api.example.com/9mobile/sme', 'Provider URL for 9mobile SME Data'),
('9mobileCorporateApi', 'your_api_key_here', 'API Key for 9mobile Corporate Data'),
('9mobileCorporateProvider', 'https://api.example.com/9mobile/corporate', 'Provider URL for 9mobile Corporate Data'),
('9mobileGiftingApi', 'your_api_key_here', 'API Key for 9mobile Gifting Data'),
('9mobileGiftingProvider', 'https://api.example.com/9mobile/gifting', 'Provider URL for 9mobile Gifting Data');

-- Create indexes for faster lookups
CREATE INDEX idx_dataapiconfigs_name ON dataapiconfigs(name);
CREATE INDEX idx_dataapiconfigs_active ON dataapiconfigs(is_active);

-- Example data for testing with a real provider (e.g., n3tdata)
INSERT INTO dataapiconfigs (name, value, description) VALUES
('mtnSMEApi', 'YOUR_N3TDATA_API_KEY', 'N3tdata API Key for MTN SME'),
('mtnSMEProvider', 'https://n3tdata.com/api/v1/data', 'N3tdata Provider URL for MTN SME');
