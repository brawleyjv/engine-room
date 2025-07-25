-- CAUTION: This will delete the vessel named "Rusty Zeller"
-- Make sure this is what you want to do!

-- First, check what data would be affected:
SELECT 'MainEngines' as TableType, COUNT(*) as Records FROM mainengines WHERE VesselID IN (SELECT VesselID FROM vessels WHERE VesselName = 'Rusty Zeller')
UNION ALL
SELECT 'Generators' as TableType, COUNT(*) as Records FROM generators WHERE VesselID IN (SELECT VesselID FROM vessels WHERE VesselName = 'Rusty Zeller')
UNION ALL
SELECT 'Gears' as TableType, COUNT(*) as Records FROM gears WHERE VesselID IN (SELECT VesselID FROM vessels WHERE VesselName = 'Rusty Zeller');

-- If you're sure you want to delete the duplicate vessel (THIS WILL DELETE ALL ASSOCIATED LOG DATA):
-- DELETE FROM vessels WHERE VesselName = 'Rusty Zeller';

-- Or if you want to keep the vessel but make it inactive:
-- UPDATE vessels SET IsActive = 0 WHERE VesselName = 'Rusty Zeller';
