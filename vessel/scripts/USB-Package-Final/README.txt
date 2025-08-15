VESSEL LOGGER USB DEPLOYMENT 
============================== 
 
This is a portable vessel logger system that runs entirely from USB. 
 
QUICK START: 
1. Double-click START_VESSEL_LOGGER.bat 
2. Open browser to http://localhost:8080 
3. Follow setup wizard on first run 
 
SCRIPTS: 
- START_VESSEL_LOGGER.bat: Start the vessel logger 
- STOP_VESSEL_LOGGER.bat: Stop the vessel logger 
- CHECK_STATUS.bat: Check if logger is running 
 
SECURITY: 
- Only runs on port 8080 (port 80 blocked) 
- No external network access by default 
- SQLite database for offline operation 
- Session-based authentication 
 
TROUBLESHOOTING: 
If port 8080 is in use, edit vessel-production.conf and change Listen port 
