<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Jobs\GeneratePdfStatement;
use Illuminate\Support\Facades\Log;

class GenerateMonthlyStatements extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statements:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate monthly PDF statements for all active users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting statement generation...");
        
        $users = User::where('status', 'active')->get();
        $month = now()->subMonth();
        
        $count = 0;
        foreach ($users as $user) {
            GeneratePdfStatement::dispatch($user, $month->year, $month->month);
            $count++;
        }
        
        $this->info("Dispatched {$count} jobs.");
        Log::info("Dispatched {$count} GeneratePdfStatement jobs for {$month->format('Y-m')}.");
    }
}
