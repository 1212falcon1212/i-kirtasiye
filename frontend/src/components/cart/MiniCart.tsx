'use client';

import { useEffect, useState } from 'react';
import { usePathname } from 'next/navigation';
import { ShoppingCart, Plus, Minus, Trash2, ArrowRight, Box, AlertTriangle } from 'lucide-react';
import { useCartStore, ValidationIssue } from '@/stores/useCartStore';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetDescription,
} from '@/components/ui/sheet';
import Link from 'next/link';
import { cn } from '@/lib/utils';

function ProductImage({ src, alt }: { src: string | null | undefined; alt: string }) {
    const [error, setError] = useState(false);

    if (!src || error) {
        return (
            <div className="w-12 h-12 bg-slate-200 dark:bg-slate-700 rounded-md flex items-center justify-center flex-shrink-0">
                <Box className="h-5 w-5 text-slate-400" />
            </div>
        );
    }

    return (
        <img
            src={src}
            alt={alt}
            className="w-12 h-12 rounded-md object-cover flex-shrink-0"
            onError={() => setError(true)}
        />
    );
}

export function MiniCart() {
    const {
        itemsBySeller,
        itemCount,
        total,
        isLoading,
        isShaking,
        lastAddedItemId,
        removingItemId,
        validationIssues,
        fetchCart,
        validateCart,
        updateQuantity,
        removeItem,
    } = useCartStore();

    const [isOpen, setIsOpen] = useState(false);
    const pathname = usePathname();
    const isCartPage = pathname === '/market/sepet';

    useEffect(() => {
        fetchCart();
        validateCart();
    }, [fetchCart, validateCart]);

    // Listen to store's isOpen for external triggers (e.g. AddToCartButton's "Sepeti Gor")
    const storeIsOpen = useCartStore((s) => s.isOpen);
    const storeSetOpen = useCartStore((s) => s.setOpen);

    useEffect(() => {
        if (storeIsOpen && !isCartPage) {
            setIsOpen(true);
            storeSetOpen(false);
        }
    }, [storeIsOpen, storeSetOpen, isCartPage]);

    // Close on route change
    useEffect(() => {
        setIsOpen(false);
    }, [pathname]);

    const formatPrice = (price: number) => {
        return new Intl.NumberFormat('tr-TR', {
            style: 'currency',
            currency: 'TRY',
        }).format(price);
    };

    const getPriceIssue = (itemId: number): ValidationIssue | undefined => {
        return validationIssues.find(i => i.item_id === itemId && i.type === 'price_changed');
    };

    const priceChangedCount = validationIssues.filter(i => i.type === 'price_changed').length;

    const handleQuantityChange = async (itemId: number, newQuantity: number, maxStock: number) => {
        if (newQuantity < 1) {
            await removeItem(itemId);
        } else if (newQuantity <= maxStock) {
            await updateQuantity(itemId, newQuantity);
        }
    };

    return (
        <>
            {/* Cart trigger */}
            <button
                className={cn(
                    "flex items-center gap-1.5 text-slate-600 dark:text-slate-300 hover:text-[#b8651a] cursor-pointer transition-all duration-300",
                    isShaking && "animate-shake"
                )}
                onClick={() => {
                    if (itemCount > 0 && !isCartPage) {
                        setIsOpen(true);
                    }
                }}
            >
                <div className="relative">
                    <ShoppingCart className="h-[22px] w-[22px]" />
                    {itemCount > 0 && (
                        <span className={cn(
                            "absolute -top-2 -right-2.5 min-w-[18px] h-[18px] px-1 rounded-full bg-[#b8651a] text-white text-[10px] flex items-center justify-center font-bold transition-transform pointer-events-none",
                            isShaking && "scale-125"
                        )}>
                            {itemCount > 99 ? '99+' : itemCount}
                        </span>
                    )}
                </div>
                <span className="text-[13px] font-medium hidden lg:inline">Sepetim</span>
            </button>

            {/* Cart Sheet Sidebar */}
            <Sheet open={isOpen} onOpenChange={setIsOpen}>
                <SheetContent side="right" className="w-full sm:max-w-[420px] flex flex-col p-0 gap-0">
                    {/* Header */}
                    <SheetHeader className="p-4 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-700 space-y-0">
                        <div className="flex items-center gap-2">
                            <ShoppingCart className="h-5 w-5 text-[#b8651a]" />
                            <SheetTitle className="text-base">Sepetim</SheetTitle>
                            <Badge variant="secondary" className="text-xs">
                                {itemCount} ürün
                            </Badge>
                        </div>
                        <SheetDescription className="sr-only">
                            Sepetinizdeki ürünler
                        </SheetDescription>
                    </SheetHeader>

                    {/* Price change warning */}
                    {priceChangedCount > 0 && (
                        <div className="px-4 py-2 bg-amber-50 dark:bg-amber-900/30 border-b border-amber-200 dark:border-amber-700 flex items-center gap-2">
                            <AlertTriangle className="h-3.5 w-3.5 text-amber-600 flex-shrink-0" />
                            <p className="text-xs text-amber-700 dark:text-amber-300">
                                {priceChangedCount} ürünün fiyatı değişti
                            </p>
                        </div>
                    )}

                    {/* Content */}
                    {itemCount === 0 ? (
                        <div className="flex flex-col items-center justify-center flex-1 py-12 px-4 text-center">
                            <div className="w-20 h-20 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4">
                                <ShoppingCart className="h-10 w-10 text-slate-400" />
                            </div>
                            <h3 className="font-semibold text-slate-900 dark:text-white mb-1">
                                Sepetiniz boş
                            </h3>
                            <p className="text-sm text-slate-500 mb-4">
                                Alışverişe başlamak için ürün ekleyin
                            </p>
                            <Button asChild variant="default" size="sm" onClick={() => setIsOpen(false)}>
                                <Link href="/market">
                                    <Box className="h-4 w-4 mr-2" />
                                    Ürünlere Göz At
                                </Link>
                            </Button>
                        </div>
                    ) : (
                        <>
                            {/* Scrollable items */}
                            <div className="flex-1 overflow-y-auto overscroll-contain">
                                <div className="p-4 space-y-3">
                                    {itemsBySeller.map((group) => (
                                        <div key={group.seller?.id || 'unknown'} className="space-y-2">
                                            {/* Seller header */}
                                            <div className="flex items-center gap-2 text-xs text-slate-500">
                                                <div className="w-1.5 h-1.5 rounded-full bg-[#b8651a]" />
                                                {group.seller?.id ? (
                                                    <Link
                                                        href={`/market/satici/${group.seller.id}`}
                                                        className="font-medium text-slate-700 dark:text-slate-300 hover:text-[#b8651a] hover:underline transition-colors"
                                                        onClick={() => setIsOpen(false)}
                                                    >
                                                        {group.seller.nickname || group.seller.business_name || 'Satıcı'}
                                                    </Link>
                                                ) : (
                                                    <span className="font-medium text-slate-700 dark:text-slate-300">
                                                        {group.seller?.nickname || group.seller?.business_name || 'Satıcı'}
                                                    </span>
                                                )}
                                                {group.seller?.city && (
                                                    <span className="text-slate-400">({group.seller.city})</span>
                                                )}
                                            </div>

                                            {/* Items - no limit */}
                                            {group.items.map((item) => (
                                                <div
                                                    key={item.id}
                                                    className={cn(
                                                        "flex gap-3 p-2 rounded-lg bg-slate-50 dark:bg-slate-800/50 transition-all duration-300",
                                                        lastAddedItemId === item.id && "ring-2 ring-[#b8651a] ring-offset-2 bg-[#fbeede]",
                                                        removingItemId === item.id && "opacity-0 translate-x-4"
                                                    )}
                                                >
                                                    <ProductImage
                                                        src={item.product.image_url || (item.product.image ? `${process.env.NEXT_PUBLIC_API_URL?.replace('/api', '')}/storage/${item.product.image}` : null)}
                                                        alt={item.product.name}
                                                    />

                                                    <div className="flex-1 min-w-0">
                                                        <p className="text-sm font-medium text-slate-900 dark:text-white line-clamp-1">
                                                            {item.product.name}
                                                        </p>
                                                        {(() => {
                                                            const priceIssue = getPriceIssue(item.id);
                                                            if (priceIssue && priceIssue.old_price && priceIssue.new_price) {
                                                                return (
                                                                    <div className="flex items-center gap-1.5">
                                                                        <span className="text-xs text-slate-400 line-through">{formatPrice(priceIssue.old_price)}</span>
                                                                        <span className={cn("text-xs font-medium", priceIssue.new_price > priceIssue.old_price ? "text-red-600" : "text-[#b8651a]")}>
                                                                            {formatPrice(priceIssue.new_price)}
                                                                        </span>
                                                                    </div>
                                                                );
                                                            }
                                                            return <p className="text-xs text-slate-500">{formatPrice(item.price_at_addition)}</p>;
                                                        })()}

                                                        <div className="flex items-center justify-between mt-1">
                                                            <div className="flex items-center gap-1">
                                                                <Button
                                                                    size="icon"
                                                                    variant="ghost"
                                                                    className="h-6 w-6"
                                                                    onClick={() => handleQuantityChange(item.id, item.quantity - 1, item.offer.stock)}
                                                                    disabled={isLoading}
                                                                >
                                                                    <Minus className="h-3 w-3" />
                                                                </Button>
                                                                <span className="w-6 text-center text-sm font-medium">
                                                                    {item.quantity}
                                                                </span>
                                                                <Button
                                                                    size="icon"
                                                                    variant="ghost"
                                                                    className="h-6 w-6"
                                                                    onClick={() => handleQuantityChange(item.id, item.quantity + 1, item.offer.stock)}
                                                                    disabled={isLoading || item.quantity >= item.offer.stock}
                                                                >
                                                                    <Plus className="h-3 w-3" />
                                                                </Button>
                                                            </div>
                                                            <span className="text-sm font-semibold text-slate-900 dark:text-white">
                                                                {formatPrice(item.price_at_addition * item.quantity)}
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <Button
                                                        size="icon"
                                                        variant="ghost"
                                                        className="h-6 w-6 text-slate-400 hover:text-red-500 self-start"
                                                        onClick={() => removeItem(item.id)}
                                                        disabled={isLoading}
                                                    >
                                                        <Trash2 className="h-3 w-3" />
                                                    </Button>
                                                </div>
                                            ))}
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Footer */}
                            <div className="flex-shrink-0 border-t border-slate-200 dark:border-slate-700 p-4 space-y-3 bg-slate-50 dark:bg-slate-800/50">
                                <div className="flex items-center justify-between">
                                    <span className="text-sm text-slate-600 dark:text-slate-400">Ara Toplam:</span>
                                    <span className="text-lg font-bold text-slate-900 dark:text-white">
                                        {formatPrice(total)}
                                    </span>
                                </div>

                                <div className="flex flex-col gap-2">
                                    <div className="flex gap-2">
                                        <Button
                                            variant="outline"
                                            className="flex-1"
                                            asChild
                                            onClick={() => setIsOpen(false)}
                                        >
                                            <Link href="/market/sepet">
                                                Sepete Git
                                            </Link>
                                        </Button>
                                        <Button
                                            className="flex-1 bg-[#b8651a] hover:bg-[#934f12] text-white"
                                            asChild
                                            onClick={() => setIsOpen(false)}
                                        >
                                            <Link href="/checkout">
                                                Satın Al
                                                <ArrowRight className="h-4 w-4 ml-2" />
                                            </Link>
                                        </Button>
                                    </div>
                                    <Button
                                        variant="ghost"
                                        className="w-full text-slate-500 hover:text-slate-700"
                                        onClick={() => setIsOpen(false)}
                                    >
                                        Alışverişe Devam Et
                                    </Button>
                                </div>
                            </div>
                        </>
                    )}
                </SheetContent>
            </Sheet>

            <style jsx global>{`
                @keyframes shake {
                    0%, 100% { transform: translateX(0); }
                    10%, 30%, 50%, 70%, 90% { transform: translateX(-2px); }
                    20%, 40%, 60%, 80% { transform: translateX(2px); }
                }
                .animate-shake {
                    animation: shake 0.5s ease-in-out;
                }
            `}</style>
        </>
    );
}
