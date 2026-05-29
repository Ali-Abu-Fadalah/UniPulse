<?php
// Determine environment (Local vs Production)
$is_local = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', '[::1]']) 
            || (isset($_SERVER['SERVER_ADDR']) && in_array($_SERVER['SERVER_ADDR'], ['127.0.0.1', '::1']))
            || (php_sapi_name() === 'cli');

if ($is_local) {
    // Local MySQL configuration (XAMPP / local dev)
    $db_host = '127.0.0.1';
    $db_port = '3306';
    $db_name = 'unihub';
    $db_user = 'root';
    $db_pass = 'Ali_Abu373';
} else {
    // Production MySQL configuration (InfinityFree)
    $db_host = 'sql211.infinityfree.com';
    $db_port = '3306';
    $db_name = 'if0_41781638_unihub'; // Primary database guess
    $db_user = 'if0_41781638';
    $db_pass = 'AliAbu373';
}

try {
    // Attempt PDO connection
    $pdo = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Fallback database name check for production
    if (!$is_local) {
        try {
            $db_name = 'if0_41781638_unipulse';
            $pdo = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e2) {
            die('Database connection failed: ' . $e2->getMessage());
        }
    } else {
        die('Database connection failed: ' . $e->getMessage());
    }
}

