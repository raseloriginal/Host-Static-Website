<?php
require_once __DIR__ . '/auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method not allowed'], 405);
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === ADMIN_USERNAME && password_verify($password, ADMIN_PASSWORD_HASH)) {
    session_regenerate_id(true);
    $_SESSION['authenticated'] = true;
    $_SESSION['user']          = $username;
    json_response(['success' => true, 'redirect' => BASE_URL . '/dashboard.php']);
} else {
    json_response(['success' => false, 'error' => 'Invalid username or password'], 401);
}
