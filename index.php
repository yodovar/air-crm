<?php
require_once __DIR__ . '/auth.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . url('login.php'));
    exit;
}

header('Location: ' . url('dashboard.php'));
exit;
