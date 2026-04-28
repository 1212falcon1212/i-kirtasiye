'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { ArrowRight } from 'lucide-react';

const formatTL = (n: number) =>
    new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(
        n,
    ) + ' ₺';

interface SaleItem {
    id: number | string;
    name: string;
    brand?: string;
    lowest?: number;
    highlighted?: boolean;
}

const SEED: SaleItem[] = [
    { id: 's1', name: "Pelikan 30'lu Kuru Boya Kalem Seti", brand: 'PELIKAN', lowest: 76.2 },
    { id: 's2', name: 'Faber-Castell Plastik Silgi 30\'lu Paket', brand: 'FABER', lowest: 41.5, highlighted: true },
    { id: 's3', name: 'Mas A4 80gr Fotokopi Kağıdı 5 paket', brand: 'MAS', lowest: 612 },
    { id: 's4', name: "Bic Roller 0.7mm 12'li", brand: 'BIC', lowest: 89.4 },
];

export function RecentSalesFeed() {
    const [items, setItems] = useState<SaleItem[]>(SEED);

    useEffect(() => {
        // Lightweight rotation simulating a live feed
        const t = setInterval(() => {
            setItems((prev) => {
                const next = [...prev];
                next.unshift(next.pop()!);
                return next.map((it, idx) => ({ ...it, highlighted: idx === 1 }));
            });
        }, 8000);
        return () => clearInterval(t);
    }, []);

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
                    {items.map((p) => (
                        <Link
                            key={p.id}
                            href={`/market/product/${p.id}`}
                            className="grid gap-2.5 p-2.5"
                            style={{
                                gridTemplateColumns: '60px 1fr',
                                border: '1px solid var(--border)',
                                borderRadius: 'var(--radius)',
                                background: p.highlighted
                                    ? 'var(--accent-soft)'
                                    : 'var(--bg)',
                            }}
                        >
                            <div
                                className="ph-image"
                                style={{ aspectRatio: '1 / 1', width: 60, fontSize: 8 }}
                            >
                                {(p.brand || '').slice(0, 4)}
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
                                    style={{ color: p.highlighted ? 'var(--accent)' : 'var(--fg)' }}
                                >
                                    {p.lowest != null ? formatTL(p.lowest) : '---'}
                                </div>
                            </div>
                        </Link>
                    ))}
                </div>
            </div>
        </section>
    );
}

export default RecentSalesFeed;
