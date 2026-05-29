<?php
$inc = is_dir('Includes') ? 'Includes' : 'includes';
require_once $inc . '/auth.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

header('Location: login.php');
exit;
