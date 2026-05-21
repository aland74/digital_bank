<?php

namespace App\Listeners;

use App\Events\TransactionCompleted;
use App\Models\Webhook;
use App\Jobs\SendWebhookJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class DispatchWebhooks implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TransactionCompleted $event): void
    {
        $transaction = $event->transaction;
        $accountId = $transaction->account_id;
        $account = \App\Models\Account::find($accountId);
        
        if (!$account) return;

        $user = $account->user;
        
        $webhooks = Webhook::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();
            
        foreach ($webhooks as $webhook) {
            $payload = [
                'event' => 'transaction.' . $event->action,
                'data' => [
                    'transaction_id' => $transaction->id,
                    'reference' => $transaction->reference_number,
                    'type' => $transaction->type,
                    'amount' => $transaction->amount,
                    'status' => $transaction->status,
                ]
            ];
            
            SendWebhookJob::dispatch($webhook, $payload);
        }
    }
}
