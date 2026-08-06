<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/deploy_helper.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method not allowed'], 405);
}

$name        = strtolower(trim($_POST['name']        ?? ''));
$title       = trim($_POST['title']       ?? '');
$description = trim($_POST['description'] ?? '');

if ($name === '') {
    json_response(['success' => false, 'error' => 'Website name is required.'], 400);
}
if (!valid_name($name)) {
    json_response(['success' => false, 'error' => 'Invalid name. Use only lowercase letters, numbers, and hyphens (max 50 chars).'], 400);
}
if (is_dir(website_path($name))) {
    json_response(['success' => false, 'error' => "A website named \"{$name}\" already exists."], 409);
}

deploy_zip($name, $title, $description, false);
