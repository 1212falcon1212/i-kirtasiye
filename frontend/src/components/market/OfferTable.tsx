'use client';

import { useState } from 'react';
import Link from 'next/link';
import { Box, Check, Heart, Loader2, MapPin, ShoppingCart, Truck, Zap } from 'lucide-react';
import { Stars } from '@/components/market/Stars';
import { Offer } from '@/lib/api';
import { useCartStore } from '@/stores/useCartStore';
import { toast } from 'sonner';
import { cn } from '@/lib/utils';

interface OfferTableProps {
    offers: Offer[];
    canBuyFromSeller?: (sellerId: number) => boolean;
    className?: string;
}

const formatTL = (n: number) =>
    new Intl.NumberFormat('tr-TR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(n) + ' ₺';

export function OfferTable({ offers, canBuyFromSeller, className }: OfferTableProps) {
    const [adding, setAdding] = useState<number | null>(null);
    const [added, setAdded] = useState<number | null>(null);
    const { addItem, setOpen } = useCartStore();

    const handleAddToCart = async (offer: Offer) => {
        if (offer.stock <= 0) return;
        if (canBuyFromSeller && offer.seller?.id && !canBuyFromSeller(offer.seller.id)) return;
        setAdding(offer.id);
        try {
            await addItem(offer.id, 1);
            setAdded(offer.id);
            toast.success(
                <span className="flex items-center gap-2">
                    <Check className="w-4 h-4" style={{ color: 'var(--accent)' }} />
                    Ürün sepete eklendi
                </span>,
                {
                    action: { label: 'Sepeti Gör', onClick: () => setOpen(true) },
                },
            );
            setTimeout(() => setAdded(null), 2000);
        } catch (error) {
            toast.error(error instanceof Error ? error.message : 'Ürün eklenemedi.');
        } finally {
            setAdding(null);
        }
    };

    if (offers.length === 0) {
        return (
            <div
                className={cn('rounded-[10px] p-12 text-center', className)}
                style={{
                    background: 'var(--bg-elevated)',
                    border: '1px solid var(--border)',
                }}
            >
                <Box
                    className="w-12 h-12 mx-auto mb-3"
                    style={{ color: 'var(--fg-soft)' }}
                />
                <h3 className="text-[15px] font-semibold">Henüz teklif yok</h3>
                <p className="mt-1 text-[13px]" style={{ color: 'var(--fg-muted)' }}>
                    Bu ürün için aktif satıcı ilanı bulunmuyor.
                </p>
            </div>
        );
    }

    return (
        <div className={className}>
            {/* Column header (desktop) */}
            <div
                className="hidden md:grid items-center gap-3 px-4 py-2 text-[11px] font-semibold uppercase tracking-wide"
                style={{
                    gridTemplateColumns: '44px 1fr 120px 120px 140px 200px',
                    color: 'var(--fg-soft)',
                }}
            >
                <span>#</span>
                <span>Satıcı</span>
                <span>Stok</span>
                <span>Teslimat</span>
                <span className="text-right">Fiyat</span>
                <span className="text-right">İşlem</span>
            </div>

            {/* Rows */}
            <div className="flex flex-col gap-1.5">
                {offers.map((offer, idx) => {
                    const isBest = idx === 0;
                    const blocked =
                        canBuyFromSeller && offer.seller?.id
                            ? !canBuyFromSeller(offer.seller.id)
                            : false;
                    const verified = (offer.seller?.seller_score ?? 0) >= 4;
                    const fastShip = offer.stock > 50;
                    const reviewCount = offer.seller?.seller_review_count ?? 0;
                    const sellerScore = offer.seller?.seller_score ?? 0;
                    return (
                        <div
                            key={offer.id}
                            className={cn(
                                'rounded-[6px] transition-colors',
                                isBest && 'shadow-sm',
                            )}
                            style={{
                                background: isBest ? 'var(--accent-soft)' : 'var(--bg-elevated)',
                                border: isBest
                                    ? '1px solid var(--accent)'
                                    : '1px solid var(--border)',
                                borderLeftWidth: isBest ? 4 : 1,
                                borderLeftColor: isBest ? 'var(--accent)' : 'var(--border)',
                            }}
                        >
                            {/* Desktop layout */}
                            <div
                                className="hidden md:grid items-center gap-3 px-4 py-3.5"
                                style={{
                                    gridTemplateColumns: '44px 1fr 120px 120px 140px 200px',
                                }}
                            >
                                {/* Rank */}
                                <span
                                    className="mono text-[11px] font-semibold"
                                    style={{
                                        color: isBest ? 'var(--accent)' : 'var(--fg-soft)',
                                    }}
                                >
                                    #{(idx + 1).toString().padStart(2, '0')}
                                </span>

                                {/* Seller */}
                                <div className="min-w-0">
                                    <div className="flex items-center gap-2">
                                        {offer.seller?.id ? (
                                            <Link
                                                href={`/market/satici/${offer.seller.id}`}
                                                className="text-[14px] font-semibold truncate hover:underline"
                                                style={{ color: 'var(--fg)' }}
                                            >
                                                {offer.seller.nickname ||
                                                    offer.seller.business_name}
                                            </Link>
                                        ) : (
                                            <span
                                                className="text-[14px] font-semibold truncate"
                                                style={{ color: 'var(--fg)' }}
                                            >
                                                {offer.seller?.nickname ||
                                                    offer.seller?.business_name ||
                                                    'Satıcı'}
                                            </span>
                                        )}
                                        {verified && (
                                            <span
                                                className="chip chip-success"
                                                title="Onaylı satıcı"
                                            >
                                                <Check className="w-2.5 h-2.5" />
                                                Onaylı
                                            </span>
                                        )}
                                        {fastShip && (
                                            <span
                                                className="chip chip-accent"
                                                title="Hızlı kargo"
                                            >
                                                <Zap className="w-2.5 h-2.5" />
                                                Hızlı
                                            </span>
                                        )}
                                    </div>
                                    <div
                                        className="flex items-center gap-2 mt-1 text-[12px]"
                                        style={{ color: 'var(--fg-muted)' }}
                                    >
                                        <Stars value={sellerScore} size={12} />
                                        <span>
                                            <span
                                                style={{
                                                    color: 'var(--fg)',
                                                    fontWeight: 500,
                                                }}
                                            >
                                                {sellerScore.toFixed(1)}
                                            </span>{' '}
                                            ·{' '}
                                            <span className="mono">
                                                {reviewCount.toLocaleString('tr-TR')}
                                            </span>{' '}
                                            yorum
                                        </span>
                                        {offer.seller?.city && (
                                            <span className="inline-flex items-center gap-1">
                                                <MapPin className="w-3 h-3" />{' '}
                                                {offer.seller.city}
                                            </span>
                                        )}
                                    </div>
                                </div>

                                {/* Stock */}
                                <div>
                                    <div
                                        className="text-[10px] uppercase tracking-wide"
                                        style={{ color: 'var(--fg-soft)' }}
                                    >
                                        Stok
                                    </div>
                                    <div
                                        className="mono text-[13px] font-medium"
                                        style={{ color: 'var(--fg)' }}
                                    >
                                        {offer.stock.toLocaleString('tr-TR')} adet
                                    </div>
                                </div>

                                {/* Delivery */}
                                <div>
                                    <div
                                        className="text-[10px] uppercase tracking-wide"
                                        style={{ color: 'var(--fg-soft)' }}
                                    >
                                        Teslimat
                                    </div>
                                    <div
                                        className="text-[13px] font-medium inline-flex items-center gap-1.5"
                                        style={{ color: 'var(--fg)' }}
                                    >
                                        <Truck className="w-3.5 h-3.5" /> 1-2 gün
                                    </div>
                                </div>

                                {/* Price */}
                                <div className="text-right">
                                    <div
                                        className="text-[10px] uppercase tracking-wide"
                                        style={{ color: 'var(--fg-soft)' }}
                                    >
                                        KDV dahil
                                    </div>
                                    <div className="mono text-[18px] font-bold">
                                        {formatTL(offer.price)}
                                    </div>
                                </div>

                                {/* Action */}
                                <div className="flex items-center justify-end gap-2">
                                    <button
                                        type="button"
                                        className="btn btn-ghost btn-sm"
                                        title="Favorilere ekle"
                                        aria-label="Favorilere ekle"
                                    >
                                        <Heart className="w-3.5 h-3.5" />
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => handleAddToCart(offer)}
                                        disabled={
                                            adding === offer.id ||
                                            offer.stock <= 0 ||
                                            blocked
                                        }
                                        className={isBest ? 'btn btn-primary btn-sm' : 'btn btn-soft btn-sm'}
                                    >
                                        {adding === offer.id ? (
                                            <Loader2 className="w-3.5 h-3.5 animate-spin" />
                                        ) : added === offer.id ? (
                                            <>
                                                <Check className="w-3.5 h-3.5" /> Eklendi
                                            </>
                                        ) : offer.stock <= 0 ? (
                                            'Stok yok'
                                        ) : blocked ? (
                                            'İzin yok'
                                        ) : (
                                            <>
                                                <ShoppingCart className="w-3.5 h-3.5" /> Sepete ekle
                                            </>
                                        )}
                                    </button>
                                </div>
                            </div>

                            {/* Mobile layout */}
                            <div className="md:hidden p-4 space-y-3">
                                <div className="flex items-start justify-between gap-3">
                                    <div className="min-w-0">
                                        <div className="flex items-center gap-1.5 flex-wrap">
                                            {offer.seller?.id ? (
                                                <Link
                                                    href={`/market/satici/${offer.seller.id}`}
                                                    className="text-[14px] font-semibold truncate"
                                                    style={{ color: 'var(--fg)' }}
                                                >
                                                    {offer.seller.nickname ||
                                                        offer.seller.business_name}
                                                </Link>
                                            ) : (
                                                <span
                                                    className="text-[14px] font-semibold truncate"
                                                    style={{ color: 'var(--fg)' }}
                                                >
                                                    {offer.seller?.nickname ||
                                                        offer.seller?.business_name ||
                                                        'Satıcı'}
                                                </span>
                                            )}
                                            {verified && (
                                                <span className="chip chip-success">
                                                    <Check className="w-2.5 h-2.5" /> Onaylı
                                                </span>
                                            )}
                                            {isBest && (
                                                <span className="chip chip-accent">
                                                    En uygun
                                                </span>
                                            )}
                                        </div>
                                        <div
                                            className="text-[12px] mt-0.5"
                                            style={{ color: 'var(--fg-muted)' }}
                                        >
                                            <span className="mono">{offer.stock}</span> adet
                                            stok ·{' '}
                                            {offer.seller?.city ?? '—'}
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <div
                                            className="text-[10px] uppercase"
                                            style={{ color: 'var(--fg-soft)' }}
                                        >
                                            KDV dahil
                                        </div>
                                        <div className="mono text-[18px] font-bold">
                                            {formatTL(offer.price)}
                                        </div>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    onClick={() => handleAddToCart(offer)}
                                    disabled={
                                        adding === offer.id ||
                                        offer.stock <= 0 ||
                                        blocked
                                    }
                                    className={cn(
                                        'w-full',
                                        isBest ? 'btn btn-primary' : 'btn btn-soft',
                                    )}
                                >
                                    {adding === offer.id ? (
                                        <Loader2 className="w-4 h-4 animate-spin" />
                                    ) : added === offer.id ? (
                                        <>
                                            <Check className="w-4 h-4" /> Eklendi
                                        </>
                                    ) : offer.stock <= 0 ? (
                                        'Stokta yok'
                                    ) : blocked ? (
                                        'Bu satıcıdan satın alamazsınız'
                                    ) : (
                                        <>
                                            <ShoppingCart className="w-4 h-4" /> Sepete ekle
                                        </>
                                    )}
                                </button>
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
