<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\UserDevice;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;

class CheckNewDevice
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
    public function handle(Login $event): void
    {
        $user = $event->user;
        $ip = Request::ip();
        $userAgent = Request::userAgent();
        
        $deviceId = hash('sha256', $user->id . '|' . $ip . '|' . $userAgent);
        
        $device = UserDevice::where('user_id', $user->id)
            ->where('device_id', $deviceId)
            ->first();

        if (!$device) {
            UserDevice::create([
                'user_id' => $user->id,
                'device_id' => $deviceId,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'last_active_at' => now(),
            ]);

            AuditLog::log('new_device_login', [
                'model_type' => 'User',
                'model_id' => $user->id,
                'new_values' => [
                    'ip_address' => $ip,
                    'user_agent' => $userAgent,
                ],
                'severity' => 'medium',
            ]);
        } else {
            $device->update(['last_active_at' => now()]);
        }
    }
}
