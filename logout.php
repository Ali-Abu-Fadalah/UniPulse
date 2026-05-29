<?php
$inc = is_dir('Includes') ? 'Includes' : 'includes';
require_once $inc . '/auth.php';

logout_user();
