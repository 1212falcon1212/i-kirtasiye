<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Support\Collection;

class CartService
{
    /**
     * Get or create an active cart for the user
     */
    public function getOrCreateCart(User $user): Cart
    {
        return Cart::firstOrCreate(
            ['user_id' => $user->id, 'status' => 'active'],
            ['status' => 'active']
        );
    }

    /**
     * Get user's active cart with items
     */
    public function getCart(User $user): ?Cart
    {
        return Cart::where('user_id', $user->id)
            ->where('status', 'active')
            ->with(['items.product', 'items.offer', 'items.seller:id,business_name,nickname,city,role'])
            ->first();
    }

    /**
     * Add an item to the cart
     *
     * Role-based restrictions:
     * - Pharmacies can buy from anyone (pharmacies and companies)
     * - Sellers can only buy from retailers they have approved links with
     */
    public function addItem(Cart $cart, Offer $offer, int $quantity = 1): CartItem
    {
        $buyer = $cart->user;
        $seller = $offer->seller;

        // Check if buyer can buy from this seller
        if (!$buyer->canBuyFrom($seller)) {
            if ($buyer->isSeller()) {
                throw new \Exception('Bu kırtasiyeciden satın alabilmek için onaylanmış bir bağlantınız olmalıdır.');
            }
            throw new \Exception('Bu satıcıdan ürün satın alamazsınız.');
        }

        // Check if offer is available
        if (!$offer->isAvailable()) {
            throw new \Exception('Bu teklif mevcut değil.');
        }

        // Check stock
        if ($offer->stock < $quantity) {
            throw new \Exception('Yeterli stok bulunmuyor.');
        }

        // Check if item already exists in cart
        $existingItem = $cart->items()->where('offer_id', $offer->id)->first();

        if ($existingItem) {
            // Update quantity
            $newQuantity = $existingItem->quantity + $quantity;

            if ($offer->stock < $newQuantity) {
                throw new \Exception('Yeterli stok bulunmuyor.');
            }

            $existingItem->update(['quantity' => $newQuantity]);
            return $existingItem->fresh();
        }

        // Create new cart item
        return CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $offer->product_id,
            'offer_id' => $offer->id,
            'seller_id' => $offer->seller_id,
            'quantity' => $quantity,
            'price_at_addition' => $offer->price,
        ]);
    }

    /**
     * Update item quantity
     */
    public function updateItemQuantity(CartItem $item, int $quantity): CartItem
    {
        if ($quantity <= 0) {
            $this->removeItem($item);
            throw new \Exception('Ürün sepetten kaldırıldı.');
        }

        if (!$item->offer) {
            throw new \Exception('Teklif artık mevcut değil.');
        }

        if ($item->offer->stock < $quantity) {
            throw new \Exception('Yeterli stok bulunmuyor.');
        }

        $item->update(['quantity' => $quantity]);
        return $item->fresh();
    }

    /**
     * Remove item from cart
     */
    public function removeItem(CartItem $item): void
    {
        $item->delete();
    }

    /**
     * Clear all items from cart
     */
    public function clearCart(Cart $cart): void
    {
        $cart->items()->delete();
    }

    /**
     * Validate cart items (check stock and price changes)
     * Returns array of validation issues
     */
    public function validateCart(Cart $cart): array
    {
        $issues = [];
        $cart->load(['items.offer', 'items.product']);

        foreach ($cart->items as $item) {
            // Check if offer still exists and is available
            if (!$item->offer || !$item->offer->isAvailable()) {
                $issues[] = [
                    'item_id' => $item->id,
                    'product_name' => $item->product->name ?? 'Bilinmeyen Ürün',
                    'type' => 'unavailable',
                    'message' => 'Bu ürün artık mevcut değil.',
                ];
                continue;
            }

            // Check stock
            if ($item->offer->stock < $item->quantity) {
                $issues[] = [
                    'item_id' => $item->id,
                    'product_name' => $item->product->name,
                    'type' => 'stock',
                    'message' => "Stok yetersiz. Mevcut: {$item->offer->stock}",
                    'available_stock' => $item->offer->stock,
                ];
            }

            // Check price change
            if ($item->hasPriceChanged()) {
                $oldPrice = number_format($item->price_at_addition, 2);
                $newPrice = number_format($item->offer->price, 2);
                $issues[] = [
                    'item_id' => $item->id,
                    'product_name' => $item->product->name,
                    'type' => 'price_changed',
                    'message' => "Fiyat değişti: {$oldPrice} ₺ → {$newPrice} ₺",
                    'old_price' => $item->price_at_addition,
                    'new_price' => $item->offer->price,
                ];
            }
        }

        return $issues;
    }

    /**
     * Sync all cart item prices with current offer prices
     */
    public function syncPrices(Cart $cart): void
    {
        $cart->load('items.offer');

        foreach ($cart->items as $item) {
            if ($item->offer) {
                $item->syncPriceWithOffer();
            }
        }
    }
}
