<?php
$files = glob(__DIR__ . '/tests/**/*.php');
foreach ($files as $file) {
    $content = file_get_contents($file);
    if (strpos($content, 'RefreshDatabase') !== false) {
        echo "Found RefreshDatabase in " . basename($file) . "\n";
    }
}
$files2 = glob(__DIR__ . '/tests/*.php');
foreach ($files2 as $file) {
    $content = file_get_contents($file);
    if (strpos($content, 'RefreshDatabase') !== false) {
        echo "Found RefreshDatabase in " . basename($file) . "\n";
    }
}
