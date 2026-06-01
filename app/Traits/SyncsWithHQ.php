<?php

namespace App\Traits;

use App\Services\DistributedDatabaseService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Trait SyncsWithHQ
 *
 * Automatically syncs Eloquent model creates, updates, and deletes to the HQ database.
 * This ensures HQ always has a complete copy of all branch data.
 *
 * It is fully resilient: if HQ is down, it stores the pending sync in a local
 * 'pending_hq_syncs' table. If a branch is down, and writes are routed directly to HQ
 * in HQ fallback mode, it logs pending syncs to 'pending_branch_syncs' on HQ.
 *
 * Usage: Add `use SyncsWithHQ;` to any model that needs HQ replication.
 */
trait SyncsWithHQ
{
    /**
     * Boot the trait — register model event listeners for HQ sync.
     */
    public static function bootSyncsWithHQ(): void
    {
        // After creating a record in the branch DB, replicate it to HQ
        static::created(function ($model) {
            $model->syncCreatedToHQ();
        });

        // After updating a record in the branch DB, replicate the changes to HQ
        static::updated(function ($model) {
            $model->syncUpdatedToHQ();
        });

        // After deleting (soft or hard), replicate to HQ
        static::deleted(function ($model) {
            $model->syncDeletedToHQ();
        });
    }

    /**
     * Get the actual resolved connection name for this model.
     * Handles the case where getConnectionName() returns null (uses default).
     */
    private function resolvedConnectionName(): string
    {
        return $this->getConnectionName() ?? Config::get('database.default');
    }

    /**
     * Check if we're currently writing to HQ (no sync needed).
     */
    private function isOnHQConnection(): bool
    {
        return $this->resolvedConnectionName() === DistributedDatabaseService::getHqConnection();
    }

    /**
     * Prepare a data array for HQ insertion by converting any objects to strings.
     */
    private function prepareDataForHQ(array $data): array
    {
        foreach ($data as $key => $value) {
            // Convert DateTimeInterface objects to string
            if ($value instanceof \DateTimeInterface) {
                $data[$key] = $value->format('Y-m-d H:i:s');
            }
            // Convert arrays/objects to JSON
            elseif (is_array($value) || is_object($value)) {
                $data[$key] = json_encode($value);
            }
        }

        return $data;
    }

    /**
     * Sync a newly created record to HQ.
     */
    protected function syncCreatedToHQ(): void
    {
        $data = $this->getAttributes();
        $data['id'] = $this->getKey();
        $data = $this->prepareDataForHQ($data);

        // Don't sync if we're already writing to HQ
        if ($this->isOnHQConnection()) {
            // If in HQ fallback mode, we must queue this write on HQ to sync back to the branch later!
            $fallbackBranch = DistributedDatabaseService::getFallbackBranch();
            if ($fallbackBranch) {
                try {
                    DB::connection(DistributedDatabaseService::getHqConnection())
                        ->table('pending_branch_syncs')
                        ->insert([
                            'branch' => $fallbackBranch,
                            'table' => $this->getTable(),
                            'record_id' => $this->getKey(),
                            'action' => 'insert',
                            'data' => json_encode($data),
                            'created_at' => now(),
                        ]);
                    Log::info("SyncsWithHQ: Recorded HQ fallback insert for down branch '{$fallbackBranch}'");
                } catch (\Exception $e) {
                    Log::critical("SyncsWithHQ: Failed to write pending branch sync to HQ: " . $e->getMessage());
                }
            }
            return;
        }

        try {
            // Use insertOrIgnore to prevent duplicate key errors if HQ already has this record
            DB::connection(DistributedDatabaseService::getHqConnection())
                ->table($this->getTable())
                ->insertOrIgnore($data);

            Log::debug("SyncsWithHQ: Created {$this->getTable()} #{$this->getKey()} in HQ (from {$this->resolvedConnectionName()})");
        } catch (\Exception $e) {
            Log::error("SyncsWithHQ: Failed to sync create to HQ for {$this->getTable()} #{$this->getKey()}: " . $e->getMessage() . ". Logging to pending_hq_syncs.");
            
            // Queue failure in the local branch database
            try {
                DB::connection($this->resolvedConnectionName())->table('pending_hq_syncs')->insert([
                    'table' => $this->getTable(),
                    'record_id' => $this->getKey(),
                    'action' => 'insert',
                    'data' => json_encode($data),
                    'created_at' => now(),
                ]);
            } catch (\Exception $innerEx) {
                Log::critical("SyncsWithHQ: Outbox critical failure on insert queue: " . $innerEx->getMessage());
            }
        }
    }

    /**
     * Sync updates to HQ.
     */
    protected function syncUpdatedToHQ(): void
    {
        $changes = $this->getDirty();
        if (empty($changes)) {
            return;
        }

        $changes = $this->prepareDataForHQ($changes);

        // Also sync updated_at
        if ($this->usesTimestamps() && !isset($changes['updated_at'])) {
            $changes['updated_at'] = $this->freshTimestampString();
        }

        if ($this->isOnHQConnection()) {
            // If in HQ fallback mode, we must queue this update on HQ to sync back to the branch later!
            $fallbackBranch = DistributedDatabaseService::getFallbackBranch();
            if ($fallbackBranch) {
                try {
                    DB::connection(DistributedDatabaseService::getHqConnection())
                        ->table('pending_branch_syncs')
                        ->insert([
                            'branch' => $fallbackBranch,
                            'table' => $this->getTable(),
                            'record_id' => $this->getKey(),
                            'action' => 'update',
                            'data' => json_encode($changes),
                            'created_at' => now(),
                        ]);
                    Log::info("SyncsWithHQ: Recorded HQ fallback update for down branch '{$fallbackBranch}'");
                } catch (\Exception $e) {
                    Log::critical("SyncsWithHQ: Failed to write pending branch sync update to HQ: " . $e->getMessage());
                }
            }
            return;
        }

        try {
            $hq = DB::connection(DistributedDatabaseService::getHqConnection())
                ->table($this->getTable())
                ->where('id', $this->getKey());

            // If the record doesn't exist in HQ (edge case), insert it entirely
            if (!$hq->exists()) {
                $data = $this->prepareDataForHQ($this->getAttributes());
                $data['id'] = $this->getKey();
                DB::connection(DistributedDatabaseService::getHqConnection())
                    ->table($this->getTable())
                    ->insert($data);
            } else {
                $hq->update($changes);
            }

            Log::debug("SyncsWithHQ: Updated {$this->getTable()} #{$this->getKey()} in HQ");
        } catch (\Exception $e) {
            Log::error("SyncsWithHQ: Failed to sync update to HQ for {$this->getTable()} #{$this->getKey()}: " . $e->getMessage() . ". Logging to pending_hq_syncs.");

            // Queue failure in the local branch database
            try {
                DB::connection($this->resolvedConnectionName())->table('pending_hq_syncs')->insert([
                    'table' => $this->getTable(),
                    'record_id' => $this->getKey(),
                    'action' => 'update',
                    'data' => json_encode($changes),
                    'created_at' => now(),
                ]);
            } catch (\Exception $innerEx) {
                Log::critical("SyncsWithHQ: Outbox critical failure on update queue: " . $innerEx->getMessage());
            }
        }
    }

    /**
     * Sync deletes (soft or hard) to HQ.
     */
    protected function syncDeletedToHQ(): void
    {
        $isSoft = method_exists($this, 'trashed') && $this->trashed();

        if ($this->isOnHQConnection()) {
            // If in HQ fallback mode, we must queue this delete on HQ to sync back to the branch later!
            $fallbackBranch = DistributedDatabaseService::getFallbackBranch();
            if ($fallbackBranch) {
                try {
                    DB::connection(DistributedDatabaseService::getHqConnection())
                        ->table('pending_branch_syncs')
                        ->insert([
                            'branch' => $fallbackBranch,
                            'table' => $this->getTable(),
                            'record_id' => $this->getKey(),
                            'action' => 'delete',
                            'data' => json_encode(['is_soft' => $isSoft]),
                            'created_at' => now(),
                        ]);
                    Log::info("SyncsWithHQ: Recorded HQ fallback delete for down branch '{$fallbackBranch}'");
                } catch (\Exception $e) {
                    Log::critical("SyncsWithHQ: Failed to write pending branch sync delete to HQ: " . $e->getMessage());
                }
            }
            return;
        }

        try {
            // Check if this model uses soft deletes
            if ($isSoft) {
                DB::connection(DistributedDatabaseService::getHqConnection())
                    ->table($this->getTable())
                    ->where('id', $this->getKey())
                    ->update(['deleted_at' => now()->format('Y-m-d H:i:s')]);
            } else {
                DB::connection(DistributedDatabaseService::getHqConnection())
                    ->table($this->getTable())
                    ->where('id', $this->getKey())
                    ->delete();
            }

            Log::debug("SyncsWithHQ: Deleted {$this->getTable()} #{$this->getKey()} from HQ");
        } catch (\Exception $e) {
            Log::error("SyncsWithHQ: Failed to sync delete to HQ for {$this->getTable()} #{$this->getKey()}: " . $e->getMessage() . ". Logging to pending_hq_syncs.");

            // Queue failure in local branch database
            try {
                DB::connection($this->resolvedConnectionName())->table('pending_hq_syncs')->insert([
                    'table' => $this->getTable(),
                    'record_id' => $this->getKey(),
                    'action' => 'delete',
                    'data' => json_encode(['is_soft' => $isSoft]),
                    'created_at' => now(),
                ]);
            } catch (\Exception $innerEx) {
                Log::critical("SyncsWithHQ: Outbox critical failure on delete queue: " . $innerEx->getMessage());
            }
        }
    }
}
