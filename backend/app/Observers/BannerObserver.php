<?php

namespace App\Observers;

use App\Models\Banner;
use Illuminate\Support\Facades\Cache;

class BannerObserver
{
    /**
     * Clear banner related caches
     */
    private function clearBannerCache(Banner $banner): void
    {
        Cache::forget('cms.layout');
        Cache::forget("cms.banners.{$banner->location}");
        
        // Clear all possible banner locations
        foreach (['home_hero', 'home_middle', 'home_brand', 'home_grid', 'home_bottom'] as $location) {
            Cache::forget("cms.banners.{$location}");
        }
    }

    public function created(Banner $banner): void
    {
        $this->clearBannerCache($banner);
    }

    public function updated(Banner $banner): void
    {
        $this->clearBannerCache($banner);
    }

    public function deleted(Banner $banner): void
    {
        $this->clearBannerCache($banner);
    }
}
