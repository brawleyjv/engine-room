-- Add generator scale columns to vessels table
USE vesseldata;

-- Add generator scale columns
ALTER TABLE vessels ADD COLUMN GenMin INT DEFAULT 20;
ALTER TABLE vessels ADD COLUMN GenMax INT DEFAULT 400;

-- Set default values for existing vessels
UPDATE vessels SET 
    GenMin = 20,
    GenMax = 400
WHERE GenMin IS NULL OR GenMax IS NULL;

SELECT 'Generator scale columns added successfully!' as Status;
