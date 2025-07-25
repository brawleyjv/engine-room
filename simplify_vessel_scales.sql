-- Remove separate pressure columns since temp and pressure use same scale
ALTER TABLE vessels DROP COLUMN PressureMin;
ALTER TABLE vessels DROP COLUMN PressureMax;
