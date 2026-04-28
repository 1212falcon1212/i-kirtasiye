<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    /**
     * Get user's wishlist items.
     */
    public function index()
    {
        $items = Wishlist::where('user_id', Auth::id())
            ->with([
                'product' => function ($query) {
                    // Include category and basic product info
                    $query->select('id', 'name', 'barcode', 'brand', 'image', 'category_id')
                        ->with('category:id,name');
                }
            ])
            ->latest()
            ->paginate(20)
            ->through(function ($item) {
                if ($item->product) {
                    $item->product->append('lowest_price');
                }
                return $item;
            });

        return response()->json($items);
    }

    /**
     * Add or remove item from wishlist.
     */
    public function toggle(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'target_price' => 'nullable|numeric|min:0'
        ]);

        $userId = Auth::id();
        $productId = $request->product_id;

        $wishlistItem = Wishlist::where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();

        if ($wishlistItem) {
            $wishlistItem->delete();
            return response()->json([
                'message' => 'Ürün takip listesinden çıkarıldı.',
                'in_wishlist' => false
            ]);
        }

        Wishlist::create([
            'user_id' => $userId,
            'product_id' => $productId,
            'target_price' => $request->target_price
        ]);

        return response()->json([
            'message' => 'Ürün takip listesine eklendi.',
            'in_wishlist' => true
        ]);
    }
}
