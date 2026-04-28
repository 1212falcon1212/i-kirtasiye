<?php

namespace App\Jobs;

use App\Models\Offer;
use App\Models\Wishlist;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifyPriceDropJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Offer $offer,
        public float $oldPrice = 0
    ) {
    }

    public function handle(NotificationService $notificationService): void
    {
        Log::info('Price drop detected for offer', ['offer_id' => $this->offer->id, 'new_price' => $this->offer->price]);

        $wishlists = Wishlist::where('product_id', $this->offer->product_id)
            ->with(['user'])
            ->get();

        foreach ($wishlists as $wishlist) {
            if ($wishlist->target_price && $this->offer->price > $wishlist->target_price) {
                continue;
            }

            $notificationService->notifyPriceDrop($this->offer, $wishlist->user, $this->oldPrice);

            Log::info('Notified user about price drop', [
                'user_id' => $wishlist->user_id,
                'product_id' => $wishlist->product_id,
            ]);
        }
    }
}
