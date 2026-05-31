<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class DistributedDatabaseService
{
    /**
     * Valid branch identifiers mapped to their database connection names.
     */
    public const BRANCHES = [
        'erbil'         => 'mysql_erbil',
        'sulaimaniyah'  => 'mysql_sulaimaniyah',
        'duhok'         => 'mysql_duhok',
    ];

    /**
     * HQ connection name — always has ALL data.
     */
    public const HQ_CONNECTION = 'mysql_hq';

    /**
     * The currently active branch connection for this request.
     */
    protected static ?string $activeBranchConnection = null;

    /**
     * Store the branch name for which the system has fallen back to HQ (HQ fallback mode).
     */
    protected static ?string $fallbackForBranch = null;

    /**
     * Get the HQ connection name.
     */
    public static function getHqConnection(): string
    {
        return self::HQ_CONNECTION;
    }

    /**
     * Get the list of valid branch keys for validation rules.
     */
    public static function branchKeys(): array
    {
        return array_keys(self::BRANCHES);
    }

    /**
     * Get the display names for the branches (for dropdowns, etc.).
     */
    public static function branchDisplayNames(): array
    {
        return [
            'erbil'         => 'Erbil',
            'sulaimaniyah'  => 'Sulaimaniyah',
            'duhok'         => 'Duhok',
        ];
    }

    /**
     * Resolve the database connection name for a given branch key.
     */
    public static function connectionForBranch(string $branch): string
    {
        $branch = strtolower(trim($branch));

        if (!isset(self::BRANCHES[$branch])) {
            throw new \InvalidArgumentException("Unknown branch: {$branch}");
        }

        return self::BRANCHES[$branch];
    }

    /**
     * Set the active branch connection for this request.
     * This changes the DEFAULT database connection so all models automatically use it.
     */
    public static function setActiveBranch(string $branch): void
    {
        $connection = self::connectionForBranch($branch);
        self::$activeBranchConnection = $connection;
        self::$fallbackForBranch = null;
        Config::set('database.default', $connection);

        Log::debug("DistributedDB: Active branch set to '{$branch}' (connection: {$connection})");
    }

    /**
     * Set connection to HQ.
     */
    public static function setHQ(): void
    {
        self::$activeBranchConnection = null;
        self::$fallbackForBranch = null;
        Config::set('database.default', self::HQ_CONNECTION);

        Log::debug("DistributedDB: Connection set to HQ");
    }

    /**
     * Set the connection to HQ as a fallback for a down branch database.
     */
    public static function setHQFallback(string $branch): void
    {
        self::$activeBranchConnection = null;
        self::$fallbackForBranch = $branch;
        Config::set('database.default', self::HQ_CONNECTION);

        Log::warning("DistributedDB: Branch '{$branch}' database is down! Operating in HQ fallback mode.");
    }

    /**
     * Get the branch for which the system has fallen back to HQ, or null if not in fallback.
     */
    public static function getFallbackBranch(): ?string
    {
        return self::$fallbackForBranch;
    }

    /**
     * Get the currently active branch connection name.
     */
    public static function getActiveBranchConnection(): ?string
    {
        return self::$activeBranchConnection;
    }

    /**
     * Check if a database connection is currently online and accepting requests.
     */
    public static function isConnectionOnline(string $connection): bool
    {
        try {
            DB::connection($connection)->getPdo();
            return true;
        } catch (\Exception $e) {
            Log::warning("DistributedDB: Connection '{$connection}' is OFFLINE: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Look up a user's branch by email using the HQ database.
     * Fall back to checking branch databases sequentially if HQ is down.
     */
    public static function findUserBranch(string $email): ?string
    {
        try {
            $user = DB::connection(self::HQ_CONNECTION)
                ->table('users')
                ->where('email', $email)
                ->whereNull('deleted_at')
                ->first(['branch']);

            return $user?->branch;
        } catch (\Exception $e) {
            Log::error("DistributedDB: HQ is down in findUserBranch! Sequence-scanning branch databases directly.");

            foreach (self::branchConnections() as $branchConn) {
                try {
                    $user = DB::connection($branchConn)
                        ->table('users')
                        ->where('email', $email)
                        ->whereNull('deleted_at')
                        ->first(['branch']);

                    if ($user) {
                        return $user->branch;
                    }
                } catch (\Exception $ex) {
                    // Try next branch
                }
            }
        }

        return null;
    }

    /**
     * Look up a user's branch by user ID using HQ.
     * Fall back to scanning branches if HQ is down.
     */
    public static function findUserBranchById(int $userId): ?string
    {
        try {
            $user = DB::connection(self::HQ_CONNECTION)
                ->table('users')
                ->where('id', $userId)
                ->whereNull('deleted_at')
                ->first(['branch']);

            return $user?->branch;
        } catch (\Exception $e) {
            Log::error("DistributedDB: HQ is down in findUserBranchById! Sequence-scanning branch databases directly.");

            foreach (self::branchConnections() as $branchConn) {
                try {
                    $user = DB::connection($branchConn)
                        ->table('users')
                        ->where('id', $userId)
                        ->whereNull('deleted_at')
                        ->first(['branch']);

                    if ($user) {
                        return $user->branch;
                    }
                } catch (\Exception $ex) {
                    // Try next branch
                }
            }
        }

        return null;
    }

    /**
     * Get the outbox insert data with new status/retry columns.
     */
    private static function outboxData(string $table, int $recordId, string $action, array $data): array
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
     * Sync a single record to HQ after it has been written to the branch DB.
     * If HQ is down, records the sync into `pending_hq_syncs` table locally.
     */
    public static function syncRecordToHQ(string $table, array $data, string $primaryKey = 'id'): void
    {
        $resolvedConn = Config::get('database.default');

        try {
            $hq = DB::connection(self::HQ_CONNECTION);

            $exists = $hq->table($table)
                ->where($primaryKey, $data[$primaryKey] ?? null)
                ->exists();

            if ($exists) {
                $hq->table($table)
                    ->where($primaryKey, $data[$primaryKey])
                    ->update($data);
            } else {
                $hq->table($table)->insert($data);
            }

            Log::debug("DistributedDB: Synced {$table} record #{$data[$primaryKey]} to HQ");
        } catch (\Exception $e) {
            Log::error("DistributedDB: Failed to sync {$table} to HQ: " . $e->getMessage() . ". Logging to pending_hq_syncs.");

            try {
                DB::connection($resolvedConn)->table('pending_hq_syncs')->insert(
                    self::outboxData($table, $data[$primaryKey] ?? 0, 'insert', $data)
                );
            } catch (\Exception $innerEx) {
                Log::critical("DistributedDB: Outbox failure! Unable to save pending HQ sync: " . $innerEx->getMessage());
            }
        }
    }

    /**
     * Sync a soft-deleted record to HQ.
     * Queues locally if HQ is offline.
     */
    public static function syncDeleteToHQ(string $table, int $id, string $primaryKey = 'id'): void
    {
        $resolvedConn = Config::get('database.default');

        try {
            DB::connection(self::HQ_CONNECTION)
                ->table($table)
                ->where($primaryKey, $id)
                ->update(['deleted_at' => now()]);

            Log::debug("DistributedDB: Synced soft-delete for {$table} record #{$id} to HQ");
        } catch (\Exception $e) {
            Log::error("DistributedDB: Failed to sync delete to HQ: " . $e->getMessage() . ". Logging to pending_hq_syncs.");

            try {
                DB::connection($resolvedConn)->table('pending_hq_syncs')->insert(
                    self::outboxData($table, $id, 'delete', ['is_soft' => true])
                );
            } catch (\Exception $innerEx) {
                Log::critical("DistributedDB: Outbox delete failure: " . $innerEx->getMessage());
            }
        }
    }

    /**
     * Write a record to BOTH the current branch DB and HQ simultaneously.
     * If HQ is down, records sync in branch outbox.
     * Returns the created record ID from the branch DB.
     */
    public static function dualWrite(string $table, array $data, ?string $branchConnection = null): int
    {
        $connection = $branchConnection ?? Config::get('database.default');

        $id = DB::connection($connection)->table($table)->insertGetId($data);

        $data['id'] = $id;
        try {
            DB::connection(self::HQ_CONNECTION)->table($table)->insert($data);
        } catch (\Exception $e) {
            Log::error("DistributedDB: HQ dual-write failed for {$table}: " . $e->getMessage() . ". Queueing in pending_hq_syncs.");

            try {
                DB::connection($connection)->table('pending_hq_syncs')->insert(
                    self::outboxData($table, $id, 'insert', $data)
                );
            } catch (\Exception $innerEx) {
                Log::critical("DistributedDB: Failed to save dualWrite pending HQ sync: " . $innerEx->getMessage());
            }
        }

        return $id;
    }

    /**
     * Update a record in BOTH the branch DB and HQ.
     * If HQ is down, records sync in branch outbox.
     */
    public static function dualUpdate(string $table, int $id, array $data, ?string $branchConnection = null): void
    {
        $connection = $branchConnection ?? Config::get('database.default');

        DB::connection($connection)->table($table)->where('id', $id)->update($data);

        try {
            DB::connection(self::HQ_CONNECTION)->table($table)->where('id', $id)->update($data);
        } catch (\Exception $e) {
            Log::error("DistributedDB: HQ dual-update failed for {$table}#{$id}: " . $e->getMessage() . ". Queueing in pending_hq_syncs.");

            try {
                DB::connection($connection)->table('pending_hq_syncs')->insert(
                    self::outboxData($table, $id, 'update', $data)
                );
            } catch (\Exception $innerEx) {
                Log::critical("DistributedDB: Failed to save dualUpdate pending HQ sync: " . $innerEx->getMessage());
            }
        }
    }

    /**
     * Get all connection names (branch + HQ).
     */
    public static function allConnections(): array
    {
        return array_merge(self::branchConnections(), [self::HQ_CONNECTION]);
    }

    /**
     * Get all branch connection names (without HQ).
     */
    public static function branchConnections(): array
    {
        $connections = [];
        foreach (array_keys(self::BRANCHES) as $branch) {
            $connections[] = self::connectionForBranch($branch);
        }
        return $connections;
    }
}
