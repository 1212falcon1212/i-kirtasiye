'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { useCartStore, CartBySeller, CartItem, ValidationIssue } from '@/stores/useCartStore';
import { productsApi, Product, shippingApi } from '@/lib/api';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Checkbox } from '@/components/ui/checkbox';
import { ProductCarousel } from '@/components/market/ProductCarousel';
import {
    ShoppingCart,
    Plus,
    Minus,
    Trash2,
    ArrowRight,
    ArrowLeft,
    Box,
    AlertCircle,
    Store,
    Loader2,
    X,
    Truck,
    MapPin,
    Gift,
    ShieldCheck,
} from 'lucide-react';
import { cn } from '@/lib/utils';

function ProductImage({ src, alt }: { src: string | null | undefined; alt: string }) {
    const [error, setError] = useState(false);

    if (!src || error) {
        return (
            <div className="w-[72px] h-[72px] bg-slate-50 rounded-xl flex items-center justify-center flex-shrink-0 border border-slate-100">
                <Box className="h-6 w-6 text-slate-300" />
            </div>
        );
    }

    return (
        <img
            src={src}
            alt={alt}
            className="w-[72px] h-[72px] rounded-xl object-cover flex-shrink-0 border border-slate-100"
            onError={() => setError(true)}
        />
    );
}

function formatPrice(price: number) {
    return new Intl.NumberFormat('tr-TR', {
        style: 'currency',
        currency: 'TRY',
    }).format(price);
}

function CartItemRow({
    item,
    isLoading,
    validationMessage,
    priceIssue,
    isLast,
    onQuantityChange,
    onRemove,
}: {
    item: CartItem;
    isLoading: boolean;
    validationMessage?: string;
    priceIssue?: ValidationIssue;
    isLast?: boolean;
    onQuantityChange: (itemId: number, qty: number) => void;
    onRemove: (itemId: number) => void;
}) {
    const imageSrc = item.product.image_url ||
        (item.product.image ? `${process.env.NEXT_PUBLIC_API_URL?.replace('/api', '')}/storage/${item.product.image}` : null);

    return (
        <div className={cn(
            "px-4 sm:px-5 py-4",
            !isLast && "border-b border-slate-100",
            validationMessage && "bg-red-50/50"
        )}>
            <div className="flex gap-4">
                {/* Product Image */}
                <Link href={`/market/product/${item.product_id}`} className="flex-shrink-0">
                    <ProductImage src={imageSrc} alt={item.product.name} />
                </Link>

                {/* Product Info */}
                <div className="flex-1 min-w-0">
                    <div className="flex items-start justify-between gap-3">
                        <div className="min-w-0 flex-1">
                            <Link
                                href={`/market/product/${item.product_id}`}
                                className="text-sm font-medium text-slate-800 hover:text-[#b8651a] transition-colors line-clamp-2 leading-snug"
                            >
                                {item.product.name}
                            </Link>
                            {item.product.barcode && (
                                <p className="text-[11px] text-slate-400 mt-1 font-mono tracking-wide">{item.product.barcode}</p>
                            )}
                        </div>
                        <button
                            className="p-1.5 rounded-lg text-slate-300 hover:text-red-500 hover:bg-red-50 transition-all flex-shrink-0"
                            onClick={() => onRemove(item.id)}
                            disabled={isLoading}
                        >
                            <X className="h-4 w-4" />
                        </button>
                    </div>

                    {validationMessage && (
                        <p className="text-xs text-red-600 mt-1.5 flex items-center gap-1">
                            <AlertCircle className="w-3 h-3 flex-shrink-0" />
                            {validationMessage}
                        </p>
                    )}

                    {/* Price + Quantity Row */}
                    <div className="flex items-center justify-between mt-3 gap-3">
                        {priceIssue && priceIssue.old_price && priceIssue.new_price ? (
                            <div className="flex items-center gap-1.5">
                                <span className="text-xs text-slate-400 line-through">{formatPrice(priceIssue.old_price)}</span>
                                <span className={cn("text-sm font-semibold", priceIssue.new_price > priceIssue.old_price ? "text-red-600" : "text-[#b8651a]")}>
                                    {formatPrice(priceIssue.new_price)}
                                </span>
                            </div>
                        ) : (
                            <p className="text-sm text-slate-400">
                                {formatPrice(item.price_at_addition)} / adet
                            </p>
                        )}

                        {/* Quantity Controls */}
                        <div className="flex items-center border border-slate-200 rounded-lg overflow-hidden">
                            <button
                                className="h-8 w-8 flex items-center justify-center text-slate-500 hover:bg-slate-50 transition-colors disabled:opacity-40"
                                onClick={() => onQuantityChange(item.id, item.quantity - 1)}
                                disabled={isLoading}
                            >
                                <Minus className="h-3.5 w-3.5" />
                            </button>
                            <span className="w-10 text-center text-sm font-semibold text-slate-800 border-x border-slate-200 h-8 flex items-center justify-center bg-slate-50/50">
                                {item.quantity}
                            </span>
                            <button
                                className="h-8 w-8 flex items-center justify-center text-slate-500 hover:bg-slate-50 transition-colors disabled:opacity-40"
                                onClick={() => onQuantityChange(item.id, item.quantity + 1)}
                                disabled={isLoading || item.quantity >= item.offer.stock}
                            >
                                <Plus className="h-3.5 w-3.5" />
                            </button>
                        </div>

                        {/* Line Total */}
                        <p className="text-sm font-bold text-slate-900 min-w-[80px] text-right">
                            {formatPrice(item.price_at_addition * item.quantity)}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}

interface ShippingInfo {
    cost: number;
    loading: boolean;
    freeShipping: boolean;
}

function SellerGroup({
    group,
    isLoading,
    isSelected,
    validationIssues,
    shippingInfo,
    onToggle,
    onQuantityChange,
    onRemove,
}: {
    group: CartBySeller;
    isLoading: boolean;
    isSelected: boolean;
    validationIssues: ValidationIssue[];
    shippingInfo?: ShippingInfo;
    onToggle: () => void;
    onQuantityChange: (itemId: number, qty: number) => void;
    onRemove: (itemId: number) => void;
}) {
    return (
        <div className={cn(
            "transition-opacity duration-200",
            !isSelected && "opacity-50"
        )}>
            {/* Seller Header */}
            <div
                className="flex items-center gap-3 px-4 sm:px-5 py-3.5 bg-slate-50/70 border-b border-slate-100 cursor-pointer"
                onClick={(e) => {
                    // Prevent double-toggle when clicking the checkbox itself
                    if ((e.target as HTMLElement).closest('[data-slot="checkbox"]')) return;
                    onToggle();
                }}
            >
                <Checkbox
                    checked={isSelected}
                    onCheckedChange={onToggle}
                    className="h-5 w-5 rounded-md border-2 border-slate-300 data-[state=checked]:bg-[#fbeede] data-[state=checked]:border-[#fbeede]"
                />
                <div className="w-9 h-9 rounded-xl bg-[#fbeede] flex items-center justify-center flex-shrink-0">
                    <Store className="w-4 h-4 text-[#b8651a]" />
                </div>
                <div className="flex-1 min-w-0">
                    {group.seller?.id ? (
                        <Link
                            href={`/market/satici/${group.seller.id}`}
                            className="font-semibold text-sm text-slate-800 truncate hover:text-[#b8651a] hover:underline transition-colors block"
                            onClick={(e) => e.stopPropagation()}
                        >
                            {group.seller.nickname || group.seller.business_name || 'Satıcı'}
                        </Link>
                    ) : (
                        <h3 className="font-semibold text-sm text-slate-800 truncate">
                            {group.seller?.nickname || group.seller?.business_name || 'Satıcı'}
                        </h3>
                    )}
                    {group.seller?.city && (
                        <p className="text-xs text-slate-400 flex items-center gap-1 mt-0.5">
                            <MapPin className="w-3 h-3" />
                            {group.seller.city}
                        </p>
                    )}
                </div>
                <div className="text-right flex-shrink-0">
                    <p className="text-[11px] text-slate-400 uppercase tracking-wider font-medium">Ara Toplam</p>
                    <p className="text-base font-bold text-slate-900 tabular-nums">
                        {formatPrice(group.subtotal)}
                    </p>
                </div>
            </div>

            {/* Items */}
            {group.items.map((item, idx) => {
                const issue = validationIssues.find((i) => i.item_id === item.id && i.type !== 'price_changed');
                const priceIssue = validationIssues.find((i) => i.item_id === item.id && i.type === 'price_changed');
                return (
                    <CartItemRow
                        key={item.id}
                        item={item}
                        isLoading={isLoading}
                        isLast={idx === group.items.length - 1 && !shippingInfo}
                        validationMessage={issue?.message}
                        priceIssue={priceIssue}
                        onQuantityChange={onQuantityChange}
                        onRemove={onRemove}
                    />
                );
            })}

            {/* Shipping Info - always free */}
            <div className="flex items-center gap-2.5 px-4 sm:px-5 py-3 bg-slate-50/40 border-t border-slate-100">
                <Truck className="w-4 h-4 text-slate-400 flex-shrink-0" />
                <span className="text-xs font-semibold text-[#b8651a] bg-[#fbeede] px-2.5 py-1 rounded-full flex items-center gap-1">
                    <Gift className="w-3 h-3" />
                    Ücretsiz Kargo
                </span>
            </div>
        </div>
    );
}

export default function SepetPage() {
    const {
        itemsBySeller,
        itemCount,
        total,
        isLoading,
        validationIssues,
        selectedSellers,
        selectedTotal,
        fetchCart,
        validateCart,
        updateQuantity,
        removeItem,
        clearCart,
        toggleSeller,
        selectAllSellers,
        deselectAllSellers,
    } = useCartStore();

    const [clearing, setClearing] = useState(false);
    const [suggestedProducts, setSuggestedProducts] = useState<Product[]>([]);
    const [sellerShipping, setSellerShipping] = useState<Record<number, ShippingInfo>>({});

    useEffect(() => {
        fetchCart();
        validateCart();
    }, [fetchCart, validateCart]);

    // Fetch shipping per seller
    useEffect(() => {
        if (itemsBySeller.length === 0) return;

        const fetchShipping = async () => {
            const newShipping: Record<number, ShippingInfo> = {};

            itemsBySeller.forEach(group => {
                const sellerId = group.seller?.id;
                if (sellerId) {
                    newShipping[sellerId] = { cost: 0, loading: true, freeShipping: false };
                }
            });
            setSellerShipping(newShipping);

            await Promise.all(itemsBySeller.map(async (group) => {
                const sellerId = group.seller?.id;
                if (!sellerId) return;

                const totalDesi = group.items.reduce((sum, item) => {
                    const desi = (item.product as unknown as { desi?: number }).desi || 0.5;
                    return sum + (desi * item.quantity);
                }, 0) || 1;

                try {
                    const response = await shippingApi.getOptions(totalDesi, group.subtotal);
                    const options = response.data?.options || [];
                    if (options.length > 0) {
                        const cheapest = options.reduce((min, o) => o.price < min.price ? o : min, options[0]);
                        newShipping[sellerId] = {
                            cost: cheapest.price,
                            loading: false,
                            freeShipping: cheapest.is_free || cheapest.price === 0,
                        };
                    } else {
                        newShipping[sellerId] = { cost: 0, loading: false, freeShipping: false };
                    }
                } catch {
                    newShipping[sellerId] = { cost: 0, loading: false, freeShipping: false };
                }
            }));

            setSellerShipping({ ...newShipping });
        };

        fetchShipping();
    }, [itemsBySeller]);

    // Fetch suggested products
    useEffect(() => {
        const loadSuggestions = async () => {
            try {
                const response = await productsApi.getAll({ per_page: 30 });
                const products = response.data?.products || [];
                const withOffers = products.filter((p: Product) => (p.offers_count ?? 0) > 0);
                const shuffled = [...withOffers].sort(() => Math.random() - 0.5);
                setSuggestedProducts(shuffled.slice(0, 12));
            } catch {
                // Silently fail
            }
        };
        loadSuggestions();
    }, []);

    const handleQuantityChange = async (itemId: number, newQuantity: number) => {
        if (newQuantity < 1) {
            await removeItem(itemId);
        } else {
            await updateQuantity(itemId, newQuantity);
        }
        validateCart();
    };

    const handleRemove = async (itemId: number) => {
        await removeItem(itemId);
        validateCart();
    };

    const handleClearCart = async () => {
        setClearing(true);
        try {
            await clearCart();
        } finally {
            setClearing(false);
        }
    };

    const hasBlockingIssues = validationIssues.some(
        (i) => i.type === 'unavailable' || i.type === 'stock'
    );

    const priceChangedCount = validationIssues.filter(i => i.type === 'price_changed').length;

    const selectedCount = selectedSellers.length;
    const totalSellerCount = itemsBySeller.length;
    const allSelected = selectedCount === totalSellerCount && totalSellerCount > 0;
    const noneSelected = selectedCount === 0;
    const computedSelectedTotal = selectedTotal();

    const MIN_ORDER_AMOUNT = 2000;
    const isBelowMinOrder = computedSelectedTotal < MIN_ORDER_AMOUNT && !noneSelected;
    const remainingForMinOrder = MIN_ORDER_AMOUNT - computedSelectedTotal;

    const totalShipping = selectedSellers.reduce((sum, sellerId) => {
        const info = sellerShipping[sellerId];
        if (info && !info.loading) return sum + info.cost;
        return sum;
    }, 0);

    const anyShippingLoading = selectedSellers.some(id => sellerShipping[id]?.loading);

    // Empty cart state
    if (itemCount === 0 && !isLoading) {
        return (
            <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div className="flex flex-col items-center justify-center py-20 text-center">
                    <div className="w-24 h-24 rounded-full bg-slate-50 flex items-center justify-center mb-6">
                        <ShoppingCart className="h-12 w-12 text-slate-300" />
                    </div>
                    <h1 className="text-2xl font-bold text-slate-900 mb-2">
                        Sepetiniz boş
                    </h1>
                    <p className="text-slate-500 mb-8 max-w-sm">
                        Harika ürünlerimize göz atın ve sepetinizi doldurun!
                    </p>
                    <Button
                        asChild
                        className="bg-[#b8651a] hover:bg-[#934f12] text-white"
                        size="lg"
                    >
                        <Link href="/market">
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Alışverişe Başla
                        </Link>
                    </Button>
                </div>

                {suggestedProducts.length > 0 && (
                    <div className="mt-10 sm:mt-14 w-full max-w-6xl bg-white rounded-lg border border-slate-200 shadow-sm p-4 sm:p-6">
                        <ProductCarousel
                            title="İlginizi Çekebilir"
                            products={suggestedProducts}
                            linkUrl="/market"
                        />
                    </div>
                )}
            </div>
        );
    }

    return (
        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10">
            {/* Page Header */}
            <div className="flex items-center justify-between mb-8">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 flex items-center gap-2.5">
                        <div className="w-8 h-8 rounded-lg bg-[#fbeede] flex items-center justify-center">
                            <ShoppingCart className="h-4.5 w-4.5 text-[#b8651a]" />
                        </div>
                        Sepetim
                        <span className="text-sm font-normal text-slate-400 ml-1">({itemCount} ürün)</span>
                    </h1>
                </div>
                <Button
                    variant="ghost"
                    size="sm"
                    className="text-slate-400 hover:text-red-500 hover:bg-red-50 gap-2"
                    onClick={handleClearCart}
                    disabled={isLoading || clearing}
                >
                    {clearing ? (
                        <Loader2 className="h-4 w-4 animate-spin" />
                    ) : (
                        <Trash2 className="h-4 w-4" />
                    )}
                    <span className="hidden sm:inline">Sepeti Temizle</span>
                </Button>
            </div>

            {/* Seller Selection Controls */}
            {totalSellerCount > 1 && (
                <div className="flex flex-wrap items-center gap-3 mb-5 bg-white rounded-xl border border-slate-200 px-4 py-3 shadow-sm">
                    <div className="flex items-center gap-2 flex-1 min-w-[180px]">
                        <div className="w-2 h-2 rounded-full bg-[#fbeede]" />
                        <span className="text-sm text-slate-600 font-medium">
                            Seçili Satıcılar: <span className="text-[#b8651a] font-bold">{selectedCount}</span> / {totalSellerCount}
                        </span>
                    </div>
                    <div className="flex gap-2">
                        <button
                            className={cn(
                                "px-3.5 py-1.5 rounded-lg text-xs font-semibold border transition-all",
                                allSelected
                                    ? "bg-[#fbeede] text-[#b8651a] border-[#fbeede] cursor-default"
                                    : "text-slate-600 border-slate-200 hover:bg-[#934f12] hover:text-[#b8651a] hover:border-[#fbeede]"
                            )}
                            onClick={selectAllSellers}
                            disabled={allSelected}
                        >
                            Tümünü Seç
                        </button>
                        <button
                            className={cn(
                                "px-3.5 py-1.5 rounded-lg text-xs font-semibold border transition-all",
                                noneSelected
                                    ? "bg-slate-50 text-slate-400 border-slate-100 cursor-default"
                                    : "text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-700"
                            )}
                            onClick={deselectAllSellers}
                            disabled={noneSelected}
                        >
                            Seçimi Kaldır
                        </button>
                    </div>
                </div>
            )}

            {/* Validation Alerts */}
            {validationIssues.filter(i => i.type !== 'price_changed').length > 0 && (
                <Alert variant="destructive" className="mb-5 border-red-200 bg-red-50 rounded-xl">
                    <AlertCircle className="h-4 w-4" />
                    <AlertDescription>
                        Sepetinizde düzeltilmesi gereken {validationIssues.filter(i => i.type !== 'price_changed').length} sorun var. Lütfen aşağıdaki uyarıları kontrol edin.
                    </AlertDescription>
                </Alert>
            )}

            {priceChangedCount > 0 && (
                <Alert className="mb-5 border-amber-200 bg-amber-50 rounded-xl">
                    <AlertCircle className="h-4 w-4 text-amber-600" />
                    <AlertDescription className="text-amber-700">
                        {priceChangedCount} ürünün fiyatı değişti. Güncel fiyatlar aşağıda gösterilmektedir.
                    </AlertDescription>
                </Alert>
            )}

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
                {/* Cart Items */}
                <div className="lg:col-span-2">
                    <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden divide-y-2 divide-slate-100">
                        {itemsBySeller.map((group, index) => {
                            const sellerId = group.seller?.id;
                            const isSelected = sellerId ? selectedSellers.includes(sellerId) : false;
                            return (
                                <SellerGroup
                                    key={sellerId || `group-${index}`}
                                    group={group}
                                    isLoading={isLoading}
                                    isSelected={isSelected}
                                    validationIssues={validationIssues}
                                    shippingInfo={sellerId ? sellerShipping[sellerId] : undefined}
                                    onToggle={() => sellerId && toggleSeller(sellerId)}
                                    onQuantityChange={handleQuantityChange}
                                    onRemove={handleRemove}
                                />
                            );
                        })}
                    </div>
                </div>

                {/* Order Summary Sidebar */}
                <div className="lg:col-span-1">
                    <div className="sticky top-28 space-y-4">
                        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                            {/* Summary Header */}
                            <div className="px-5 py-4 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
                                <h2 className="text-base font-bold text-slate-900">
                                    Sipariş Özeti
                                </h2>
                                {totalSellerCount > 1 && (
                                    <p className="text-xs text-slate-400 mt-0.5">
                                        Seçili Satıcılar: {selectedCount} / {totalSellerCount}
                                    </p>
                                )}
                            </div>

                            <div className="px-5 py-4 space-y-4">
                                {/* Seller subtotals */}
                                <div className="space-y-2.5">
                                    {itemsBySeller
                                        .filter(g => g.seller && selectedSellers.includes(g.seller.id))
                                        .map((group, index) => (
                                        <div key={group.seller?.id || `summary-${index}`} className="flex justify-between items-center">
                                            <div className="flex items-center gap-2 min-w-0 mr-3">
                                                <div className="w-1.5 h-1.5 rounded-full bg-[#fbeede] flex-shrink-0" />
                                                <span className="text-sm text-slate-500 truncate">
                                                    {group.seller?.nickname || group.seller?.business_name || 'Satıcı'}
                                                </span>
                                            </div>
                                            <span className="text-sm text-slate-800 font-semibold flex-shrink-0 tabular-nums">
                                                {formatPrice(group.subtotal)}
                                            </span>
                                        </div>
                                    ))}
                                    {noneSelected && (
                                        <p className="text-sm text-slate-400 text-center py-3">
                                            Satıcı seçiniz
                                        </p>
                                    )}
                                </div>

                                <div className="h-px bg-slate-100" />

                                {/* Totals */}
                                <div className="space-y-2.5">
                                    <div className="flex justify-between text-sm">
                                        <span className="text-slate-500">Ürün Toplamı</span>
                                        <span className="text-slate-800 font-medium tabular-nums">
                                            {formatPrice(computedSelectedTotal)}
                                        </span>
                                    </div>
                                    <div className="flex justify-between text-sm">
                                        <span className="text-slate-500">Kargo</span>
                                        <span className="font-medium tabular-nums text-[#b8651a]">
                                            Ücretsiz
                                        </span>
                                    </div>
                                </div>

                                <div className="h-px bg-slate-100" />

                                {/* Grand Total */}
                                <div className="flex justify-between items-center pt-1">
                                    <span className="text-sm font-semibold text-slate-600">
                                        Genel Toplam
                                    </span>
                                    <span className="text-xl font-bold text-slate-900 tabular-nums">
                                        {formatPrice(computedSelectedTotal)}
                                    </span>
                                </div>
                            </div>

                            {/* Checkout CTA */}
                            <div className="px-5 pb-5 space-y-3">
                                <Button
                                    asChild={!noneSelected && !isBelowMinOrder}
                                    className="w-full h-12 bg-[#b8651a] hover:bg-[#934f12] text-white font-semibold rounded-xl shadow-lg shadow-[#b8651a]/15 transition-all hover:shadow-[#b8651a]/25"
                                    size="lg"
                                    disabled={isLoading || hasBlockingIssues || noneSelected || isBelowMinOrder}
                                >
                                    {noneSelected || isBelowMinOrder ? (
                                        <span className="flex items-center justify-center gap-2">
                                            Siparişi Tamamla
                                            <ArrowRight className="w-4 h-4" />
                                        </span>
                                    ) : (
                                        <Link href="/checkout" className="flex items-center justify-center gap-2">
                                            Siparişi Tamamla
                                            <ArrowRight className="w-4 h-4" />
                                        </Link>
                                    )}
                                </Button>

                                {isBelowMinOrder && (
                                    <p className="text-xs text-amber-600 text-center">
                                        Minimum sipariş tutarı {formatPrice(MIN_ORDER_AMOUNT)}&apos;dir. {formatPrice(remainingForMinOrder)} daha eklemeniz gerekiyor.
                                    </p>
                                )}

                                {noneSelected && !isBelowMinOrder && (
                                    <p className="text-xs text-amber-600 text-center">
                                        Devam etmek için en az bir satıcı seçiniz.
                                    </p>
                                )}

                                {hasBlockingIssues && (
                                    <p className="text-xs text-red-500 text-center">
                                        Devam etmek için sepetinizdeki sorunları giderin.
                                    </p>
                                )}

                                <Button
                                    variant="ghost"
                                    className="w-full text-slate-400 hover:text-slate-700 text-sm"
                                    asChild
                                >
                                    <Link href="/market">
                                        <ArrowLeft className="h-3.5 w-3.5 mr-1.5" />
                                        Alışverişe Devam Et
                                    </Link>
                                </Button>
                            </div>
                        </div>

                        {/* Trust badges */}
                        <div className="flex items-center justify-center gap-5 py-3 px-4">
                            <div className="flex items-center gap-1.5 text-slate-400">
                                <ShieldCheck className="w-3.5 h-3.5" />
                                <span className="text-[11px] font-medium">Güvenli Ödeme</span>
                            </div>
                            <div className="w-px h-3.5 bg-slate-200" />
                            <div className="flex items-center gap-1.5 text-slate-400">
                                <Truck className="w-3.5 h-3.5" />
                                <span className="text-[11px] font-medium">Hızlı Teslimat</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Suggested Products */}
            {suggestedProducts.length > 0 && (
                <div className="mt-10 sm:mt-14 bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-6">
                    <ProductCarousel
                        title="İlginizi Çekebilir"
                        products={suggestedProducts}
                        linkUrl="/market"
                    />
                </div>
            )}
        </div>
    );
}
