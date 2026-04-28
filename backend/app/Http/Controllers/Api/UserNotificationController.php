<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubOrder;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = UserNotification::forUser($request->user()->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $unreadCount = UserNotification::forUser($request->user()->id)
            ->unread()
            ->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
            'pending_orders_count' => $this->getPendingOrdersCount($request->user()->id),
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = UserNotification::forUser($request->user()->id)
            ->unread()
            ->count();

        return response()->json([
            'unread_count' => $count,
            'pending_orders_count' => $this->getPendingOrdersCount($request->user()->id),
        ]);
    }

    /**
     * Count pending sub_orders where this user is a seller
     */
    private function getPendingOrdersCount(int $userId): int
    {
        return SubOrder::where('seller_id', $userId)
            ->where('status', 'pending')
            ->count();
    }

    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $notification = UserNotification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$notification) {
            return response()->json(['message' => 'Bildirim bulunamadi.'], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'message' => 'Bildirim okundu olarak isaretlendi.',
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        UserNotification::forUser($request->user()->id)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'message' => 'Tum bildirimler okundu olarak isaretlendi.',
        ]);
    }
}
