<?php

namespace App\Console\Commands;

use App\Services\DistributedDatabaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class SetupDistributedDatabases extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'db:setup-distributed {--fresh : Drop and recreate all databases} {--seed : Run seeders after migration} {--driver= : Specify database driver (sqlite or mysql)}';

    /**
     * The console command description.
     */
    protected $description = 'Create and migrate all distributed databases (HQ + 3 city branches)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('');
        $this->info('========================================');
        $this->info('  Distributed Bank Distributed Database Setup  ');
        $this->info('  HQ + Erbil + Sulaimaniyah + Duhok     ');
        $this->info('========================================');
        $this->info('');

        $fresh = $this->option('fresh');
        $driver = $this->option('driver');

        if (!$driver) {
            // Check if we can run interactively
            if (function_exists('posix_isatty') || DIRECTORY_SEPARATOR === '\\') {
                try {
                    $driver = $this->choice(
                        'Which database system would you like to use?',
                        ['sqlite' => 'SQLite (Fast, local file-based, zero setup required)', 'mysql' => 'MySQL (Requires running MySQL server)'],
                        'sqlite'
                    );
                } catch (\Exception $e) {
                    $driver = env('DB_CONNECTION', 'sqlite');
                }
            } else {
                $driver = env('DB_CONNECTION', 'sqlite');
            }
        }

        $driver = strtolower($driver);
        if (!in_array($driver, ['sqlite', 'mysql'])) {
            $this->error("Invalid driver: {$driver}. Must be sqlite or mysql.");
            return self::FAILURE;
        }

        $this->updateEnvFile($driver);

        // Bootstrap the database configuration again to ensure .env changes are loaded
        config(['database.default' => $driver === 'sqlite' ? 'sqlite_hq' : 'mysql_hq']);

        if ($driver === 'sqlite') {
            $this->info('Step 1: Creating SQLite database files...');
            $files = [
                'sqlite_hq' => database_path('nexus_bank_hq.sqlite'),
                'sqlite_erbil' => database_path('nexus_bank_erbil.sqlite'),
                'sqlite_sulaimaniyah' => database_path('nexus_bank_sulaimaniyah.sqlite'),
                'sqlite_duhok' => database_path('nexus_bank_duhok.sqlite'),
            ];

            foreach ($files as $conn => $filePath) {
                if ($fresh && file_exists($filePath)) {
                    unlink($filePath);
                    $this->warn("   Dropped SQLite file: " . basename($filePath));
                }
                if (!file_exists($filePath)) {
                    touch($filePath);
                    $this->info("   [OK] Created database file: " . basename($filePath));
                } else {
                    $this->info("   [OK] Database file ready: " . basename($filePath));
                }
            }

            $connections = ['sqlite_hq', 'sqlite_erbil', 'sqlite_sulaimaniyah', 'sqlite_duhok'];
        } else {
            // MySQL
            $connections = [
                'mysql_hq',
                'mysql_erbil',
                'mysql_sulaimaniyah',
                'mysql_duhok'
            ];

            $this->info('Step 1: Creating MySQL databases...');
            foreach ($connections as $connectionName) {
                $dbName = config("database.connections.{$connectionName}.database");
                $host = config("database.connections.{$connectionName}.host");
                $port = config("database.connections.{$connectionName}.port");
                $username = config("database.connections.{$connectionName}.username");
                $password = config("database.connections.{$connectionName}.password");

                try {
                    // Connect to MySQL without a specific database to create one
                    $pdo = new \PDO(
                        "mysql:host={$host};port={$port}",
                        $username,
                        $password
                    );

                    if ($fresh) {
                        $pdo->exec("DROP DATABASE IF EXISTS `{$dbName}`");
                        $this->warn("   Dropped database: {$dbName}");
                    }

                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $this->info("   [OK] Database ready: {$dbName}");
                } catch (\Exception $e) {
                    $this->error("   [FAIL] Failed to create database {$dbName}: " . $e->getMessage());
                    return self::FAILURE;
                }
            }
        }

        $this->info('');

        // Step 2: Run migrations on each connection
        $this->info('Step 2: Running migrations...');
        foreach ($connections as $connectionName) {
            $dbName = config("database.connections.{$connectionName}.database");
            if ($driver === 'sqlite') {
                $dbName = basename($dbName);
            }
            $this->info("   Migrating: {$dbName} ({$connectionName})...");

            try {
                $migrateCommand = $fresh ? 'migrate:fresh' : 'migrate';
                Artisan::call($migrateCommand, [
                    '--database' => $connectionName,
                    '--force' => true,
                ]);
                $this->info("   [OK] Migrated: {$dbName}");

                if ($driver === 'sqlite') {
                    $this->setSqliteSequences($connectionName);
                }
            } catch (\Exception $e) {
                $this->error("   [FAIL] Migration failed for {$dbName}: " . $e->getMessage());
                return self::FAILURE;
            }
        }

        $this->info('');

        // Step 3: Seed default data (bank settings, etc.) into HQ
        $hqConn = $driver === 'sqlite' ? 'sqlite_hq' : 'mysql_hq';
        $branchConns = $driver === 'sqlite' 
            ? ['sqlite_erbil', 'sqlite_sulaimaniyah', 'sqlite_duhok'] 
            : ['mysql_erbil', 'mysql_sulaimaniyah', 'mysql_duhok'];

        if ($this->option('seed') || $fresh) {
            $this->info('Step 3: Seeding default data to HQ...');
            try {
                Artisan::call('db:seed', [
                    '--database' => $hqConn,
                    '--force' => true,
                ]);
                $this->info('   [OK] HQ seeded');
            } catch (\Exception $e) {
                $this->warn("   [WARN] Seeding skipped: " . $e->getMessage());
            }

            // Also seed bank_settings to each branch so BankSetting lookups work
            $this->info('   Copying bank_settings to branches...');
            try {
                $settings = DB::connection($hqConn)
                    ->table('bank_settings')
                    ->get();

                if ($settings->isNotEmpty()) {
                    foreach ($branchConns as $branchConn) {
                        foreach ($settings as $setting) {
                            DB::connection($branchConn)->table('bank_settings')->insertOrIgnore([
                                'id' => $setting->id,
                                'key' => $setting->key,
                                'value' => $setting->value,
                                'description' => $setting->description,
                                'created_at' => $setting->created_at,
                                'updated_at' => $setting->updated_at,
                            ]);
                        }
                        $branchDbName = config("database.connections.{$branchConn}.database");
                        if ($driver === 'sqlite') {
                            $branchDbName = basename($branchDbName);
                        }
                        $this->info("   [OK] Settings copied to {$branchDbName}");
                    }
                }
            } catch (\Exception $e) {
                $this->warn("   [WARN] Settings copy skipped: " . $e->getMessage());
            }
        }

        $this->info('');
        $this->info('========================================');
        $this->info('  All databases ready!                  ');
        $this->info('                                        ');
        if ($driver === 'sqlite') {
            $this->info('  HQ:           nexus_bank_hq.sqlite    ');
            $this->info('  Erbil:        nexus_bank_erbil.sqlite ');
            $this->info('  Sulaimaniyah: nexus_bank_sulaimaniyah.sqlite ');
            $this->info('  Duhok:        nexus_bank_duhok.sqlite ');
        } else {
            $this->info('  HQ:           nexus_bank_hq           ');
            $this->info('  Erbil:        nexus_bank_erbil        ');
            $this->info('  Sulaimaniyah: nexus_bank_sulaimaniyah ');
            $this->info('  Duhok:        nexus_bank_duhok        ');
        }
        $this->info('========================================');
        $this->info('');

        return self::SUCCESS;
    }

    /**
     * Configure non-colliding SQLite autoincrement sequence offsets for branch connections.
     */
    private function setSqliteSequences(string $connectionName): void
    {
        $offset = match ($connectionName) {
            'sqlite_hq' => 0,
            'sqlite_erbil' => 1000000,
            'sqlite_sulaimaniyah' => 2000000,
            'sqlite_duhok' => 3000000,
            default => null,
        };

        if ($offset === null || $offset === 0) {
            return;
        }

        $tables = [
            'users',
            'accounts',
            'transactions',
            'beneficiaries',
            'cards',
            'loans',
            'loan_repayments',
            'bill_payments',
            'scheduled_payments',
            'kyc_documents',
            'notifications',
            'audit_logs',
            'support_tickets',
            'currencies',
            'idempotent_requests',
            'ledger_entries',
            'user_devices',
            'webhooks',
            'pending_transfers',
            'bank_settings',
            'pin_change_requests',
            'personal_access_tokens'
        ];

        foreach ($tables as $table) {
            try {
                DB::connection($connectionName)->statement(
                    "INSERT OR REPLACE INTO sqlite_sequence (name, seq) VALUES (?, ?)",
                    [$table, $offset]
                );
            } catch (\Exception $e) {
                // Some tables might not have autoincrement sequence defined yet, which is fine
            }
        }

        $this->info("   [OK] SQLite autoincrement sequences offset set to {$offset} for {$connectionName}");
    }

    /**
     * Update the .env file with the selected database connection.
     */
    private function updateEnvFile(string $driver): void
    {
        $path = base_path('.env');
        if (!file_exists($path)) {
            $path = base_path('.env.example');
            if (!file_exists($path)) {
                return;
            }
            // Copy example to .env
            copy($path, base_path('.env'));
            $path = base_path('.env');
        }

        $content = file_get_contents($path);

        if (str_contains($content, 'DB_CONNECTION=')) {
            $content = preg_replace('/DB_CONNECTION=\w*/', "DB_CONNECTION={$driver}", $content);
        } else {
            $content = preg_replace('/(APP_KEY=.*)/', "$1\nDB_CONNECTION={$driver}", $content);
        }

        $hqConnName = $driver === 'sqlite' ? 'sqlite_hq' : 'mysql_hq';

        if (str_contains($content, 'SESSION_CONNECTION=')) {
            $content = preg_replace('/SESSION_CONNECTION=\w*/', "SESSION_CONNECTION={$hqConnName}", $content);
        }
        if (str_contains($content, 'CACHE_CONNECTION=')) {
            $content = preg_replace('/CACHE_CONNECTION=\w*/', "CACHE_CONNECTION={$hqConnName}", $content);
        }

        file_put_contents($path, $content);
        $this->info("   [OK] Updated .env file to set DB_CONNECTION={$driver}, SESSION_CONNECTION={$hqConnName}, CACHE_CONNECTION={$hqConnName}");
    }
}
