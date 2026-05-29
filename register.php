<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

if (is_logged_in()) {
    header('Location: /unihub/dashboard.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name        = trim($_POST['full_name'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$full_name || !$email || !$password || !$confirm_password) {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            // Automatically make the first registered user an admin
            $user_count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
            $role = ($user_count === 0) ? 'admin' : 'user';

            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt   = $pdo->prepare('INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)');
            $stmt->execute([$full_name, $email, $hashed, $role]);

            $new_user_id = $pdo->lastInsertId();
            $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
            $stmt->execute([$new_user_id]);
            $user = $stmt->fetch();

            if ($user) {
                login_user($user);
                header('Location: /unihub/dashboard.php');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — UniHub</title>
    <meta name="description" content="Create your UniHub account and join the campus portal.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/unihub/assets/css/style.css?v=6">
    <link rel="stylesheet" href="/unihub/assets/css/enhancements.css?v=6">
    <link rel="stylesheet" href="/unihub/assets/css/tailwind.css?v=6">
    <script src="/unihub/assets/js/theme.js?v=6"></script>
</head>
<body>

<div class="auth-wrapper">
    <div class="theme-switcher auth-theme-switcher">
        <button class="theme-btn active" data-theme="dark" title="Dark Theme">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" /></svg>
        </button>
        <button class="theme-btn" data-theme="light" title="Light Theme">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m0 13.5V21M4.22 4.22l1.58 1.58m12.4 12.4 1.58 1.58M3 12h2.25m13.5 0H21M6.78 17.22l-1.58 1.58m12.4-12.4 1.58-1.58M12 7.5a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9Z" /></svg>
        </button>
        <button class="theme-btn" data-theme="blue" title="Blue Theme">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-1.897v-1.476ZM21.75 12.75a4.5 4.5 0 0 1-4.75 4.5h-.75a.75.75 0 0 0-.75.75c0 .414-.336.75-.75.75H13.5a.75.75 0 0 1-.75-.75v-1.476M21.75 12.75A9 9 0 0 0 12 3v1.5a.75.75 0 0 1-.75.75h-.75a.75.75 0 0 0-.75.75v1.5a.75.75 0 0 1-.75.75H7.5A.75.75 0 0 0 6.75 9v1.5a.75.75 0 0 1-.75.75H4.5A2.25 2.25 0 0 0 2.25 13.5v.75m19.5-1.5a9 9 0 0 1-2.25 5.86" /></svg>
        </button>
        <div class="theme-indicator"></div>
    </div>
    <div class="auth-card">

        <div class="auth-logo">
            <div class="logo-icon">U</div>
            <span>UniHub</span>
        </div>

        <h1>Create your account</h1>
        <p class="subtitle">Join thousands of students on UniHub.</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="full_name">Full name</label>
                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    placeholder="John Smith"
                    value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                    required
                    autocomplete="name"
                >
            </div>

            <div class="form-group">
                <label for="email">Email address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="you@university.edu"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required
                    autocomplete="email"
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Min. 6 characters"
                    required
                    autocomplete="new-password"
                >
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm password</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="••••••••"
                    required
                    autocomplete="new-password"
                >
            </div>

            <button type="submit" class="btn btn-primary" id="register-submit-btn">Get Started</button>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="/unihub/login.php">Sign in →</a>
        </div>

    </div>
</div>

<script src="/unihub/assets/js/main.js"></script>
</body>
</html>
