<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\PendingTransfer;
use App\Services\DistributedDatabaseService;
use App\Services\TransactionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReleaseOrphanedHolds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:release-orphaned-holds {--dry-run : Print details without making actual updates}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit all branch databases, detect orphaned funds in hold_amount, and release them to restore available balances';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('');
        $this->info('========================================================');
        $this->info('   Distributed Bank — Orphaned Holds Audit & Cleanup    ');
        $this->info('========================================================');
        $this->info('');

        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn('⚠️ Running in DRY RUN mode. No modifications will be written to the database.');
            $this->info('');
        }

        $hqConn = DistributedDatabaseService::getHqConnection();
        if (!DistributedDatabaseService::isConnectionOnline($hqConn)) {
            $this->error('❌ HQ database is offline. Cannot perform cross-branch pending transfer audits.');
            return 1;
        }

        $branches = DistributedDatabaseService::branchDisplayNames(); // ['erbil' => 'Erbil', ...]
        $transactionService = app(TransactionService::class);

        $totalAudited = 0;
        $totalCleaned = 0;
        $totalReleasedFunds = 0;

        foreach ($branches as $branchKey => $branchName) {
            $this->info("Auditing branch: {$branchName}...");
            $branchConn = DistributedDatabaseService::connectionForBranch($branchKey);

            if (!DistributedDatabaseService::isConnectionOnline($branchConn)) {
                $this->warn("  ⚠️ Branch connection '{$branchConn}' is offline. Skipping.");
                $this->info('');
                continue;
            }

            try {
                // Get all accounts on this branch with hold_amount > 0
                $accounts = DB::connection($branchConn)
                    ->table('accounts')
                    ->where('hold_amount', '>', 0)
                    ->get();

                if ($accounts->isEmpty()) {
                    $this->info("  ✓ No held funds detected on this branch.");
                    $this->info('');
                    continue;
                }

                foreach ($accounts as $account) {
                    $totalAudited++;
                    $this->info("  Analyzing Account #{$account->id} ({$account->account_number}) | Name: {$account->account_name}");
                    $this->info("    - Local Database Hold Amount: {$account->currency} " . number_format($account->hold_amount, 2));

                    // Query active pending transfers from HQ connection for this account
                    $activeTransfers = DB::connection($hqConn)
                        ->table('pending_transfers')
                        ->where('sender_account_id', $account->id)
                        ->where('status', 'pending')
                        ->get();

                    $activeHoldSum = $activeTransfers->sum('amount');
                    $this->info("    - HQ Active Pending Transfers Count: " . $activeTransfers->count() . " (Sum: {$account->currency} " . number_format($activeHoldSum, 2) . ")");

                    $orphanedAmount = $account->hold_amount - $activeHoldSum;

                    if ($orphanedAmount > 0) {
                        $this->warn("    ⚠️ DETECTED ORPHANED HOLD of {$account->currency} " . number_format($orphanedAmount, 2) . "!");
                        
                        if ($dryRun) {
                            $this->info("    [DRY RUN] Would release {$account->currency} " . number_format($orphanedAmount, 2) . " back to available balance.");
                        } else {
                            // Resolve the Eloquent model on the specific branch connection
                            $accountModel = Account::on($branchConn)->find($account->id);

                            if ($accountModel) {
                                // Call TransactionService to release the excess hold
                                $transactionService->releaseHold($accountModel, $orphanedAmount);

                                // Explicitly sync updated account to HQ to ensure consistency
                                $accountModel->refresh();
                                DistributedDatabaseService::syncRecordToHQ('accounts', $accountModel->getAttributes());

                                $this->info("    ✅ SUCCESS: Released hold of {$account->currency} " . number_format($orphanedAmount, 2) . ". Balance restored!");
                                
                                Log::info("ReleaseOrphanedHolds: Automatically released orphaned hold of {$account->currency} {$orphanedAmount} for Account #{$account->id} ({$account->account_number}) on branch '{$branchKey}'");

                                $totalCleaned++;
                                $totalReleasedFunds += $orphanedAmount;
                            } else {
                                $this->error("    ❌ FAILED: Unable to instantiate Account model on connection {$branchConn} for ID {$account->id}");
                            }
                        }
                    } else {
                        $this->info("    ✓ Hold is completely valid and synchronized.");
                    }
                }
            } catch (\Exception $e) {
                $this->error("  ❌ Error auditing branch '{$branchName}': " . $e->getMessage());
            }
            $this->info('');
        }

        $this->info('========================================================');
        $this->info('   Audit & Cleanup Summary:                             ');
        $this->info("     - Accounts Audited:        {$totalAudited}");
        $this->info("     - Accounts Corrected:      {$totalCleaned}");
        $this->info("     - Funds Released:          USD " . number_format($totalReleasedFunds, 2));
        $this->info('========================================================');
        $this->info('');

        return 0;
    }
}
