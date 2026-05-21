<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Webhook;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWebhookJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    public $webhook;
    public $payload;

    /**
     * Create a new job instance.
     */
    public function __construct(Webhook $webhook, array $payload)
    {
        $this->webhook = $webhook;
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $headers = [];
            if ($this->webhook->secret) {
                $signature = hash_hmac('sha256', json_encode($this->payload), $this->webhook->secret);
                $headers['X-DigitalBank-Signature'] = $signature;
            }

            $response = Http::withHeaders($headers)
                ->timeout(5)
                ->post($this->webhook->url, $this->payload);

            if ($response->failed()) {
                Log::warning("Webhook failed to {$this->webhook->url} with status {$response->status()}");
            }
        } catch (\Exception $e) {
            Log::error("Webhook error for {$this->webhook->url}: " . $e->getMessage());
        }
    }
}
