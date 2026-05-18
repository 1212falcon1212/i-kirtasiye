'use client';

import React, { useEffect, useState } from 'react';
import { Sparkles, Clock, Flame, TrendingUp, Box } from 'lucide-react';
import Link from 'next/link';
import Image from 'next/image';
import { cmsApi, FeaturedOffer, RecentlySoldItem } from '@/lib/api';
import { Skeleton } from '@/components/ui/skeleton';

export function FeaturedSections() {
    const [seasonHighlights, setSeasonHighlights] = useState<FeaturedOffer[]>([]);
    const [weekProducts, setWeekProducts] = useState<FeaturedOffer[]>([]);
    const [recentlySold, setRecentlySold] = useState<RecentlySoldItem[]>([]);
    const [dealOfDay, setDealOfDay] = useState<FeaturedOffer | null>(null);
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        const loadFeaturedSections = async () => {
            try {
                const response = await cmsApi.getFeaturedSections();
                if (response.data) {
                    setSeasonHighlights(response.data.season_highlights || []);
                    setWeekProducts(response.data.week_products || []);
                    setRecentlySold(response.data.recently_sold || []);
                    setDealOfDay(response.data.deal_of_day || null);
                }
            } catch (error) {
                console.error('Failed to load featured sections', error);
            } finally {
                setIsLoading(false);
            }
        };
        loadFeaturedSections();
    }, []);

    const formatPrice = (price: number) => {
        return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(price);
    };

    if (isLoading) {
        return (
            <div className="space-y-4">
                <Skeleton className="h-64 rounded-3xl" />
                <div className="grid grid-cols-2 md:grid-cols-4 gap-3.5">
                    {[1, 2, 3, 4].map(i => <Skeleton key={i} className="h-48 rounded-2xl" />)}
                </div>
            </div>
        );
    }

    const hasSeasonData = seasonHighlights.length > 0;
    const hasWeekData = weekProducts.length > 0;
    const hasRecentlySoldData = recentlySold.length > 0;
    const hasDealOfDay = dealOfDay !== null;

    if (!hasSeasonData && !hasWeekData && !hasRecentlySoldData && !hasDealOfDay) {
        return null;
    }

    return (
        <section className="space-y-4">
            {/* Deal of Day - Full width hero style */}
            {hasDealOfDay && dealOfDay && (
                <Link href={`/market/product/${dealOfDay.product_id}`} className="block">
                    <div className="relative overflow-hidden rounded-3xl bg-gradient-to-r from-[#ffedd5] via-[#ffedd5] to-[#ffedd5] border border-[#ffedd5]/50 p-8 md:p-10 group hover:shadow-xl transition-shadow">
                        {/* Decorative elements */}
                        <div className="absolute top-0 right-0 w-80 h-80 bg-[#1e3a8a]/5 rounded-full blur-3xl" />
                        <div className="absolute bottom-0 left-0 w-60 h-60 bg-[#d99248]/5 rounded-full blur-3xl" />

                        <div className="relative flex flex-col md:flex-row items-center gap-8">
                            {/* Left: Badge + Text */}
                            <div className="flex-1 text-center md:text-left">
                                <div className="inline-flex items-center gap-2 bg-[#1e3a8a] text-white text-xs font-bold px-4 py-1.5 rounded-full mb-4">
                                    <Flame className="w-3.5 h-3.5" />
                                    GÜNÜN FIRSATI
                                </div>
                                <h3 className="text-2xl md:text-3xl font-black text-[#1a1a1a] leading-tight mb-3 line-clamp-2">
                                    {dealOfDay.name}
                                </h3>
                                <p className="text-sm text-[#6b7280] mb-4">
                                    Satıcı: {dealOfDay.seller}
                                </p>
                                <div className="flex items-baseline gap-2 justify-center md:justify-start">
                                    <span className="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-black text-[#ea580c]">
                                        {formatPrice(dealOfDay.price)}
                                    </span>
                                </div>
                                <div className="mt-6">
                                    <span className="inline-flex items-center gap-2 bg-gradient-to-r from-[#1e3a8a] to-[#1e40af] text-white font-extrabold text-sm px-7 py-3 rounded-xl group-hover:scale-105 transition-all shadow-lg">
                                        Fırsatı Yakala
                                        <TrendingUp className="w-4 h-4" />
                                    </span>
                                </div>
                            </div>

                            {/* Right: Product Image */}
                            <div className="relative w-48 h-48 md:w-56 md:h-56 flex-shrink-0">
                                <div className="absolute inset-0 bg-white rounded-3xl" />
                                <div className="relative w-full h-full rounded-3xl overflow-hidden flex items-center justify-center p-4 bg-white">
                                    {(dealOfDay.image_url || dealOfDay.image) ? (
                                        <Image
                                            src={(dealOfDay.image_url || dealOfDay.image)!}
                                            alt={dealOfDay.name}
                                            fill
                                            sizes="224px"
                                            className="object-contain p-4 group-hover:scale-110 transition-transform duration-500"
                                        />
                                    ) : (
                                        <Box className="w-20 h-20 text-white/30" />
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </Link>
            )}

            {/* Season Highlights + Week Products - Horizontal scroll cards */}
            {(hasSeasonData || hasWeekData) && (
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    {/* Season Highlights */}
                    {hasSeasonData && (
                        <div className="bg-gradient-to-br from-[#fef3e2] to-white rounded-3xl border border-[#ffedd5]/60 p-6 overflow-hidden">
                            <div className="flex items-center gap-2 mb-5">
                                <div className="w-9 h-9 bg-gradient-to-br from-[#ea580c] to-[#c2410c] rounded-xl flex items-center justify-center shadow-md">
                                    <Sparkles className="w-4.5 h-4.5 text-white" />
                                </div>
                                <h3 className="text-lg font-black text-[#1a1a1a]">Sezonun Öne Çıkanları</h3>
                            </div>
                            <div className="flex overflow-x-auto gap-3 snap-x snap-mandatory pb-2 -mx-2 px-2 md:grid md:grid-cols-3 md:gap-3 md:snap-none md:pb-0 md:-mx-0 md:px-0">
                                {seasonHighlights.slice(0, 3).map((item, idx) => (
                                    <Link
                                        key={item.id || idx}
                                        href={`/market/product/${item.product_id}`}
                                        className="group snap-start flex-shrink-0 w-[140px] md:w-auto"
                                    >
                                        <div className="relative w-full aspect-square bg-gradient-to-br from-[#fef3e2] to-white rounded-2xl border border-[#ffedd5]/60 overflow-hidden mb-2 group-hover:border-[#ea580c] group-hover:shadow-md transition-all">
                                            {(item.image_url || item.image) ? (
                                                <Image
                                                    src={(item.image_url || item.image)!}
                                                    alt={item.name}
                                                    fill
                                                    sizes="140px"
                                                    className="object-contain p-3 group-hover:scale-105 transition-transform"
                                                />
                                            ) : (
                                                <div className="w-full h-full flex items-center justify-center">
                                                    <Box className="w-10 h-10 text-[#ea580c]/30" />
                                                </div>
                                            )}
                                        </div>
                                        <p className="text-[11px] font-medium text-[#6b7280] line-clamp-1">{item.name}</p>
                                        <p className="text-sm font-black text-[#ea580c] mt-0.5">{formatPrice(item.price)}</p>
                                    </Link>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Week Products */}
                    {hasWeekData && (
                        <div className="bg-gradient-to-br from-[#e0e7ff] to-white rounded-3xl border border-[#c7d2fe]/60 p-6 overflow-hidden">
                            <div className="flex items-center gap-2 mb-5">
                                <div className="w-9 h-9 bg-gradient-to-br from-[#1e40af] to-[#1e3a8a] rounded-xl flex items-center justify-center shadow-md">
                                    <Clock className="w-4.5 h-4.5 text-white" />
                                </div>
                                <h3 className="text-lg font-black text-[#1a1a1a]">Haftanın Ürünleri</h3>
                            </div>
                            <div className="flex overflow-x-auto gap-3 snap-x snap-mandatory pb-2 -mx-2 px-2 md:grid md:grid-cols-3 md:gap-3 md:snap-none md:pb-0 md:-mx-0 md:px-0">
                                {weekProducts.slice(0, 3).map((item, idx) => (
                                    <Link
                                        key={item.id || idx}
                                        href={`/market/product/${item.product_id}`}
                                        className="group snap-start flex-shrink-0 w-[140px] md:w-auto"
                                    >
                                        <div className="relative w-full aspect-square bg-gradient-to-br from-[#e0e7ff] to-white rounded-2xl border border-[#c7d2fe]/60 overflow-hidden mb-2 group-hover:border-[#1e40af] group-hover:shadow-md transition-all">
                                            {(item.image_url || item.image) ? (
                                                <Image
                                                    src={(item.image_url || item.image)!}
                                                    alt={item.name}
                                                    fill
                                                    sizes="140px"
                                                    className="object-contain p-3 group-hover:scale-105 transition-transform"
                                                />
                                            ) : (
                                                <div className="w-full h-full flex items-center justify-center">
                                                    <Box className="w-10 h-10 text-[#1e40af]/30" />
                                                </div>
                                            )}
                                        </div>
                                        <p className="text-[11px] font-medium text-[#6b7280] line-clamp-1">{item.name}</p>
                                        <p className="text-sm font-black text-[#1e40af] mt-0.5">{formatPrice(item.price)}</p>
                                    </Link>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            )}

            {/* Recently Sold - Ticker style */}
            {hasRecentlySoldData && (
                <div className="bg-gradient-to-r from-[#fff7ed] via-white to-[#fef3e2] -mx-4 sm:-mx-7 px-4 sm:px-7 border-y border-[#ffedd5]/40 py-6 sm:py-12">
                    <div className="flex items-center gap-2 sm:gap-3 mb-7">
                        <div className="w-9 h-9 sm:w-[44px] sm:h-[44px] bg-gradient-to-br from-[#ea580c] to-[#c2410c] rounded-xl flex items-center justify-center flex-shrink-0 shadow-md">
                            <TrendingUp className="w-4 h-4 sm:w-5 sm:h-5 text-white" />
                        </div>
                        <h3 className="text-xl sm:text-[26px] font-black text-[#1a1a1a]">Son Satılanlar</h3>
                        <span className="ml-auto text-xs text-[#6b7280] font-medium">Canlı güncellemeler</span>
                    </div>
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        {recentlySold.slice(0, 6).map((product, idx) => (
                            <Link key={product.id || idx} href={`/market/product/${product.product_id}`}
                                className="flex flex-row bg-white rounded-3xl border border-[#f0eceb] overflow-hidden hover:border-[#ffedd5] transition-colors group">
                                {/* Left: Image */}
                                <div className="w-[130px] sm:w-[210px] md:w-[240px] h-[150px] sm:h-[180px] md:h-[210px] flex-shrink-0 bg-white relative flex items-center justify-center">
                                    {(product.image_url || product.image) ? (
                                        <Image
                                            src={(product.image_url || product.image)!}
                                            alt={product.name}
                                            fill
                                            sizes="240px"
                                            className="object-contain p-4 group-hover:scale-105 transition-transform"
                                        />
                                    ) : (
                                        <Box className="w-12 h-12 text-[#d1d5db]" />
                                    )}
                                </div>
                                {/* Right: Info */}
                                <div className="flex-1 min-w-0 p-4 sm:p-6 flex flex-col justify-center">
                                    <p className="text-sm sm:text-base font-semibold text-[#1a1a1a] line-clamp-2 mb-3 group-hover:text-[#ea580c] transition-colors">
                                        {product.name}
                                    </p>
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className="text-xl sm:text-2xl font-black text-[#1a1a1a]">{formatPrice(product.price)}</span>
                                        {(product.offers_count ?? 0) > 0 && (
                                            <span className="text-[11px] font-bold px-2 py-0.5 rounded-md bg-[#ffedd5] text-[#ea580c]">
                                                {product.offers_count} ilan
                                            </span>
                                        )}
                                    </div>
                                </div>
                            </Link>
                        ))}
                    </div>
                </div>
            )}
        </section>
    );
}
