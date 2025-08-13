<?php
// Step 4: Vessel Configuration
$error = '';
$success = '';

if ($_POST) {
    $vessel_name = trim($_POST['vessel_name'] ?? '');
    $vessel_type = $_POST['vessel_type'] ?? '';
    $engine_config = $_POST['engine_config'] ?? '';
    $vessel_length = trim($_POST['vessel_length'] ?? '');
    $vessel_beam = trim($_POST['vessel_beam'] ?? '');
    $vessel_draft = trim($_POST['vessel_draft'] ?? '');
    $vessel_tonnage = trim($_POST['vessel_tonnage'] ?? '');
    $home_port = trim($_POST['home_port'] ?? '');
    $imo_number = trim($_POST['imo_number'] ?? '');
    $call_sign = trim($_POST['call_sign'] ?? '');
    
    // Validation
    if (empty($vessel_name) || empty($vessel_type) || empty($engine_config)) {
        $error = 'Vessel name, type, and engine configuration are required.';
    } else {
        // Store vessel info in session
        $_SESSION['install_data']['vessel_name'] = $vessel_name;
        $_SESSION['install_data']['vessel_type'] = $vessel_type;
        $_SESSION['install_data']['engine_config'] = $engine_config;
        $_SESSION['install_data']['vessel_length'] = $vessel_length;
        $_SESSION['install_data']['vessel_beam'] = $vessel_beam;
        $_SESSION['install_data']['vessel_draft'] = $vessel_draft;
        $_SESSION['install_data']['vessel_tonnage'] = $vessel_tonnage;
        $_SESSION['install_data']['home_port'] = $home_port;
        $_SESSION['install_data']['imo_number'] = $imo_number;
        $_SESSION['install_data']['call_sign'] = $call_sign;
        
        header('Location: ?step=5');
        exit;
    }
}

$data = $_SESSION['install_data'] ?? [];
?>

<h2>Primary Vessel Configuration</h2>
<p>Configure your first vessel. You can add additional vessels later (subject to your subscription plan).</p>

<?php if ($error): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<form method="POST">
    <div class="form-group">
        <label for="vessel_name">Vessel Name *</label>
        <input type="text" name="vessel_name" id="vessel_name" 
               value="<?php echo htmlspecialchars($data['vessel_name'] ?? ''); ?>" 
               placeholder="M/V ACME RIVER" required>
    </div>

    <div class="form-group">
        <label for="vessel_type">Vessel Type *</label>
        <select name="vessel_type" id="vessel_type" required>
            <option value="">Select Vessel Type</option>
            <option value="towboat" <?php echo ($data['vessel_type'] ?? '') == 'towboat' ? 'selected' : ''; ?>>Towboat</option>
            <option value="fishing" <?php echo ($data['vessel_type'] ?? '') == 'fishing' ? 'selected' : ''; ?>>Commercial Fishing Vessel</option>
            <option value="workboat" <?php echo ($data['vessel_type'] ?? '') == 'workboat' ? 'selected' : ''; ?>>Workboat</option>
            <option value="tug" <?php echo ($data['vessel_type'] ?? '') == 'tug' ? 'selected' : ''; ?>>Tugboat</option>
            <option value="supply" <?php echo ($data['vessel_type'] ?? '') == 'supply' ? 'selected' : ''; ?>>Supply Vessel</option>
            <option value="other" <?php echo ($data['vessel_type'] ?? '') == 'other' ? 'selected' : ''; ?>>Other</option>
        </select>
    </div>

    <div class="form-group">
        <label for="engine_config">Engine Configuration *</label>
        <select name="engine_config" id="engine_config" required>
            <option value="">Select Engine Configuration</option>
            <option value="single" <?php echo ($data['engine_config'] ?? '') == 'single' ? 'selected' : ''; ?>>Single Engine</option>
            <option value="twin" <?php echo ($data['engine_config'] ?? '') == 'twin' ? 'selected' : ''; ?>>Twin Engine (Port/Starboard)</option>
            <option value="triple" <?php echo ($data['engine_config'] ?? '') == 'triple' ? 'selected' : ''; ?>>Triple Engine (Port/Center/Starboard)</option>
        </select>
    </div>

    <div style="display: flex; gap: 10px;">
        <div class="form-group" style="flex: 1;">
            <label for="vessel_length">Length (ft)</label>
            <input type="number" name="vessel_length" id="vessel_length" 
                   value="<?php echo htmlspecialchars($data['vessel_length'] ?? ''); ?>" 
                   placeholder="120" step="0.1">
        </div>
        <div class="form-group" style="flex: 1;">
            <label for="vessel_beam">Beam (ft)</label>
            <input type="number" name="vessel_beam" id="vessel_beam" 
                   value="<?php echo htmlspecialchars($data['vessel_beam'] ?? ''); ?>" 
                   placeholder="35" step="0.1">
        </div>
        <div class="form-group" style="flex: 1;">
            <label for="vessel_draft">Draft (ft)</label>
            <input type="number" name="vessel_draft" id="vessel_draft" 
                   value="<?php echo htmlspecialchars($data['vessel_draft'] ?? ''); ?>" 
                   placeholder="9" step="0.1">
        </div>
    </div>

    <div class="form-group">
        <label for="vessel_tonnage">Gross Tonnage</label>
        <input type="number" name="vessel_tonnage" id="vessel_tonnage" 
               value="<?php echo htmlspecialchars($data['vessel_tonnage'] ?? ''); ?>" 
               placeholder="1200">
    </div>

    <div class="form-group">
        <label for="home_port">Home Port</label>
        <input type="text" name="home_port" id="home_port" 
               value="<?php echo htmlspecialchars($data['home_port'] ?? ''); ?>" 
               placeholder="New Orleans, LA">
    </div>

    <div style="display: flex; gap: 10px;">
        <div class="form-group" style="flex: 1;">
            <label for="imo_number">IMO Number</label>
            <input type="text" name="imo_number" id="imo_number" 
                   value="<?php echo htmlspecialchars($data['imo_number'] ?? ''); ?>" 
                   placeholder="1234567">
        </div>
        <div class="form-group" style="flex: 1;">
            <label for="call_sign">Call Sign</label>
            <input type="text" name="call_sign" id="call_sign" 
                   value="<?php echo htmlspecialchars($data['call_sign'] ?? ''); ?>" 
                   placeholder="WDX1234">
        </div>
    </div>

    <div style="background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 15px 0;">
        <h4>📋 Note:</h4>
        <ul style="margin: 5px 0 0 20px;">
            <li><strong>Trial includes 1 vessel</strong> - Perfect for testing</li>
            <li>Additional vessels require subscription upgrade</li>
            <li>Engine configuration determines available logging options</li>
            <li>All vessel details can be edited after installation</li>
        </ul>
    </div>

    <div style="margin-top: 20px;">
        <a href="?step=3" style="margin-right: 10px; text-decoration: none; color: #666;">← Back</a>
        <button type="submit" class="btn">Continue →</button>
    </div>
</form>

<script>
// Show/hide engine configuration help
document.getElementById('engine_config').addEventListener('change', function() {
    const config = this.value;
    const helpText = document.getElementById('engine_help');
    
    if (helpText) helpText.remove();
    
    if (config) {
        const help = document.createElement('div');
        help.id = 'engine_help';
        help.style.cssText = 'background: #fff3e0; padding: 10px; border-radius: 3px; margin-top: 10px; font-size: 14px;';
        
        if (config === 'single') {
            help.innerHTML = '<strong>Single Engine:</strong> One main propulsion engine. Ideal for smaller vessels.';
        } else if (config === 'twin') {
            help.innerHTML = '<strong>Twin Engine:</strong> Port and Starboard engines. Most common for towboats and workboats.';
        } else if (config === 'triple') {
            help.innerHTML = '<strong>Triple Engine:</strong> Port, Center Main, and Starboard engines. For larger towboats and specialized vessels.';
        }
        
        this.parentNode.appendChild(help);
    }
});
</script>
