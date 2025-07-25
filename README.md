# Vessel Data Logger

A comprehensive PHP/MySQL web application for logging and tracking vessel equipment performance data including main engines, generators, and gear systems.

## Features

- **Multi-Equipment Logging**: Track main engines, generators, and gear systems
- **Dual-Side Support**: Port and Starboard equipment tracking
- **Data Validation**: Required field validation and duplicate hour prevention
- **Advanced Graphing**: Interactive Chart.js-based performance trends
- **Real-time Statistics**: Equipment performance statistics and summaries
- **Date Range Filtering**: Filter data by custom date ranges
- **Responsive Design**: Mobile-friendly interface

## Equipment Types Supported

### Main Engines
- RPM monitoring
- Oil pressure and temperature
- Fuel pressure
- Water temperature
- Operating hours tracking

### Generators
- Oil pressure monitoring
- Fuel pressure
- Water temperature
- Generator hours tracking

### Gear Systems
- Oil pressure monitoring
- Temperature tracking
- Gear hours
- Engine RPM correlation

## Technology Stack

- **Backend**: PHP 7.4+ with mysqli
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **Charts**: Chart.js with date-fns adapter
- **Styling**: Custom CSS with responsive grid layout

## Installation

1. Clone this repository to your web server directory
2. Import the database schema (create tables for mainengines, generators, gears)
3. Configure database connection in `config.php`
4. Ensure web server has PHP mysqli extension enabled
5. Access via web browser

## Database Schema

Each equipment table includes:
- Side (Port/Starboard)
- EntryDate and Timestamp
- Equipment-specific metrics
- Operating hours

## Graph Features

- **Operational Data Filtering**: Automatically excludes non-operational periods (zero RPM/oil pressure)
- **Multi-Metric Display**: All relevant metrics on single charts with dual Y-axes
- **Custom Scaling**: RPM scale (650-1750), Temperature/Pressure scale (20-400)
- **Interactive Tooltips**: Detailed data point information
- **Date Range Controls**: Filter by specific time periods

## Usage

1. **Add Data**: Use the main form to log equipment readings
2. **View Logs**: Browse historical data in table format
3. **Analyze Trends**: View interactive graphs for performance analysis
4. **Edit Records**: Modify or delete existing entries as needed

## Development

This project was developed with a focus on:
- Data integrity and validation
- User-friendly interface
- Robust error handling
- Performance optimization
- Maritime industry requirements

## License

MIT License - Feel free to modify and distribute

## Contributing

Contributions welcome! Please submit pull requests for any improvements.
