<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PDO;

class CreateTestingDatabase extends Command
{
    protected $signature = 'db:create-testing';
    protected $description = 'Create testing database';

    public function handle()
    {
        $database = config('database.connections.mysql_testing.database');
        $charset = config('database.connections.mysql_testing.charset', 'utf8mb4');
        $collation = config('database.connections.mysql_testing.collation', 'utf8mb4_unicode_ci');

        try {
            $pdo = new PDO(
                sprintf(
                    'mysql:host=%s;port=%d',
                    config('database.connections.mysql_testing.host'),
                    config('database.connections.mysql_testing.port')
                ),
                config('database.connections.mysql_testing.username'),
                config('database.connections.mysql_testing.password')
            );

            $this->info("Creating database $database...");

            $pdo->exec(sprintf(
                'CREATE DATABASE IF NOT EXISTS %s CHARACTER SET %s COLLATE %s;',
                $database,
                $charset,
                $collation
            ));

            $this->info('Database created successfully!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
