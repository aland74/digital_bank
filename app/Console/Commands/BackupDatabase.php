<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\DistributedDatabaseService;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup {--connection= : Back up a specific database connection name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform scheduled monthly or on-demand backups for HQ and branch databases (SQLite & MySQL)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Initializing database backup system...");
        Log::info("BackupDatabase: Starting execution.");

        $backupDir = storage_path('app/backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
            $this->info("Created backups directory: {$backupDir}");
        }

        $specifiedConnection = $this->option('connection');
        
        if ($specifiedConnection) {
            $connectionsToBackup = [$specifiedConnection];
        } else {
            $connectionsToBackup = DistributedDatabaseService::allConnections();
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $successfulBackups = 0;

        foreach ($connectionsToBackup as $connection) {
            $this->info("Processing backup for connection '{$connection}'...");

            try {
                if (!DistributedDatabaseService::isConnectionOnline($connection)) {
                    $this->error("Connection '{$connection}' is offline! Cannot backup.");
                    continue;
                }

                $config = config("database.connections.{$connection}");
                if (!$config) {
                    $this->error("No configuration found for connection '{$connection}'.");
                    continue;
                }

                $driver = $config['driver'] ?? 'mysql';

                if ($driver === 'sqlite') {
                    $dbPath = $config['database'];
                    if (file_exists($dbPath)) {
                        $backupFilename = "{$connection}_backup_{$timestamp}.sqlite";
                        $backupFilePath = "{$backupDir}/{$backupFilename}";
                        copy($dbPath, $backupFilePath);
                        
                        $this->info("   [SUCCESS] SQLite backup saved to: " . basename($backupFilePath));
                        Log::info("BackupDatabase: SQLite backup successful for '{$connection}' -> {$backupFilename}");
                        $successfulBackups++;
                    } else {
                        $this->error("SQLite database file not found at: {$dbPath}");
                    }
                } else {
                    // MySQL Portable Dump
                    $backupFilename = "{$connection}_backup_{$timestamp}.sql";
                    $backupFilePath = "{$backupDir}/{$backupFilename}";
                    
                    $this->info("   Performing portable SQL schema and data dump for '{$connection}'...");
                    $sqlContent = "-- Distributed Bank Automated SQL Backup\n";
                    $sqlContent .= "-- Connection: {$connection}\n";
                    $sqlContent .= "-- Exported: " . now()->toDateTimeString() . "\n";
                    $sqlContent .= "-- ------------------------------------------------------\n\n";
                    $sqlContent .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

                    // Retrieve tables
                    $tables = [];
                    $dbName = $config['database'];
                    
                    // SHOW TABLES returns an array of stdClass objects where properties are dynamic
                    $tablesResult = DB::connection($connection)->select("SHOW TABLES");
                    foreach ($tablesResult as $row) {
                        $rowArray = (array)$row;
                        $tables[] = reset($rowArray); // Get the first value in the row
                    }

                    foreach ($tables as $table) {
                        $sqlContent .= "--\n-- Table structure for table `{$table}`\n--\n\n";
                        $sqlContent .= "DROP TABLE IF EXISTS `{$table}`;\n";

                        // Get SHOW CREATE TABLE
                        $createTableResult = DB::connection($connection)->select("SHOW CREATE TABLE `{$table}`");
                        if (!empty($createTableResult)) {
                            $createRow = (array)$createTableResult[0];
                            // The second column in SHOW CREATE TABLE is the SQL statement
                            $createStatement = $createRow['Create Table'] ?? array_values($createRow)[1];
                            $sqlContent .= "{$createStatement};\n\n";
                        }

                        // Get rows
                        $sqlContent .= "--\n-- Dumping data for table `{$table}`\n--\n\n";
                        $rows = DB::connection($connection)->table($table)->get();

                        if ($rows->count() > 0) {
                            $sqlContent .= "INSERT INTO `{$table}` VALUES ";
                            $insertRows = [];

                            foreach ($rows as $row) {
                                $values = [];
                                foreach ((array)$row as $val) {
                                    if ($val === null) {
                                        $values[] = 'NULL';
                                    } else {
                                        $values[] = DB::connection($connection)->getPdo()->quote($val);
                                    }
                                }
                                $insertRows[] = "\n(" . implode(", ", $values) . ")";
                            }

                            $sqlContent .= implode(",", $insertRows) . ";\n\n";
                        }
                    }

                    $sqlContent .= "SET FOREIGN_KEY_CHECKS=1;\n";
                    
                    // Write backup file
                    file_put_contents($backupFilePath, $sqlContent);

                    $this->info("   [SUCCESS] MySQL backup saved to: " . basename($backupFilePath));
                    Log::info("BackupDatabase: MySQL backup successful for '{$connection}' -> {$backupFilename}");
                    $successfulBackups++;
                }
            } catch (\Exception $e) {
                $this->error("Failed to back up connection '{$connection}': " . $e->getMessage());
                Log::error("BackupDatabase: Failed backing up connection '{$connection}': " . $e->getMessage());
            }
        }

        $this->info("Completed! Successfully backed up {$successfulBackups} database(s).");
        Log::info("BackupDatabase: Completed execution. Successfully backed up {$successfulBackups} databases.");
        return 0;
    }
}
