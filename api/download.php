<?php
require_once __DIR__ . '/auth_check.php';
require_auth();

$name = strtolower(trim($_GET['name'] ?? ''));

if (!valid_name($name)) {
    http_response_code(400);
    exit('Invalid website name.');
}

$dir = website_path($name);
if (!is_dir($dir)) {
    http_response_code(404);
    exit('Website not found.');
}

$zip_name = $name . '.zip';
$tmp_zip  = TEMP_DIR . '/' . uniqid('dl_', true) . '.zip';

$zip = new ZipArchive();
if ($zip->open($tmp_zip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    exit('Could not create ZIP archive.');
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $item) {
    $relative = str_replace('\\', '/', $iterator->getSubPathname());
    if ($item->isDir()) {
        $zip->addEmptyDir($relative);
    } else {
        $zip->addFile($item->getRealPath(), $relative);
    }
}
$zip->close();

// Stream to browser
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zip_name . '"');
header('Content-Length: ' . filesize($tmp_zip));
header('Cache-Control: no-cache, no-store, must-revalidate');
readfile($tmp_zip);
unlink($tmp_zip);
exit;
