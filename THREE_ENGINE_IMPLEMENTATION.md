# Three Engine Support Implementation

## 🚢 Overview
Added comprehensive support for vessels with three main engines (Port, Center Main, Starboard) in addition to the existing two-engine configuration (Port, Starboard).

## 🔧 Database Changes

### New Column: `vessels.EngineConfig`
- **Type:** `ENUM('standard', 'three_engine')`
- **Default:** `'standard'`
- **Purpose:** Track whether vessel has 2 or 3 main engines

### Migration Scripts
- `add_three_engine_support.sql` - SQL commands to add column
- `setup_three_engine.php` - PHP migration script with user feedback

## 📝 Files Modified

### Core Functions (`vessel_functions.php`)
- **New:** `get_vessel_sides($conn, $vessel_id)` - Returns available sides for vessel
- **New:** `vessel_has_center_engine($conn, $vessel_id)` - Check if vessel has center engine
- Added null-safe handling for existing vessels without EngineConfig

### Vessel Management (`manage_vessels.php`)
- Added Engine Configuration dropdown in vessel creation form
- Updated database insertion to include EngineConfig
- Added engine configuration display in vessel listing
- Shows "Three Engine" or "Standard" configuration for each vessel

### Data Entry Forms
**`add_log.php`**
- Dynamic side dropdown based on vessel configuration
- Automatically includes "Center Main" for three-engine vessels

**`edit_log.php`**
- Dynamic side dropdown for editing existing entries
- Maintains compatibility with existing data

### Data Viewing (`view_logs.php`)
- Dynamic side filtering based on vessel configuration
- Graph links generated for all available sides (including Center Main)
- Side counting logic updated for dynamic sides

### Graphing (`graph_logs.php`)
- Added support for vessel sides detection
- Center Main engine data displays correctly in charts

## 🎯 Features Added

### Vessel Creation
- Engine Configuration selector:
  - **Standard:** Port & Starboard (default)
  - **Three Engine:** Port, Center Main & Starboard
- Helper text explains the difference
- Backward compatible with existing vessels

### Data Entry
- Side dropdown automatically adjusts based on vessel configuration
- Three-engine vessels show: Port, Center Main, Starboard
- Standard vessels show: Port, Starboard
- All validation and duplicate checking works with center engine

### Data Viewing & Analysis
- View logs page shows all available sides
- Graph buttons appear for each configured side
- Equipment data filters correctly by all sides
- Charts display center main engine data properly

### Visual Indicators
- Vessel management page shows engine configuration
- Clear visual distinction between standard and three-engine vessels
- Color-coded configuration display

## 🔄 Migration Process

### For Existing Installations
1. Run `setup_three_engine.php` to add database support
2. Existing vessels default to "standard" configuration
3. New vessels can be created with either configuration
4. No data loss or compatibility issues

### For New Installations
- EngineConfig column included in fresh database setup
- All features available immediately

## 🛡️ Backward Compatibility
- Existing vessels work exactly as before
- All existing data remains accessible
- Default behavior unchanged for standard vessels
- Graceful handling of NULL EngineConfig values

## 📊 Impact on Existing Features
- ✅ All log entry forms work with both configurations
- ✅ All viewing/filtering respects vessel configuration  
- ✅ All graphing includes center engine data
- ✅ User authentication unaffected
- ✅ Vessel switching works with all configurations
- ✅ Export/import includes new configuration data

## 🚀 Usage Instructions

### Setting Up Three-Engine Vessel
1. Go to Manage Vessels
2. Click "Add New Vessel"
3. Select "Three Engine (Port, Center, Starboard)" in Engine Configuration
4. Complete vessel details and save

### Using Center Main Engine
1. Select vessel with three-engine configuration
2. Go to Add Log Entry
3. Side dropdown now includes "Center Main" option
4. Enter data normally - all equipment types supported

### Viewing Center Main Data
1. View Logs page shows Center Main entries
2. Graph buttons include "Center Main Graph" when data exists
3. All filtering and date ranges include center engine data

## 🎉 Benefits
- **Complete Flexibility:** Support for both 2 and 3 engine vessels
- **Zero Disruption:** Existing users see no changes until they need them
- **Future-Proof:** Easy to extend for other configurations
- **Data Integrity:** All validation and duplicate prevention works
- **Comprehensive:** Every feature supports the new configuration
