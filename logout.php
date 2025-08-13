<?php
require_once __DIR__ . '/auth_functions.php';

logout_user();
header('Location: login.php?message=logged_out');
exit;
?>
