<?php
require_once __DIR__ . '/auth_check.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method not allowed'], 405);
}

$name = strtolower(trim($_POST['name'] ?? ''));

if (!valid_name($name)) {
    json_response(['success' => false, 'error' => 'Invalid website name.'], 400);
}
if (!is_dir(website_path($name))) {
    json_response(['success' => false, 'error' => "Website \"{$name}\" not found."], 404);
}

if (!rrmdir(website_path($name))) {
    json_response(['success' => false, 'error' => 'Failed to delete website directory.'], 500);
}

json_response(['success' => true, 'message' => "Website \"{$name}\" deleted successfully."]);
