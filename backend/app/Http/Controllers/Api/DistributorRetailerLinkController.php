<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DistributorRetailerLink;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DistributorRetailerLinkController extends Controller
{
    /**
     * Get list of retailers (kirtasiyeci) that a distributor can send a link request to.
     */
    public function listRetailers(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isDistributor()) {
            return response()->json([
                'message' => 'Sadece tedarikçiler bu işlemi yapabilir',
            ], 403);
        }

        $query = User::retailers()
            ->where('verification_status', 'approved')
            ->select('id', 'business_name', 'nickname', 'city', 'vergi_no');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                    ->orWhere('nickname', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('vergi_no', 'like', "%{$search}%");
            });
        }

        $retailers = $query->paginate(20);

        $retailerIds = $retailers->pluck('id')->toArray();
        $existingLinks = $user->sentLinkRequests()
            ->whereIn('retailer_id', $retailerIds)
            ->get()
            ->keyBy('retailer_id');

        $retailers->getCollection()->transform(function ($retailer) use ($existingLinks) {
            $link = $existingLinks->get($retailer->id);
            $retailer->link_status = $link ? $link->status : null;
            $retailer->link_id = $link ? $link->id : null;

            return $retailer;
        });

        return response()->json($retailers);
    }

    /**
     * Send a link request to a retailer (for distributors).
     */
    public function sendRequest(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isDistributor()) {
            return response()->json([
                'message' => 'Sadece tedarikçiler istek gönderebilir',
            ], 403);
        }

        $validated = $request->validate([
            'retailer_id' => 'required|exists:users,id',
            'message' => 'nullable|string|max:500',
        ]);

        $retailer = User::find($validated['retailer_id']);
        if (! $retailer || ! $retailer->isRetailer()) {
            return response()->json([
                'message' => 'Geçersiz kırtasiyeci',
            ], 400);
        }

        $existingLink = DistributorRetailerLink::where('distributor_id', $user->id)
            ->where('retailer_id', $retailer->id)
            ->first();

        if ($existingLink) {
            if ($existingLink->status === DistributorRetailerLink::STATUS_PENDING) {
                return response()->json([
                    'message' => 'Bu kırtasiyeciye zaten bekleyen bir isteğiniz var',
                ], 400);
            }
            if ($existingLink->status === DistributorRetailerLink::STATUS_APPROVED) {
                return response()->json([
                    'message' => 'Bu kırtasiyeci ile zaten bağlantınız var',
                ], 400);
            }
            $existingLink->update([
                'status' => DistributorRetailerLink::STATUS_PENDING,
                'message' => $validated['message'] ?? null,
                'rejection_reason' => null,
                'rejected_at' => null,
            ]);
            $link = $existingLink;
        } else {
            $link = DistributorRetailerLink::create([
                'distributor_id' => $user->id,
                'retailer_id' => $retailer->id,
                'message' => $validated['message'] ?? null,
            ]);
        }

        return response()->json([
            'message' => 'İstek başarıyla gönderildi',
            'link' => $link->load('retailer:id,business_name,city'),
        ]);
    }

    /**
     * Get distributor's sent link requests.
     */
    public function mySentRequests(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isDistributor()) {
            return response()->json([
                'message' => 'Sadece tedarikçiler bu işlemi yapabilir',
            ], 403);
        }

        $links = $user->sentLinkRequests()
            ->with('retailer:id,business_name,nickname,city,vergi_no')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($links);
    }

    /**
     * Cancel a pending request (for distributors).
     */
    public function cancelRequest(Request $request, DistributorRetailerLink $link): JsonResponse
    {
        $user = $request->user();

        if ($link->distributor_id !== $user->id) {
            return response()->json([
                'message' => 'Bu istek size ait değil',
            ], 403);
        }

        if (! $link->isPending()) {
            return response()->json([
                'message' => 'Sadece bekleyen istekler iptal edilebilir',
            ], 400);
        }

        $link->delete();

        return response()->json([
            'message' => 'İstek iptal edildi',
        ]);
    }

    /**
     * Get retailer's received link requests.
     */
    public function myReceivedRequests(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isRetailer()) {
            return response()->json([
                'message' => 'Sadece kırtasiyeciler bu işlemi yapabilir',
            ], 403);
        }

        $status = $request->query('status');

        $query = $user->receivedLinkRequests()
            ->with('distributor:id,business_name,nickname,email,phone');

        if ($status) {
            $query->where('status', $status);
        }

        $links = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($links);
    }

    /**
     * Approve a link request (for retailers).
     */
    public function approveRequest(Request $request, DistributorRetailerLink $link): JsonResponse
    {
        $user = $request->user();

        if ($link->retailer_id !== $user->id) {
            return response()->json([
                'message' => 'Bu istek size ait değil',
            ], 403);
        }

        if (! $link->isPending()) {
            return response()->json([
                'message' => 'Bu istek zaten işlem görmüş',
            ], 400);
        }

        $link->approve();

        return response()->json([
            'message' => 'İstek onaylandı',
            'link' => $link->load('distributor:id,business_name,email'),
        ]);
    }

    /**
     * Reject a link request (for retailers).
     */
    public function rejectRequest(Request $request, DistributorRetailerLink $link): JsonResponse
    {
        $user = $request->user();

        if ($link->retailer_id !== $user->id) {
            return response()->json([
                'message' => 'Bu istek size ait değil',
            ], 403);
        }

        if (! $link->isPending()) {
            return response()->json([
                'message' => 'Bu istek zaten işlem görmüş',
            ], 400);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $link->reject($validated['reason'] ?? null);

        return response()->json([
            'message' => 'İstek reddedildi',
            'link' => $link,
        ]);
    }

    /**
     * Revoke an approved link (for retailers).
     */
    public function revokeLink(Request $request, DistributorRetailerLink $link): JsonResponse
    {
        $user = $request->user();

        if ($link->retailer_id !== $user->id) {
            return response()->json([
                'message' => 'Bu bağlantı size ait değil',
            ], 403);
        }

        if (! $link->isApproved()) {
            return response()->json([
                'message' => 'Sadece onaylanmış bağlantılar iptal edilebilir',
            ], 400);
        }

        $link->delete();

        return response()->json([
            'message' => 'Bağlantı iptal edildi',
        ]);
    }

    /**
     * Get count of pending requests for retailer (for badge).
     */
    public function pendingCount(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isRetailer()) {
            return response()->json(['count' => 0]);
        }

        $count = $user->pendingLinkRequests()->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Get list of approved retailer IDs for a distributor.
     */
    public function approvedRetailerIds(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isDistributor()) {
            return response()->json(['retailer_ids' => []]);
        }

        $retailerIds = $user->sentLinkRequests()
            ->where('status', DistributorRetailerLink::STATUS_APPROVED)
            ->pluck('retailer_id')
            ->toArray();

        return response()->json(['retailer_ids' => $retailerIds]);
    }
}
