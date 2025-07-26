#!/bin/bash
# Database Export Script for IONOS Deployment
# Run this from your local XAMPP environment

echo "🗄️ Exporting Vessel Logger Database for IONOS Deployment..."

# Export structure and data (using your actual database name and credentials)
mysqldump -u chief -p VesselData > vessel_logger_complete.sql

# Export structure only (if you want to start fresh)
mysqldump -u chief -p --no-data VesselData > vessel_logger_structure.sql

# Export sample data only (for testing)
mysqldump -u chief -p --no-create-info VesselData > vessel_logger_data.sql

echo "✅ Export complete! Files created:"
echo "   - vessel_logger_complete.sql    (Full database)"
echo "   - vessel_logger_structure.sql   (Structure only)" 
echo "   - vessel_logger_data.sql        (Data only)"
echo ""
echo "📤 Upload the appropriate .sql file to your IONOS hosting and import via phpMyAdmin"
