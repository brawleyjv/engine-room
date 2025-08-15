<?php
// Clear all session data
session_start();
session_destroy();
session_unset();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Clear any other related cookies
$cookies_to_clear = ['PHPSESSID', 'company_domain', 'user_id'];
foreach ($cookies_to_clear as $cookie) {
    if (isset($_COOKIE[$cookie])) {
        setcookie($cookie, '', time()-3600, '/');
    }
}

echo "<h2>Session Cleared</h2>";
echo "<p>All session data and cookies have been cleared.</p>";
echo "<p><a href='index.php'>Go to Landing Page</a></p>";
echo "<p><a href='signup.php'>Go to Signup</a></p>";
?>
