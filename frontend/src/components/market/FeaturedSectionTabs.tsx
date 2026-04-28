'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { ProductCard } from '@/components/market/ProductCard';
import { productsApi } from '@/lib/api';

const TABS = [
    { id: 'best', label: 'Çok satan', sort: 'sales_desc' },
    { id: 'new', label: 'Yeni eklenen', sort: 'newest' },
    { id: 'drop', label: 'Fiyat düşen', sort: 'price_drop' },
    { id: 'fast', label: 'Hızlı kargo', sort: 'fast_ship' },
];

interface ProductLite {
    id: number;
    slug?: string;
    name: string;
    brand?: string;
    sku?: string;
    image?: string;
    image_url?: string;
    lowest_price?: number;
    psf_price?: number;
    offers_count?: number;
}

interface FeaturedSectionTabsProps {
    initialProducts?: ProductLite[];
}

export function FeaturedSectionTabs({ initialProducts = [] }: FeaturedSectionTabsProps) {
    const [active, setActive] = useState(TABS[0].id);
    const [products, setProducts] = useState<ProductLite[]>(initialProducts);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        const tab = TABS.find((t) => t.id === active);
        if (!tab) return;
        let cancelled = false;
        // Defer to microtask so the setState is not synchronous within the effect body
        Promise.resolve().then(() => {
            if (cancelled) return;
            setLoading(true);
        });
        productsApi
            .getAll({ sort_by: tab.sort, per_page: 8 })
            .then((res) => {
                if (cancelled) return;
                const list = res.data?.products as ProductLite[] | undefined;
                if (list && list.length > 0) setProducts(list);
            })
            .finally(() => {
                if (!cancelled) setLoading(false);
            });
        return () => {
            cancelled = true;
        };
    }, [active]);

    return (
        <section className="max-w-[1440px] mx-auto px-4 sm:px-6 pt-8">
            <div className="flex flex-col sm:flex-row sm:justify-between sm:items-baseline gap-3 mb-4">
                <div className="flex flex-wrap gap-6 items-baseline">
                    <h2 className="text-xl font-semibold" style={{ color: 'var(--fg)' }}>
                        Öne çıkan ilanlar
                    </h2>
                    <div className="flex flex-wrap gap-1">
                        {TABS.map((t) => (
                            <button
                                key={t.id}
                                type="button"
                                onClick={() => setActive(t.id)}
                                className={t.id === active ? 'btn btn-soft btn-sm' : 'btn btn-ghost btn-sm'}
                            >
                                {t.label}
                            </button>
                        ))}
                    </div>
                </div>
                <Link
                    href="/market/products"
                    className="text-sm font-medium"
                    style={{ color: 'var(--accent)' }}
                >
                    Tümünü gör →
                </Link>
            </div>
            {loading && products.length === 0 ? (
                <div
                    className="grid grid-cols-2 lg:grid-cols-4 gap-4 animate-pulse"
                    aria-hidden
                >
                    {Array.from({ length: 8 }).map((_, i) => (
                        <div key={i} className="card aspect-[3/4] p-3" />
                    ))}
                </div>
            ) : (
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    {products.slice(0, 8).map((p) => (
                        <ProductCard key={p.id} product={p} />
                    ))}
                </div>
            )}
        </section>
    );
}

export default FeaturedSectionTabs;
