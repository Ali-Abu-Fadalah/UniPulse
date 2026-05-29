<?php
/**
 * UniHub Admin CLI Reset Tool
 * 
 * This script allows you to reset any user's password or promote them to admin.
 * For security reasons, this script can ONLY be executed via the command line interface (CLI).
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Access Denied: This utility can only be run via the Command Line (CLI).\n");
}

require_once __DIR__ . '/includes/db.php';

echo "==========================================\n";
echo "       UniHub Admin CLI Reset Tool        \n";
echo "==========================================\n\n";

// List all users to make it easy
try {
    $stmt = $pdo->query("SELECT id, full_name, email, role FROM users");
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "No users found in the database. Please register an account first via the website.\n";
        exit(0);
    }
    
    echo "Existing Users:\n";
    foreach ($users as $u) {
        echo "  [ID: {$u['id']}] {$u['full_name']} ({$u['email']}) - Role: " . strtoupper($u['role']) . "\n";
    }
    echo "\n";
} catch (Exception $e) {
    die("Error querying database: " . $e->getMessage() . "\n");
}

// Ask for email of target user
echo "Enter the email of the account to reset: ";
$email = trim(fgets(STDIN));

$stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    echo "Error: No user found with the email '{$email}'.\n";
    exit(1);
}

echo "Found user: {$user['full_name']}\n\n";

echo "What would you like to do?\n";
echo "  1) Reset password\n";
echo "  2) Promote to Admin\n";
echo "  3) Both (Reset password & Promote to Admin)\n";
echo "Enter choice (1-3): ";
$choice = trim(fgets(STDIN));

if (!in_array($choice, ['1', '2', '3'])) {
    echo "Invalid choice. Exiting.\n";
    exit(1);
}

$new_role = null;
$new_hash = null;
$plain_pass = '';

if ($choice === '1' || $choice === '3') {
    echo "Enter new password (min 6 characters): ";
    $plain_pass = trim(fgets(STDIN));
    
    if (strlen($plain_pass) < 6) {
        echo "Error: Password must be at least 6 characters long.\n";
        exit(1);
    }
    $new_hash = password_hash($plain_pass, PASSWORD_DEFAULT);
}

if ($choice === '2' || $choice === '3') {
    $new_role = 'admin';
}

try {
    if ($new_hash && $new_role) {
        $stmt = $pdo->prepare("UPDATE users SET password = ?, role = ? WHERE email = ?");
        $stmt->execute([$new_hash, $new_role, $email]);
        echo "\nSuccess: Password has been reset and role has been upgraded to ADMIN.\n";
    } elseif ($new_hash) {
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$new_hash, $email]);
        echo "\nSuccess: Password has been successfully reset.\n";
    } elseif ($new_role) {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE email = ?");
        $stmt->execute([$new_role, $email]);
        echo "\nSuccess: User role upgraded to ADMIN.\n";
    }
} catch (Exception $e) {
    echo "Error updating database: " . $e->getMessage() . "\n";
    exit(1);
}
