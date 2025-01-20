<?php

namespace Tests\Unit\Commands;

use Tests\TestCase;
use PDO;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;

class CreateTestingDatabaseTest extends TestCase
{
    protected string $database;
    protected ?PDO $pdo = null;
    protected bool $originalDatabaseExists = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Get database configuration
        $this->database = config('database.connections.mysql_testing.database');

        // Create PDO connection without database
        $this->pdo = new PDO(
            sprintf(
                'mysql:host=%s;port=%d',
                config('database.connections.mysql_testing.host'),
                config('database.connections.mysql_testing.port')
            ),
            config('database.connections.mysql_testing.username'),
            config('database.connections.mysql_testing.password')
        );

        // Check if database exists before we start
        $stmt = $this->pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$this->database}'");
        $this->originalDatabaseExists = !empty($stmt->fetchAll());

        // Only drop if it wasn't there originally
        if (!$this->originalDatabaseExists) {
            $this->pdo->exec("DROP DATABASE IF EXISTS {$this->database}");
        }
    }

    public function test_command_creates_database_successfully()
    {
        // Skip if database already exists
        if ($this->originalDatabaseExists) {
            $this->markTestSkipped('Database already exists');
        }

        // Run the command
        $this->artisan('db:create-testing')
            ->expectsOutput("Creating database {$this->database}...")
            ->expectsOutput('Database created successfully!')
            ->assertSuccessful();

        // Verify database exists
        $stmt = $this->pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$this->database}'");
        $this->assertNotFalse($stmt);
        $this->assertNotEmpty($stmt->fetchAll());
    }

    public function test_command_handles_existing_database()
    {
        // Create the database if it doesn't exist
        if (!$this->originalDatabaseExists) {
            $this->pdo->exec("CREATE DATABASE {$this->database}");
        }

        // Run the command
        $this->artisan('db:create-testing')
            ->expectsOutput("Creating database {$this->database}...")
            ->expectsOutput('Database created successfully!')
            ->assertSuccessful();
    }

    public function test_command_fails_with_invalid_credentials()
    {
        // Set invalid credentials
        Config::set('database.connections.mysql_testing.username', 'invalid_user');
        Config::set('database.connections.mysql_testing.password', 'invalid_password');

        // Run the command
        $this->artisan('db:create-testing')
            ->assertFailed();
    }

    protected function tearDown(): void
    {
        if ($this->pdo) {
            // Only drop the database if it wasn't there when we started
            if (!$this->originalDatabaseExists) {
                $this->pdo->exec("DROP DATABASE IF EXISTS {$this->database}");
            }
            $this->pdo = null;
        }

        parent::tearDown();
    }
}
