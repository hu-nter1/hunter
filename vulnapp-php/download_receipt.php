<?php
require __DIR__ . '/config.php';
require __DIR__ . '/includes/session_check.php';

$file = $_GET['f'] ?? '';

if (VULN_PATH_TRAVERSAL_DOWNLOAD) {
    $path = 'uploads/' . $file;
} else {
    // SECURE: basename and check if it exists in uploads/
    $base = basename($file);
    $path = 'uploads/' . $base;
}

if (file_exists($path)) {
    header('Content-Type: application/octet-stream');
    readfile($path);
} else {
    die("File not found.");
}
