CREATE TABLE IF NOT EXISTS `apiconfigs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `value` text NOT NULL,
  `description` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default cable configuration
INSERT INTO `apiconfigs` (`name`, `value`, `description`) VALUES
('cableProvider', 'https://n3tdata.com/api/cable/purchase', 'Cable subscription provider API endpoint'),
('cableApi', 'your-api-key', 'Cable subscription API key'),
('cableVerificationProvider', 'https://n3tdata.com/api/cable/verify', 'Cable verification provider API endpoint'),
('cableVerificationApi', 'your-api-key', 'Cable verification API key');

-- Aspfiy (Palmpay & Paga) placeholders
INSERT INTO `apiconfigs` (`name`, `value`, `description`) VALUES
('asfiyApi', 'your_aspfiy_secret_here', 'Aspfiy secret key used for reserve-palmpay and reserve-paga endpoints'),
('asfiyWebhook', 'https://yourdomain.com/aspfiy-webhook', 'Optional Aspfiy webhook URL for reservation callbacks');
