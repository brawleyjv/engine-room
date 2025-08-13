# File Audit Report - 2025-08-12 21:48:57

## KEEP MODERN (31 files)

- `dashboard_nav.php` - ✅ Keep as-is (already modernized)
- `office_dashboard.php` - ✅ Keep as-is (already modernized)
- `vessel/engineroom/add_log.php` - ✅ Keep as-is (already modernized)
- `vessel/engineroom/comprehensive_debug.php` - ✅ Keep as-is (already modernized)
- `vessel/engineroom/dashboard.php` - ✅ Keep as-is (already modernized)
- `vessel/engineroom/debug_add_log_form.php` - ✅ Keep as-is (already modernized)
- `vessel/engineroom/debug_center_main_data.php` - ✅ Keep as-is (already modernized)
- `vessel/engineroom/debug_config.php` - ✅ Keep as-is (already modernized)
- `vessel/engineroom/debug_current_vessel.php` - ✅ Keep as-is (already modernized)
- `vessel/engineroom/debug_database_schema.php` - ✅ Keep as-is (already modernized)
- `vessel/engineroom/debug_engine_config.php` - ✅ Keep as-is (already modernized)
- `vessel/engineroom/debug_real_insert.php` - ✅ Keep as-is (already modernized)
- `vessel/engineroom/debug_side_mismatch.php` - ✅ Keep as-is (already modernized)
- `vessel/engineroom/debug_vessels.php` - ✅ Keep as-is (already modernized)
- `vessel/engineroom/debug_view_logs.php` - ✅ Keep as-is (already modernized)
- `vessel/engineroom/debug_view_logs_test.php` - ✅ Keep as-is (already modernized)
- `vessel/engineroom/debug_view_logs_url.php` - ✅ Keep as-is (already modernized)
- `vessel/engineroom/edit_log.php` - ✅ Keep as-is (already modernized)
- `vessel/engineroom/get_engine_data.php` - ✅ Keep as-is (already modernized)
- `vessel/engineroom/get_equipment_sides.php` - ✅ Keep as-is (already modernized)
- `vessel/engineroom/graph_logs.php` - ✅ Keep as-is (already modernized)
- `vessel/engineroom/test_connection.php` - ✅ Keep as-is (already modernized)
- `vessel/engineroom/test_db_insert.php` - ✅ Keep as-is (already modernized)
- `vessel/engineroom/test_duplicate.php` - ✅ Keep as-is (already modernized)
- `vessel/engineroom/test_form.php` - ✅ Keep as-is (already modernized)
- `vessel/engineroom/test_sides.php` - ✅ Keep as-is (already modernized)
- `vessel/engineroom/test_starboard_data.php` - ✅ Keep as-is (already modernized)
- `vessel/engineroom/test_system.php` - ✅ Keep as-is (already modernized)
- `vessel/engineroom/test_vessel_switch.php` - ✅ Keep as-is (already modernized)
- `vessel/engineroom/view_logs.php` - ✅ Keep as-is (already modernized)
- `welcome.php` - ✅ Keep as-is (already modernized)

## KEEP MIGRATE (10 files)

- `auth_functions.php` - 🔄 Update paths and integrate with new structure
- `index.php` - 🔄 Update paths and integrate with new structure
- `license_manager.php` - 🔄 Update paths and integrate with new structure
- `login.php` - 🔄 Update paths and integrate with new structure
- `logout.php` - 🔄 Update paths and integrate with new structure
- `manage_users.php` - 🔄 Update paths and integrate with new structure
- `manage_vessels.php` - 🔄 Update paths and integrate with new structure
- `select_vessel.php` - 🔄 Update paths and integrate with new structure
- `switch_vessel.php` - 🔄 Update paths and integrate with new structure
- `vessel_functions.php` - 🔄 Update paths and integrate with new structure

## OFFICE LEGACY (1 files)

- `office/index.php` - 🏢 Migrate functionality to new office_dashboard.php

## DEVELOPMENT (1 files)

- `comprehensive_fix_center_main.php` - 🔧 Move to development/ folder

## DEPRECATED (10 files)

- `fix_center_main_data.php` - ❌ Archive or remove (no longer needed)
- `fix_database_schema.php` - ❌ Archive or remove (no longer needed)
- `fix_generators.php` - ❌ Archive or remove (no longer needed)
- `fix_session.php` - ❌ Archive or remove (no longer needed)
- `quick_session_check.php` - ❌ Archive or remove (no longer needed)
- `setup_logicdock_tracking.php` - ❌ Archive or remove (no longer needed)
- `setup_multivessel.php` - ❌ Archive or remove (no longer needed)
- `setup_three_engine.php` - ❌ Archive or remove (no longer needed)
- `setup_user_system.php` - ❌ Archive or remove (no longer needed)
- `web_session_fix.php` - ❌ Archive or remove (no longer needed)

## CONFIG (21 files)

- `add_generator_scales.sql` - ⚙️ Review and consolidate
- `add_three_engine_support.sql` - ⚙️ Review and consolidate
- `add_vessel_scales.sql` - ⚙️ Review and consolidate
- `backup_all_databases.sh` - ⚙️ Review and consolidate
- `check_vessels.sql` - ⚙️ Review and consolidate
- `config-new.php` - ⚙️ Review and consolidate
- `config.php` - ⚙️ Review and consolidate
- `configNEW.php` - ⚙️ Review and consolidate
- `config_production.php` - ⚙️ Review and consolidate
- `create_user_system.sql` - ⚙️ Review and consolidate
- `export_database.sh` - ⚙️ Review and consolidate
- `fix_duplicate_vessel.sql` - ⚙️ Review and consolidate
- `live_database_update.sql` - ⚙️ Review and consolidate
- `logicdock_tracking_tables.sql` - ⚙️ Review and consolidate
- `setup_master_database.sql` - ⚙️ Review and consolidate
- `setup_vps_logicdock.sh` - ⚙️ Review and consolidate
- `simplify_vessel_scales.sql` - ⚙️ Review and consolidate
- `system_health_monitor.sh` - ⚙️ Review and consolidate
- `vessel_logger_complete.sql` - ⚙️ Review and consolidate
- `vessel_logger_data.sql` - ⚙️ Review and consolidate
- `vessel_logger_structure.sql` - ⚙️ Review and consolidate

## DOCUMENTATION (10 files)

- `deployment_checklist.md` - 📚 Keep and update
- `file_audit_report.md` - 📚 Keep and update
- `INSTALLATION_README.md` - 📚 Keep and update
- `live_deployment_guide.md` - 📚 Keep and update
- `LOGICDOCK_TRACKING_GUIDE.md` - 📚 Keep and update
- `MODERNIZATION_PLAN.md` - 📚 Keep and update
- `README.md` - 📚 Keep and update
- `SAAS_PLATFORM_COMPLETE.md` - 📚 Keep and update
- `THREE_ENGINE_IMPLEMENTATION.md` - 📚 Keep and update
- `VPS_DEPLOYMENT_GUIDE.md` - 📚 Keep and update

## UNCATEGORIZED (28 files)

- `.gitignore` - ❓ Needs manual review
- `add_generator_columns.php` - ❓ Needs manual review
- `audit_files.php` - ❓ Needs manual review
- `check_schema.php` - ❓ Needs manual review
- `check_table_structure.php` - ❓ Needs manual review
- `edit_vessel_scales.php` - ❓ Needs manual review
- `export_database.bat` - ❓ Needs manual review
- `export_database_secure.bat` - ❓ Needs manual review
- `favicon-helper.html` - ❓ Needs manual review
- `favicon.svg` - ❓ Needs manual review
- `forgot_password.php` - ❓ Needs manual review
- `install.php` - ❓ Needs manual review
- `logicdock_cron.php` - ❓ Needs manual review
- `logicdock_dashboard.php` - ❓ Needs manual review
- `logicdock_tracker.php` - ❓ Needs manual review
- `payment_processor.php` - ❓ Needs manual review
- `reset_password.php` - ❓ Needs manual review
- `set_test_vessel.php` - ❓ Needs manual review
- `signup.php` - ❓ Needs manual review
- `style.css` - ❓ Needs manual review
- `subscription.php` - ❓ Needs manual review
- `subscription_expired.php` - ❓ Needs manual review
- `subscription_success.php` - ❓ Needs manual review
- `support_dashboard.php` - ❓ Needs manual review
- `trial_management.php` - ❓ Needs manual review
- `vessel/index.php` - ❓ Needs manual review
- `vps_management.php` - ❓ Needs manual review
- `webhook_handler.php` - ❓ Needs manual review

## Recommended Actions

### 1. Create new directory structure:
```bash
mkdir -p development
mkdir -p archive
mkdir -p vessel/offline
mkdir -p office/api
```

### 2. Move development files:
```bash
mv "comprehensive_fix_center_main.php" development/
```

### 3. Archive deprecated files:
```bash
mv "fix_center_main_data.php" archive/
mv "fix_database_schema.php" archive/
mv "fix_generators.php" archive/
mv "fix_session.php" archive/
mv "quick_session_check.php" archive/
mv "setup_logicdock_tracking.php" archive/
mv "setup_multivessel.php" archive/
mv "setup_three_engine.php" archive/
mv "setup_user_system.php" archive/
mv "web_session_fix.php" archive/
```

### 4. Files requiring manual migration:
- auth_functions.php
- index.php
- license_manager.php
- login.php
- logout.php
- manage_users.php
- manage_vessels.php
- select_vessel.php
- switch_vessel.php
- vessel_functions.php

### 5. Office files to integrate:
- office/index.php
