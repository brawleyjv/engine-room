<?php
// Step 1: Welcome & Requirements Check
$php_version = phpversion();
$mysql_available = extension_loaded('mysqli');
$curl_available = extension_loaded('curl');
$requirements_met = version_compare($php_version, '7.4', '>=') && $mysql_available && $curl_available;
?>

<h2>Welcome to Vessel Management System</h2>

<p>This installation wizard will set up your dedicated vessel management platform. Each installation creates a completely isolated environment for your company.</p>

<div class="form-group">
    <h3>System Requirements Check:</h3>
    <ul>
        <li>PHP Version: <?php echo $php_version; ?> 
            <?php echo version_compare($php_version, '7.4', '>=') ? '✅' : '❌ (7.4+ required)'; ?>
        </li>
        <li>MySQL Support: <?php echo $mysql_available ? '✅' : '❌'; ?></li>
        <li>cURL Support: <?php echo $curl_available ? '✅' : '❌'; ?></li>
    </ul>
</div>

<div class="form-group">
    <h3>What You'll Get:</h3>
    <ul>
        <li>🛠️ <strong>Engine Room Module:</strong> RPM, hours, fuel tracking</li>
        <li>⚓ <strong>Wheelhouse Module:</strong> Navigation logs, weather, positions</li>
        <li>📊 <strong>Dashboard:</strong> Real-time vessel status overview</li>
        <li>🔄 <strong>Data Sync:</strong> Automatic cloud backup and sync</li>
        <li>👥 <strong>Multi-User:</strong> Captain, engineer, crew access levels</li>
        <li>📱 <strong>Mobile Ready:</strong> Works on tablets and phones</li>
    </ul>
</div>

<div class="form-group">
    <h3>Trial Information:</h3>
    <div style="background: #e3f2fd; padding: 15px; border-radius: 5px;">
        <p><strong>30-Day Free Trial</strong> - Full access to all basic features</p>
        <p><strong>1 Vessel Included</strong> - Perfect for testing</p>
        <p><strong>No Credit Card Required</strong> - Start immediately</p>
        <p>After trial: $99/month for basic package (1 vessel)</p>
    </div>
</div>

<?php if ($requirements_met): ?>
    <a href="?step=2" class="btn">Start Installation</a>
<?php else: ?>
    <div class="error">
        <p>⚠️ System requirements not met. Please contact your hosting provider to update PHP or enable required extensions.</p>
    </div>
<?php endif; ?>
