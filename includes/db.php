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
    
    try {
        $pdo = new PDO(
            "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4",
            $db_user,
            $db_pass,
            [
                PDO::ATTR_TIMEOUT => 3,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    } catch (PDOException $e) {
        die('Database connection failed (Local): ' . $e->getMessage());
    }
} else {
    // Production MySQL configuration (InfinityFree)
    $db_host = 'sql211.infinityfree.com';
    $db_port = '3306';
    $db_user = 'if0_41781638';
    
    // We will dynamically try both common passwords and databases to ensure connection succeeds
    $databases = ['if0_41781638_unihub', 'if0_41781638_unipulse'];
    $passwords = ['AliAbu373', 'Ali_Abu373'];
    
    $connected = false;
    $last_error = '';
    
    foreach ($passwords as $pass) {
        foreach ($databases as $db) {
            try {
                $pdo = new PDO(
                    "mysql:host=$db_host;port=$db_port;dbname=$db;charset=utf8mb4",
                    $db_user,
                    $pass,
                    [
                        PDO::ATTR_TIMEOUT => 2, // Set a short 2s timeout per connection attempt to avoid execution timeout crash
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]
                );
                $connected = true;
                break 2; // Break both loops if connection succeeded
            } catch (PDOException $e) {
                $last_error = $e->getMessage();
            }
        }
    }
    
    if (!$connected) {
        // Output a clean error container instead of raw execution limit abort (empty response)
        header('HTTP/1.1 500 Internal Server Error');
        echo "<div style='font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif; padding: 2.5rem; background: #0b0d19; color: #f3f4f6; border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 12px; max-width: 600px; margin: 4rem auto; box-shadow: 0 10px 30px rgba(0,0,0,0.5); text-align: center;'>";
        echo "<div style='font-size: 3rem; margin-bottom: 1rem;'>⚠️</div>";
        echo "<h3 style='margin-top: 0; color: #ef4444; font-size: 1.5rem;'>Database Connection Failed</h3>";
        echo "<p style='color: #9ca3af; font-size: 0.95rem; line-height: 1.6;'>UniHub could not connect to your live MySQL database on InfinityFree.</p>";
        echo "<div style='background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.15); padding: 1rem; border-radius: 8px; text-align: left; margin: 1.5rem 0; font-family: monospace; font-size: 0.85rem; color: #f87171; overflow-x: auto;'>";
        echo "<b>Error:</b> " . htmlspecialchars($last_error);
        echo "</div>";
        echo "<p style='color: #9ca3af; font-size: 0.95rem; line-height: 1.6;'>Please verify that your database host, username, password, and database names are correct in <code>includes/db.php</code>.</p>";
        echo "</div>";
        exit;
    }
}

