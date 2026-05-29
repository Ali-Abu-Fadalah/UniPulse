<?php
// Enable full error reporting to bypass standard server 500 screens
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<div style='font-family: monospace; background: #121214; color: #e2e8f0; padding: 2rem; border-radius: 8px; max-width: 800px; margin: 2rem auto; box-shadow: 0 4px 20px rgba(0,0,0,0.3);'>";
echo "<h1 style='color: #7c6dff; border-bottom: 1px solid #2e303e; padding-bottom: 0.5rem; margin-top: 0;'>🔍 UniHub Environment Diagnostics</h1>";

// 1. Check Directory Layout
echo "<h3 style='color: #a78bfa; margin-bottom: 0.5rem;'>1. Directory Check</h3>";
echo "Current directory: <b>" . htmlspecialchars(getcwd()) . "</b><br>";
echo "Available files and folders in root:<br>";
echo "<pre style='background: #1a1b26; padding: 1rem; border-radius: 4px; border: 1px solid #2e303e;'>";
print_r(scandir('.'));
echo "</pre>";

// 2. Check Includes Folder
echo "<h3 style='color: #a78bfa; margin-bottom: 0.5rem;'>2. Includes Casing Check</h3>";
$inc = is_dir('Includes') ? 'Includes' : 'includes';
echo "System detected folder naming: <b>" . $inc . "</b><br>";

// 3. Test auth.php loading
echo "<h3 style='color: #a78bfa; margin-bottom: 0.5rem;'>3. Testing auth.php</h3>";
if (file_exists($inc . '/auth.php')) {
    echo "✅ auth.php found. Attempting load...<br>";
    require_once $inc . '/auth.php';
    echo "🚀 auth.php loaded successfully!<br>";
} else {
    echo "❌ <b>ERROR</b>: auth.php was NOT found inside folder <b>$inc/</b>. Please check your uploads.<br>";
}

// 4. Test db.php and database connection
echo "<h3 style='color: #a78bfa; margin-bottom: 0.5rem;'>4. Testing db.php & Database Connection</h3>";
if (file_exists($inc . '/db.php')) {
    echo "✅ db.php found. Attempting load...<br>";
    require_once $inc . '/db.php';
    echo "🚀 db.php loaded successfully!<br>";
    
    if (isset($pdo)) {
        echo "✅ Database connection variable (\$pdo) is set!<br>";
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM users");
            $count = $stmt->fetchColumn();
            echo "🎉 <b>SUCCESS</b>: Database connection and query succeeded! Total users found: <b>" . $count . "</b><br>";
        } catch (Exception $e) {
            echo "❌ <b>QUERY ERROR</b>: Connected to MySQL, but query failed: " . htmlspecialchars($e->getMessage()) . "<br>";
        }
    } else {
        echo "❌ <b>ERROR</b>: The database connection variable \$pdo was not set inside db.php.<br>";
    }
} else {
    echo "❌ <b>ERROR</b>: db.php was NOT found inside folder <b>$inc/</b>. Please check your uploads.<br>";
}

echo "<div style='margin-top: 2rem; font-size: 0.85rem; color: #64748b; border-top: 1px solid #2e303e; padding-top: 1rem;'>UniHub Diagnostics Tool</div>";
echo "</div>";
