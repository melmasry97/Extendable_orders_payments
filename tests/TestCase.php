<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Skip database preparation for database command tests
        if (!$this->shouldSkipDatabasePreparation()) {
            $this->prepareDatabase();
        }
    }

    protected function prepareDatabase(): void
    {
        // Create database if it doesn't exist
        Artisan::call('db:create-testing');

        // Run migrations
        Artisan::call('migrate:fresh', [
            '--seed' => false,
            '--env' => 'testing'
        ]);
    }

    protected function shouldSkipDatabasePreparation(): bool
    {
        return $this instanceof \Tests\Unit\Commands\CreateTestingDatabaseTest;
    }

    /**
     * Creates the application.
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }
}
