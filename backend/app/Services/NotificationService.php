<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Offer;
use App\Models\Order;
use App\Models\SubOrder;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Notification\Push\FirebaseService;
use Illuminate\Support\Facades\Log;

/**
 * In-app bildirim servisi (DB kaydi).
 *
 * E-posta bildirimleri Event/Listener/Job zincirleriyle gonderilir:
 *
 * | Bildirim Turu              | DB (NotificationService) | E-posta (Event sistemi)           |
 * |---------------------------|--------------------------|-----------------------------------|
 * | Siparis olusturuldu        | notifyOrderCreated()     | OrderCreated -> SendOrderEmails   |
 * | Odeme basarili             | notifyOrderCreated()*    | OrderPaid -> SendPaymentSuccessEmail |
 * | Odeme basarisiz            | -                        | PaymentFailed -> SendPaymentFailedEmail |
 * | Alt siparis onaylandi      | notifySubOrderConfirmed()| -                                 |
 * | Alt siparis kargoda        | notifySubOrderShipped()  | OrderStatusChanged -> SendShippingEmail |
 * | Alt siparis teslim edildi  | notifySubOrderDelivered()| -                                 |
 * | Alici teslimati onayladi   | notifyBuyerConfirmedDelivery() | -                          |
 * | Siparis iptal edildi       | notifyOrderCancelled()   | -                                 |
 * | Iade talebi olusturuldu    | notifyReturnRequestCreated() | ReturnRequestCreated -> SendReturnRequestEmail |
 * | Iade talebi onaylandi      | notifyReturnRequestApproved() | -                           |
 * | Iade talebi reddedildi     | notifyReturnRequestRejected() | -                           |
 * | Fiyat dustu                | notifyPriceDrop()        | -                                 |
 * | Hos geldiniz               | notifyWelcome()          | -                                 |
 * | Bakiye serbest birakildi   | notifyWalletBalanceReleased() | -                           |
 *
 * (*) notifyOrderCreated() PaymentController::callback() icinde cagirilir (odeme sonrasi).
 *
 * NOT: E-posta zaten Event sistemiyle gonderilen bildirimler icin burada tekrar
 * e-posta gonderilmez (duplikasyon onlenir).
 */
class NotificationService
{
    public function notifyOrderCreated(Order $order): void
    {
        // Notify buyer
        UserNotification::createForUser(
            $order->user_id,
            'order_created',
            'Siparişiniz alındı',
            "#{$order->order_number} numaralı siparişiniz alındı. Satıcı onayını bekliyorsunuz.",
            [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'url' => '/market/hesabim?tab=siparislerim&sub=satin-aldiklarim',
            ]
        );

        $this->sendPushIfAvailable($order->user_id, 'Siparişiniz alındı', "#{$order->order_number} numaralı siparişiniz alındı.");

        // Notify each seller in the order
        $sellerIds = $order->items->pluck('seller_id')->unique();
        foreach ($sellerIds as $sellerId) {
            $buyerName = $order->user?->business_name ?? $order->user?->nickname ?? 'Alıcı';
            $sellerTotal = $order->items->where('seller_id', $sellerId)->sum('total_price');
            $formattedTotal = number_format($sellerTotal, 2, ',', '.').' TL';

            UserNotification::createForUser(
                $sellerId,
                'new_order',
                'Yeni sipariş geldi!',
                "#{$order->order_number} — {$buyerName} tarafından {$formattedTotal} tutarında yeni sipariş. Onaylamanız bekleniyor.",
                [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'url' => '/market/hesabim?tab=siparislerim&sub=sattiklarim',
                ]
            );

            $this->sendPushIfAvailable($sellerId, 'Yeni sipariş geldi!', "#{$order->order_number} — {$buyerName} tarafından {$formattedTotal} tutarında yeni sipariş.");
        }
    }

    public function notifyOrderConfirmed(Order $order): void
    {
        UserNotification::createForUser(
            $order->user_id,
            'order_confirmed',
            'Siparişiniz onaylandı',
            "#{$order->order_number} numaralı siparişiniz satıcı tarafından onaylandı.",
            [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'url' => '/market/hesabim?tab=siparislerim&sub=satin-aldiklarim',
            ]
        );
    }

    public function notifyOrderShipped(Order $order): void
    {
        UserNotification::createForUser(
            $order->user_id,
            'order_shipped',
            'Siparişiniz kargoda',
            "#{$order->order_number} numaralı siparişiniz kargoya verildi.",
            [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'url' => '/market/hesabim?tab=siparislerim',
            ]
        );
    }

    public function notifyOrderDelivered(Order $order): void
    {
        UserNotification::createForUser(
            $order->user_id,
            'order_delivered',
            'Siparişiniz teslim edildi',
            "#{$order->order_number} numaralı siparişiniz teslim edildi. Teslimatınızı onaylamak için sipariş detayına gidin.",
            [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'url' => '/market/hesabim?tab=siparislerim',
            ]
        );
    }

    // ── Sub-order based notifications ──

    public function notifySubOrderConfirmed(Order $order, SubOrder $subOrder, string $sellerName): void
    {
        UserNotification::createForUser(
            $order->user_id,
            'order_confirmed',
            'Siparişiniz onaylandı',
            "#{$order->order_number} — {$sellerName} siparişinizi onayladı.",
            [
                'order_id' => $order->id,
                'sub_order_id' => $subOrder->id,
                'order_number' => $order->order_number,
                'url' => '/market/hesabim?tab=siparislerim&sub=satin-aldiklarim',
            ]
        );

        $this->sendPushIfAvailable($order->user_id, 'Siparişiniz onaylandı', "#{$order->order_number} — {$sellerName} siparişinizi onayladı.");
    }

    public function notifySubOrderShipped(Order $order, SubOrder $subOrder, string $sellerName): void
    {
        UserNotification::createForUser(
            $order->user_id,
            'order_shipped',
            'Siparişiniz kargoda',
            "#{$order->order_number} — {$sellerName} siparişinizi kargoya verdi.",
            [
                'order_id' => $order->id,
                'sub_order_id' => $subOrder->id,
                'order_number' => $order->order_number,
                'url' => '/market/hesabim?tab=siparislerim',
            ]
        );

        $this->sendPushIfAvailable($order->user_id, 'Siparişiniz kargoda', "#{$order->order_number} — {$sellerName} siparişinizi kargoya verdi.");
    }

    public function notifySubOrderDelivered(Order $order, SubOrder $subOrder, string $sellerName): void
    {
        UserNotification::createForUser(
            $order->user_id,
            'order_delivered',
            'Siparişiniz teslim edildi',
            "#{$order->order_number} — {$sellerName} siparişiniz teslim edildi. Teslimatınızı onaylamak için sipariş detayına gidin.",
            [
                'order_id' => $order->id,
                'sub_order_id' => $subOrder->id,
                'order_number' => $order->order_number,
                'url' => '/market/hesabim?tab=siparislerim',
            ]
        );

        $this->sendPushIfAvailable($order->user_id, 'Siparişiniz teslim edildi', "#{$order->order_number} — {$sellerName} siparişiniz teslim edildi.");
    }

    public function notifyBuyerConfirmedDelivery(Order $order, ?array $sellerIds = null): void
    {
        $holdDays = (int) \App\Models\Setting::getValue('payment.payout_hold_days', 35);

        // Use provided seller IDs or fall back to all sellers in order
        $targetSellerIds = $sellerIds ?? $order->items->pluck('seller_id')->unique()->toArray();

        foreach ($targetSellerIds as $sellerId) {
            UserNotification::createForUser(
                $sellerId,
                'buyer_confirmed',
                'Alıcı teslimatı onayladı',
                "#{$order->order_number} numaralı siparişin alıcısı teslimatı onayladı. Hakedişiniz {$holdDays} gün sonra hesabınıza aktarılacak.",
                [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'url' => '/market/hesabim?tab=sattiklarim',
                ]
            );
        }
    }

    public function notifyWalletBalanceReleased(User $seller, Order $order): void
    {
        UserNotification::createForUser(
            $seller->id,
            'wallet_released',
            'Bakiye serbest bırakıldı',
            "#{$order->order_number} numaralı siparişin hakedişiniz kullanılabilir bakiyenize aktarıldı.",
            [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'url' => '/market/hesabim?tab=cuzdan',
            ]
        );
    }

    public function notifyOrderCancelled(Order $order): void
    {
        UserNotification::createForUser(
            $order->user_id,
            'order_cancelled',
            'Siparişiniz iptal edildi',
            "#{$order->order_number} numaralı siparişiniz iptal edildi.",
            [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'url' => '/market/hesabim?tab=siparislerim',
            ]
        );
    }

    public function notifyPriceDrop(Offer $offer, User $user, float $oldPrice): void
    {
        $productName = $offer->product?->name ?? 'Ürün';
        $newPrice = number_format($offer->price, 2, ',', '.');

        UserNotification::createForUser(
            $user->id,
            'price_drop',
            'Fiyat düştü!',
            "Takip ettiğiniz {$productName} ürünü {$newPrice} TL'ye düştü.",
            [
                'product_id' => $offer->product_id,
                'offer_id' => $offer->id,
                'old_price' => $oldPrice,
                'new_price' => $offer->price,
                'url' => "/market/product/{$offer->product_id}",
            ]
        );
    }

    public function notifyWelcome(User $user): void
    {
        UserNotification::createForUser(
            $user->id,
            'welcome',
            'Hoşgeldiniz!',
            'i-kirtasiye.com ailesine hoşgeldiniz. İhtiyacınız olan ürünleri uygun fiyatlarla bulabilirsiniz.',
            [
                'url' => '/market',
            ]
        );
    }

    // ── Return Request Notifications ──

    public function notifyReturnRequestCreated(int $sellerId, Order $order, string $buyerName, float $refundAmount): void
    {
        $formatted = number_format($refundAmount, 2, ',', '.').' TL';

        UserNotification::createForUser(
            $sellerId,
            'return_request',
            'Yeni iade talebi',
            "#{$order->order_number} — {$buyerName} {$formatted} tutarında iade talebi oluşturdu.",
            [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'url' => '/market/hesabim?tab=siparislerim&sub=iade-talepleri',
            ]
        );

        $this->sendPushIfAvailable($sellerId, 'Yeni iade talebi', "#{$order->order_number} — {$buyerName} {$formatted} tutarında iade talebi oluşturdu.");
    }

    public function notifyReturnRequestApproved(int $buyerId, Order $order): void
    {
        UserNotification::createForUser(
            $buyerId,
            'return_approved',
            'İade talebiniz onaylandı',
            "#{$order->order_number} numaralı siparişiniz için iade talebiniz onaylandı.",
            [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'url' => '/market/hesabim?tab=siparislerim&sub=iadelerim',
            ]
        );

        $this->sendPushIfAvailable($buyerId, 'İade talebiniz onaylandı', "#{$order->order_number} numaralı siparişiniz için iade talebiniz onaylandı.");
    }

    public function notifyReturnRequestRejected(int $buyerId, Order $order): void
    {
        UserNotification::createForUser(
            $buyerId,
            'return_rejected',
            'İade talebiniz reddedildi',
            "#{$order->order_number} numaralı siparişiniz için iade talebiniz reddedildi.",
            [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'url' => '/market/hesabim?tab=siparislerim&sub=iadelerim',
            ]
        );

        $this->sendPushIfAvailable($buyerId, 'İade talebiniz reddedildi', "#{$order->order_number} numaralı siparişiniz için iade talebiniz reddedildi.");
    }

    // ── Push Notification Helper ──

    /**
     * Send a push notification via FCM if the user has a registered token.
     *
     * @param  array<string, string>  $data
     */
    private function sendPushIfAvailable(int $userId, string $title, string $body, array $data = []): void
    {
        try {
            $user = User::find($userId);
            if ($user?->fcm_token) {
                app(FirebaseService::class)->sendToToken($user->fcm_token, $title, $body, $data);
            }
        } catch (\Throwable $e) {
            Log::warning('Push notification failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
