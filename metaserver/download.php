<?php
/**
 * Download handler for Dune Legacy installers
 * Redirects to SourceForge for the latest version
 */

$downloads = [
    'windows' => 'https://sourceforge.net/projects/dunelegacy/files/dunelegacy/0.98.0aplpha/DuneLegacy-0.98.7.1-Windows-x64.exe/download',
    'macos' => 'https://sourceforge.net/projects/dunelegacy/files/dunelegacy/0.98.0aplpha/DuneLegacy-0.98.7.1-macOS.dmg/download',
    'linux' => 'https://sourceforge.net/projects/dunelegacy/files/dunelegacy/0.98.0aplpha/DuneLegacy-0.98.6.6-Linux.tar.gz/download',
];

$platform = $_GET['platform'] ?? '';

if (!isset($downloads[$platform])) {
    // Default to SourceForge files page if no platform specified
    header("Location: https://sourceforge.net/projects/dunelegacy/files/dunelegacy/0.98.0aplpha/");
    exit;
}

// Redirect to SourceForge download
header("Location: " . $downloads[$platform]);
exit;

