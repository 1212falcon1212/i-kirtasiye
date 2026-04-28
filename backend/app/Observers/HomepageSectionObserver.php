<?php

namespace App\Observers;

use App\Models\HomepageSection;
use Illuminate\Support\Facades\Cache;

class HomepageSectionObserver
{
    /**
     * Clear section related caches
     */
    private function clearSectionCache(): void
    {
        Cache::forget('cms.homepage.sections');
        Cache::forget('cms.featured_sections');
    }

    public function created(HomepageSection $section): void
    {
        $this->clearSectionCache();
    }

    public function updated(HomepageSection $section): void
    {
        $this->clearSectionCache();
    }

    public function deleted(HomepageSection $section): void
    {
        $this->clearSectionCache();
    }
}
