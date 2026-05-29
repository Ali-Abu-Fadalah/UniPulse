<?php

ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    $fingerprint = md5($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (!isset($_SESSION['fingerprint']) || $_SESSION['fingerprint'] !== $fingerprint) {
        logout_user();
    }

    return true;
}

function verify_session() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    $fingerprint = md5($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (!isset($_SESSION['fingerprint']) || $_SESSION['fingerprint'] !== $fingerprint) {
        return false;
    }

    return true;
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function is_admin() {
    return is_logged_in() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function require_admin() {
    if (!is_admin()) {
        header('Location: dashboard.php');
        exit;
    }
}

function login_user($user) {
    session_regenerate_id(true);

    $_SESSION['user_id']     = $user['id'];
    $_SESSION['full_name']   = $user['full_name'];
    $_SESSION['email']       = $user['email'];
    $_SESSION['role']        = $user['role'] ?? 'user';
    $_SESSION['avatar']      = $user['avatar'] ?? '';
    $_SESSION['fingerprint'] = md5($_SERVER['HTTP_USER_AGENT'] ?? '');
}

function logout_user() {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}
