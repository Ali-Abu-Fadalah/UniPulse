<?php
require_once 'includes/auth.php';

if (is_logged_in()) {
    header('Location: /unihub/dashboard.php');
    exit;
}

header('Location: /unihub/login.php');
exit;
