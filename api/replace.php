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
    json_response(['success' => false, 'error' => 'Invalid website name.'], 400);
}
if (!is_dir(website_path($name))) {
    json_response(['success' => false, 'error' => "Website \"{$name}\" not found."], 404);
}

// Read existing meta so we can preserve title/description if not provided
$meta_file = website_path($name) . '/.hostsw_meta.json';
$old_meta  = file_exists($meta_file) ? (json_decode(file_get_contents($meta_file), true) ?? []) : [];
$title       = $title       ?: ($old_meta['title']       ?? $name);
$description = $description ?: ($old_meta['description'] ?? '');

deploy_zip($name, $title, $description, true);
