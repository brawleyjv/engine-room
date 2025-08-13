# Multi-Tier Authentication & Database Architecture

## 🔐 **Authentication Layers**

### **Layer 1: User Authentication (Individual Login)**
Each person has their own username/password to access the vessel logger application:

**Vessel Roles:**
- **Engineers** - Full engine room logging access
- **Wheelmen** - Navigation + engine monitoring
- **Deck Crew** - Basic logging capabilities  
- **Chief Engineer** - Vessel admin with all permissions

**Shore Roles:**
- **Fleet Manager** - Oversee all vessels and operations
- **Port Engineer** - Technical analysis and reporting
- **Office Admin** - Full system administration
- **Support Tech** - Technical support and troubleshooting

### **Layer 2: Database Authentication (System-Level)**
The application uses different database credentials based on location:

**Shore System:** Universal credentials for all shore users
- Username: `chief`
- Password: `rustyzeller`
- Database: `VesselData`

**Vessel Systems:** Each vessel has unique database credentials
- Vessel 001: `vessel_001_user` / `vessel_001_pass_2024`
- Vessel 002: `vessel_002_user` / `vessel_002_pass_2024`
- etc...

## 🔄 **Data Flow Architecture**

```
USER LOGIN → ROLE CHECK → DATABASE SELECTION → DATA ACCESS
    ↓             ↓              ↓               ↓
Individual    Permission     Shore/Vessel      SQLite/MariaDB
Credentials   Validation     DB Credentials    Connection
```

### **Shore Operations:**
```
Shore User Login → Shore MariaDB (chief/rustyzeller) → All Vessel Data
```

### **Vessel Operations:**
```
Vessel User Login → Vessel SQLite → Sync to Shore MariaDB
```

## 🚢 **Vessel Deployment Process**

### **1. Initial Setup (Shore)**
```sql
-- Create vessel database credentials
INSERT INTO vessel_database_configs (VesselID, DatabaseName, Username, Password)
VALUES (123, 'vessel_123_data', 'vessel_123_user', 'vessel_123_pass_2024');
```

### **2. User Creation**
```sql
-- Create vessel users assigned to specific vessel
INSERT INTO users (Username, Email, PasswordHash, FirstName, LastName, Role, AssignedVesselID)
VALUES 
('john_engineer', 'john@vessel123.com', '$hash', 'John', 'Smith', 'engineer', 123),
('mary_wheelman', 'mary@vessel123.com', '$hash', 'Mary', 'Jones', 'wheelman', 123);
```

### **3. Offline Database Setup**
```bash
# Initialize vessel offline database
http://localhost/enginerm/vessel/offline/setup.php
# Select Vessel 123 → Initialize Offline System
```

### **4. Vessel Deployment**
- Copy SQLite database to vessel computer
- Engineers log in with individual credentials (john_engineer/mary_wheelman)
- System automatically uses vessel_123 database connection
- Data logs offline to SQLite
- Syncs to shore when internet available

## 🎯 **Key Benefits**

✅ **Individual Accountability** - Each person has unique login
✅ **Role-Based Security** - Permissions based on job function  
✅ **Vessel Isolation** - Each vessel's data is separate
✅ **Offline Capability** - Works without internet connection
✅ **Automatic Sync** - Data flows to shore when connected
✅ **Audit Trail** - Track who did what and when

## 🔧 **Files Created/Updated**

- `config_multi_tier.php` - Database connection management
- `user_roles.php` - Role definitions and permissions
- `auth_functions_enhanced.php` - Enhanced authentication
- `upgrade_user_roles.sql` - Database schema updates
- `test_offline_standalone.php` - Offline testing (no MariaDB required)

The system now supports your exact requirements: individual user authentication with role-based permissions, while using location-specific database credentials transparently in the background!
