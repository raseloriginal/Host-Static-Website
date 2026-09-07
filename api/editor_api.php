<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/deploy_helper.php';
require_auth();

// Function to get a flat list of files with relative paths
function get_flat_site_files($baseDir, $dir = null) {
    if ($dir === null) {
        $dir = $baseDir;
    }
    $result = [];
    $cdir = scandir($dir);
    foreach ($cdir as $value) {
        if (!in_array($value, [".", "..", ".hostsw_meta.json"])) {
            $path = $dir . DIRECTORY_SEPARATOR . $value;
            $relativePath = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $path);
            // Convert backslashes to forward slashes for cross-platform consistency
            $relativePath = str_replace('\\', '/', $relativePath);
            
            if (is_dir($path)) {
                $result = array_merge($result, get_flat_site_files($baseDir, $path));
            } else {
                $result[] = $relativePath;
            }
        }
    }
    return $result;
}

$action = $_REQUEST['action'] ?? '';
$site = strtolower(trim($_REQUEST['site'] ?? ''));

if ($site === '' || !valid_name($site)) {
    json_response(['success' => false, 'error' => 'Invalid or missing website name.'], 400);
}

$site_path = website_path($site);
if (!is_dir($site_path)) {
    json_response(['success' => false, 'error' => 'Website not found.'], 404);
}

if ($action === 'list') {
    $files = get_flat_site_files($site_path);
    json_response(['success' => true, 'files' => $files]);
} elseif ($action === 'read') {
    $filepath = $_GET['file'] ?? '';
    // Sanitize path to prevent traversal
    $clean_path = sanitize_zip_path($filepath);
    if ($clean_path === null || str_contains($filepath, '..')) {
         json_response(['success' => false, 'error' => 'Invalid file path.'], 400);
    }
    
    $full_path = $site_path . '/' . ltrim($clean_path, '/');
    if (!file_exists($full_path) || is_dir($full_path)) {
        json_response(['success' => false, 'error' => 'File not found.'], 404);
    }
    
    $content = file_get_contents($full_path);
    json_response(['success' => true, 'content' => $content]);
} elseif ($action === 'save') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['success' => false, 'error' => 'Method not allowed'], 405);
    }
    $filepath = $_POST['file'] ?? '';
    $content = $_POST['content'] ?? '';
    
    $clean_path = sanitize_zip_path($filepath);
    if ($clean_path === null || str_contains($filepath, '..')) {
         json_response(['success' => false, 'error' => 'Invalid file path.'], 400);
    }
    
    $full_path = $site_path . '/' . ltrim($clean_path, '/');
    if (!file_exists($full_path) || is_dir($full_path)) {
        json_response(['success' => false, 'error' => 'File not found.'], 404);
    }
    
    if (file_put_contents($full_path, $content) === false) {
        json_response(['success' => false, 'error' => 'Failed to save file.'], 500);
    }
    
    // Update site size metadata
    $meta_file = $site_path . '/.hostsw_meta.json';
    if (file_exists($meta_file)) {
        $meta = json_decode(file_get_contents($meta_file), true);
        if ($meta) {
            $meta['size_bytes'] = dir_size($site_path);
            file_put_contents($meta_file, json_encode($meta, JSON_PRETTY_PRINT));
        }
    }
    
    json_response(['success' => true, 'message' => 'File saved successfully.']);
} else {
    json_response(['success' => false, 'error' => 'Invalid action.'], 400);
}
