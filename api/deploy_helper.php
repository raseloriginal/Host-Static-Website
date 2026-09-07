<?php
// ── Shared ZIP deployment logic ──────────────────────────────
// Included by upload.php and replace.php

/**
 * Sanitize a path from inside a ZIP to prevent directory traversal.
 * Returns null if the path should be skipped.
 */
function sanitize_zip_path(string $entry_name): ?string {
    // Normalize separators
    $path = str_replace('\\', '/', $entry_name);

    // Reject paths with traversal sequences
    if (strpos($path, '..') !== false) return null;

    // Reject absolute paths
    if (str_starts_with($path, '/')) return null;

    // Reject null bytes
    if (strpos($path, "\0") !== false) return null;

    // Reject Windows device paths
    if (preg_match('/^(con|prn|aux|nul|com[0-9]|lpt[0-9])(\..*)?$/i', basename($path))) return null;

    return $path;
}

/**
 * Validate, extract and deploy a ZIP file.
 * Sends a JSON response and exits.
 */
function deploy_zip(string $name, string $title, string $description, bool $replace = false): void {
    if (!isset($_FILES['zipfile']) || $_FILES['zipfile']['error'] !== UPLOAD_ERR_OK) {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds form upload limit.',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary upload folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'Upload blocked by server extension.',
        ];
        $code  = $_FILES['zipfile']['error'] ?? UPLOAD_ERR_NO_FILE;
        $msg   = $upload_errors[$code] ?? 'Unknown upload error.';
        json_response(['success' => false, 'error' => $msg], 400);
    }

    $tmp_file = $_FILES['zipfile']['tmp_name'];
    $size     = $_FILES['zipfile']['size'];

    // Size limit
    if ($size > MAX_UPLOAD_BYTES) {
        json_response(['success' => false, 'error' => 'File size exceeds the ' . format_bytes(MAX_UPLOAD_BYTES) . ' limit.'], 400);
    }

    // Must be a ZIP (MIME + magic bytes)
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mime     = $finfo->file($tmp_file);
    $zip_mimes = ['application/zip', 'application/x-zip', 'application/x-zip-compressed', 'application/octet-stream'];
    $orig_name = strtolower($_FILES['zipfile']['name']);

    if (!in_array($mime, $zip_mimes) && !str_ends_with($orig_name, '.zip')) {
        json_response(['success' => false, 'error' => 'Only ZIP files are accepted.'], 400);
    }

    // Open ZIP
    $zip = new ZipArchive();
    $res = $zip->open($tmp_file);
    if ($res !== true) {
        json_response(['success' => false, 'error' => 'The uploaded file is not a valid ZIP archive.'], 400);
    }

    $allowed = ALLOWED_EXTENSIONS;
    $blocked = [];
    $has_index = false;
    $entries   = [];

    // ── Scan ZIP contents ─────────────────────────────────────
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $entry = $zip->getNameIndex($i);
        $clean = sanitize_zip_path($entry);

        if ($clean === null) {
            $zip->close();
            json_response(['success' => false, 'error' => "Rejected: path traversal attempt detected in \"{$entry}\"."], 400);
        }

        if (str_ends_with($clean, '/')) continue; // directory entry

        $ext = strtolower(pathinfo($clean, PATHINFO_EXTENSION));

        if ($ext !== '' && !in_array($ext, $allowed)) {
            $blocked[] = $clean;
            continue; // collect all blocked files, report at end
        }

        // Check for index.html at any top-level position
        // (handle ZIPs with a single root folder)
        $parts = explode('/', $clean);
        if (count($parts) === 1 && strtolower($parts[0]) === 'index.html') $has_index = true;
        if (count($parts) === 2 && strtolower($parts[1]) === 'index.html') $has_index = true; // single subfolder ZIP

        $entries[] = ['zip_name' => $entry, 'clean_name' => $clean];
    }

    if (!empty($blocked)) {
        $zip->close();
        $list = implode(', ', array_slice($blocked, 0, 5));
        $more = count($blocked) > 5 ? ' … and ' . (count($blocked) - 5) . ' more.' : '';
        json_response(['success' => false, 'error' => "Unsupported file types detected: {$list}{$more} Only static files are allowed."], 400);
    }

    if (!$has_index) {
        $zip->close();
        json_response(['success' => false, 'error' => 'No index.html found at the website root. Please ensure your ZIP contains index.html.'], 400);
    }

    // ── Extract to temp location ──────────────────────────────
    $tmp_extract = TEMP_DIR . '/' . uniqid('extract_', true);
    if (!mkdir($tmp_extract, 0755, true)) {
        $zip->close();
        json_response(['success' => false, 'error' => 'Server error: could not create temp directory.'], 500);
    }

    if (!$zip->extractTo($tmp_extract)) {
        $zip->close();
        rrmdir($tmp_extract);
        json_response(['success' => false, 'error' => 'Failed to extract ZIP file.'], 500);
    }
    $zip->close();

    // ── Detect single-root-folder ZIPs and unwrap them ────────
    $extracted_items = array_diff(scandir($tmp_extract), ['.', '..']);
    $source_dir = $tmp_extract;
    if (count($extracted_items) === 1) {
        $only = reset($extracted_items);
        $candidate = $tmp_extract . '/' . $only;
        if (is_dir($candidate) && file_exists($candidate . '/index.html')) {
            $source_dir = $candidate; // unwrap single top-level folder
        }
    }

    // ── Delete old site if replacing ──────────────────────────
    $dest = website_path($name);
    if ($replace && is_dir($dest)) {
        rrmdir($dest);
    }

    // ── Move to final destination ─────────────────────────────
    if (!rename($source_dir, $dest)) {
        // rename() may fail across drives; fall back to copy
        if (!mkdir($dest, 0755, true)) {
            rrmdir($tmp_extract);
            json_response(['success' => false, 'error' => 'Server error: could not create website directory.'], 500);
        }
        // Recursive copy
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source_dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iter as $item) {
            $target = $dest . '/' . $iter->getSubPathname();
            if ($item->isDir()) {
                mkdir($target, 0755, true);
            } else {
                copy($item->getRealPath(), $target);
            }
        }
    }
    rrmdir($tmp_extract); // clean up (no-op if renamed)

    // ── Write metadata ────────────────────────────────────────
    $meta = [
        'title'       => $title ?: $name,
        'description' => $description,
        'deployed_at' => time(),
        'size_bytes'  => dir_size($dest),
    ];
    file_put_contents($dest . '/.hostsw_meta.json', json_encode($meta, JSON_PRETTY_PRINT));

    json_response([
        'success' => true,
        'message' => $replace ? 'Website replaced successfully.' : 'Website deployed successfully.',
        'name'    => $name,
        'url'     => website_url($name),
        'size'    => format_bytes($meta['size_bytes']),
    ]);
}

/**
 * Deploy a website from raw HTML code string.
 * Sends a JSON response and exits.
 */
function deploy_html(string $name, string $title, string $description, string $html_code): void {
    if (trim($html_code) === '') {
        json_response(['success' => false, 'error' => 'HTML code cannot be empty.'], 400);
    }

    $dest = website_path($name);
    if (!is_dir($dest)) {
        if (!mkdir($dest, 0755, true)) {
            json_response(['success' => false, 'error' => 'Server error: could not create website directory.'], 500);
        }
    }

    if (file_put_contents($dest . '/index.html', $html_code) === false) {
        json_response(['success' => false, 'error' => 'Server error: failed to write index.html.'], 500);
    }

    $meta = [
        'title'       => $title ?: $name,
        'description' => $description,
        'deployed_at' => time(),
        'size_bytes'  => dir_size($dest),
    ];
    file_put_contents($dest . '/.hostsw_meta.json', json_encode($meta, JSON_PRETTY_PRINT));

    json_response([
        'success' => true,
        'message' => 'Website deployed successfully.',
        'name'    => $name,
        'url'     => website_url($name),
        'size'    => format_bytes($meta['size_bytes']),
    ]);
}

