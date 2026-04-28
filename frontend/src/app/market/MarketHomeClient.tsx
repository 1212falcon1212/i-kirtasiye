'use client';

import { useEffect, useState } from 'react';
import { HeroBanner } from '@/components/market/HeroBanner';
import { InfoChipRow } from '@/components/market/InfoChipRow';
import { DailyDealCard, type DailyDealProduct } from '@/components/market/DailyDealCard';
import { QuickOrderCard } from '@/components/market/QuickOrderCard';
import { WeeklyHighlights } from '@/components/market/WeeklyHighlights';
import { RecentSalesFeed } from '@/components/market/RecentSalesFeed';
import { CategoryGrid } from '@/components/market/CategoryGrid';
import { FeaturedSectionTabs } from '@/components/market/FeaturedSectionTabs';
import { BrandStrip } from '@/components/market/BrandStrip';
import { PromoBannerRow } from '@/components/market/PromoBannerRow';
import { productsApi, type Product } from '@/lib/api';

interface MiniProduct {
    id: number | string;
    name: string;
    brand?: string;
    image?: string;
    lowest?: number;
    href?: string;
}

function toMini(p: Product): MiniProduct {
    const lowest =
        p.lowest_price != null && Number.isFinite(Number(p.lowest_price))
            ? Number(p.lowest_price)
            : p.psf != null && Number.isFinite(Number(p.psf))
              ? Number(p.psf)
              : undefined;
    return {
        id: p.id,
        name: p.name,
        brand:
            typeof p.brand === 'string'
                ? p.brand
                : (p.brand as { name?: string } | undefined)?.name,
        image: p.image_url || p.image || undefined,
        lowest,
        href: `/market/product/${p.id}`,
    };
}

export function MarketHomeClient() {
    const [dailyDeal, setDailyDeal] = useState<DailyDealProduct | null>(null);
    const [season, setSeason] = useState<MiniProduct[]>([]);
    const [weekly, setWeekly] = useState<MiniProduct[]>([]);

    useEffect(() => {
        let active = true;
        productsApi
            .getAll({ per_page: 40, sort_by: 'offers_count' })
            .then((res) => {
                if (!active || !res.data?.products) return;
                const products = res.data.products as Product[];
                if (products.length === 0) return;

                // Daily deal: psf-lowest farkı en büyük ürün
                let bestDeal: Product | null = null;
                let bestSavings = 0;
                for (const p of products) {
                    const psf = Number(p.psf ?? 0);
                    const lowest = Number(p.lowest_price ?? 0);
                    if (psf > 0 && lowest > 0 && lowest < psf) {
                        const savings = psf - lowest;
                        if (savings > bestSavings) {
                            bestSavings = savings;
                            bestDeal = p;
                        }
                    }
                }
                const deal = bestDeal ?? products[0];
                setDailyDeal({
                    id: deal.id,
                    name: deal.name,
                    brand:
                        typeof deal.brand === 'string'
                            ? deal.brand
                            : (deal.brand as { name?: string } | undefined)?.name,
                    image: deal.image_url || deal.image || undefined,
                    psf: Number(deal.psf ?? 0),
                    lowest: Number(deal.lowest_price ?? deal.psf ?? 0),
                    sellersCount: Number(deal.offers_count ?? 0),
                    href: `/market/product/${deal.id}`,
                });

                // Weekly highlights: 6 farklı ürün
                const minis = products.map(toMini);
                setSeason(minis.slice(1, 4));
                setWeekly(minis.slice(4, 7));
            })
            .catch(() => {});
        return () => {
            active = false;
        };
    }, []);

    return (
        <div className="min-h-screen pb-20">
            <HeroBanner />
            <InfoChipRow />

            <section className="max-w-[1440px] mx-auto px-4 sm:px-6 pt-6">
                <div className="grid grid-cols-1 lg:grid-cols-[2fr_1fr] gap-4">
                    <DailyDealCard product={dailyDeal} />
                    <QuickOrderCard />
                </div>
            </section>

            <WeeklyHighlights season={season} weekly={weekly} />
            <RecentSalesFeed />
            <CategoryGrid />
            <FeaturedSectionTabs />
            <BrandStrip />
            <PromoBannerRow />
        </div>
    );
}

export default MarketHomeClient;
