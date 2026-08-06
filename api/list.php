<?php
require_once __DIR__ . '/auth_check.php';
require_auth();

$websites    = [];
$total_bytes = 0;
$last_upload = null;

if (is_dir(WEBSITES_DIR)) {
    $entries = array_diff(scandir(WEBSITES_DIR), ['.', '..']);
    foreach ($entries as $name) {
        $path = WEBSITES_DIR . '/' . $name;
        if (!is_dir($path)) continue;

        // Read metadata file if it exists
        $meta_file = $path . '/.hostsw_meta.json';
        $meta = [];
        if (file_exists($meta_file)) {
            $meta = json_decode(file_get_contents($meta_file), true) ?? [];
        }

        $size        = dir_size($path);
        $deploy_time = $meta['deployed_at'] ?? filemtime($path);
        $has_index   = file_exists($path . '/index.html');

        $total_bytes += $size;
        if ($last_upload === null || $deploy_time > $last_upload) {
            $last_upload = $deploy_time;
        }

        // Count files (excluding hidden meta file)
        $file_count = 0;
        $iterator   = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $f) {
            if ($f->isFile() && $f->getFilename() !== '.hostsw_meta.json') $file_count++;
        }

        $websites[] = [
            'name'        => $name,
            'title'       => $meta['title']       ?? $name,
            'description' => $meta['description'] ?? '',
            'url'         => website_url($name),
            'size'        => $size,
            'size_fmt'    => format_bytes($size),
            'file_count'  => $file_count,
            'deployed_at' => $deploy_time,
            'deployed_at_fmt' => date('Y-m-d H:i', $deploy_time),
            'status'      => $has_index ? 'live' : 'broken',
        ];
    }
}

// Sort by newest first by default
usort($websites, fn($a, $b) => $b['deployed_at'] - $a['deployed_at']);

json_response([
    'success'  => true,
    'websites' => $websites,
    'stats'    => [
        'total_sites'   => count($websites),
        'total_storage' => format_bytes($total_bytes),
        'total_bytes'   => $total_bytes,
        'last_upload'   => $last_upload ? date('Y-m-d H:i', $last_upload) : 'Never',
    ],
]);
