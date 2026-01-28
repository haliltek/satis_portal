-- Add SPECODE and is_export columns to sirket table
-- Date: 2026-01-25

ALTER TABLE sirket 
ADD COLUMN IF NOT EXISTS specode VARCHAR(100) NULL AFTER trading_grp,
ADD COLUMN IF NOT EXISTS is_export TINYINT(1) DEFAULT 0 AFTER specode;

-- Add index for faster export customer queries
CREATE INDEX IF NOT EXISTS idx_is_export ON sirket(is_export);

-- Update existing records: detect export customers from existing data if needed
-- (This will be populated by next sync from Logo)
