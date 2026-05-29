<?php
// Determine environment (Local vs Production)
$is_local = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', '[::1]'])
            || (isset($_SERVER['SERVER_ADDR']) && in_array($_SERVER['SERVER_ADDR'], ['127.0.0.1', '::1']))
            || (php_sapi_name() === 'cli');

if ($is_local) {
    // ── LOCAL (XAMPP) ────────────────────────────────────────────
    try {
        $pdo = new PDO(
            'mysql:host=127.0.0.1;port=3306;dbname=unihub;charset=utf8mb4',
            'root',
            'Ali_Abu373',
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    } catch (PDOException $e) {
        die('Local database connection failed: ' . $e->getMessage());
    }

} else {
    // ── PRODUCTION (InfinityFree) ─────────────────────────────────
    // NOTE: PDO::ATTR_TIMEOUT is NOT a valid MySQL PDO constructor option
    // and will throw a fatal exception on some PHP versions.
    // The correct way to limit MySQL connect time is via the DSN `connect_timeout`.

    $db_host = 'sql211.infinityfree.com';
    $db_port = '3306';
    $db_user = 'if0_41781638';

    // Try every database-name / password combination.
    // connect_timeout=3 in the DSN string is the correct MySQL-compatible timeout.
    $databases = ['if0_41781638_unihub', 'if0_41781638_unipulse'];
    $passwords  = ['AliAbu373', 'Ali_Abu373'];

    $pdo        = null;
    $last_error = 'No connection attempt was made.';

    foreach ($passwords as $pass) {
        foreach ($databases as $db) {
            try {
                $dsn = "mysql:host=$db_host;port=$db_port;dbname=$db;charset=utf8mb4;connect_timeout=3";
                $pdo = new PDO(
                    $dsn,
                    $db_user,
                    $pass,
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                );
                $last_error = '';
                break 2; // Success — exit both loops
            } catch (PDOException $e) {
                $pdo        = null;
                $last_error = $e->getMessage();
            }
        }
    }

    if ($pdo === null) {
        // Show a readable error page instead of a silent/empty crash
        http_response_code(500);
        echo "<!DOCTYPE html><html lang='en'><head>
              <meta charset='UTF-8'>
              <meta name='viewport' content='width=device-width,initial-scale=1'>
              <title>UniHub — Database Error</title>
              <style>
                body{margin:0;display:flex;align-items:center;justify-content:center;
                     min-height:100vh;background:#0b0d19;font-family:-apple-system,
                     BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#f3f4f6}
                .card{background:#111827;border:1px solid rgba(239,68,68,.25);border-radius:16px;
                      padding:3rem 2.5rem;max-width:580px;width:90%;text-align:center;
                      box-shadow:0 20px 60px rgba(0,0,0,.6)}
                .icon{font-size:3rem;margin-bottom:1rem}
                h1{color:#ef4444;font-size:1.6rem;margin:0 0 .75rem}
                p{color:#9ca3af;font-size:.95rem;line-height:1.7;margin:.5rem 0}
                .err{background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);
                     border-radius:8px;padding:1rem;text-align:left;font-family:monospace;
                     font-size:.82rem;color:#f87171;margin:1.25rem 0;overflow-x:auto;
                     white-space:pre-wrap;word-break:break-all}
                code{background:rgba(255,255,255,.08);padding:.1rem .35rem;border-radius:4px}
              </style></head><body>
              <div class='card'>
                <div class='icon'>⚠️</div>
                <h1>Database Connection Failed</h1>
                <p>UniHub could not connect to the MySQL database on InfinityFree.</p>
                <div class='err'>" . htmlspecialchars($last_error) . "</div>
                <p>Please open your InfinityFree control panel, verify the database host, name,
                   username and password, then update <code>includes/db.php</code>.</p>
              </div></body></html>";
        exit;
    }
}
