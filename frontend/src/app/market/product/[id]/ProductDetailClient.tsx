'use client';

import { useEffect, useMemo, useState } from 'react';
import { useParams, useRouter } from 'next/navigation';
import Link from 'next/link';
import Image from 'next/image';
import {
    productsApi,
    wishlistApi,
    reviewsApi,
    distributorRetailerLinkApi,
    Product,
    Offer,
    Review,
} from '@/lib/api';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { OfferTable } from '@/components/market/OfferTable';
import { ProductCard } from '@/components/market/ProductCard';
import { Stars } from '@/components/market/Stars';
import { useCartStore } from '@/stores/useCartStore';
import { useAuth } from '@/contexts/AuthContext';
import { ProductJsonLd } from '@/components/seo/ProductJsonLd';
import { BreadcrumbJsonLd } from '@/components/seo/BreadcrumbJsonLd';
import { cn } from '@/lib/utils';
import { toast } from 'sonner';
import {
    Box,
    ChevronRight,
    Heart,
    Loader2,
    Minus,
    Plus,
    ShieldCheck,
    ShoppingCart,
    Truck,
    Wallet,
    Zap,
} from 'lucide-react';

const formatTL = (n?: number | string | null) => {
    if (n === undefined || n === null) return '---';
    const num = typeof n === 'string' ? parseFloat(n) : n;
    if (Number.isNaN(num)) return '---';
    return (
        new Intl.NumberFormat('tr-TR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(num) + ' ₺'
    );
};

export function ProductDetailClient() {
    const params = useParams();
    const router = useRouter();
    const productId = Number(params.id);

    const [product, setProduct] = useState<Product | null>(null);
    const [offers, setOffers] = useState<Offer[]>([]);
    const [similarProducts, setSimilarProducts] = useState<Product[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [isFavorite, setIsFavorite] = useState(false);
    const [isTogglingFavorite, setIsTogglingFavorite] = useState(false);
    const [quantity, setQuantity] = useState(1);
    const [adding, setAdding] = useState(false);
    const [reviews, setReviews] = useState<Review[]>([]);
    const [averageRating, setAverageRating] = useState(0);
    const [activeImageIdx, setActiveImageIdx] = useState(0);
    const [approvedRetailerIds, setApprovedRetailerIds] = useState<number[]>([]);

    const { addItem, setOpen } = useCartStore();
    const { user } = useAuth();

    const isSeller = user?.role === 'seller';

    useEffect(() => {
        if (!Number.isFinite(productId)) return;
        loadProductDetails();
        loadReviews();
    }, [productId]);

    useEffect(() => {
        if (user && isSeller) {
            loadApprovedRetailers();
        }
    }, [user, isSeller]);

    const loadApprovedRetailers = async () => {
        try {
            const res = await distributorRetailerLinkApi.approvedRetailerIds();
            if (res.data) {
                setApprovedRetailerIds(res.data.retailer_ids);
            }
        } catch (e) {
            console.error('Failed to load approved retailers:', e);
        }
    };

    const loadProductDetails = async () => {
        setIsLoading(true);
        try {
            const offersRes = await productsApi.getOffers(productId);
            if (offersRes.data) {
                setProduct(offersRes.data.product);
                const sortedOffers = [...(offersRes.data.offers || [])].sort(
                    (a, b) => a.price - b.price,
                );
                setOffers(sortedOffers);
            }
            // Load similar products from same category
            if (offersRes.data?.product?.category_id) {
                const simRes = await productsApi.getAll({
                    category: offersRes.data.product.category?.slug,
                    per_page: 5,
                });
                if (simRes.data?.products) {
                    setSimilarProducts(
                        simRes.data.products
                            .filter((p) => p.id !== productId)
                            .slice(0, 4),
                    );
                }
            }
        } finally {
            setIsLoading(false);
        }
    };

    const loadReviews = async () => {
        try {
            const res = await reviewsApi.getProductReviews(productId);
            if (res.data) {
                const list = res.data.reviews || [];
                setReviews(list);
                if (list.length > 0) {
                    const total = list.reduce(
                        (sum: number, r: Review) => sum + r.rating,
                        0,
                    );
                    setAverageRating(total / list.length);
                }
            }
        } catch (e) {
            console.error('Failed to load reviews', e);
        }
    };

    const canBuyFromSeller = (sellerId: number): boolean => {
        if (!user) return true;
        if (user.id === sellerId) return false;
        if (!isSeller) return true; // retailers can buy from any seller
        return approvedRetailerIds.includes(sellerId);
    };

    const cheapestOffer = useMemo(() => offers[0] || null, [offers]);
    const lowestPrice = useMemo(() => {
        if (cheapestOffer) return cheapestOffer.price;
        return product?.lowest_price ?? null;
    }, [cheapestOffer, product]);
    const highestPrice = useMemo(() => {
        if (offers.length > 0) return Math.max(...offers.map((o) => o.price));
        return product?.highest_price ?? null;
    }, [offers, product]);
    const psf = useMemo(() => {
        if (!product?.psf) return null;
        const num = typeof product.psf === 'string' ? parseFloat(product.psf) : product.psf;
        return Number.isFinite(num) ? num : null;
    }, [product]);

    const handleQuantityChange = (delta: number) => {
        const max = cheapestOffer?.stock ?? 99;
        setQuantity((q) => Math.max(1, Math.min(max, q + delta)));
    };

    const handleAddCheapestToCart = async () => {
        if (!cheapestOffer) return;
        if (!canBuyFromSeller(cheapestOffer.seller?.id ?? 0)) return;
        setAdding(true);
        try {
            await addItem(cheapestOffer.id, quantity);
            toast.success(`${quantity > 1 ? `${quantity} adet ürün` : 'Ürün'} sepete eklendi`, {
                action: { label: 'Sepeti Gör', onClick: () => setOpen(true) },
            });
        } catch (error) {
            toast.error(error instanceof Error ? error.message : 'Ürün eklenemedi');
        } finally {
            setAdding(false);
        }
    };

    const handleToggleFavorite = async () => {
        if (!user) {
            toast.error('Favorilere eklemek için giriş yapmalısınız.');
            router.push('/login');
            return;
        }
        if (isTogglingFavorite || !product) return;
        setIsTogglingFavorite(true);
        try {
            const response = await wishlistApi.toggle(product.id);
            if (response.data) {
                setIsFavorite(response.data.in_wishlist);
                toast.success(
                    response.data.in_wishlist
                        ? 'Favorilere eklendi'
                        : 'Favorilerden çıkarıldı',
                );
            }
        } catch (error) {
            console.error('Failed to toggle wishlist:', error);
            toast.error('Bir hata oluştu.');
        } finally {
            setIsTogglingFavorite(false);
        }
    };

    if (isLoading) {
        return (
            <div className="max-w-[1440px] mx-auto px-6 pt-5">
                <Skeleton className="h-4 w-64 mb-4" />
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                    <Skeleton className="aspect-square rounded-[10px]" />
                    <div className="space-y-4">
                        <Skeleton className="h-8 w-3/4" />
                        <Skeleton className="h-4 w-1/2" />
                        <Skeleton className="h-40 w-full" />
                    </div>
                </div>
            </div>
        );
    }

    if (!product) {
        return (
            <div className="max-w-[1440px] mx-auto px-6 py-12">
                <div
                    className="rounded-[10px] py-16 text-center"
                    style={{
                        background: 'var(--bg-elevated)',
                        border: '1px solid var(--border)',
                    }}
                >
                    <Box
                        className="w-16 h-16 mx-auto mb-3"
                        style={{ color: 'var(--fg-soft)' }}
                    />
                    <h3 className="text-[16px] font-semibold mb-1">Ürün bulunamadı</h3>
                    <p className="text-[13px]" style={{ color: 'var(--fg-muted)' }}>
                        Aradığınız ürün mevcut değil.
                    </p>
                    <Link href="/market">
                        <Button className="mt-4">Pazaryerine Dön</Button>
                    </Link>
                </div>
            </div>
        );
    }

    const breadcrumbItems = [
        { name: 'Anasayfa', url: 'https://i-kirtasiye.com/market' },
        ...(product.category
            ? [
                  {
                      name: product.category.name,
                      url: `https://i-kirtasiye.com/market/category/${product.category.slug}`,
                  },
              ]
            : []),
        { name: product.name, url: `https://i-kirtasiye.com/market/product/${product.id}` },
    ];

    const mainImage = product.image_url || product.image;
    const galleryImages = (product as { images?: string[] }).images;
    const thumbnails =
        Array.isArray(galleryImages) && galleryImages.length > 0
            ? galleryImages
            : mainImage
              ? [mainImage]
              : [];
    const showThumbnailColumn = thumbnails.length > 1;

    return (
        <div className="max-w-[1440px] mx-auto px-6 pt-5 pb-12">
            <ProductJsonLd
                name={product.name}
                description={product.description}
                image={mainImage}
                brand={product.brand}
                barcode={product.barcode}
                lowestPrice={lowestPrice ?? undefined}
                highestPrice={highestPrice ?? undefined}
                offersCount={offers.length}
                inStock={offers.length > 0}
                reviewCount={reviews.length}
                averageRating={averageRating}
            />
            <BreadcrumbJsonLd items={breadcrumbItems} />

            {/* Breadcrumb */}
            <div className="mb-4 text-[12px]" style={{ color: 'var(--fg-muted)' }}>
                <Link href="/market" className="hover:underline">
                    Anasayfa
                </Link>
                {product.category && (
                    <>
                        <span> · </span>
                        <Link
                            href={`/market/products?category=${product.category.slug}`}
                            className="hover:underline"
                        >
                            {product.category.name}
                        </Link>
                    </>
                )}
                <span> · </span>
                <span style={{ color: 'var(--fg)' }}>{product.name}</span>
            </div>

            {/* Hero: gallery + summary */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
                {/* Gallery */}
                <div>
                    <div
                        className="grid gap-2"
                        style={{
                            gridTemplateColumns: showThumbnailColumn ? '64px 1fr' : '1fr',
                        }}
                    >
                        {showThumbnailColumn && (
                            <div className="flex flex-col gap-2">
                                {thumbnails.map((thumb, i) => (
                                    <button
                                        type="button"
                                        key={i}
                                        onClick={() => setActiveImageIdx(i)}
                                        className="relative aspect-square overflow-hidden"
                                        style={{
                                            background: '#ffffff',
                                            border:
                                                i === activeImageIdx
                                                    ? '2px solid var(--accent)'
                                                    : '1px solid var(--border)',
                                            borderRadius: 6,
                                        }}
                                    >
                                        <Image
                                            src={thumb}
                                            alt={`${product.name} ${i + 1}`}
                                            fill
                                            sizes="64px"
                                            className="object-contain p-1"
                                        />
                                    </button>
                                ))}
                            </div>
                        )}
                        <div
                            className={
                                'relative aspect-square overflow-hidden ' +
                                (mainImage ? '' : 'ph-image')
                            }
                            style={{
                                borderRadius: 'var(--radius-lg)',
                                background: mainImage ? '#ffffff' : undefined,
                                border: mainImage ? '1px solid var(--border)' : undefined,
                            }}
                        >
                            {mainImage ? (
                                <Image
                                    src={thumbnails[activeImageIdx] || mainImage}
                                    alt={product.name}
                                    fill
                                    sizes="(max-width: 1024px) 100vw, 600px"
                                    className="object-contain p-6"
                                    priority
                                />
                            ) : (
                                <span style={{ color: 'var(--fg-soft)' }}>
                                    {product.brand || 'ÜRÜN'}
                                </span>
                            )}
                        </div>
                    </div>
                </div>

                {/* Summary */}
                <div className="flex flex-col gap-3.5">
                    {/* Brand link */}
                    {product.brand && (
                        <Link
                            href={`/market/marka/${product.brand.toLowerCase().replace(/\s+/g, '-')}`}
                            className="text-[13px] hover:underline"
                            style={{ color: 'var(--fg-muted)' }}
                        >
                            {product.brand}
                        </Link>
                    )}
                    <h1 className="text-[26px] font-semibold leading-tight">{product.name}</h1>
                    <div
                        className="flex items-center gap-3 text-[13px] flex-wrap"
                        style={{ color: 'var(--fg-muted)' }}
                    >
                        <Stars value={averageRating} size={14} />
                        <span>
                            <span style={{ color: 'var(--fg)', fontWeight: 500 }}>
                                {averageRating.toFixed(1)}
                            </span>{' '}
                            ·{' '}
                            <span className="mono">
                                {reviews.length.toLocaleString('tr-TR')}
                            </span>{' '}
                            değerlendirme
                        </span>
                        {product.barcode && (
                            <span className="mono text-[12px]" style={{ color: 'var(--fg-muted)' }}>
                                SKU {product.barcode}
                            </span>
                        )}
                    </div>

                    {/* Price hero card */}
                    <div
                        className="rounded-[10px] p-5 flex flex-col gap-3"
                        style={{
                            background: 'var(--bg-elevated)',
                            border: '1px solid var(--border)',
                        }}
                    >
                        <div className="flex justify-between items-baseline gap-3">
                            <div>
                                <div
                                    className="text-[11px] uppercase tracking-wide"
                                    style={{ color: 'var(--fg-soft)' }}
                                >
                                    Önerilen liste fiyatı
                                </div>
                                <div
                                    className="mono text-[16px] line-through"
                                    style={{ color: 'var(--fg-muted)' }}
                                >
                                    {psf ? formatTL(psf) : '---'}
                                </div>
                                <div className="text-[10px]" style={{ color: 'var(--fg-soft)' }}>
                                    KDV dahil
                                </div>
                            </div>
                            <div className="text-right">
                                <div
                                    className="text-[11px] font-semibold uppercase tracking-wide"
                                    style={{ color: 'var(--success)' }}
                                >
                                    En düşük
                                </div>
                                <div
                                    className="mono text-[28px] sm:text-[32px] font-bold leading-none"
                                    style={{ color: 'var(--accent)' }}
                                >
                                    {formatTL(lowestPrice)}
                                </div>
                                <div
                                    className="text-[11px] mt-1"
                                    style={{ color: 'var(--fg-soft)' }}
                                >
                                    KDV dahil · adet başı
                                </div>
                            </div>
                        </div>

                        {offers.length > 0 && (
                            <div
                                className="flex items-center gap-2 px-3 py-2.5 rounded-[6px] text-[12px]"
                                style={{
                                    background: 'var(--accent-soft)',
                                    color: 'var(--accent)',
                                }}
                            >
                                <Zap className="w-3.5 h-3.5" />
                                <span>
                                    <strong>{offers.length} satıcı</strong> bu ürünü listeliyor
                                    {highestPrice != null && lowestPrice != null && (
                                        <>
                                            {' '}— fiyat aralığı{' '}
                                            <span className="mono">{formatTL(lowestPrice)}</span> –{' '}
                                            <span className="mono">{formatTL(highestPrice)}</span>
                                        </>
                                    )}
                                </span>
                            </div>
                        )}

                        {/* Quantity + cart */}
                        <div className="flex items-stretch gap-2">
                            <div
                                className="flex items-center overflow-hidden"
                                style={{
                                    border: '1px solid var(--border)',
                                    borderRadius: 'var(--radius)',
                                }}
                            >
                                <button
                                    type="button"
                                    onClick={() => handleQuantityChange(-1)}
                                    className="px-3 h-11"
                                    aria-label="Adet azalt"
                                >
                                    <Minus className="w-3.5 h-3.5" />
                                </button>
                                <input
                                    className="mono w-14 text-center text-[14px]"
                                    value={quantity}
                                    onChange={(e) => {
                                        const v = parseInt(e.target.value);
                                        if (Number.isFinite(v) && v > 0) setQuantity(v);
                                    }}
                                    style={{
                                        height: 44,
                                        background: 'transparent',
                                        outline: 'none',
                                        border: 'none',
                                    }}
                                />
                                <button
                                    type="button"
                                    onClick={() => handleQuantityChange(1)}
                                    className="px-3 h-11"
                                    aria-label="Adet arttır"
                                >
                                    <Plus className="w-3.5 h-3.5" />
                                </button>
                            </div>
                            <button
                                type="button"
                                onClick={handleAddCheapestToCart}
                                disabled={
                                    !cheapestOffer ||
                                    adding ||
                                    cheapestOffer.stock <= 0 ||
                                    !canBuyFromSeller(cheapestOffer.seller?.id ?? 0)
                                }
                                className="btn btn-primary btn-lg flex-1"
                            >
                                {adding ? (
                                    <Loader2 className="w-4 h-4 animate-spin" />
                                ) : (
                                    <>
                                        <ShoppingCart className="w-4 h-4" /> En ucuzu sepete ekle
                                    </>
                                )}
                            </button>
                            <button
                                type="button"
                                onClick={handleToggleFavorite}
                                disabled={isTogglingFavorite}
                                className="btn btn-ghost btn-lg"
                                title="Favorilere ekle"
                                aria-label="Favorilere ekle"
                            >
                                <Heart
                                    className="w-4 h-4"
                                    fill={isFavorite ? 'currentColor' : 'none'}
                                    style={{
                                        color: isFavorite ? 'var(--danger)' : 'var(--fg)',
                                    }}
                                />
                            </button>
                        </div>

                        {/* Mini info row */}
                        <div
                            className="grid grid-cols-3 gap-2.5 mt-1 text-[12px]"
                            style={{ color: 'var(--fg-muted)' }}
                        >
                            <span className="inline-flex items-center gap-1.5">
                                <Truck className="w-3.5 h-3.5" /> 1-2 günde teslim
                            </span>
                            <span className="inline-flex items-center gap-1.5">
                                <ShieldCheck className="w-3.5 h-3.5" /> Onaylı satıcı
                            </span>
                            <span className="inline-flex items-center gap-1.5">
                                <Wallet className="w-3.5 h-3.5" /> Vadeli ödeme
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {/* Specs + listings split */}
            <div className="grid grid-cols-1 lg:grid-cols-[320px_1fr] gap-6 mb-12">
                {/* Specs */}
                <div
                    className="rounded-[10px] p-5"
                    style={{
                        background: 'var(--bg-elevated)',
                        border: '1px solid var(--border)',
                        alignSelf: 'start',
                    }}
                >
                    <div className="eyebrow mb-3">Ürün özellikleri</div>
                    <div
                        className="flex flex-col gap-2 text-[13px]"
                    >
                        {[
                            ['Marka', product.brand],
                            ['Üretici', product.manufacturer],
                            ['Kategori', product.category?.name],
                            ['Barkod', product.barcode],
                        ]
                            .filter(([, v]) => !!v)
                            .map(([k, v]) => (
                                <div
                                    key={k as string}
                                    className="flex justify-between gap-3 pb-1.5"
                                    style={{
                                        borderBottom: '1px dashed var(--border)',
                                    }}
                                >
                                    <span style={{ color: 'var(--fg-soft)' }}>{k}</span>
                                    <span style={{ fontWeight: 500 }}>{v}</span>
                                </div>
                            ))}
                    </div>
                    {product.description && (
                        <>
                            <div className="eyebrow mt-5 mb-2">Açıklama</div>
                            <p
                                className="text-[13px] leading-relaxed"
                                style={{ color: 'var(--fg-muted)' }}
                            >
                                {product.description}
                            </p>
                        </>
                    )}
                </div>

                {/* Listings */}
                <div>
                    <div className="mb-4 flex items-end justify-between gap-3">
                        <div>
                            <div className="eyebrow">Tüm satıcı ilanları</div>
                            <h2 className="text-[20px] sm:text-[22px] font-semibold mt-1 leading-tight">
                                {offers.length} satıcının ilanı — en düşük fiyattan sıralı
                            </h2>
                        </div>
                    </div>
                    <OfferTable offers={offers} canBuyFromSeller={canBuyFromSeller} />
                </div>
            </div>

            {/* Similar products */}
            {similarProducts.length > 0 && (
                <div>
                    <h2 className="text-[20px] font-semibold mb-4">Benzer Ürünler</h2>
                    <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3.5">
                        {similarProducts.map((p) => (
                            <ProductCard key={p.id} product={p} />
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}
