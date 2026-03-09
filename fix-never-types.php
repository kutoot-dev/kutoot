<?php

/**
 * Script to find and report problematic 'never' type usage in PHP 8.4+
 * The 'never' return type cannot be combined with other types in unions
 */

$projectRoot = __DIR__;
$appDir = "$projectRoot/app";
$filesToCheck = [];

// Recursively get all PHP files in app directory
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($appDir),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $file) {
    if ($file->getExtension() === 'php') {
        $filesToCheck[] = $file->getPathname();
    }
}

$problemFiles = [];

foreach ($filesToCheck as $file) {
    $content = file_get_contents($file);
    $lines = explode("\n", $content);

    foreach ($lines as $lineNum => $line) {
        // Look for 'never' combined with other types in return types or property types
        if (preg_match('/:\s*\w+.*\|.*never|:\s*never\s*\|/', $line)) {
            // Skip comments
            if (!preg_match('/^\s*(\/\/|\/\*|\*|#)/', trim($line))) {
                $problemFiles[] = [
                    'file' => str_replace($projectRoot, '.', $file),
                    'line' => $lineNum + 1,
                    'content' => trim($line)
                ];
            }
        }
    }
}

if (empty($problemFiles)) {
    echo "✓ No problematic 'never' type usage found!\n";
} else {
    echo "Found " . count($problemFiles) . " potential issues:\n\n";
    foreach ($problemFiles as $problem) {
        echo "{$problem['file']}:{$problem['line']}\n";
        echo "  {$problem['content']}\n\n";
    }
}
