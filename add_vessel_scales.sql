-- Add scale settings to vessels table
ALTER TABLE vessels ADD COLUMN RPMMin INT DEFAULT 650;
ALTER TABLE vessels ADD COLUMN RPMMax INT DEFAULT 1750;
ALTER TABLE vessels ADD COLUMN TempMin INT DEFAULT 20;
ALTER TABLE vessels ADD COLUMN TempMax INT DEFAULT 400;
ALTER TABLE vessels ADD COLUMN PressureMin INT DEFAULT 20;
ALTER TABLE vessels ADD COLUMN PressureMax INT DEFAULT 400;

-- Update existing vessels with default values (you can adjust these as needed)
UPDATE vessels SET 
    RPMMin = 650,
    RPMMax = 1750,
    TempMin = 20,
    TempMax = 400,
    PressureMin = 20,
    PressureMax = 400;
