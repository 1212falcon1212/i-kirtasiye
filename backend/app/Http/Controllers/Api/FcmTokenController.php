<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FcmTokenController extends Controller
{
    /**
     * Store or update the authenticated user's FCM token.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $user->update([
            'fcm_token' => $validated['token'],
            'fcm_token_updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'FCM token kaydedildi.',
        ]);
    }
}
