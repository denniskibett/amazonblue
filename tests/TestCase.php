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
        if ($database !== 'loans_testing') {
            throw new RuntimeException(
                "Tests must run against the 'loans_testing' database, not '{$database}'. Run `php artisan config:clear` first."
            );
        }

        return $app;
    }
}
