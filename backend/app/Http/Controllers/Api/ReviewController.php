<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    /**
     * Create a review for an order item
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_item_id' => 'required|exists:order_items,id',
            'rating' => 'required|integer|min:1|max:5',
            'delivery_rating' => 'nullable|integer|min:1|max:5',
            'quality_rating' => 'nullable|integer|min:1|max:5',
            'communication_rating' => 'nullable|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ], [
            'order_item_id.required' => 'Sipariş ürünü seçmelisiniz.',
            'order_item_id.exists' => 'Geçersiz sipariş ürünü.',
            'rating.required' => 'Genel puan zorunludur.',
            'rating.min' => 'Puan en az 1 olmalıdır.',
            'rating.max' => 'Puan en fazla 5 olabilir.',
            'comment.max' => 'Yorum en fazla 1000 karakter olabilir.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Doğrulama hatası.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $orderItem = OrderItem::with('order')->findOrFail($request->input('order_item_id'));
        $order = $orderItem->order;

        // Check if user owns the order
        if ($order->user_id !== $user->id) {
            return response()->json([
                'message' => 'Bu sipariş size ait değil.',
            ], 403);
        }

        // Check if order is delivered
        if ($order->status !== 'delivered') {
            return response()->json([
                'message' => 'Sadece teslim edilmiş siparişler için yorum yapabilirsiniz.',
            ], 400);
        }

        // Check if review already exists
        if (Review::where('order_item_id', $orderItem->id)->exists()) {
            return response()->json([
                'message' => 'Bu ürün için zaten yorum yaptınız.',
            ], 400);
        }

        $review = Review::create([
            'order_item_id' => $orderItem->id,
            'product_id' => $orderItem->product_id,
            'seller_id' => $orderItem->seller_id,
            'buyer_id' => $user->id,
            'rating' => $request->input('rating'),
            'delivery_rating' => $request->input('delivery_rating'),
            'quality_rating' => $request->input('quality_rating'),
            'communication_rating' => $request->input('communication_rating'),
            'comment' => $request->input('comment'),
            'status' => Review::STATUS_PENDING,
        ]);

        $review->load(['product:id,name,barcode,image', 'seller:id,business_name,nickname']);

        return response()->json([
            'message' => 'Yorumunuz başarıyla gönderildi. Yönetici onayından sonra yayınlanacaktır.',
            'review' => $review,
        ], 201);
    }

    /**
     * Get reviews received by the authenticated seller
     */
    public function sellerReviews(Request $request): JsonResponse
    {
        $status = $request->input('status');
        $perPage = $request->input('per_page', 15);

        $query = $request->user()->reviewsReceived()
            ->with([
                'buyer:id,business_name,nickname,city',
                'product:id,name,barcode,image',
            ]);

        if ($status) {
            $query->where('status', $status);
        } else {
            // Default to approved reviews for seller view
            $query->where('status', Review::STATUS_APPROVED);
        }

        $reviews = $query->latest()->paginate($perPage);

        // Get seller ratings summary
        $ratings = Review::getSellerRatings($request->user()->id);

        return response()->json([
            'reviews' => $reviews->items(),
            'ratings' => $ratings,
            'pagination' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    /**
     * Add seller reply to a review
     */
    public function reply(Request $request, Review $review): JsonResponse
    {
        // Check ownership
        if ($review->seller_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Bu yoruma yanıt verme yetkiniz yok.',
            ], 403);
        }

        // Check if already replied
        if ($review->seller_reply) {
            return response()->json([
                'message' => 'Bu yoruma zaten yanıt verdiniz.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'reply' => 'required|string|max:1000',
        ], [
            'reply.required' => 'Yanıt metni zorunludur.',
            'reply.max' => 'Yanıt en fazla 1000 karakter olabilir.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Doğrulama hatası.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $review->addReply($request->input('reply'));

        return response()->json([
            'message' => 'Yanıtınız başarıyla eklendi.',
            'review' => $review,
        ]);
    }

    /**
     * Get approved reviews for a product
     */
    public function productReviews(Request $request, int $productId): JsonResponse
    {
        $perPage = $request->input('per_page', 10);

        $reviews = Review::forProduct($productId)
            ->approved()
            ->with([
                'buyer:id,business_name,nickname',
                'seller:id,business_name,nickname',
            ])
            ->latest()
            ->paginate($perPage);

        // Calculate product average ratings
        $allReviews = Review::forProduct($productId)->approved()->get();
        $averageRating = $allReviews->avg('rating') ?: 0;
        $ratingCounts = [
            5 => $allReviews->where('rating', 5)->count(),
            4 => $allReviews->where('rating', 4)->count(),
            3 => $allReviews->where('rating', 3)->count(),
            2 => $allReviews->where('rating', 2)->count(),
            1 => $allReviews->where('rating', 1)->count(),
        ];

        return response()->json([
            'reviews' => $reviews->items(),
            'summary' => [
                'average_rating' => round($averageRating, 1),
                'total_reviews' => $allReviews->count(),
                'rating_counts' => $ratingCounts,
            ],
            'pagination' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    /**
     * Get reviews given by the authenticated buyer
     */
    public function myReviews(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);

        $reviews = $request->user()->reviewsGiven()
            ->with([
                'seller:id,business_name,nickname,city',
                'product:id,name,barcode,image',
            ])
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'reviews' => $reviews->items(),
            'pagination' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    /**
     * Get reviewable items (delivered order items without reviews)
     */
    public function reviewableItems(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get delivered order items that don't have reviews yet
        $items = OrderItem::whereHas('order', function ($query) use ($user) {
            $query->where('user_id', $user->id)
                ->where('status', 'delivered');
        })
            ->whereDoesntHave('review')
            ->with([
                'product:id,name,barcode,brand,image',
                'seller:id,business_name,nickname,city',
                'order:id,order_number,delivered_at',
            ])
            ->latest()
            ->get();

        return response()->json([
            'items' => $items,
            'count' => $items->count(),
        ]);
    }
}
