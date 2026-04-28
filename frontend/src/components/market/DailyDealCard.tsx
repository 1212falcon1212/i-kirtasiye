'use client';

import Link from 'next/link';
import Image from 'next/image';
import { Flame, ArrowRight } from 'lucide-react';

export interface DailyDealProduct {
    id: number | string;
    name: string;
    brand?: string;
    image?: string;
    psf: number;
    lowest: number;
    sellersCount?: number;
    href?: string;
}

interface DailyDealCardProps {
    product?: DailyDealProduct | null;
    title?: string;
}

const formatTL = (n: number) =>
    new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(
        n,
    ) + ' ₺';

export function DailyDealCard({ product, title = 'Günün Fırsatı' }: DailyDealCardProps) {
    if (!product) {
        return (
            <div
                className="grid items-center gap-5 p-5"
                style={{
                    background: 'var(--bg-elevated)',
                    border: '1px solid var(--border)',
                    borderRadius: 'var(--radius-lg)',
                    gridTemplateColumns: '200px 1fr auto',
                    minHeight: 240,
                }}
            >
                <div
                    className="ph-image animate-pulse"
                    style={{ aspectRatio: '1 / 1', width: 200, borderRadius: 10 }}
                />
                <div className="flex flex-col gap-2">
                    <div className="h-5 w-24 rounded animate-pulse" style={{ background: 'var(--bg-muted)' }} />
                    <div className="h-6 w-3/4 rounded animate-pulse" style={{ background: 'var(--bg-muted)' }} />
                    <div className="h-8 w-40 rounded animate-pulse" style={{ background: 'var(--bg-muted)' }} />
                </div>
                <div className="h-12 w-44 rounded animate-pulse" style={{ background: 'var(--bg-muted)' }} />
            </div>
        );
    }

    const { name, brand, image, psf, lowest, sellersCount, href } = product;
    const savings = psf > lowest ? Math.round(((psf - lowest) / psf) * 100) : 0;
    const productHref = href || `/market/product/${product.id}`;

    return (
        <div
            className="relative grid items-center gap-5 p-5 overflow-hidden"
            style={{
                background: 'var(--bg-elevated)',
                border: '1px solid var(--border)',
                borderRadius: 'var(--radius-lg)',
                gridTemplateColumns: '200px 1fr auto',
            }}
        >
            <Link
                href={productHref}
                className="relative block overflow-hidden"
                style={{
                    aspectRatio: '1 / 1',
                    width: 200,
                    borderRadius: 10,
                    background: 'var(--bg-muted)',
                }}
            >
                {image ? (
                    <Image
                        src={image}
                        alt={name}
                        fill
                        sizes="200px"
                        className="object-contain p-3"
                    />
                ) : (
                    <div
                        className="ph-image w-full h-full flex items-center justify-center"
                        style={{ fontSize: 12 }}
                    >
                        {brand?.toUpperCase() || 'ÜRÜN'}
                    </div>
                )}
            </Link>

            <div>
                <span className="chip chip-warning" style={{ fontWeight: 700 }}>
                    <Flame className="w-3 h-3" /> {title}
                </span>
                {brand && (
                    <div
                        className="text-[11px] uppercase tracking-wider mt-2"
                        style={{ color: 'var(--fg-muted)' }}
                    >
                        {brand}
                    </div>
                )}
                <Link href={productHref}>
                    <h3
                        className="text-xl mt-1 leading-tight line-clamp-2 hover:underline"
                        style={{ color: 'var(--fg)' }}
                    >
                        {name}
                    </h3>
                </Link>
                {typeof sellersCount === 'number' && sellersCount > 0 && (
                    <div className="text-[13px] mt-1.5" style={{ color: 'var(--fg-muted)' }}>
                        <span className="mono font-semibold" style={{ color: 'var(--fg)' }}>
                            {sellersCount}
                        </span>{' '}
                        satıcı ilanı ·{' '}
                        <span style={{ color: 'var(--success)', fontWeight: 600 }}>Stokta</span>
                    </div>
                )}
                <div className="flex items-baseline gap-3 mt-3 flex-wrap">
                    {psf > lowest && (
                        <span
                            className="mono text-[13px] line-through"
                            style={{ color: 'var(--fg-soft)' }}
                        >
                            {formatTL(psf)}
                        </span>
                    )}
                    <span
                        className="mono text-[28px] font-bold"
                        style={{ color: 'var(--accent)' }}
                    >
                        {formatTL(lowest)}
                    </span>
                    {savings > 0 && <span className="chip chip-success">%{savings} indirim</span>}
                </div>
            </div>

            <Link
                href={productHref}
                className="btn btn-primary btn-lg self-stretch"
                style={{ paddingLeft: 24, paddingRight: 24 }}
            >
                Fırsatı Yakala <ArrowRight className="w-3.5 h-3.5" />
            </Link>
        </div>
    );
}

export default DailyDealCard;
