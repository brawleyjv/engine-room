# Database SQL Files

This folder contains database setup files for the LogicDock SaaS platform.

## 🎯 **SIMPLE SETUP - Just Import One File!**

### For Production Server Setup:
1. **Download** `license_database_schema.sql` from this folder
2. **Upload** it to your server 
3. **Import** it in phpMyAdmin:
   - Go to phpMyAdmin
   - Click "Import" tab
   - Choose `license_database_schema.sql` file
   - Click "Go"

**That's it!** This one file creates everything you need.

## 📁 **What Each File Does:**

### `license_database_schema.sql` ⭐ **MAIN FILE**
**This is the ONLY file you need to import!**
- Creates the license database
- Creates all tables for companies, users, billing
- Includes sample demo data for testing
- Ready to use immediately after import

### Other Files (Optional):
- `setup_master_database.sql` - Alternative setup (don't use this)
- `logicdock_tracking_tables.sql` - Extra analytics (optional)
- `upgrade_user_roles.sql` - User role upgrades (optional)

## ❓ **Which Method Should I Use?**

### ✅ **Recommended: phpMyAdmin Import**
1. Open phpMyAdmin on your server
2. Create database named: `vessel_license_master`
3. Select that database
4. Go to "Import" tab
5. Upload `license_database_schema.sql`
6. Click "Go"

### ⚡ **Alternative: Command Line**
```bash
mysql -u license_admin -p vessel_license_master < license_database_schema.sql
```

## 🧪 **After Import - Test It:**
Upload and run `test_production_db.php` to verify everything works.

---
**Bottom Line**: Just import `license_database_schema.sql` in phpMyAdmin and you're done!
