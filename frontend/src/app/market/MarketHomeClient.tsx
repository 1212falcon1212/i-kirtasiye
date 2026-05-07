'use client';

import { useEffect, useState } from 'react';
import dynamic from 'next/dynamic';
import { HeroBanner, type HeroBannerSlide } from '@/components/market/HeroBanner';
import { InfoChipRow } from '@/components/market/InfoChipRow';
import { DailyDealCard, type DailyDealProduct } from '@/components/market/DailyDealCard';
import { QuickOrderCard } from '@/components/market/QuickOrderCard';
import { WeeklyHighlights } from '@/components/market/WeeklyHighlights';
import { useInViewOnce } from '@/hooks/use-in-view-once';
import {
    cmsApi,
    productsApi,
    type CmsHomepageResponse,
    type Product,
    type ProductsResponse,
} from '@/lib/api';

// Below-the-fold sections — code-split into their own chunks. Each is mounted
// only after the user scrolls within 300px of its placeholder, avoiding both
// JS evaluation cost and API requests on initial load.
const RecentSalesFeed = dynamic(
    () => import('@/components/market/RecentSalesFeed').then((m) => m.RecentSalesFeed),
    { ssr: false, loading: () => <SectionSkeleton heightClass="h-44" /> },
);
const CategoryGrid = dynamic(
    () => import('@/components/market/CategoryGrid').then((m) => m.CategoryGrid),
    { ssr: false, loading: () => <SectionSkeleton heightClass="h-40" /> },
);
const FeaturedSectionTabs = dynamic(
    () => import('@/components/market/FeaturedSectionTabs').then((m) => m.FeaturedSectionTabs),
    { ssr: false, loading: () => <SectionSkeleton heightClass="h-[420px]" /> },
);
const BrandStrip = dynamic(
    () => import('@/components/market/BrandStrip').then((m) => m.BrandStrip),
    { ssr: false, loading: () => <SectionSkeleton heightClass="h-24" /> },
);
const AllProductsList = dynamic(
    () => import('@/components/market/AllProductsList').then((m) => m.AllProductsList),
    { ssr: false, loading: () => <SectionSkeleton heightClass="h-[600px]" /> },
);
const PromoBannerRow = dynamic(
    () => import('@/components/market/PromoBannerRow').then((m) => m.PromoBannerRow),
    { ssr: false, loading: () => <SectionSkeleton heightClass="h-32" /> },
);

function SectionSkeleton({ heightClass }: { heightClass: string }) {
    return (
        <div className="max-w-[1440px] mx-auto px-4 sm:px-6 pt-8">
            <div
                className={`w-full ${heightClass} rounded-lg animate-pulse`}
                style={{ background: 'var(--bg-muted)' }}
                aria-hidden
            />
        </div>
    );
}

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

function heroSlidesFromHomepage(homepage: CmsHomepageResponse | null): HeroBannerSlide[] {
    const heroes = homepage?.banners?.hero ?? [];
    return heroes.map((b) => ({
        id: b.id,
        eyebrow: b.badge_text ?? null,
        headline: b.title ?? null,
        description: b.subtitle ?? null,
        ctaLabel: b.button_text ?? null,
        ctaHref: b.link_url ?? null,
        sideImage: b.image_url ?? null,
        sideImageAlt: b.title ?? '',
    }));
}

interface DerivedProductSections {
    dailyDeal: DailyDealProduct | null;
    season: MiniProduct[];
    weekly: MiniProduct[];
}

function deriveProductSections(
    productsResponse: ProductsResponse | null,
): DerivedProductSections {
    const products = productsResponse?.products ?? [];
    if (products.length === 0) {
        return { dailyDeal: null, season: [], weekly: [] };
    }

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
    const dailyDeal: DailyDealProduct = {
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
    };

    const minis = products.map(toMini);
    return {
        dailyDeal,
        season: minis.slice(1, 4),
        weekly: minis.slice(4, 7),
    };
}

interface MarketHomeClientProps {
    initialHomepage?: CmsHomepageResponse | null;
    initialProducts?: ProductsResponse | null;
}

export function MarketHomeClient({
    initialHomepage = null,
    initialProducts = null,
}: MarketHomeClientProps) {
    // Lazy initializer: server'dan gelen veriyi ilk render'da kullan, böylece
    // hero/daily-deal/weekly bölümleri mount anında doludur — spinner görünmez.
    const [heroSlides, setHeroSlides] = useState<HeroBannerSlide[]>(() =>
        heroSlidesFromHomepage(initialHomepage),
    );
    const initialDerived = deriveProductSections(initialProducts);
    const [dailyDeal, setDailyDeal] = useState<DailyDealProduct | null>(
        () => initialDerived.dailyDeal,
    );
    const [season, setSeason] = useState<MiniProduct[]>(() => initialDerived.season);
    const [weekly, setWeekly] = useState<MiniProduct[]>(() => initialDerived.weekly);

    // Sentinels: each below-the-fold block mounts when scrolled near.
    const [recentSalesRef, recentSalesInView] = useInViewOnce<HTMLDivElement>({
        rootMargin: '300px 0px',
    });
    const [categoryRef, categoryInView] = useInViewOnce<HTMLDivElement>({
        rootMargin: '300px 0px',
    });
    const [featuredRef, featuredInView] = useInViewOnce<HTMLDivElement>({
        rootMargin: '400px 0px',
    });
    const [brandsRef, brandsInView] = useInViewOnce<HTMLDivElement>({
        rootMargin: '400px 0px',
    });
    const [allProductsRef, allProductsInView] = useInViewOnce<HTMLDivElement>({
        rootMargin: '400px 0px',
    });
    const [promoRef, promoInView] = useInViewOnce<HTMLDivElement>({
        rootMargin: '400px 0px',
    });

    useEffect(() => {
        let active = true;
        const fetches: Promise<unknown>[] = [];

        // Sadece server'dan veri gelmediyse client-side fetch et — ISR fail
        // veya dev'de SSR atlanırsa fallback olarak çalışır.
        if (!initialHomepage) {
            const homepagePromise = cmsApi
                .getHomepage()
                .then((res) => {
                    if (!active || !res.data) return;
                    setHeroSlides(heroSlidesFromHomepage(res.data));
                })
                .catch(() => {});
            fetches.push(homepagePromise);
        }

        if (!initialProducts) {
            const productsPromise = productsApi
                .getAll({ per_page: 40, sort_by: 'offers_count' })
                .then((res) => {
                    if (!active || !res.data) return;
                    const derived = deriveProductSections(res.data);
                    setDailyDeal(derived.dailyDeal);
                    setSeason(derived.season);
                    setWeekly(derived.weekly);
                })
                .catch(() => {});
            fetches.push(productsPromise);
        }

        // Reference promises so eslint/no-floating-promises is satisfied.
        if (fetches.length > 0) {
            void Promise.all(fetches);
        }

        return () => {
            active = false;
        };
    }, [initialHomepage, initialProducts]);

    return (
        <div className="min-h-screen pb-20">
            {/* Above-the-fold: hero + info chips + daily deal + quick order + weekly */}
            <HeroBanner slides={heroSlides} />
            <InfoChipRow />

            <section className="max-w-[1440px] mx-auto px-4 sm:px-6 pt-6">
                <div className="grid grid-cols-1 lg:grid-cols-[2fr_1fr] gap-4">
                    <DailyDealCard product={dailyDeal} />
                    <QuickOrderCard />
                </div>
            </section>

            <WeeklyHighlights season={season} weekly={weekly} />

            {/* Below-the-fold: each section lazy-mounts on scroll */}
            <div ref={recentSalesRef}>
                {recentSalesInView ? <RecentSalesFeed /> : <SectionSkeleton heightClass="h-44" />}
            </div>
            <div ref={categoryRef}>
                {categoryInView ? <CategoryGrid /> : <SectionSkeleton heightClass="h-40" />}
            </div>
            <div ref={featuredRef}>
                {featuredInView ? (
                    <FeaturedSectionTabs />
                ) : (
                    <SectionSkeleton heightClass="h-[420px]" />
                )}
            </div>
            <div ref={brandsRef}>
                {brandsInView ? <BrandStrip /> : <SectionSkeleton heightClass="h-24" />}
            </div>
            <div ref={allProductsRef}>
                {allProductsInView ? (
                    <AllProductsList />
                ) : (
                    <SectionSkeleton heightClass="h-[600px]" />
                )}
            </div>
            <div ref={promoRef}>
                {promoInView ? <PromoBannerRow /> : <SectionSkeleton heightClass="h-32" />}
            </div>
        </div>
    );
}

export default MarketHomeClient;
