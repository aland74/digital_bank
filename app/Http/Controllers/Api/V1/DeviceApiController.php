<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UserDevice;
use Illuminate\Http\Request;

class DeviceApiController extends Controller
{
    /**
     * Register or update FCM device token.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'device_id' => 'required|string',
            'fcm_token' => 'required|string',
            'platform' => 'required|in:ios,android',
        ]);

        $device = UserDevice::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'device_id' => $validated['device_id'],
            ],
            [
                'fcm_token' => $validated['fcm_token'],
                'platform' => $validated['platform'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'last_active_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Device registered successfully.',
            'device' => $device,
        ]);
    }

    /**
     * Remove a device registration.
     */
    public function destroy(Request $request, $deviceId)
    {
        $device = UserDevice::where('user_id', $request->user()->id)
            ->where('device_id', $deviceId)
            ->first();

        if (!$device) {
            return response()->json(['message' => 'Device not found.'], 404);
        }

        $device->delete();

        return response()->json([
            'message' => 'Device removed successfully.',
        ]);
    }
}
