-- Check what vessels already exist
SELECT VesselID, VesselName, VesselType, IsActive FROM vessels ORDER BY VesselName;

-- If you want to see the duplicate "Rusty Zeller" entries:
SELECT * FROM vessels WHERE VesselName = 'Rusty Zeller';
