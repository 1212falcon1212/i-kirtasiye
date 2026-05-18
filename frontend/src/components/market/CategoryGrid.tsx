'use client';

import { useEffect, useRef, useState } from 'react';
import Link from 'next/link';
import Image from 'next/image';
import { ChevronLeft, ChevronRight, Box } from 'lucide-react';
import { categoriesApi, Category } from '@/lib/api';

interface UICategory {
    id: number | string;
    slug: string;
    full_slug?: string;
    name: string;
    image_url?: string | null;
    count?: number;
}

function CategoryImage({ src, alt }: { src: string; alt: string }) {
    const [errored, setErrored] = useState(false);
    if (errored) {
        return (
            <div className="w-full h-full flex items-center justify-center">
                <Box className="w-10 h-10" style={{ color: 'var(--fg-soft)' }} />
            </div>
        );
    }
    return (
        <Image
            src={src}
            alt={alt}
            fill
            sizes="110px"
            className="object-contain p-3 transition-transform group-hover:scale-105"
            onError={() => setErrored(true)}
            loading="lazy"
        />
    );
}

export function CategoryGrid() {
    const [items, setItems] = useState<UICategory[]>([]);
    const [activeId, setActiveId] = useState<string | number | null>(null);
    const scrollerRef = useRef<HTMLDivElement>(null);
    const [canScrollLeft, setCanScrollLeft] = useState(false);
    const [canScrollRight, setCanScrollRight] = useState(false);

    useEffect(() => {
        let active = true;
        // NOTE: Do not invalidate /categories cache here — that endpoint has a 5-minute TTL
        // (see CACHE_TTL_MAP in lib/api.ts). The market header also uses /categories, so
        // letting the cache work means category nav + grid share a single network request.
        categoriesApi
            .getAll()
            .then((res) => {
                if (!active) return;
                const data = res.data?.categories;
                if (data && Array.isArray(data) && data.length > 0) {
                    const mapped: UICategory[] = (data as (Category & { image_url?: string | null; full_slug?: string })[]).map((c) => ({
                        id: c.id,
                        slug: c.slug,
                        full_slug: c.full_slug,
                        name: c.name,
                        image_url: c.image_url ?? null,
                        count: c.products_count,
                    }));
                    setItems(mapped);
                }
            })
            .catch(() => {});
        return () => {
            active = false;
        };
    }, []);

    const updateScrollState = () => {
        const el = scrollerRef.current;
        if (!el) return;
        setCanScrollLeft(el.scrollLeft > 4);
        setCanScrollRight(el.scrollLeft + el.clientWidth < el.scrollWidth - 4);
    };

    useEffect(() => {
        updateScrollState();
        const el = scrollerRef.current;
        if (!el) return;
        const onScroll = () => updateScrollState();
        el.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', updateScrollState);
        return () => {
            el.removeEventListener('scroll', onScroll);
            window.removeEventListener('resize', updateScrollState);
        };
    }, [items.length]);

    const scrollBy = (dir: 1 | -1) => {
        const el = scrollerRef.current;
        if (!el) return;
        el.scrollBy({ left: dir * Math.max(360, el.clientWidth * 0.8), behavior: 'smooth' });
    };

    if (items.length === 0) return null;

    return (
        <section className="max-w-[1440px] mx-auto px-4 sm:px-6 pt-8">
            <div className="flex justify-between items-baseline mb-4">
                <div className="flex items-center gap-3">
                    <div className="w-1.5 h-7 bg-gradient-to-b from-[#ea580c] to-[#c2410c] rounded-full" />
                    <h2 className="text-xl font-bold" style={{ color: 'var(--fg)' }}>
                        İlgini Çekebilecek Kategoriler
                    </h2>
                </div>
                <Link
                    href="/market/products"
                    className="text-sm font-medium px-4 py-2 rounded-lg bg-[#ea580c] text-white hover:bg-[#c2410c] hover:shadow-md transition-all"
                >
                    Tümünü gör →
                </Link>
            </div>

            <div className="relative">
                {canScrollLeft && (
                    <button
                        type="button"
                        onClick={() => scrollBy(-1)}
                        aria-label="Önceki kategoriler"
                        className="absolute left-0 top-1/2 -translate-y-1/2 z-10 hidden md:flex items-center justify-center transition-opacity"
                        style={{
                            width: 36,
                            height: 36,
                            background: '#ffffff',
                            color: 'var(--fg)',
                            borderRadius: 999,
                            boxShadow: 'var(--shadow-lg)',
                            border: '1px solid var(--border)',
                            transform: 'translate(-50%, -60%)',
                        }}
                    >
                        <ChevronLeft className="w-4 h-4" />
                    </button>
                )}
                {canScrollRight && (
                    <button
                        type="button"
                        onClick={() => scrollBy(1)}
                        aria-label="Sonraki kategoriler"
                        className="absolute right-0 top-1/2 -translate-y-1/2 z-10 hidden md:flex items-center justify-center transition-opacity"
                        style={{
                            width: 36,
                            height: 36,
                            background: '#ffffff',
                            color: 'var(--fg)',
                            borderRadius: 999,
                            boxShadow: 'var(--shadow-lg)',
                            border: '1px solid var(--border)',
                            transform: 'translate(50%, -60%)',
                        }}
                    >
                        <ChevronRight className="w-4 h-4" />
                    </button>
                )}

                <div
                    ref={scrollerRef}
                    className="flex gap-3 sm:gap-4 overflow-x-auto scrollbar-hide pb-2 justify-start lg:justify-center"
                    style={{ scrollSnapType: 'x mandatory' }}
                >
                    {items.map((c) => {
                        const isActive = activeId === c.id;
                        const href = `/market/category/${c.full_slug ?? c.slug}`;
                        return (
                            <Link
                                key={c.id}
                                href={href}
                                onMouseEnter={() => setActiveId(c.id)}
                                onMouseLeave={() => setActiveId((cur) => (cur === c.id ? null : cur))}
                                onFocus={() => setActiveId(c.id)}
                                className="flex flex-col items-center gap-2 flex-shrink-0 group"
                                style={{ scrollSnapAlign: 'start', width: 120 }}
                            >
                                <div
                                    className="relative overflow-hidden transition-all"
                                    style={{
                                        width: 110,
                                        height: 110,
                                        borderRadius: 999,
                                        background: '#ffffff',
                                        border: isActive
                                            ? '2px solid var(--primary)'
                                            : '1px solid var(--border)',
                                        boxShadow: isActive ? '0 6px 24px rgba(30,58,138,0.18)' : 'var(--shadow-sm)',
                                    }}
                                >
                                    {c.image_url ? (
                                        <CategoryImage src={c.image_url} alt={c.name} />
                                    ) : (
                                        <div className="w-full h-full flex items-center justify-center">
                                            <Box className="w-10 h-10" style={{ color: 'var(--fg-soft)' }} />
                                        </div>
                                    )}
                                </div>
                                <span
                                    className="text-xs sm:text-sm font-medium text-center leading-tight px-1 line-clamp-2 transition-colors"
                                    style={{ color: isActive ? 'var(--primary)' : 'var(--fg)' }}
                                >
                                    {c.name}
                                </span>
                            </Link>
                        );
                    })}
                </div>
            </div>
        </section>
    );
}

export default CategoryGrid;
