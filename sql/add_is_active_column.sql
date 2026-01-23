-- Add is_active column to custom_campaigns table if it doesn't exist

ALTER TABLE `custom_campaigns` 
ADD COLUMN IF NOT EXISTS `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Kampanya aktif mi?' AFTER `priority`;
