'use client';

import { useEffect, useState } from 'react';
import { useParams, useRouter } from 'next/navigation';
import Link from 'next/link';
import { offersApi, Offer, SellerOffersResponse } from '@/lib/api';
import { GridProductCard } from '@/components/market/GridProductCard';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';
import {
    Store,
    MapPin,
    Box,
    ArrowLeft,
    Calendar,
    Tag,
    ShoppingCart,
    AlertCircle,
    Loader2,
    ChevronLeft,
    ChevronRight,
} from 'lucide-react';
import { useAuth } from '@/contexts/AuthContext';
import { useCartStore } from '@/stores/useCartStore';
import { toast } from 'sonner';

function formatPrice(price: number): string {
    return new Intl.NumberFormat('tr-TR', {
        style: 'currency',
        currency: 'TRY',
    }).format(price);
}

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString('tr-TR', {
        year: 'numeric',
        month: 'short',
    });
}

// Offer Card Component
function OfferCard({ offer }: { offer: Offer }) {
    const [isAdding, setIsAdding] = useState(false);
    const { addItem } = useCartStore();
    const { user } = useAuth();

    // Sellers cannot buy from other sellers
    const canBuy = !user || user.role !== 'seller';

    const handleAddToCart = async () => {
        if (!canBuy) return;

        setIsAdding(true);
        try {
            await addItem(offer.id, 1);
            toast.success('Sepete eklendi');
        } catch (error) {
            toast.error('Sepete eklenemedi');
        } finally {
            setIsAdding(false);
        }
    };

    const productImage = offer.product?.image_url || offer.product?.image;

    return (
        <div className="group bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-[#ffedd5] dark:hover:border-[#ffedd5] transition-all duration-300 overflow-hidden hover:shadow-lg">
            {/* Product Image */}
            <div className="relative aspect-square bg-slate-50 p-4 flex items-center justify-center">
                <Link href={`/market/product/${offer.product_id}`} className="absolute inset-0 z-10" />
                {productImage ? (
                    <img
                        src={productImage}
                        alt={offer.product?.name || 'Ürün'}
                        className="w-full h-full object-contain mix-blend-multiply group-hover:scale-105 transition-transform duration-300"
                    />
                ) : (
                    <Box className="w-16 h-16 text-slate-300" />
                )}

                {/* Stock Badge */}
                <div className="absolute top-2 right-2 z-20">
                    <Badge className={cn(
                        "text-xs",
                        offer.stock > 10 ? "bg-[#ffedd5] text-[#ea580c]" :
                            offer.stock > 0 ? "bg-amber-100 text-amber-700" :
                                "bg-red-100 text-red-700"
                    )}>
                        {offer.stock > 0 ? `${offer.stock} adet` : 'Stokta yok'}
                    </Badge>
                </div>
            </div>

            {/* Content */}
            <div className="p-4">
                {/* Brand */}
                {offer.product?.brand && (
                    <p className="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">
                        {offer.product.brand}
                    </p>
                )}

                {/* Product Name */}
                <Link
                    href={`/market/product/${offer.product_id}`}
                    className="text-sm font-bold text-slate-800 dark:text-slate-200 line-clamp-2 h-10 mb-2 hover:text-[#ea580c] transition-colors"
                >
                    {offer.product?.name || 'Ürün'}
                </Link>

                {/* Barcode */}
                <p className="text-xs text-slate-400 mb-3">
                    Barkod: {offer.product?.barcode}
                </p>

                {/* Expiry Date */}
                <div className="flex items-center gap-1 text-xs text-slate-500 mb-3">
                    <Calendar className="w-3 h-3" />
                    <span>SKT: {formatDate(offer.expiry_date)}</span>
                </div>

                {/* Price & Add to Cart */}
                <div className="flex items-center justify-between pt-3 border-t border-slate-100">
                    <div>
                        <span className="text-lg font-bold text-slate-900">
                            {formatPrice(offer.price)}
                        </span>
                    </div>

                    {canBuy && offer.stock > 0 && (
                        <Button
                            size="sm"
                            onClick={handleAddToCart}
                            disabled={isAdding}
                            className="bg-[#1e3a8a] hover:bg-[#1e40af]"
                        >
                            {isAdding ? (
                                <Loader2 className="w-4 h-4 animate-spin" />
                            ) : (
                                <>
                                    <ShoppingCart className="w-4 h-4 mr-1" />
                                    Ekle
                                </>
                            )}
                        </Button>
                    )}
                </div>
            </div>
        </div>
    );
}

// Empty State Component
function EmptyOffers() {
    return (
        <div className="flex flex-col items-center justify-center py-16 px-4 text-center">
            <div className="relative mb-6">
                <div className="absolute inset-0 bg-gradient-to-b from-slate-100 to-slate-50 rounded-full blur-lg scale-150"></div>
                <div className="relative w-24 h-24 bg-gradient-to-br from-slate-50 to-slate-100 rounded-full flex items-center justify-center border border-slate-200">
                    <Box className="w-12 h-12 text-slate-400" strokeWidth={1.5} />
                </div>
            </div>
            <h3 className="text-xl font-semibold text-slate-900 mb-2">
                Henüz ilan yoktur
            </h3>
            <p className="text-slate-500 max-w-md">
                Bu satıcı henüz aktif bir ilan yayınlamamış.
            </p>
        </div>
    );
}

// Loading Skeleton
function LoadingSkeleton() {
    return (
        <div className="max-w-7xl mx-auto px-4 py-8">
            {/* Back button skeleton */}
            <Skeleton className="h-10 w-32 mb-6" />

            {/* Header skeleton */}
            <div className="bg-white rounded-lg p-6 mb-8 border border-slate-200">
                <div className="flex items-center gap-4">
                    <Skeleton className="w-16 h-16 rounded-full" />
                    <div>
                        <Skeleton className="h-7 w-48 mb-2" />
                        <Skeleton className="h-4 w-32" />
                    </div>
                </div>
            </div>

            {/* Products grid skeleton */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                {[...Array(9)].map((_, i) => (
                    <div key={i} className="space-y-3">
                        <Skeleton className="aspect-square rounded-xl" />
                        <Skeleton className="h-4 w-16" />
                        <Skeleton className="h-5 w-full" />
                        <Skeleton className="h-6 w-20" />
                    </div>
                ))}
            </div>
        </div>
    );
}

// Error State
function ErrorState({ message, onRetry }: { message: string; onRetry: () => void }) {
    const router = useRouter();

    return (
        <div className="max-w-7xl mx-auto px-4 py-8">
            <div className="flex flex-col items-center justify-center py-16 text-center">
                <div className="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
                    <AlertCircle className="w-8 h-8 text-red-500" />
                </div>
                <h2 className="text-xl font-semibold text-slate-900 mb-2">Bir hata oluştu</h2>
                <p className="text-slate-500 mb-6 max-w-md">{message}</p>
                <div className="flex gap-3">
                    <Button variant="outline" onClick={() => router.back()}>
                        <ArrowLeft className="w-4 h-4 mr-2" />
                        Geri Dön
                    </Button>
                    <Button onClick={onRetry}>
                        Tekrar Dene
                    </Button>
                </div>
            </div>
        </div>
    );
}

export default function SaticiProfilePage() {
    const params = useParams();
    const router = useRouter();
    const sellerId = Number(params.id);

    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [data, setData] = useState<SellerOffersResponse | null>(null);
    const [currentPage, setCurrentPage] = useState(1);

    const fetchOffers = async (page: number = 1) => {
        setLoading(true);
        setError(null);

        try {
            const response = await offersApi.getSellerOffers(sellerId, page);

            if (response.error) {
                setError(response.error);
                return;
            }

            if (response.data) {
                setData(response.data);
                setCurrentPage(page);
            }
        } catch (err) {
            setError('Beklenmeyen bir hata oluştu');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        if (sellerId) {
            fetchOffers(1);
        }
    }, [sellerId]);

    if (loading && !data) {
        return <LoadingSkeleton />;
    }

    if (error) {
        return <ErrorState message={error} onRetry={() => fetchOffers(currentPage)} />;
    }

    if (!data) {
        return <ErrorState message="Veri yüklenemedi" onRetry={() => fetchOffers(1)} />;
    }

    const { seller, offers, pagination } = data;
    const displayName = seller.nickname || seller.business_name;

    return (
        <div className="max-w-7xl mx-auto px-4 py-8">
            {/* Back Button */}
            <Button
                variant="ghost"
                onClick={() => router.back()}
                className="mb-6 text-slate-600 hover:text-slate-900"
            >
                <ArrowLeft className="w-4 h-4 mr-2" />
                Geri Dön
            </Button>

            {/* Seller Header */}
            <div className="bg-white rounded-lg p-6 mb-8 border border-slate-200 shadow-sm">
                <div className="flex items-center gap-4">
                    <div className="w-16 h-16 bg-gradient-to-br from-[#ffedd5] to-teal-600 rounded-full flex items-center justify-center">
                        <Store className="w-8 h-8 text-white" />
                    </div>
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">
                            {displayName}
                        </h1>
                        {seller.city && (
                            <div className="flex items-center gap-1 text-slate-500 mt-1">
                                <MapPin className="w-4 h-4" />
                                <span>{seller.city}</span>
                            </div>
                        )}
                    </div>
                    <div className="ml-auto">
                        <Badge className="bg-[#ffedd5] text-[#ea580c] text-sm px-3 py-1">
                            <Tag className="w-4 h-4 mr-1" />
                            {pagination.total} ilan
                        </Badge>
                    </div>
                </div>
            </div>

            {/* Offers Grid or Empty State */}
            {offers.length === 0 ? (
                <EmptyOffers />
            ) : (
                <>
                    {/* Offers Grid */}
                    <div className="bg-white dark:bg-slate-900 rounded-xl border border-black/10 dark:border-slate-700 overflow-clip mb-8">
                        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 p-3">
                            {offers.map((offer) => (
                                offer.product ? (
                                    <GridProductCard key={offer.id} product={{
                                        ...offer.product,
                                        lowest_price: offer.price,
                                        offers_count: 1,
                                    }} />
                                ) : null
                            ))}
                        </div>
                    </div>

                    {/* Pagination */}
                    {pagination.last_page > 1 && (
                        <div className="flex items-center justify-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => fetchOffers(currentPage - 1)}
                                disabled={currentPage === 1 || loading}
                            >
                                <ChevronLeft className="w-4 h-4" />
                            </Button>

                            <div className="flex items-center gap-1">
                                {[...Array(pagination.last_page)].map((_, i) => {
                                    const page = i + 1;
                                    // Show first, last, and pages around current
                                    if (
                                        page === 1 ||
                                        page === pagination.last_page ||
                                        (page >= currentPage - 1 && page <= currentPage + 1)
                                    ) {
                                        return (
                                            <Button
                                                key={page}
                                                variant={page === currentPage ? "default" : "outline"}
                                                size="sm"
                                                onClick={() => fetchOffers(page)}
                                                disabled={loading}
                                                className={cn(
                                                    "w-8 h-8 p-0",
                                                    page === currentPage && "bg-[#1e3a8a]"
                                                )}
                                            >
                                                {page}
                                            </Button>
                                        );
                                    } else if (
                                        page === currentPage - 2 ||
                                        page === currentPage + 2
                                    ) {
                                        return <span key={page} className="text-slate-400">...</span>;
                                    }
                                    return null;
                                })}
                            </div>

                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => fetchOffers(currentPage + 1)}
                                disabled={currentPage === pagination.last_page || loading}
                            >
                                <ChevronRight className="w-4 h-4" />
                            </Button>
                        </div>
                    )}
                </>
            )}
        </div>
    );
}
