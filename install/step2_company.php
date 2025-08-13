<?php
// Step 2: Company Information
$error = '';
$success = '';

// Function to check database prefix availability
function check_database_prefix_availability($prefix) {
    // For now, we'll create a simple list of reserved/used prefixes
    // In production, this would check against the master database
    $reserved_prefixes = ['SYS', 'ADM', 'LOG', 'TMP', 'TST', 'DEV'];
    $used_prefixes = ['CBL', 'MTC', 'ACM', 'RIV']; // Example used prefixes
    
    $prefix = strtoupper($prefix);
    
    if (in_array($prefix, $reserved_prefixes)) {
        return [
            'available' => false,
            'message' => "The prefix '{$prefix}' is reserved by the system. Please choose a different combination."
        ];
    }
    
    if (in_array($prefix, $used_prefixes)) {
        return [
            'available' => false,
            'message' => "The prefix '{$prefix}' is already in use by another company. Please choose a different combination."
        ];
    }
    
    return ['available' => true, 'message' => 'Prefix is available'];
}

// Auto-generate prefix suggestion from company name
function suggest_prefix($company_name) {
    $words = explode(' ', strtoupper($company_name));
    $suggestions = [];
    
    // Try first letters of each word
    if (count($words) >= 2) {
        $prefix = '';
        foreach ($words as $word) {
            if (!empty($word)) {
                $prefix .= substr($word, 0, 1);
                if (strlen($prefix) >= 3) break;
            }
        }
        if (strlen($prefix) >= 2) {
            $suggestions[] = $prefix;
        }
    }
    
    // Try first 3 letters of first word
    if (!empty($words[0]) && strlen($words[0]) >= 3) {
        $suggestions[] = substr($words[0], 0, 3);
    }
    
    return $suggestions;
}

if ($_POST) {
    $company_name = trim($_POST['company_name'] ?? '');
    $company_type = $_POST['company_type'] ?? '';
    $contact_name = trim($_POST['contact_name'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $contact_phone = trim($_POST['contact_phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $zip = trim($_POST['zip'] ?? '');
    $database_prefix = strtoupper(trim($_POST['database_prefix'] ?? ''));
    
    // Validation
    if (empty($company_name) || empty($contact_name) || empty($contact_email) || empty($database_prefix)) {
        $error = 'Company name, contact name, email, and database prefix are required.';
    } elseif (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!preg_match('/^[A-Z]{2,4}$/', $database_prefix)) {
        $error = 'Database prefix must be 2-4 uppercase letters only (no numbers or special characters).';
    } else {
        // Check if prefix is available
        $prefix_check = check_database_prefix_availability($database_prefix);
        if (!$prefix_check['available']) {
            $error = $prefix_check['message'];
        } else {
            // Store in session for later use
            $_SESSION['install_data'] = [
                'company_name' => $company_name,
                'company_type' => $company_type,
                'contact_name' => $contact_name,
                'contact_email' => $contact_email,
                'contact_phone' => $contact_phone,
                'address' => $address,
                'city' => $city,
                'state' => $state,
                'zip' => $zip,
                'database_prefix' => $database_prefix,
                'database_name' => $database_prefix . '_logicdoc'
            ];
            
            header('Location: ?step=3');
            exit;
        }
    }
}

$data = $_SESSION['install_data'] ?? [];
$suggested_prefixes = !empty($data['company_name']) ? suggest_prefix($data['company_name']) : [];
?>

<h2>Company Information</h2>
<p>Tell us about your company. This information will be used for your account setup and billing.</p>

<?php if ($error): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<form method="POST">
    <div class="form-group">
        <label for="company_name">Company Name *</label>
        <input type="text" name="company_name" id="company_name" 
               value="<?php echo htmlspecialchars($data['company_name'] ?? ''); ?>" 
               placeholder="Acme Marine Services" required>
    </div>

    <div class="form-group">
        <label for="company_type">Company Type</label>
        <select name="company_type" id="company_type">
            <option value="">Select Type</option>
            <option value="towboat" <?php echo ($data['company_type'] ?? '') == 'towboat' ? 'selected' : ''; ?>>Towboat/Barge Operator</option>
            <option value="fishing" <?php echo ($data['company_type'] ?? '') == 'fishing' ? 'selected' : ''; ?>>Commercial Fishing</option>
            <option value="workboat" <?php echo ($data['company_type'] ?? '') == 'workboat' ? 'selected' : ''; ?>>Workboat Services</option>
            <option value="tug" <?php echo ($data['company_type'] ?? '') == 'tug' ? 'selected' : ''; ?>>Tugboat Services</option>
            <option value="other" <?php echo ($data['company_type'] ?? '') == 'other' ? 'selected' : ''; ?>>Other</option>
        </select>
    </div>

    <div class="form-group">
        <label for="contact_name">Primary Contact Name *</label>
        <input type="text" name="contact_name" id="contact_name" 
               value="<?php echo htmlspecialchars($data['contact_name'] ?? ''); ?>" 
               placeholder="John Smith" required>
    </div>

    <div class="form-group">
        <label for="contact_email">Contact Email *</label>
        <input type="email" name="contact_email" id="contact_email" 
               value="<?php echo htmlspecialchars($data['contact_email'] ?? ''); ?>" 
               placeholder="john@acmemarine.com" required>
    </div>

    <div class="form-group">
        <label for="database_prefix">Database Prefix * <small>(2-4 letters that will identify your company's data)</small></label>
        <input type="text" name="database_prefix" id="database_prefix" 
               value="<?php echo htmlspecialchars($data['database_prefix'] ?? ''); ?>" 
               placeholder="CBL" maxlength="4" pattern="[A-Z]{2,4}" 
               style="text-transform: uppercase;" required>
        <small style="color: #666;">
            Examples: Canal Barge Line = "CBL", Marquette Towing = "MTC"<br>
            Your database will be named: <strong><span id="db_name_preview"><?php echo htmlspecialchars($data['database_prefix'] ?? 'XXX'); ?>_logicdoc</span></strong>
        </small>
        <?php if (!empty($suggested_prefixes)): ?>
            <div style="margin-top: 8px;">
                <small>Suggestions based on company name: 
                <?php foreach ($suggested_prefixes as $suggestion): ?>
                    <button type="button" onclick="document.getElementById('database_prefix').value='<?php echo $suggestion; ?>'; updateDbPreview();" 
                            style="background: #e3f2fd; border: 1px solid #2196f3; padding: 2px 6px; margin: 2px; border-radius: 3px; cursor: pointer;">
                        <?php echo $suggestion; ?>
                    </button>
                <?php endforeach; ?>
                </small>
            </div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="contact_phone">Phone Number</label>
        <input type="tel" name="contact_phone" id="contact_phone" 
               value="<?php echo htmlspecialchars($data['contact_phone'] ?? ''); ?>" 
               placeholder="(555) 123-4567">
    </div>

    <div class="form-group">
        <label for="address">Address</label>
        <input type="text" name="address" id="address" 
               value="<?php echo htmlspecialchars($data['address'] ?? ''); ?>" 
               placeholder="123 River Road">
    </div>

    <div style="display: flex; gap: 10px;">
        <div class="form-group" style="flex: 2;">
            <label for="city">City</label>
            <input type="text" name="city" id="city" 
                   value="<?php echo htmlspecialchars($data['city'] ?? ''); ?>" 
                   placeholder="New Orleans">
        </div>
        <div class="form-group" style="flex: 1;">
            <label for="state">State</label>
            <input type="text" name="state" id="state" 
                   value="<?php echo htmlspecialchars($data['state'] ?? ''); ?>" 
                   placeholder="LA" maxlength="2">
        </div>
        <div class="form-group" style="flex: 1;">
            <label for="zip">ZIP Code</label>
            <input type="text" name="zip" id="zip" 
                   value="<?php echo htmlspecialchars($data['zip'] ?? ''); ?>" 
                   placeholder="70112">
        </div>
    </div>

    <div style="margin-top: 20px;">
        <a href="?step=1" style="margin-right: 10px; text-decoration: none; color: #666;">← Back</a>
        <button type="submit" class="btn">Continue →</button>
    </div>
</form>

<script>
function updateDbPreview() {
    const prefix = document.getElementById('database_prefix').value.toUpperCase();
    const preview = document.getElementById('db_name_preview');
    preview.textContent = (prefix || 'XXX') + '_logicdoc';
}

document.getElementById('database_prefix').addEventListener('input', function() {
    this.value = this.value.toUpperCase().replace(/[^A-Z]/g, '');
    if (this.value.length > 4) {
        this.value = this.value.substring(0, 4);
    }
    updateDbPreview();
});

// Auto-suggest prefix when company name changes
document.getElementById('company_name').addEventListener('input', function() {
    const companyName = this.value.toUpperCase();
    const prefixField = document.getElementById('database_prefix');
    
    if (companyName && !prefixField.value) {
        const words = companyName.split(' ').filter(word => word.length > 0);
        let suggestion = '';
        
        // Try first letters of each word
        if (words.length >= 2) {
            for (let word of words) {
                suggestion += word.charAt(0);
                if (suggestion.length >= 3) break;
            }
        } else if (words.length === 1 && words[0].length >= 3) {
            suggestion = words[0].substring(0, 3);
        }
        
        if (suggestion.length >= 2) {
            prefixField.value = suggestion;
            updateDbPreview();
        }
    }
});
</script>
