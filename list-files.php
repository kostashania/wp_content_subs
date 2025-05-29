<?php
function listFiles($dir) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        echo str_replace($dir . DIRECTORY_SEPARATOR, '', $file->getPathname()) . "\n";
    }
}

// Replace this with your plugin directory path
$pluginDir = __DIR__;

echo "<pre>\n";
listFiles($pluginDir);
echo "</pre>";
