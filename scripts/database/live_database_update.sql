-- SQL Script to Add Three Engine Support to Live Database
-- Run this script on your IONOS MySQL database via phpMyAdmin

-- Step 1: Add EngineConfig column to vessels table
ALTER TABLE vessels 
ADD COLUMN EngineConfig ENUM('standard', 'three_engine') DEFAULT 'standard' 
AFTER VesselType;

-- Step 2: Set all existing vessels to standard configuration
UPDATE vessels 
SET EngineConfig = 'standard' 
WHERE EngineConfig IS NULL;

-- Step 3: Verify the changes
SELECT VesselID, VesselName, VesselType, EngineConfig 
FROM vessels 
ORDER BY VesselID;
