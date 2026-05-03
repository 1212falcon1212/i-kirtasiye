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
import { toast } from 'sonner';
import {
    Box,
    Heart,
    Loader2,
    Minus,
    Plus,
    ShoppingCart,
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

            {/* Main: image + PSF (left) | listings (right) */}
            <div className="grid grid-cols-1 lg:grid-cols-[minmax(0,420px)_1fr] gap-6 mb-10">
                {/* Left: title, brand, gallery, PSF */}
                <div
                    className="rounded-[10px] p-5 flex flex-col gap-4"
                    style={{
                        background: 'var(--bg-elevated)',
                        border: '1px solid var(--border)',
                        alignSelf: 'start',
                    }}
                >
                    <div className="flex flex-col gap-1.5">
                        {product.brand && (
                            <Link
                                href={`/market/marka/${product.brand.toLowerCase().replace(/\s+/g, '-')}`}
                                className="text-[11px] font-bold uppercase tracking-wider hover:underline"
                                style={{ color: 'var(--accent)' }}
                            >
                                {product.brand}
                            </Link>
                        )}
                        <h1 className="text-[20px] font-semibold leading-tight">{product.name}</h1>
                        {product.barcode && (
                            <div
                                className="mono text-[11px]"
                                style={{ color: 'var(--fg-soft)' }}
                            >
                                SKU {product.barcode}
                            </div>
                        )}
                    </div>

                    {/* Gallery */}
                    <div
                        className="grid gap-2"
                        style={{
                            gridTemplateColumns: showThumbnailColumn ? '56px 1fr' : '1fr',
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
                                                    ? '2px solid var(--primary)'
                                                    : '1px solid var(--border)',
                                            borderRadius: 6,
                                        }}
                                    >
                                        <Image
                                            src={thumb}
                                            alt={`${product.name} ${i + 1}`}
                                            fill
                                            sizes="56px"
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
                                border: '1px solid var(--border)',
                            }}
                        >
                            {mainImage ? (
                                <Image
                                    src={thumbnails[activeImageIdx] || mainImage}
                                    alt={product.name}
                                    fill
                                    sizes="(max-width: 1024px) 100vw, 420px"
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

                    {/* PSF */}
                    <div
                        className="rounded-[8px] px-4 py-3 flex items-baseline justify-between"
                        style={{ background: 'var(--bg-muted)' }}
                    >
                        <div>
                            <div
                                className="text-[10px] font-semibold uppercase tracking-wider"
                                style={{ color: 'var(--fg-soft)' }}
                            >
                                PSF
                            </div>
                            <div className="text-[10px]" style={{ color: 'var(--fg-soft)' }}>
                                Önerilen liste fiyatı
                            </div>
                        </div>
                        <div
                            className="mono text-[22px] font-bold"
                            style={{ color: 'var(--fg)' }}
                        >
                            {psf ? formatTL(psf) : '---'}
                        </div>
                    </div>

                    {/* Favorite */}
                    <button
                        type="button"
                        onClick={handleToggleFavorite}
                        disabled={isTogglingFavorite}
                        className="btn btn-ghost w-full"
                    >
                        <Heart
                            className="w-4 h-4"
                            fill={isFavorite ? 'currentColor' : 'none'}
                            style={{
                                color: isFavorite ? 'var(--danger)' : 'var(--fg)',
                            }}
                        />
                        {isFavorite ? 'Favorilerde' : 'Favorilere ekle'}
                    </button>
                </div>

                {/* Right: listings */}
                <div
                    className="rounded-[10px] p-5"
                    style={{
                        background: 'var(--bg-elevated)',
                        border: '1px solid var(--border)',
                        alignSelf: 'start',
                    }}
                >
                    <div className="flex items-end justify-between gap-3 mb-4 pb-4" style={{ borderBottom: '1px solid var(--border)' }}>
                        <div>
                            <h2 className="text-[18px] sm:text-[20px] font-semibold leading-tight">
                                Ürünün Tüm İlanları
                            </h2>
                            {offers.length > 0 && (
                                <div
                                    className="text-[12px] mt-1"
                                    style={{ color: 'var(--fg-muted)' }}
                                >
                                    <span className="mono font-semibold" style={{ color: 'var(--fg)' }}>
                                        {offers.length}
                                    </span>{' '}
                                    satıcı
                                    {highestPrice != null && lowestPrice != null && highestPrice > lowestPrice && (
                                        <>
                                            {' '}· Fiyat aralığı{' '}
                                            <span className="mono">{formatTL(lowestPrice)}</span> –{' '}
                                            <span className="mono">{formatTL(highestPrice)}</span>
                                        </>
                                    )}
                                </div>
                            )}
                        </div>
                        {cheapestOffer && cheapestOffer.stock > 0 && canBuyFromSeller(cheapestOffer.seller?.id ?? 0) && (
                            <div className="hidden sm:flex items-stretch gap-2">
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
                                        className="px-2.5 h-9"
                                        aria-label="Adet azalt"
                                    >
                                        <Minus className="w-3.5 h-3.5" />
                                    </button>
                                    <input
                                        className="mono w-12 text-center text-[13px]"
                                        value={quantity}
                                        onChange={(e) => {
                                            const v = parseInt(e.target.value);
                                            if (Number.isFinite(v) && v > 0) setQuantity(v);
                                        }}
                                        style={{
                                            height: 36,
                                            background: 'transparent',
                                            outline: 'none',
                                            border: 'none',
                                        }}
                                    />
                                    <button
                                        type="button"
                                        onClick={() => handleQuantityChange(1)}
                                        className="px-2.5 h-9"
                                        aria-label="Adet arttır"
                                    >
                                        <Plus className="w-3.5 h-3.5" />
                                    </button>
                                </div>
                                <button
                                    type="button"
                                    onClick={handleAddCheapestToCart}
                                    disabled={adding}
                                    className="btn btn-primary btn-sm whitespace-nowrap"
                                >
                                    {adding ? (
                                        <Loader2 className="w-3.5 h-3.5 animate-spin" />
                                    ) : (
                                        <>
                                            <ShoppingCart className="w-3.5 h-3.5" /> En ucuzu sepete
                                        </>
                                    )}
                                </button>
                            </div>
                        )}
                    </div>
                    <OfferTable offers={offers} canBuyFromSeller={canBuyFromSeller} />
                </div>
            </div>

            {/* Specs + description */}
            <div
                className="rounded-[10px] p-5 mb-10"
                style={{
                    background: 'var(--bg-elevated)',
                    border: '1px solid var(--border)',
                }}
            >
                <div className="grid grid-cols-1 lg:grid-cols-[280px_1fr] gap-6">
                    <div>
                        <div className="eyebrow mb-3">Ürün özellikleri</div>
                        <div className="flex flex-col gap-2 text-[13px]">
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
                    </div>
                    {product.description ? (
                        <div>
                            <div className="eyebrow mb-3">Açıklama</div>
                            <p
                                className="text-[13px] leading-relaxed"
                                style={{ color: 'var(--fg-muted)' }}
                            >
                                {product.description}
                            </p>
                        </div>
                    ) : (
                        <div className="hidden lg:flex items-center justify-center text-[12px]" style={{ color: 'var(--fg-soft)' }}>
                            Açıklama mevcut değil.
                        </div>
                    )}
                </div>
                {(reviews.length > 0 || averageRating > 0) && (
                    <div
                        className="mt-5 pt-4 flex items-center gap-3 text-[13px]"
                        style={{ borderTop: '1px solid var(--border)', color: 'var(--fg-muted)' }}
                    >
                        <Stars value={averageRating} size={14} />
                        <span>
                            <span style={{ color: 'var(--fg)', fontWeight: 600 }}>
                                {averageRating.toFixed(1)}
                            </span>{' '}
                            ·{' '}
                            <span className="mono">
                                {reviews.length.toLocaleString('tr-TR')}
                            </span>{' '}
                            değerlendirme
                        </span>
                    </div>
                )}
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
