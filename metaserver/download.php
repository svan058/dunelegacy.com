<?php
/**
 * Download handler for Dune Legacy installers
 */

$downloads = [
    'macos' => 'DuneLegacy-0.98.7.0-macOS.dmg',
];

$platform = $_GET['platform'] ?? '';

if (!isset($downloads[$platform])) {
    header("HTTP/1.0 404 Not Found");
    echo "Invalid download request";
    exit;
}

$file = __DIR__ . '/' . $downloads[$platform];

if (!file_exists($file)) {
    header("HTTP/1.0 404 Not Found");
    echo "File not found";
    exit;
}

// Set headers for file download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($file) . '"');
header('Content-Length: ' . filesize($file));
header('Cache-Control: public, max-age=86400'); // Cache for 1 day
header('Pragma: public');

// Output file
readfile($file);
exit;

