'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import Image from 'next/image';
import { ArrowRight, Box } from 'lucide-react';
import { cmsApi, type RecentlySoldItem } from '@/lib/api';

const formatTL = (n: number) =>
    new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(
        n,
    ) + ' ₺';

export function RecentSalesFeed() {
    const [items, setItems] = useState<RecentlySoldItem[]>([]);
    const [highlightIdx, setHighlightIdx] = useState<number>(1);

    useEffect(() => {
        let active = true;
        const load = () => {
            cmsApi
                .getFeaturedSections()
                .then((res) => {
                    if (!active) return;
                    const list = res.data?.recently_sold ?? [];
                    setItems(list.slice(0, 8));
                })
                .catch(() => {});
        };
        load();
        // Refresh + rotate highlight every 8s
        const t = setInterval(() => {
            if (!active) return;
            setHighlightIdx((idx) => (idx + 1) % 8);
        }, 8000);
        return () => {
            active = false;
            clearInterval(t);
        };
    }, []);

    if (items.length === 0) return null;

    return (
        <section className="max-w-[1440px] mx-auto px-4 sm:px-6 pt-6">
            <div
                className="p-5"
                style={{
                    background: 'var(--bg-elevated)',
                    border: '1px solid var(--border)',
                    borderRadius: 'var(--radius-lg)',
                }}
            >
                <div className="flex justify-between items-center mb-3.5">
                    <div className="flex items-center gap-2.5">
                        <div
                            className="w-7 h-7 rounded-md flex items-center justify-center"
                            style={{
                                background: 'var(--success-soft)',
                                color: 'var(--success)',
                            }}
                        >
                            <ArrowRight className="w-[15px] h-[15px]" />
                        </div>
                        <h3 className="text-base font-semibold" style={{ color: 'var(--fg)' }}>
                            Son Satılanlar
                        </h3>
                    </div>
                    <span
                        className="inline-flex items-center gap-1.5 text-[11px] font-semibold"
                        style={{ color: 'var(--success)' }}
                    >
                        <span
                            className="w-2 h-2 rounded-full animate-pulse-live"
                            style={{ background: 'var(--success)' }}
                        />
                        Canlı güncellemeler
                    </span>
                </div>
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-2.5">
                    {items.slice(0, 4).map((p, idx) => {
                        const img = p.image_url || p.image;
                        const highlighted = idx === highlightIdx % 4;
                        return (
                            <Link
                                key={p.id}
                                href={`/market/product/${p.product_id}`}
                                className="grid gap-2.5 p-2.5 transition-all hover:shadow-md"
                                style={{
                                    gridTemplateColumns: '60px 1fr',
                                    border: highlighted
                                        ? '1px solid var(--accent)'
                                        : '1px solid var(--border)',
                                    borderRadius: 'var(--radius)',
                                    background: highlighted ? 'var(--accent-soft)' : 'var(--bg)',
                                }}
                            >
                                <div
                                    className="relative overflow-hidden flex-shrink-0"
                                    style={{
                                        aspectRatio: '1 / 1',
                                        width: 60,
                                        background: '#ffffff',
                                        border: '1px solid var(--border)',
                                        borderRadius: 6,
                                    }}
                                >
                                    {img ? (
                                        <Image
                                            src={img}
                                            alt={p.name}
                                            fill
                                            sizes="60px"
                                            className="object-contain p-1"
                                        />
                                    ) : (
                                        <div className="w-full h-full flex items-center justify-center">
                                            <Box className="w-5 h-5" style={{ color: 'var(--fg-soft)' }} />
                                        </div>
                                    )}
                                </div>
                                <div className="min-w-0">
                                    <div
                                        className="text-xs font-medium leading-tight line-clamp-2"
                                        style={{ color: 'var(--fg)' }}
                                    >
                                        {p.name}
                                    </div>
                                    <div
                                        className="mono text-[13px] font-bold mt-0.5"
                                        style={{ color: highlighted ? 'var(--accent)' : 'var(--fg)' }}
                                    >
                                        {p.price != null ? formatTL(p.price) : '---'}
                                    </div>
                                </div>
                            </Link>
                        );
                    })}
                </div>
            </div>
        </section>
    );
}

export default RecentSalesFeed;
