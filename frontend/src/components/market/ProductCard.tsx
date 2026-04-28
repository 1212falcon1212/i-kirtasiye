'use client';

import React, { useState } from 'react';
import Image from 'next/image';
import Link from 'next/link';
import { ShoppingCart, Check, Loader2 } from 'lucide-react';
import { useCartStore } from '@/stores/useCartStore';
import { useAuth } from '@/contexts/AuthContext';
import { cn } from '@/lib/utils';
import { Stars } from '@/components/market/Stars';

interface ProductCardProps {
    product: {
        id: number;
        slug?: string;
        name: string;
        image?: string;
        image_url?: string;
        brand?: string | { name?: string } | null;
        category?: string | { name?: string } | null;
        sku?: string;
        lowest_price?: number;
        psf_price?: number;
        offers_count?: number | string;
        stock_status?: 'in_stock' | 'low_stock' | 'out_of_stock';
        default_offer_id?: number;
        rating?: number;
        review_count?: number;
        tag?: string;
    };
    layout?: 'grid' | 'list';
    style?: 'solid' | 'outline';
    className?: string;
}

const toName = (v: unknown): string | undefined => {
    if (!v) return undefined;
    if (typeof v === 'string') return v;
    if (typeof v === 'object' && v && 'name' in v) {
        const n = (v as { name?: unknown }).name;
        return typeof n === 'string' ? n : undefined;
    }
    return undefined;
};

const formatTL = (n?: number) => {
    if (n === undefined || n === null) return '---';
    return (
        new Intl.NumberFormat('tr-TR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(n) + ' ₺'
    );
};

export const ProductCard = React.memo(function ProductCard({
    product,
    layout = 'grid',
    style = 'solid',
    className,
}: ProductCardProps) {
    const [adding, setAdding] = useState(false);
    const [added, setAdded] = useState(false);
    const [imgError, setImgError] = useState(false);
    const addItem = useCartStore((s) => s.addItem);
    const { user } = useAuth();

    const canBuy = !user || user.role !== 'seller';
    const isList = layout === 'list';

    const offersCount =
        typeof product.offers_count === 'number'
            ? product.offers_count
            : parseInt(String(product.offers_count || 0)) || 0;

    const outOfStock = product.stock_status === 'out_of_stock' || offersCount === 0;

    const handleAddToCart = async (e: React.MouseEvent) => {
        e.preventDefault();
        e.stopPropagation();
        if (outOfStock || !product.default_offer_id || !canBuy || adding) return;
        setAdding(true);
        try {
            await addItem(product.default_offer_id, 1);
            setAdded(true);
            setTimeout(() => setAdded(false), 2000);
        } finally {
            setAdding(false);
        }
    };

    const href = product.slug ? `/market/product/${product.slug}` : `/market/product/${product.id}`;
    const img = product.image_url || product.image;
    const psf = product.psf_price;
    const lowest = product.lowest_price;
    const savings = psf && lowest && psf > lowest ? Math.round(((psf - lowest) / psf) * 100) : 0;
    const brandName = toName(product.brand);

    return (
        <Link
            href={href}
            className={cn(
                'group relative rounded-[10px] transition-colors',
                isList ? 'grid gap-4 p-3' : 'flex flex-col gap-2.5 p-3',
                className,
            )}
            style={{
                background: 'var(--bg-elevated)',
                border: '1px solid var(--border)',
                boxShadow: style === 'solid' ? 'var(--shadow-sm)' : 'none',
                gridTemplateColumns: isList ? '120px 1fr 220px' : undefined,
            }}
        >
            {/* Image */}
            <div
                className={cn('ph-image relative overflow-hidden')}
                style={{
                    aspectRatio: '1 / 1',
                    width: isList ? 120 : '100%',
                }}
            >
                {img && !imgError ? (
                    <Image
                        src={img}
                        alt={product.name}
                        fill
                        sizes={isList ? '120px' : '(max-width: 768px) 50vw, 25vw'}
                        className="object-cover"
                        onError={() => setImgError(true)}
                    />
                ) : (
                    <span>{brandName || 'ÜRÜN'}</span>
                )}
                {product.tag && (
                    <span
                        className={cn(
                            'absolute top-2 left-2',
                            product.tag === 'Az stok'
                                ? 'chip chip-warning'
                                : product.tag === 'Çok satan'
                                  ? 'chip chip-accent'
                                  : 'chip',
                        )}
                    >
                        {product.tag}
                    </span>
                )}
            </div>

            {/* Body */}
            <div className="flex flex-col gap-1.5 flex-1 min-w-0">
                {(product.sku || brandName) && (
                    <div className="flex items-center gap-1.5">
                        {product.sku && (
                            <span
                                className="mono text-[10px]"
                                style={{ color: 'var(--fg-soft)' }}
                            >
                                {product.sku}
                            </span>
                        )}
                        {brandName && (
                            <span className="text-[11px]" style={{ color: 'var(--fg-soft)' }}>
                                · {brandName}
                            </span>
                        )}
                    </div>
                )}
                <h3
                    className="text-[13px] font-medium leading-tight line-clamp-2"
                    style={{ color: 'var(--fg)' }}
                >
                    {product.name}
                </h3>

                {(product.rating || product.review_count) && (
                    <div className="flex items-center gap-2 text-[11px]" style={{ color: 'var(--fg-muted)' }}>
                        <Stars value={product.rating || 0} size={12} />
                        {product.review_count != null && (
                            <span className="mono">{product.review_count.toLocaleString('tr-TR')}</span>
                        )}
                    </div>
                )}
            </div>

            {/* Price + actions */}
            <div
                className={cn('flex flex-col gap-2', isList ? '' : 'mt-auto pt-2.5')}
                style={isList ? {} : { borderTop: '1px solid var(--border)' }}
            >
                <div>
                    {psf && psf > (lowest || 0) && (
                        <div className="flex justify-between text-[11px]" style={{ color: 'var(--fg-soft)' }}>
                            <span>PSF</span>
                            <span className="mono line-through">{formatTL(psf)}</span>
                        </div>
                    )}
                    <div className="flex items-baseline justify-between mt-0.5">
                        <span className="text-[11px] font-semibold" style={{ color: 'var(--success)' }}>
                            {savings ? `%${savings} indirim` : 'En düşük'}
                        </span>
                        <span className="mono text-[18px] font-bold" style={{ color: 'var(--fg)' }}>
                            {formatTL(lowest)}
                        </span>
                    </div>
                    {offersCount > 0 && (
                        <div className="text-[11px] mt-1" style={{ color: 'var(--fg-muted)' }}>
                            <span className="mono">{offersCount}</span> satıcı ilanı
                        </div>
                    )}
                </div>

                {canBuy && (
                    <button
                        type="button"
                        onClick={handleAddToCart}
                        disabled={adding || outOfStock}
                        className="btn btn-soft btn-sm w-full"
                        aria-label="Sepete ekle"
                    >
                        {adding ? (
                            <>
                                <Loader2 className="w-3.5 h-3.5 animate-spin" /> Ekleniyor
                            </>
                        ) : added ? (
                            <>
                                <Check className="w-3.5 h-3.5" /> Eklendi
                            </>
                        ) : outOfStock ? (
                            'Stokta yok'
                        ) : (
                            <>
                                <ShoppingCart className="w-3.5 h-3.5" /> Sepete ekle
                            </>
                        )}
                    </button>
                )}
            </div>
        </Link>
    );
});

export default ProductCard;
