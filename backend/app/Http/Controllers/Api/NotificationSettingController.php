<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationSettingController extends Controller
{
    /**
     * Get user's notification settings.
     */
    public function index()
    {
        $settings = NotificationSetting::where('user_id', Auth::id())->get();
        return response()->json(['settings' => $settings]);
    }

    /**
     * Update a notification setting.
     */
    public function update(Request $request)
    {
        $request->validate([
            'channel' => 'required|in:sms,email,push',
            'type' => 'required|string',
            'is_enabled' => 'required|boolean'
        ]);

        $setting = NotificationSetting::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'channel' => $request->channel,
                'type' => $request->type
            ],
            [
                'is_enabled' => $request->is_enabled
            ]
        );

        return response()->json([
            'message' => 'Bildirim ayarı güncellendi.',
            'setting' => $setting
        ]);
    }
}
