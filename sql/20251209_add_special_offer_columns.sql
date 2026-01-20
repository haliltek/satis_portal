-- Add Special Offer columns to ogteklif2 table
ALTER TABLE `ogteklif2`
ADD COLUMN `is_special_offer` TINYINT(1) DEFAULT 0 COMMENT '0: Standard, 1: Special Offer',
ADD COLUMN `approval_status` ENUM('none', 'pending', 'approved', 'rejected') DEFAULT 'none' COMMENT 'Status of special offer approval',
ADD COLUMN `approved_by` INT(11) DEFAULT NULL COMMENT 'Admin ID who approved/rejected',
ADD COLUMN `approved_at` DATETIME DEFAULT NULL COMMENT 'Timestamp of approval action';

-- Add index for performance in admin dashboard (searching for pending offers)
CREATE INDEX `idx_approval_status` ON `ogteklif2` (`approval_status`);
