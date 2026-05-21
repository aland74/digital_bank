<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\DistributedDatabaseService;

class SyncDistributedDatabases extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:sync-distributed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically synchronize pending outbox data bidirectional between HQ and offline branch databases once they recover.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting distributed database synchronization...");
        Log::info("SyncDistributed: Starting execution.");

        $hqConn = DistributedDatabaseService::getHqConnection();
        $hqOnline = DistributedDatabaseService::isConnectionOnline($hqConn);

        if (!$hqOnline) {
            $this->error("HQ database connection '{$hqConn}' is OFFLINE. Cannot perform synchronization.");
            Log::warning("SyncDistributed: Aborted because HQ is offline.");
            return 1;
        }

        $branches = DistributedDatabaseService::branchDisplayNames(); // ['erbil' => 'Erbil', ...]

        foreach ($branches as $branchKey => $branchName) {
            $this->info("Processing branch: {$branchName}...");
            $branchConn = DistributedDatabaseService::connectionForBranch($branchKey);

            if (!DistributedDatabaseService::isConnectionOnline($branchConn)) {
                $this->warn("Branch '{$branchName}' connection '{$branchConn}' is OFFLINE. Skipping for now.");
                continue;
            }

            // 1. Synchronize Branch -> HQ (Outbound Queue)
            $this->syncBranchToHQ($branchKey, $branchConn, $hqConn);

            // 2. Synchronize HQ -> Branch (Inbound Queue)
            $this->syncHQToBranch($branchKey, $branchConn, $hqConn);
        }

        $this->info("Distributed database synchronization completed.");
        Log::info("SyncDistributed: Finished execution.");
        return 0;
    }

    /**
     * Sync local changes from a branch to HQ.
     */
    protected function syncBranchToHQ(string $branchKey, string $branchConn, string $hqConn): void
    {
        try {
            $pendingLogs = DB::connection($branchConn)->table('pending_hq_syncs')->orderBy('id', 'asc')->get();

            if ($pendingLogs->isEmpty()) {
                return;
            }

            $this->info("Found {$pendingLogs->count()} pending syncs from {$branchKey} to HQ.");
            Log::info("SyncDistributed: Syncing {$pendingLogs->count()} records from branch '{$branchKey}' to HQ.");

            $syncedCount = 0;

            foreach ($pendingLogs as $log) {
                try {
                    $data = json_decode($log->data, true);

                    if ($log->action === 'delete') {
                        // Attempt to soft delete on HQ
                        DB::connection($hqConn)->table($log->table)
                            ->where('id', $log->record_id)
                            ->update(['deleted_at' => now()]);
                    } else {
                        // insert or update
                        $exists = DB::connection($hqConn)->table($log->table)
                            ->where('id', $log->record_id)
                            ->exists();

                        if ($exists) {
                            DB::connection($hqConn)->table($log->table)
                                ->where('id', $log->record_id)
                                ->update($data);
                        } else {
                            DB::connection($hqConn)->table($log->table)->insert($data);
                        }
                    }

                    // Success: Remove log from branch
                    DB::connection($branchConn)->table('pending_hq_syncs')->where('id', $log->id)->delete();
                    $syncedCount++;
                } catch (\Exception $e) {
                    $this->error("Failed to sync record #{$log->id} ({$log->table}) from branch '{$branchKey}': " . $e->getMessage());
                    Log::error("SyncDistributed: Failure for record #{$log->id} ({$log->table}): " . $e->getMessage());
                    // Keep the record in queue and proceed to next to avoid blockade
                }
            }

            $this->info("Successfully synced {$syncedCount} records from {$branchKey} to HQ.");
        } catch (\Exception $e) {
            $this->error("Failed reading pending syncs from branch '{$branchKey}': " . $e->getMessage());
        }
    }

    /**
     * Sync HQ fallback changes back to the branch.
     */
    protected function syncHQToBranch(string $branchKey, string $branchConn, string $hqConn): void
    {
        try {
            $pendingLogs = DB::connection($hqConn)->table('pending_branch_syncs')
                ->where('branch', $branchKey)
                ->orderBy('id', 'asc')
                ->get();

            if ($pendingLogs->isEmpty()) {
                return;
            }

            $this->info("Found {$pendingLogs->count()} pending syncs from HQ back to {$branchKey}.");
            Log::info("SyncDistributed: Syncing {$pendingLogs->count()} records from HQ to branch '{$branchKey}'.");

            $syncedCount = 0;

            foreach ($pendingLogs as $log) {
                try {
                    $data = json_decode($log->data, true);

                    if ($log->action === 'delete') {
                        // Attempt to soft delete on branch
                        DB::connection($branchConn)->table($log->table)
                            ->where('id', $log->record_id)
                            ->update(['deleted_at' => now()]);
                    } else {
                        // insert or update
                        $exists = DB::connection($branchConn)->table($log->table)
                            ->where('id', $log->record_id)
                            ->exists();

                        if ($exists) {
                            DB::connection($branchConn)->table($log->table)
                                ->where('id', $log->record_id)
                                ->update($data);
                        } else {
                            DB::connection($branchConn)->table($log->table)->insert($data);
                        }
                    }

                    // Success: Remove log from HQ
                    DB::connection($hqConn)->table('pending_branch_syncs')->where('id', $log->id)->delete();
                    $syncedCount++;
                } catch (\Exception $e) {
                    $this->error("Failed to sync record #{$log->id} ({$log->table}) from HQ to '{$branchKey}': " . $e->getMessage());
                    Log::error("SyncDistributed: Failure for record #{$log->id} ({$log->table}) HQ->branch: " . $e->getMessage());
                    // Keep in queue
                }
            }

            $this->info("Successfully synced {$syncedCount} records from HQ to {$branchKey}.");
        } catch (\Exception $e) {
            $this->error("Failed reading pending syncs from HQ for branch '{$branchKey}': " . $e->getMessage());
        }
    }
}
