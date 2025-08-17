# File Cleanup Plan - Remove Development/Debug Files

## Files to KEEP (Production System):
- index.php                      # Main dashboard
- equipment_graphs.php           # Performance graphing interface
- graph_api.php                  # Graphing API backend
- manage_*.php                   # Equipment and fluid management pages
- service_*.php                  # Service management pages
- settings.php                   # Settings configuration
- settings_helper.php            # Settings utilities
- log_helper.php                 # Logbook integration
- view_logs.php                  # Log viewing interface
- view_edit_history.php          # Log audit trail
- test_db.php                    # Database connection (production)
- test_styles.css                # Stylesheet
- test_engine.db                 # Database file
- GRAPHING_SYSTEM_COMPLETE.md    # Documentation

## Files to REMOVE (Development/Debug):
### Check/Debug Scripts:
- check_*.php (15+ files)
- debug_*.php (8+ files) 
- verify_*.php (4+ files)
- comprehensive_verification.php
- diagnostics.php

### Test Scripts:
- test_*.php (15+ files)
- final_*.php (3 files)

### Setup/Migration Scripts:
- setup_*.php (6+ files)
- migrate_database.php
- towing_vessel_setup.php

### Sample Data Generators:
- create_sample_*.php (2 files)
- generate_sample_graph_data.php

### Fix/Cleanup Scripts:
- fix_*.php (5+ files)
- remove_duplicates.php
- reset_hours.php

### Old/Backup Files:
- manage_fuel_new.php
- manage_fuel_old.php

### Test Results:
- graph_system_test.php
