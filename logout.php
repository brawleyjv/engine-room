<?php
require_once 'auth_functions.php';

logout_user();
header('Location: login.php?message=logged_out');
exit;
?>
