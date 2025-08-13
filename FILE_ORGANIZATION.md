# Vessel Logger File Organization

This document explains the reorganized file structure for the Vessel Logger SaaS platform.

## 📁 Directory Structure

### **Root Directory**
Contains only the core SaaS application files:
- `index.php` - Main landing page
- `signup_test.php` - Company onboarding system
- `login_enhanced.php` - Multi-tenant authentication
- `welcome.php` - New user welcome experience
- `company_admin.php` - Company management dashboard
- `config_saas.php` - SaaS configuration system
- `config_multi_tier.php` - Multi-tier authentication
- Core user management and vessel management files

### **vessel/** - Current Vessel Operations
Contains the active vessel logging system:
- `vessel/engineroom/` - Modern engine room logging interface
- `vessel/offline/` - Offline/sync capabilities
- Active vessel management components

### **legacy/** - Historical Files
Contains old files kept for reference and migration:

#### **legacy/engine_setup/**
- `add_generator_columns.php` - Old generator setup
- `add_generator_scales.sql` - Generator scaling setup
- `add_three_engine_support.sql` - Three-engine system
- `add_vessel_scales.sql` - Vessel scaling configuration
- `edit_vessel_scales.php` - Scale editing interface
- `setup_three_engine.php` - Three-engine setup script
- `simplify_vessel_scales.sql` - Scale simplification
- `setup_multivessel.php` - Multi-vessel setup
- `setup_user_system.php` - User system setup

#### **legacy/database_fixes/**
- `check_schema.php` - Schema validation
- `check_table_structure.php` - Table structure check
- `fix_database_schema.php` - Database repair script
- `fix_duplicate_vessel.sql` - Duplicate vessel cleanup
- `fix_generators.php` - Generator fix script
- `comprehensive_fix_center_main.php` - Main engine fixes
- `fix_center_main_data.php` - Main engine data fixes
- `update_paths.php` - Path update utility

#### **legacy/old_config/**
- `config-new.php` - Old configuration attempt
- `configNEW.php` - Another old config
- `config_production.php` - Production config template

#### **legacy/session_fixes/**
- `fix_session.php` - Session repair utility
- `quick_session_check.php` - Session validation
- `web_session_fix.php` - Web session fixes
- `auth_functions.php` - Old authentication functions

### **scripts/** - Utility Scripts

#### **scripts/database/**
- `vessel_logger_complete.sql` - Complete database schema
- `vessel_logger_data.sql` - Sample data
- `vessel_logger_structure.sql` - Database structure
- `setup_master_database.sql` - Master DB setup
- `create_user_system.sql` - User system schema
- `upgrade_user_roles.sql` - Role upgrade script
- `live_database_update.sql` - Live update script
- `logicdock_tracking_tables.sql` - LogicDock tables
- `check_vessels.sql` - Vessel validation
- `license_database_schema.sql` - License system schema

#### **scripts/deployment/**
- `backup_all_databases.sh` - Database backup utility
- `export_database.bat` - Windows export script
- `export_database.sh` - Unix export script
- `export_database_secure.bat` - Secure export script
- `setup_vps_logicdock.sh` - VPS deployment script
- `system_health_monitor.sh` - Health monitoring

### **install/** - Installation System
- Multi-step installation wizard
- Database setup and configuration
- Initial company setup

### **admin/** - System Administration
- Administrative tools and interfaces
- System-wide management

### **api/** - API Endpoints
- `search_companies.php` - Company search API
- RESTful endpoints for integrations

### **office/** - Office Dashboard
- Fleet management interface
- Company-wide operations view

## 🎯 Purpose of Reorganization

### **Benefits:**
1. **Clean Root Directory** - Only active SaaS files in main directory
2. **Legacy Preservation** - Old files preserved for reference/migration
3. **Logical Grouping** - Related files grouped by function
4. **Easy Maintenance** - Clear separation of active vs historical code
5. **Better Navigation** - Developers can find files quickly

### **Migration Notes:**
- All legacy files are preserved and functional
- File paths may need updating in some legacy scripts
- Modern SaaS system uses new file structure
- Old engine room files moved to `vessel/engineroom/`

### **Usage Guidelines:**
- **For new development:** Use root directory and organized subdirectories
- **For maintenance:** Check legacy folders for historical context
- **For deployment:** Use scripts in `scripts/deployment/`
- **For database work:** Use scripts in `scripts/database/`

## 🚀 Current Status

The reorganization maintains full backward compatibility while creating a clean, professional structure for the SaaS platform. All core functionality remains operational:

- ✅ Multi-tenant onboarding system
- ✅ Company database isolation  
- ✅ User authentication and roles
- ✅ Vessel logging operations
- ✅ Administrative interfaces
- ✅ API endpoints
- ✅ Legacy system preservation

This organization supports both the modern SaaS architecture and preserves historical development for reference and potential migration needs.
