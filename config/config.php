<?php
// ============================================================
//  HostSW – Configuration
// ============================================================

// ── Admin Credentials ────────────────────────────────────────
// Change these before deploying.
// Password is stored as a bcrypt hash.
// To generate a new hash run: php -r "echo password_hash('yourpassword', PASSWORD_BCRYPT);"
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD_HASH', password_hash('admin123', PASSWORD_BCRYPT)); // default: admin123

// ── Base URL ─────────────────────────────────────────────────
// Set this to your actual domain. No trailing slash.
// Examples: 'http://localhost/HostSW'  |  'https://example.com'
define('BASE_URL', 'http://localhost/HostSW');

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
