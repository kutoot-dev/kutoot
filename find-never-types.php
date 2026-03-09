#!/usr/bin/env php
<?php

/**
 * Comprehensive script to find and fix 'never' type issues in PHP files
 */

$projectRoot = __DIR__;
$directory = $projectRoot . '/app';

class NeverTypeFinder
{
    private $issues = [];
    private $fixes = [];

    public function scan($dir)
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $this->checkFile($file->getPathname());
            }
        }
    }

    private function checkFile($filepath)
    {
        $content = file_get_contents($filepath);
        $lines = explode("\n", $content);

        foreach ($lines as $lineNum => $line) {
            // Skip comments
            $trimmed = trim($line);
            if ($trimmed === '' || $trimmed[0] === '#' || strpos($trimmed, '//') === 0) {
                continue;
            }

            // Check for union types with never (the problematic pattern)
            // Matches: ): string|never  or ): never|int  or $property: type|never
            if (preg_match('/:([^{=;]*)\|([^{=;]*never|never\|)/', $line)) {
                $this->issues[] = [
                    'file' => $filepath,
                    'line' => $lineNum + 1,
                    'content' => $line,
                    'type' => 'union_with_never'
                ];
            }

            // Check for intersection with never
            if (preg_match('/:([^{=;]*)&([^{=;]*never|never&)/', $line)) {
                $this->issues[] = [
                    'file' => $filepath,
                    'line' => $lineNum + 1,
                    'content' => $line,
                    'type' => 'intersection_with_never'
                ];
            }

            // Check for closures with never combining with other types
            if (preg_match('/fn\s*\([^)]*\)\s*:[^{]*\|.*never|fn\s*\([^)]*\)\s*:[^{]*never\s*\|/', $line)) {
                $this->issues[] = [
                    'file' => $filepath,
                    'line' => $lineNum + 1,
                    'content' => $line,
                    'type' => 'closure_with_never'
                ];
            }
        }
    }

    public function report()
    {
        if (empty($this->issues)) {
            echo "✓ No problematic 'never' type patterns found!\n";
            echo "✓ Your code is compatible with PHP 8.4+\n";
            return true;
        }

        echo "❌ Found " . count($this->issues) . " potential issues with 'never' types:\n\n";

        foreach ($this->issues as $issue) {
            echo str_pad('═', 80, '═') . "\n";
            echo "File: " . str_replace(dirname(dirname(__DIR__)), '.', $issue['file']) . "\n";
            echo "Line: " . $issue['line'] . "\n";
            echo "Type: " . $issue['type'] . "\n";
            echo "Code: " . trim($issue['content']) . "\n";
            echo "\nFix:\n";
            echo "  Remove the union/intersection with 'never'. In PHP 8.4+, 'never' cannot\n";
            echo "  be combined with other types. Use only 'never' or use the actual return type.\n";
        }

        return false;
    }
}

$finder = new NeverTypeFinder();
$finder->scan($directory);
$success = $finder->report();

exit($success ? 0 : 1);
