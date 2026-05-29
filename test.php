<?php
// UniHub — Basic PHP environment probe.
// Upload this file to your htdocs/ root and visit /test.php
// DELETE this file once you have confirmed PHP is working.

echo "<!DOCTYPE html><html><head>
<meta charset='UTF-8'>
<title>UniHub — Server Test</title>
<style>
  body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;
       background:#0b0d19;color:#f3f4f6;margin:0;padding:2rem;
       display:flex;justify-content:center}
  .card{background:#111827;border:1px solid rgba(124,109,255,.2);border-radius:16px;
        padding:2.5rem;max-width:680px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.6)}
  h1{color:#7c6dff;margin:0 0 1.5rem}
  .ok{color:#34d399;font-weight:600}
  .fail{color:#f87171;font-weight:600}
  table{border-collapse:collapse;width:100%;font-size:.88rem}
  td{padding:.45rem .6rem;border-bottom:1px solid rgba(255,255,255,.06)}
  td:first-child{color:#9ca3af;width:44%}
  td:last-child{color:#f3f4f6;font-family:monospace}
  .section{margin:1.5rem 0 .5rem;color:#a78bfa;font-weight:700;font-size:1rem}
</style></head><body><div class='card'>
<h1>🔍 UniHub Server Probe</h1>";

// --- 1. PHP basics ---
echo "<div class='section'>PHP Environment</div><table>";
echo "<tr><td>PHP Version</td><td>" . phpversion() . "</td></tr>";
echo "<tr><td>Server Software</td><td>" . htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'unknown') . "</td></tr>";
echo "<tr><td>HTTP Host</td><td>" . htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'unknown') . "</td></tr>";
echo "<tr><td>Document Root</td><td>" . htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? 'unknown') . "</td></tr>";
echo "<tr><td>Script Path</td><td>" . htmlspecialchars($_SERVER['SCRIPT_FILENAME'] ?? 'unknown') . "</td></tr>";
echo "</table>";

// --- 2. File structure check ---
echo "<div class='section'>File Structure Check</div><table>";
$files_to_check = [
    'index.php','login.php','register.php','logout.php','dashboard.php','admin.php',
    'debug.php',
    'includes/auth.php','includes/db.php','includes/notif_helper.php',
    'modules/admin.php','modules/events.php','modules/marketplace.php',
    'modules/messages.php','modules/notes.php','modules/notifications.php',
    'modules/profile.php','modules/skills.php',
    'assets/css/style.css','assets/css/enhancements.css','assets/js/theme.js','assets/js/main.js',
];
foreach ($files_to_check as $f) {
    $exists = file_exists($f);
    echo "<tr><td>$f</td><td>" . ($exists ? "<span class='ok'>✅ found</span>" : "<span class='fail'>❌ MISSING</span>") . "</td></tr>";
}
echo "</table>";

// --- 3. PDO/MySQL check ---
echo "<div class='section'>PDO MySQL Extension</div><table>";
$pdo_ok = extension_loaded('pdo_mysql');
echo "<tr><td>pdo_mysql</td><td>" . ($pdo_ok ? "<span class='ok'>✅ loaded</span>" : "<span class='fail'>❌ NOT loaded</span>") . "</td></tr>";
echo "<tr><td>pdo_sqlite</td><td>" . (extension_loaded('pdo_sqlite') ? "<span class='ok'>✅ loaded</span>" : "<span class='fail'>❌ not loaded</span>") . "</td></tr>";
echo "</table>";

// --- 4. Quick DB connection test ---
echo "<div class='section'>Database Connection Test</div><table>";
if ($pdo_ok) {
    $db_host = 'sql211.infinityfree.com';
    $db_user = 'if0_41781638';
    $db_pass = 'AliAbu373';
    $db_name = 'if0_41781638_unihub';
    try {
        $dsn  = "mysql:host=$db_host;port=3306;dbname=$db_name;charset=utf8mb4;connect_timeout=5";
        $conn = new PDO($dsn, $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $users = (int)$conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
        echo "<tr><td>Connection to $db_name</td><td><span class='ok'>✅ SUCCESS — $users users</span></td></tr>";
    } catch (PDOException $e) {
        echo "<tr><td>Connection to $db_name</td><td><span class='fail'>❌ " . htmlspecialchars($e->getMessage()) . "</span></td></tr>";
        // Try fallback db name
        try {
            $dsn2  = "mysql:host=$db_host;port=3306;dbname=if0_41781638_unipulse;charset=utf8mb4;connect_timeout=5";
            $conn2 = new PDO($dsn2, $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            echo "<tr><td>Fallback: if0_41781638_unipulse</td><td><span class='ok'>✅ Connected!</span></td></tr>";
        } catch (PDOException $e2) {
            echo "<tr><td>Fallback DB name</td><td><span class='fail'>❌ " . htmlspecialchars($e2->getMessage()) . "</span></td></tr>";
        }
    }
} else {
    echo "<tr><td>PDO MySQL</td><td><span class='fail'>❌ Extension not available — cannot connect</span></td></tr>";
}
echo "</table>";

// --- 5. ini_set test ---
echo "<div class='section'>ini_set Permission Check</div><table>";
$r = @ini_set('session.cookie_httponly', 1);
echo "<tr><td>ini_set session.cookie_httponly</td><td>" . ($r !== false ? "<span class='ok'>✅ allowed</span>" : "<span class='fail'>❌ blocked by host</span>") . "</td></tr>";

echo "</table>";
echo "<p style='margin-top:2rem;color:#6b7280;font-size:.8rem'>⚠️ Delete test.php from your server after diagnosis.</p>";
echo "</div></body></html>";
