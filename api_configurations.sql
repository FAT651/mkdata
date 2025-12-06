-- Create API configurations table
CREATE TABLE api_configurations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    network_id INT NOT NULL,  -- 1=MTN, 2=Airtel, 3=Glo, 4=9mobile
    plan_type ENUM('SME', 'Corporate', 'Gifting') NOT NULL,
    provider_name VARCHAR(50) NOT NULL,  -- e.g., 'n3tdata', 'azaravtu', 'airtimenigeria'
    provider_url VARCHAR(255) NOT NULL,  -- API endpoint
    api_key VARCHAR(255) NOT NULL,
    auth_type ENUM('Token', 'Bearer') NOT NULL DEFAULT 'Token',
    request_format JSON NOT NULL,  -- Store the expected request format
    active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_provider_config (network_id, plan_type, provider_name)
);

-- Insert sample configurations based on the code
INSERT INTO api_configurations (
    network_id, 
    plan_type, 
    provider_name, 
    provider_url, 
    api_key, 
    auth_type,
    request_format
) VALUES
-- N3tdata provider configuration
(1, 'SME', 'n3tdata', 'https://api.n3tdata.com/v1/purchase', 'your_api_key_here', 'Token',
 JSON_OBJECT(
    'network', 'network_id',
    'phone', 'phone_number',
    'bypass', true,
    'request-id', 'reference',
    'data_plan', 'plan_code'
 )),
-- Azaravtu provider configuration
(1, 'Corporate', 'azaravtu', 'https://api.azaravtu.com/data/purchase', 'your_api_key_here', 'Bearer',
 JSON_OBJECT(
    'network_id', 'network_id',
    'number', 'phone_number',
    'pin', '5027',
    'id', 'plan_code'
 )),
-- Airtimenigeria provider configuration
(2, 'SME', 'airtimenigeria', 'https://api.airtimenigeria.com/data/buy', 'your_api_key_here', 'Bearer',
 JSON_OBJECT(
    'phone', 'phone_number',
    'plan_id', 'plan_code',
    'customer_reference', 'reference'
 ));

-- Create a view for easy access to active configurations
CREATE VIEW active_api_configurations AS
SELECT 
    ac.*,
    CASE ac.network_id
        WHEN 1 THEN 'MTN'
        WHEN 2 THEN 'Airtel'
        WHEN 3 THEN 'Glo'
        WHEN 4 THEN '9mobile'
    END as network_name
FROM api_configurations ac
WHERE ac.active = true;
