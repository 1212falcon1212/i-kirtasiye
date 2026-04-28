"use client";

import { useRef, useCallback, useEffect, useState, useMemo } from "react";
import Link from "next/link";
import { ChevronLeft, ChevronRight } from "lucide-react";
import {
    Carousel,
    CarouselContent,
    CarouselItem,
    type CarouselApi,
} from "@/components/ui/carousel";
import Autoplay from "embla-carousel-autoplay";
import { Banner } from "@/lib/api";
import { cn } from "@/lib/utils";

interface HeroSliderProps {
    banners: Banner[];
}

interface BannerTab {
    name: string;
    banners: Banner[];
}

export function HeroSlider({ banners }: HeroSliderProps) {
    const [api, setApi] = useState<CarouselApi>();
    const [current, setCurrent] = useState(0);
    const [isHovered, setIsHovered] = useState(false);
    const [activeTabIndex, setActiveTabIndex] = useState(0);

    const plugin = useRef(
        Autoplay({ delay: 5500, stopOnInteraction: false, stopOnMouseEnter: true })
    );

    const displayBanners = banners || [];

    // Group banners by tab_name
    const tabs: BannerTab[] = useMemo(() => {
        const tabMap = new Map<string, Banner[]>();
        const noTabBanners: Banner[] = [];

        displayBanners.forEach((banner) => {
            if (banner.tab_name) {
                const existing = tabMap.get(banner.tab_name) || [];
                existing.push(banner);
                tabMap.set(banner.tab_name, existing);
            } else {
                noTabBanners.push(banner);
            }
        });

        const result: BannerTab[] = [];
        tabMap.forEach((banners, name) => {
            result.push({ name, banners });
        });

        if (noTabBanners.length > 0) {
            if (result.length === 0) {
                result.push({ name: '', banners: noTabBanners });
            } else {
                result[0].banners = [...noTabBanners, ...result[0].banners];
            }
        }

        return result;
    }, [displayBanners]);

    const hasTabs = tabs.length > 1 || (tabs.length === 1 && tabs[0].name !== '');
    const activeTab = tabs[activeTabIndex] || tabs[0];
    const activeBanners = activeTab?.banners || [];

    useEffect(() => {
        if (!api) return;

        setCurrent(api.selectedScrollSnap());

        api.on("select", () => {
            setCurrent(api.selectedScrollSnap());
        });
    }, [api]);

    useEffect(() => {
        setCurrent(0);
        api?.scrollTo(0);
    }, [activeTabIndex, api]);

    const scrollTo = useCallback(
        (index: number) => {
            api?.scrollTo(index);
        },
        [api]
    );

    const scrollPrev = useCallback(() => {
        api?.scrollPrev();
    }, [api]);

    const scrollNext = useCallback(() => {
        api?.scrollNext();
    }, [api]);

    if (displayBanners.length === 0) {
        return null;
    }

    return (
        <div
            className="relative w-full"
            onMouseEnter={() => setIsHovered(true)}
            onMouseLeave={() => setIsHovered(false)}
        >
            {/* Banner - Full width, image only */}
            <div className="relative overflow-hidden">
                <Carousel
                    key={activeTabIndex}
                    setApi={setApi}
                    plugins={[plugin.current]}
                    className="w-full"
                    opts={{
                        loop: activeBanners.length > 1,
                        align: "start",
                    }}
                >
                    <CarouselContent>
                        {activeBanners.map((banner, index) => (
                            <CarouselItem key={banner.id}>
                                {banner.link_url ? (
                                    <Link href={banner.link_url} className="block">
                                        <BannerImage banner={banner} index={index} activeTabIndex={activeTabIndex} />
                                    </Link>
                                ) : (
                                    <BannerImage banner={banner} index={index} activeTabIndex={activeTabIndex} />
                                )}
                            </CarouselItem>
                        ))}
                    </CarouselContent>
                </Carousel>

                {/* Navigation Arrows */}
                {activeBanners.length > 1 && (
                    <>
                        <button
                            onClick={scrollPrev}
                            className={cn(
                                "absolute left-3 sm:left-5 top-1/2 -translate-y-1/2 w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-white/80 backdrop-blur-sm shadow-lg flex items-center justify-center text-[#1a1a1a] hover:bg-white transition-all duration-200 z-20",
                                isHovered ? "opacity-100 scale-100" : "opacity-0 scale-90"
                            )}
                        >
                            <ChevronLeft className="w-5 h-5" />
                        </button>
                        <button
                            onClick={scrollNext}
                            className={cn(
                                "absolute right-3 sm:right-5 top-1/2 -translate-y-1/2 w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-white/80 backdrop-blur-sm shadow-lg flex items-center justify-center text-[#1a1a1a] hover:bg-white transition-all duration-200 z-20",
                                isHovered ? "opacity-100 scale-100" : "opacity-0 scale-90"
                            )}
                        >
                            <ChevronRight className="w-5 h-5" />
                        </button>
                    </>
                )}

                {/* Navigation Dots */}
                {activeBanners.length > 1 && (
                    <div className="absolute bottom-4 sm:bottom-5 left-1/2 -translate-x-1/2 flex items-center gap-2 z-20">
                        {activeBanners.map((_, index) => (
                            <button
                                key={index}
                                onClick={() => scrollTo(index)}
                                className={cn(
                                    "rounded-full transition-all duration-300",
                                    current === index
                                        ? "w-8 h-2.5 bg-[#b8651a] shadow-md"
                                        : "w-2.5 h-2.5 bg-[#1a1a1a]/30 hover:bg-[#1a1a1a]/50"
                                )}
                            />
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}

function BannerImage({ banner, index, activeTabIndex }: { banner: Banner; index: number; activeTabIndex: number }) {
    return (
        <div>
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img
                src={banner.image_url}
                alt={banner.title || ''}
                className="w-full h-auto block"
                {...(index === 0 && activeTabIndex === 0
                    ? { fetchPriority: "high" as const }
                    : { loading: "lazy" as const })}
            />
        </div>
    );
}
