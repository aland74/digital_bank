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
        static::created(function ($model) {
            $model->syncCreatedToHQ();
        });

        static::updated(function ($model) {
            $model->syncUpdatedToHQ();
        });

        static::deleted(function ($model) {
            $model->syncDeletedToHQ();
        });
    }

    /**
     * Get the actual resolved connection name for this model.
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
            if ($value instanceof \DateTimeInterface) {
                $data[$key] = $value->format('Y-m-d H:i:s');
            } elseif (is_array($value) || is_object($value)) {
                $data[$key] = json_encode($value);
            }
        }

        return $data;
    }

    /**
     * Build outbox insert data with status/retry tracking.
     */
    private function buildOutboxData(string $table, int $recordId, string $action, array $data): array
    {
        return [
            'table' => $table,
            'record_id' => $recordId,
            'action' => $action,
            'data' => json_encode($data),
            'status' => 'pending',
            'retry_count' => 0,
            'max_retries' => 5,
            'created_at' => now(),
        ];
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
                            'status' => 'pending',
                            'retry_count' => 0,
                            'max_retries' => 5,
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
            DB::connection(DistributedDatabaseService::getHqConnection())
                ->table($this->getTable())
                ->insertOrIgnore($data);

            Log::debug("SyncsWithHQ: Created {$this->getTable()} #{$this->getKey()} in HQ");
        } catch (\Exception $e) {
            Log::error("SyncsWithHQ: Failed to sync create to HQ for {$this->getTable()} #{$this->getKey()}: " . $e->getMessage());

            try {
                DB::connection($this->resolvedConnectionName())->table('pending_hq_syncs')->insert(
                    $this->buildOutboxData($this->getTable(), $this->getKey(), 'insert', $data)
                );
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

        if ($this->usesTimestamps() && !isset($changes['updated_at'])) {
            $changes['updated_at'] = $this->freshTimestampString();
        }

        if ($this->isOnHQConnection()) {
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
                            'status' => 'pending',
                            'retry_count' => 0,
                            'max_retries' => 5,
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
            Log::error("SyncsWithHQ: Failed to sync update to HQ for {$this->getTable()} #{$this->getKey()}: " . $e->getMessage());

            try {
                DB::connection($this->resolvedConnectionName())->table('pending_hq_syncs')->insert(
                    $this->buildOutboxData($this->getTable(), $this->getKey(), 'update', $changes)
                );
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
                            'status' => 'pending',
                            'retry_count' => 0,
                            'max_retries' => 5,
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
            Log::error("SyncsWithHQ: Failed to sync delete to HQ for {$this->getTable()} #{$this->getKey()}: " . $e->getMessage());

            try {
                DB::connection($this->resolvedConnectionName())->table('pending_hq_syncs')->insert(
                    $this->buildOutboxData($this->getTable(), $this->getKey(), 'delete', ['is_soft' => $isSoft])
                );
            } catch (\Exception $innerEx) {
                Log::critical("SyncsWithHQ: Outbox critical failure on delete queue: " . $innerEx->getMessage());
            }
        }
    }
}
