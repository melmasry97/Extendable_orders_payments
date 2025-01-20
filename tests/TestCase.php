<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Generate application key if not set
        if (!env('APP_KEY')) {
            Artisan::call('key:generate', ['--force' => true]);
        }

        // Create testing database and run migrations
        $this->prepareDatabase();
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
}
