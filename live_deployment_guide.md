# Three Engine Support - Live Server Update Guide

## 📋 Files to Upload to IONOS Server

Upload these **modified** files to your live server (replace existing versions):

### Core Application Files
```
manage_vessels.php       - Updated vessel creation form and display
vessel_functions.php     - Added three-engine support functions  
add_log.php             - Dynamic side dropdown based on vessel config
edit_log.php            - Dynamic side editing for all configurations
view_logs.php           - Dynamic side filtering and graph links
graph_logs.php          - Support for center main engine graphing
```

### New Documentation Files (Optional)
```
THREE_ENGINE_IMPLEMENTATION.md  - Implementation documentation
setup_three_engine.php         - Migration script (for reference)
add_three_engine_support.sql   - SQL migration (for reference)
live_database_update.sql       - Live database update script
```

## 🗄️ Database Update

### Option 1: Via phpMyAdmin (Recommended)
1. Login to your IONOS control panel
2. Go to MySQL Databases > phpMyAdmin
3. Select your database (dbs14497521)
4. Go to SQL tab
5. Copy and paste the contents of `live_database_update.sql`
6. Click "Go" to execute

### Option 2: Manual SQL Commands
```sql
-- Add EngineConfig column
ALTER TABLE vessels 
ADD COLUMN EngineConfig ENUM('standard', 'three_engine') DEFAULT 'standard' 
AFTER VesselType;

-- Update existing vessels
UPDATE vessels 
SET EngineConfig = 'standard' 
WHERE EngineConfig IS NULL;
```

## 🚀 Deployment Steps

### Step 1: Backup First
1. Export your current live database via phpMyAdmin
2. Download current PHP files as backup

### Step 2: Update Database
1. Run the SQL script via phpMyAdmin
2. Verify the EngineConfig column was added:
   ```sql
   DESCRIBE vessels;
   ```

### Step 3: Upload Files
Upload these files to your IONOS server `/enginerm/` folder:
- `manage_vessels.php`
- `vessel_functions.php` 
- `add_log.php`
- `edit_log.php`
- `view_logs.php`
- `graph_logs.php`

### Step 4: Test the Update
1. Visit your live site
2. Go to Manage Vessels
3. Try creating a new vessel - you should see "Engine Configuration" option
4. Test adding log entries with different vessel configurations

## ✅ Verification Checklist

After deployment, verify:
- [ ] Existing vessels still work normally
- [ ] New vessel form shows Engine Configuration dropdown
- [ ] Can create three-engine vessels
- [ ] Data entry shows "Center Main" for three-engine vessels
- [ ] View logs shows all sides correctly
- [ ] Graphs work for center main engine data
- [ ] No error messages in PHP error logs

## 🔧 If Issues Occur

### Database Column Missing Error
If you see "Unknown column 'EngineConfig'" error:
- The SQL script didn't run successfully
- Re-run the ALTER TABLE command via phpMyAdmin

### Side Dropdown Issues
If side dropdown doesn't show correctly:
- Check that vessel_functions.php uploaded correctly
- Verify database column exists with: `SHOW COLUMNS FROM vessels;`

### Existing Data Problems
If existing vessels don't work:
- Run: `UPDATE vessels SET EngineConfig = 'standard' WHERE EngineConfig IS NULL;`

## 🛡️ Rollback Plan

If you need to rollback:

### Database Rollback
```sql
-- Remove the EngineConfig column
ALTER TABLE vessels DROP COLUMN EngineConfig;
```

### File Rollback
- Restore your backup PHP files
- The app will work exactly as before

## 📞 Support Notes

- All existing vessels automatically become "standard" (Port/Starboard)
- No existing data is affected or lost
- New feature is additive only
- Backward compatibility maintained 100%

## 🎯 Post-Deployment Usage

### Creating Three-Engine Vessel
1. Go to Manage Vessels
2. Click "Add New Vessel"
3. Select "Three Engine (Port, Center, Starboard)" in Engine Configuration
4. Complete and save

### Using Center Main
1. Select three-engine vessel
2. Go to Add Log Entry  
3. Side dropdown will show: Port, Center Main, Starboard
4. Enter data normally

The update is designed to be seamless with zero downtime! 🚢
