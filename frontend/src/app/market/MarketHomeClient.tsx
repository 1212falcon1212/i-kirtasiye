'use client';

import { HeroBanner } from '@/components/market/HeroBanner';
import { InfoChipRow } from '@/components/market/InfoChipRow';
import { DailyDealCard } from '@/components/market/DailyDealCard';
import { QuickOrderCard } from '@/components/market/QuickOrderCard';
import { WeeklyHighlights } from '@/components/market/WeeklyHighlights';
import { RecentSalesFeed } from '@/components/market/RecentSalesFeed';
import { CategoryGrid } from '@/components/market/CategoryGrid';
import { FeaturedSectionTabs } from '@/components/market/FeaturedSectionTabs';
import { BrandStrip } from '@/components/market/BrandStrip';
import { PromoBannerRow } from '@/components/market/PromoBannerRow';

export function MarketHomeClient() {
    return (
        <div className="min-h-screen pb-20">
            <HeroBanner />
            <InfoChipRow />

            <section className="max-w-[1440px] mx-auto px-4 sm:px-6 pt-6">
                <div className="grid grid-cols-1 lg:grid-cols-[2fr_1fr] gap-4">
                    <DailyDealCard />
                    <QuickOrderCard />
                </div>
            </section>

            <WeeklyHighlights />
            <RecentSalesFeed />
            <CategoryGrid />
            <FeaturedSectionTabs />
            <BrandStrip />
            <PromoBannerRow />
        </div>
    );
}

export default MarketHomeClient;
