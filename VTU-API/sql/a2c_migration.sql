-- Migration script to add WhatsApp and contact phone to a2c_settings table
-- Run this if the a2c_settings table already exists

-- Check if columns exist before adding them
ALTER TABLE a2c_settings
ADD COLUMN IF NOT EXISTS whatsapp_number VARCHAR(20) AFTER phone_number,
ADD COLUMN IF NOT EXISTS contact_phone VARCHAR(20) AFTER whatsapp_number;

-- Update existing records with sample contact data (optional)
UPDATE a2c_settings SET
    whatsapp_number = COALESCE(whatsapp_number, phone_number),
    contact_phone = COALESCE(contact_phone, phone_number)
WHERE whatsapp_number IS NULL OR contact_phone IS NULL;
