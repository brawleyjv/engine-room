-- Add engine configuration support to vessels table
-- Run this SQL to add three-engine support

-- Add column to track engine configuration
ALTER TABLE vessels ADD COLUMN EngineConfig ENUM('standard', 'three_engine') DEFAULT 'standard' AFTER VesselType;

-- Update existing vessels to standard configuration
UPDATE vessels SET EngineConfig = 'standard' WHERE EngineConfig IS NULL;

-- Show the updated table structure
DESCRIBE vessels;
