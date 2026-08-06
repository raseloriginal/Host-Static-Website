<?php
// ── Shared session bootstrap & authentication guard ──────────
// Include this at the top of every protected API endpoint.

require_once dirname(__DIR__) . '/config/config.php';

ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

session_name(SESSION_NAME);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Abort with a JSON error if the user is not authenticated.
 */
function require_auth(): void {
    if (empty($_SESSION['authenticated'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Unauthenticated']);
        exit;
    }
}

/**
 * Send a JSON response and exit.
 */
function json_response(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Recursively delete a directory.
 */
function rrmdir(string $dir): bool {
    if (!is_dir($dir)) return false;
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getRealPath()) : unlink($item->getRealPath());
    }
    return rmdir($dir);
}

/**
 * Get the total size of a directory in bytes.
 */
function dir_size(string $dir): int {
    $bytes = 0;
    if (!is_dir($dir)) return 0;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->isFile()) $bytes += $file->getSize();
    }
    return $bytes;
}

/**
 * Format bytes into human-readable string.
 */
function format_bytes(int $bytes): string {
    if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576)    return round($bytes / 1048576, 2)    . ' MB';
    if ($bytes >= 1024)       return round($bytes / 1024, 2)       . ' KB';
    return $bytes . ' B';
}

/**
 * Validate a website name slug.
 */
function valid_name(string $name): bool {
    return (bool) preg_match(NAME_REGEX, $name);
}

/**
 * Get the full filesystem path for a website folder.
 */
function website_path(string $name): string {
    return WEBSITES_DIR . '/' . $name;
}

/**
 * Get the public URL for a website.
 */
function website_url(string $name): string {
    return BASE_URL . '/websites/' . $name . '/';
}

// Ensure temp & websites directories exist
foreach ([WEBSITES_DIR, TEMP_DIR] as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0755, true);
}
