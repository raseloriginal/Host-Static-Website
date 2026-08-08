<?php
// ============================================================
//  HostSW – Configuration
// ============================================================

// ── Admin Credentials ────────────────────────────────────────
// Change these before deploying.
// To generate a new hash: php -r "echo password_hash('yourpassword', PASSWORD_BCRYPT);"
define('ADMIN_USERNAME', 'admin');
// Default password: admin123  (pre-computed bcrypt hash — change this!)
define('ADMIN_PASSWORD_HASH', '$2y$10$HFHVOE0xUisoaLhR7lLynucSTW.H/7AIpIAvccQw3kXJIt.KQk8yO');

// ── Base URL (auto-detected) ────────────────────────────────
// Dynamically built from the current HTTP request.
// Works on localhost, example.com, or any domain/subdirectory — no manual config needed.
(function () {
    // Protocol: http or https
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        ? 'https' : 'http';

    // Host (e.g. localhost, example.com, example.com:8080)
    $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');

    // Sub-directory path (e.g. /HostSW when installed in a sub-folder)
    // Compare the real filesystem path against DOCUMENT_ROOT to find the sub-path.
    $doc_root  = rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
    $app_root  = rtrim(str_replace('\\', '/', realpath(dirname(__DIR__))), '/');
    $sub_path  = ($doc_root !== '' && str_starts_with($app_root, $doc_root))
        ? substr($app_root, strlen($doc_root))
        : '';
    $sub_path  = rtrim($sub_path, '/');

    define('BASE_URL', $scheme . '://' . $host . $sub_path);
})();

// ── Paths ────────────────────────────────────────────────────
define('ROOT_DIR',     dirname(__DIR__));
define('WEBSITES_DIR', ROOT_DIR . '/websites');
define('TEMP_DIR',     ROOT_DIR . '/temp');

// ── Upload Limits ────────────────────────────────────────────
define('MAX_UPLOAD_BYTES', 500 * 1024 * 1024); // 500 MB

// ── Allowed Static File Extensions ──────────────────────────
// Only these extensions are permitted inside uploaded ZIPs.
define('ALLOWED_EXTENSIONS', [
    // Web
    'html', 'htm', 'xhtml',
    'css',
    'js', 'mjs',
    'json', 'jsonld',
    'xml',
    'txt', 'md',
    // Images
    'png', 'jpg', 'jpeg', 'gif', 'webp', 'avif', 'ico', 'bmp', 'tiff', 'tif',
    // Vector / Fonts
    'svg',
    'woff', 'woff2', 'ttf', 'eot', 'otf',
    // Audio / Video
    'mp4', 'webm', 'ogv', 'avi', 'mov',
    'mp3', 'ogg', 'wav', 'flac', 'aac',
    // Documents / Data
    'pdf',
    'csv',
    // Other common static assets
    'map',         // source maps
    'webmanifest', // PWA manifests
    'htaccess',    // Apache overrides (safe static ones)
]);

// ── Session ──────────────────────────────────────────────────
define('SESSION_NAME', 'HOSTSW_SESSION');

// ── Website Name Validation ──────────────────────────────────
define('NAME_REGEX',    '/^[a-z0-9][a-z0-9\-]{0,49}$/');
define('NAME_MAX_LEN',  50);
