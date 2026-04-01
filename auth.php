<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/config/path.php';
}

function url($path) {
    $base = rtrim(BASE_PATH, '/');
    if ($base === '') {
        return ltrim($path, '/');
    }
    return $base . '/' . ltrim($path, '/');
}

function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . url('login.php'));
        exit;
    }
}

function requireRole($role) {
    requireAuth();
    $roles = is_array($role) ? $role : [$role];
    if (!in_array($_SESSION['user_role'] ?? '', $roles)) {
        header('Location: ' . url('dashboard.php'));
        exit;
    }
}
