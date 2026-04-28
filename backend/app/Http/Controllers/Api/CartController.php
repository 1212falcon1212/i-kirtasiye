<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Get current user's cart
     */
    public function index(Request $request): JsonResponse
    {
        $cart = $this->cartService->getCart($request->user());

        if (!$cart) {
            return response()->json([
                'cart' => null,
                'items' => [],
                'items_by_seller' => [],
                'item_count' => 0,
                'total' => 0,
            ]);
        }

        return response()->json([
            'cart' => $cart,
            'items' => $cart->items,
            'items_by_seller' => $cart->items_by_seller,
            'item_count' => $cart->item_count,
            'total' => $cart->total,
        ]);
    }

    /**
     * Add item to cart
     *
     * Role restrictions applied:
     * - Pharmacies can buy from anyone (pharmacies and companies)
     * - Companies can only buy from pharmacies they have approved links with
     */
    public function addItem(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'offer_id' => 'required|exists:offers,id',
            'quantity' => 'integer|min:1|max:100',
        ]);

        $offer = Offer::with('seller')->findOrFail($validated['offer_id']);
        $seller = $offer->seller;
        $quantity = $validated['quantity'] ?? 1;

        // Check if user can buy from this seller
        if (!$user->canBuyFrom($seller)) {
            // Company without approved link
            if ($user->isSeller()) {
                if ($seller->isRetailer()) {
                    return response()->json([
                        'message' => 'Bu kırtasiyeciden satın alabilmek için onaylanmış bir bağlantınız olmalıdır.',
                        'error_code' => 'SELLER_NO_LINK',
                    ], 403);
                }
                return response()->json([
                    'message' => 'Tedarikçi hesapları sadece bağlantısı olan kırtasiyecilerden satın alma yapabilir.',
                    'error_code' => 'SELLER_CANNOT_BUY',
                ], 403);
            }
            // Other cases (shouldn't happen but just in case)
            return response()->json([
                'message' => 'Bu satıcıdan ürün satın alamazsınız.',
                'error_code' => 'CANNOT_BUY_FROM_SELLER',
            ], 403);
        }

        try {
            $cart = $this->cartService->getOrCreateCart($user);
            $item = $this->cartService->addItem($cart, $offer, $quantity);

            $cart->refresh();

            return response()->json([
                'message' => 'Ürün sepete eklendi.',
                'item' => $item->load('product', 'offer', 'seller'),
                'item_count' => $cart->item_count,
                'total' => $cart->total,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update item quantity
     */
    public function updateItem(Request $request, int $itemId): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0|max:100',
        ]);

        $cart = $this->cartService->getCart($request->user());

        if (!$cart) {
            return response()->json(['message' => 'Sepet bulunamadı.'], 404);
        }

        $item = $cart->items()->find($itemId);

        if (!$item) {
            return response()->json(['message' => 'Ürün bulunamadı.'], 404);
        }

        try {
            if ($validated['quantity'] === 0) {
                $this->cartService->removeItem($item);
                $cart->refresh();

                return response()->json([
                    'message' => 'Ürün sepetten kaldırıldı.',
                    'item_count' => $cart->item_count,
                    'total' => $cart->total,
                ]);
            }

            $item = $this->cartService->updateItemQuantity($item, $validated['quantity']);
            $cart->refresh();

            return response()->json([
                'message' => 'Miktar güncellendi.',
                'item' => $item->load('product', 'offer', 'seller'),
                'item_count' => $cart->item_count,
                'total' => $cart->total,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove item from cart
     */
    public function removeItem(Request $request, int $itemId): JsonResponse
    {
        $cart = $this->cartService->getCart($request->user());

        if (!$cart) {
            return response()->json(['message' => 'Sepet bulunamadı.'], 404);
        }

        $item = $cart->items()->find($itemId);

        if (!$item) {
            return response()->json(['message' => 'Ürün bulunamadı.'], 404);
        }

        $this->cartService->removeItem($item);
        $cart->refresh();

        return response()->json([
            'message' => 'Ürün sepetten kaldırıldı.',
            'item_count' => $cart->item_count,
            'total' => $cart->total,
        ]);
    }

    /**
     * Clear cart
     */
    public function clear(Request $request): JsonResponse
    {
        $cart = $this->cartService->getCart($request->user());

        if ($cart) {
            $this->cartService->clearCart($cart);
        }

        return response()->json([
            'message' => 'Sepet temizlendi.',
            'item_count' => 0,
            'total' => 0,
        ]);
    }

    /**
     * Validate cart (check stock and prices)
     */
    public function validate(Request $request): JsonResponse
    {
        $cart = $this->cartService->getCart($request->user());

        if (!$cart) {
            return response()->json([
                'valid' => true,
                'issues' => [],
            ]);
        }

        $issues = $this->cartService->validateCart($cart);

        return response()->json([
            'valid' => empty($issues),
            'issues' => $issues,
        ]);
    }
}
