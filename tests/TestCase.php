<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Tests wipe and rebuild their database (RefreshDatabase). If the config is cached,
     * phpunit.xml's DB_DATABASE is ignored and that would hit the real database, so refuse
     * to run against anything but the dedicated test database.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $connection = $app['config']->get('database.default');
        $database = $app['config']->get("database.connections.{$connection}.database");
        // loans_testing or a per-agent copy such as loans_testing_b (never the live / local data)
        if (! is_string($database) || ! preg_match('/^loans_testing(_[a-z0-9]+)?$/', $database)) {
            throw new RuntimeException(
                "Tests must run against the 'loans_testing' database, not '{$database}'. Run `php artisan config:clear` first."
            );
        }

        return $app;
    }
}
