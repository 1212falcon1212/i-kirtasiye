<?php

namespace App\Observers;

use App\Jobs\NotifyPriceDropJob;
use App\Models\Offer;
use Illuminate\Support\Facades\Cache;

class OfferObserver
{
    /**
     * Clear homepage related caches when offers change
     */
    private function clearHomepageCache(): void
    {
        Cache::forget('cms.homepage.sections');
        Cache::forget('cms.featured_sections');

        // Clear best sellers and recommended with different limits
        foreach ([6, 8, 10, 12, 15, 20] as $limit) {
            Cache::forget("cms.homepage.best_sellers.{$limit}");
            Cache::forget("cms.homepage.recommended.{$limit}");
        }
    }

    /**
     * Handle the Offer "created" event.
     */
    public function created(Offer $offer): void
    {
        $this->clearHomepageCache();
    }

    /**
     * Handle the Offer "updated" event.
     */
    public function updated(Offer $offer): void
    {
        // Check if price decreased
        if ($offer->isDirty('price') && $offer->price < $offer->getOriginal('price')) {
            $oldPrice = (float) $offer->getOriginal('price');
            NotifyPriceDropJob::dispatch($offer, $oldPrice);
        }

        $this->clearHomepageCache();
    }

    /**
     * Handle the Offer "deleted" event.
     */
    public function deleted(Offer $offer): void
    {
        $this->clearHomepageCache();
    }
}
