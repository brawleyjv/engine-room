# 📊 Historical Performance Graphing System - Complete Implementation

## 🎯 System Overview
The Equipment Performance Graphing system provides interactive, time-series visualization of marine engine and generator data with professional features suitable for vessel operations and compliance documentation.

## ✅ Completed Features

### 🖥️ Main Components
- **`equipment_graphs.php`** - Main graphing interface with interactive controls
- **`graph_api.php`** - Backend API for data retrieval and settings management  
- **`generate_sample_graph_data.php`** - Sample data generator (378 realistic readings)
- **Dashboard integration** - Navigation link added to main dashboard
- **Settings integration** - Graph scale settings added to vessel settings page

### 📊 Interactive Graph Features
- **Equipment Selection**: Dropdown menus for equipment type (Engine/Generator) and specific equipment ID
- **Date Range Selection**: 7/30/90/365 days, all time, or custom date range with date pickers
- **Parameter Selection**: Organized checkboxes by category (RPM, Pressures, Temperatures, Electrical)
- **Dual Y-Axes**: Left axis for RPM, right axis for pressures/temperatures with independent scaling
- **Interactive Legend**: Click to show/hide parameter lines
- **Mouse-over Details**: Hover tooltips with precise values, units, and timestamps
- **Chart.js Integration**: Professional time-series charting with zoom and pan capabilities

### ⚙️ Configuration System
- **Scale Settings Modal**: Configure Y-axis ranges for RPM (0-2000), Pressure (0-100 PSI), Temperature (100-300°F)
- **Persistent Settings**: Scale preferences stored in `vessel_settings` database table
- **Settings Page Integration**: Graph settings section added to main vessel settings with form handling
- **Real-time Updates**: Settings changes immediately affect new graphs without page reload

### 🗃️ Data Architecture
- **Flexible Parameter Support**: Supports all existing engine and generator parameters
- **Historical Data**: Works with existing `engine_readings` and `generator_readings` tables
- **Date/Time Handling**: Proper timezone-aware date formatting and range filtering
- **Equipment Mapping**: Maps equipment types (port_main, center_gen, etc.) to database columns

### 📈 Available Parameters

#### Engines (9 categories):
- **RPM**: `rpm`
- **Pressures**: `oil_pressure`, `fuel_pressure`, `turbo_oil_pressure`, `governor_air_pressure`, `aftercooler_water_pressure`, `lube_oil_filter_pressure_in`, `lube_oil_filter_pressure_out`, `air_box_pressure`, `ship_air_pressure`
- **Temperatures**: `water_temp_in`, `water_temp_out`, `oil_temp_in`, `oil_temp_out`, `aftercooler_water_temp_out`
- **Other**: `crankcase_vacuum`

#### Generators (4 categories):
- **RPM**: `rpm`
- **Pressures**: `lube_oil_pressure`, `fuel_pressure`
- **Temperatures**: `water_temp_in`, `water_temp_out`
- **Electrical**: `battery_voltage`, `voltage_out`, `frequency_hz`, `amperage`

## 📊 Sample Data Generated
- **Engines**: port_main, center_main, starboard_main (256 readings total)
- **Generators**: port_gen, center_gen (122 readings total)
- **Date Range**: Past 30 days (2025-07-18 to 2025-08-17)
- **Realistic Values**: Marine-appropriate ranges with natural variation
- **Multiple Daily Readings**: 2-4 engine readings per day, 1-3 generator readings per day

## 🌐 Access Points

### Primary Interface
- **Graph Page**: `http://localhost/enginerm/test-engine-room/equipment_graphs.php`
- **Dashboard Link**: "📊 Performance Graphs" in main navigation
- **Settings Integration**: Graph scale settings in vessel settings page

### API Endpoints
- **Data Retrieval**: POST to `graph_api.php` with action `get_graph_data`
- **Settings Management**: POST to `graph_api.php` with action `save_scale_settings`

## 🔧 Technical Implementation

### Frontend Technologies
- **Chart.js 3.x**: Time-series line charts with dual Y-axes
- **Date-fns**: Date formatting and manipulation
- **Vanilla JavaScript**: Equipment selection, parameter filtering, API communication
- **Responsive CSS**: Mobile-friendly interface matching existing dashboard styles

### Backend Architecture
- **PHP/PDO**: Database interactions with SQLite
- **JSON API**: RESTful data endpoints with proper error handling
- **Settings Framework**: Integration with existing vessel settings system
- **Date Handling**: Proper timezone and range filtering

### Database Integration
- **Existing Tables**: Uses current `engine_readings` and `generator_readings` structures
- **Settings Storage**: Graph preferences in `vessel_settings` table
- **No Schema Changes**: Works with current database structure

## 🚀 Usage Instructions

### Creating a Graph
1. **Navigate** to Performance Graphs from dashboard or settings
2. **Select Equipment Type**: Choose "Main Engine" or "Generator"
3. **Choose Equipment**: Select specific engine/generator from dropdown
4. **Set Date Range**: Choose predefined range or set custom dates
5. **Select Parameters**: Check boxes for desired measurements to display
6. **Load Graph**: Click "📊 Load Graph" to generate interactive chart
7. **Interact**: Click legend to show/hide lines, hover for details, adjust scale settings

### Configuring Scales
1. **From Graph Page**: Click "⚙️ Scale Settings" button
2. **From Settings Page**: Navigate to "📊 Performance Graph Settings" section
3. **Adjust Ranges**: Set min/max values for RPM, Pressure (PSI), and Temperature (°F)
4. **Save Settings**: Settings persist across sessions and users

## 📋 Validation & Testing
- **✅ API Functionality**: Tested with 378 sample records across 5 equipment types
- **✅ Date Range Filtering**: Verified for 7/30/90/365 day and custom ranges
- **✅ Parameter Selection**: All engine and generator parameters rendering correctly
- **✅ Interactive Features**: Legend toggle, hover tooltips, dual Y-axes working
- **✅ Settings Persistence**: Scale preferences saved and applied correctly
- **✅ Dashboard Integration**: Navigation and styling consistent with existing interface

## 🔮 Future Enhancements (Ready for Implementation)
- **Export Functionality**: CSV/PDF export of graph data and images
- **Automated Alerts**: Trend-based notifications for parameter anomalies
- **Comparative Analysis**: Side-by-side equipment comparison graphs
- **Statistical Overlays**: Moving averages, trend lines, performance envelopes
- **Mobile Optimization**: Touch-friendly controls for tablet/phone use
- **Print Layouts**: Formatted graphs suitable for logbook inclusion

## ✨ System Status: **PRODUCTION READY**

The Historical Performance Graphing system is fully implemented and ready for vessel operations. All core functionality is operational with realistic test data, professional UI/UX, and integration with the existing engine room management system.

**Access the system**: Navigate to your engine room dashboard and click "📊 Performance Graphs" to begin analyzing equipment performance trends!
