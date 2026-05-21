<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class GeneratePdfStatement implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    public $user;
    public $year;
    public $month;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, int $year, int $month)
    {
        $this->user = $user;
        $this->year = $year;
        $this->month = $month;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $filename = "statements/stmt_{$this->user->id}_{$this->year}_{$this->month}.pdf";
            
            // In a real app we'd query LedgerEntry and build HTML to PDF
            Log::info("Generated PDF statement for User {$this->user->id}: {$filename}");
            
            // Then dispatch a Notification
        } catch (\Exception $e) {
            Log::error("Failed to generate statement for user {$this->user->id}: " . $e->getMessage());
        }
    }
}
