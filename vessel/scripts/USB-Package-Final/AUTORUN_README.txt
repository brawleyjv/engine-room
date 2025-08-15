VESSEL LOGGER - USB AUTORUN SETUP
==================================

AUTOMATIC STARTUP
------------------
This USB drive is configured for automatic startup when inserted into a Windows computer.

WHAT HAPPENS AUTOMATICALLY:
1. Windows detects the USB drive
2. Shows autorun dialog (if enabled)
3. Offers to run "Start Vessel Logger"
4. Automatically starts Apache server on port 8080
5. Opens browser to http://localhost:8080
6. Shows vessel logger setup/login page

MANUAL STARTUP OPTIONS:
-----------------------
If autorun is disabled or you prefer manual control:

• AUTOSTART_VESSEL_LOGGER.bat - Full automatic startup + browser
• START_VESSEL_LOGGER.bat - Start server only
• STOP_VESSEL_LOGGER.bat - Stop the server
• CHECK_STATUS.bat - Check if running

AUTORUN SECURITY NOTE:
----------------------
Some systems disable autorun for security. If autorun doesn't work:
1. Double-click AUTOSTART_VESSEL_LOGGER.bat manually
2. Or use START_VESSEL_LOGGER.bat then open http://localhost:8080

FIRST TIME SETUP:
-----------------
1. Insert USB drive
2. Allow autorun or run AUTOSTART_VESSEL_LOGGER.bat
3. Browser opens to http://localhost:8080
4. Follow setup wizard:
   - Set vessel name and details
   - Create admin user
   - Configure sync settings
5. Begin logging operations

TECHNICAL DETAILS:
------------------
• Runs on port 8080 only (safe, no conflicts)
• SQLite database (no external dependencies)
• Portable Apache + PHP 8.2
• All data stored on USB drive
• Works offline, syncs when connected

TROUBLESHOOTING:
----------------
If autorun doesn't work:
• Check Windows autorun policy
• Run as administrator if needed
• Ensure port 8080 is available
• Check antivirus isn't blocking

For support: Check README.txt for full documentation
