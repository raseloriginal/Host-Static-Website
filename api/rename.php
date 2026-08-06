<?php
require_once __DIR__ . '/auth_check.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method not allowed'], 405);
}

$old_name = strtolower(trim($_POST['old_name'] ?? ''));
$new_name = strtolower(trim($_POST['new_name'] ?? ''));

if (!valid_name($old_name) || !valid_name($new_name)) {
    json_response(['success' => false, 'error' => 'Invalid website name.'], 400);
}
if (!is_dir(website_path($old_name))) {
    json_response(['success' => false, 'error' => "Website \"{$old_name}\" not found."], 404);
}
if ($old_name === $new_name) {
    json_response(['success' => false, 'error' => 'New name is the same as the current name.'], 400);
}
if (is_dir(website_path($new_name))) {
    json_response(['success' => false, 'error' => "A website named \"{$new_name}\" already exists."], 409);
}

if (!rename(website_path($old_name), website_path($new_name))) {
    json_response(['success' => false, 'error' => 'Failed to rename website directory.'], 500);
}

json_response([
    'success'  => true,
    'message'  => "Renamed \"{$old_name}\" to \"{$new_name}\" successfully.",
    'new_name' => $new_name,
    'new_url'  => website_url($new_name),
]);
