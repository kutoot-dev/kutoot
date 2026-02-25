<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

// The nnjeim/world package references LARAVEL_START (normally set in public/index.php).
// Define it here so the package can compute response timing during tests.
if (! defined('LARAVEL_START')) {
    define('LARAVEL_START', microtime(true));
}

abstract class TestCase extends BaseTestCase
{
    /**
     * Boot the testing framework and strip nnjeim/world migration paths before
     * RefreshDatabase runs migrate:fresh. The world data lives in a separate
     * persistent MySQL connection (world_mysql) and must not be recreated.
     */
    protected function refreshApplication(): void
    {
        parent::refreshApplication();

        $this->removeWorldMigrationPaths();
    }

    private function removeWorldMigrationPaths(): void
    {
        $migrator = $this->app->make('migrator');

        $reflection = new \ReflectionProperty($migrator, 'paths');
        $reflection->setAccessible(true);

        $filtered = array_values(
            array_filter(
                $reflection->getValue($migrator),
                fn (string $path) => ! str_contains($path, 'nnjeim'),
            )
        );

        $reflection->setValue($migrator, $filtered);
    }
}
