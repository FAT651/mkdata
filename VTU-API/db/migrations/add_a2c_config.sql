-- Airtime-to-Cash (A2C) Configuration Migration
-- Add VTU Africa Airtime-to-Cash API credentials to apiconfigs table

-- Check if entries already exist and insert them if not
INSERT INTO apiconfigs (name, value, description, is_active) 
VALUES 
    ('a2cHost', 'https://vtuafrica.com.ng', 'VTU Africa API base host for Airtime-to-Cash service', TRUE)
ON DUPLICATE KEY UPDATE 
    value = 'https://vtuafrica.com.ng',
    description = 'VTU Africa API base host for Airtime-to-Cash service',
    is_active = TRUE;

INSERT INTO apiconfigs (name, value, description, is_active) 
VALUES 
    ('a2cApikey', 'YOUR_VTU_AFRICA_API_KEY_HERE', 'VTU Africa API Key for Airtime-to-Cash authentication', TRUE)
ON DUPLICATE KEY UPDATE 
    value = 'YOUR_VTU_AFRICA_API_KEY_HERE',
    description = 'VTU Africa API Key for Airtime-to-Cash authentication',
    is_active = TRUE;

-- Verify insertion
SELECT 'A2C Configuration Complete' as status;
