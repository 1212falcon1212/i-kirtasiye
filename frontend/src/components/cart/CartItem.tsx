'use client';

import { useState, useEffect } from 'react';
import { Plus, Minus, Trash2, Box, AlertCircle, Calendar } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import type { CartItem as CartItemType, ValidationIssue } from '@/stores/useCartStore';

// Product image component with error handling
function ProductImage({ src, alt, className }: { src: string | null | undefined; alt: string; className?: string }) {
    const [error, setError] = useState(false);

    if (!src || error) {
        return (
            <div className={cn("bg-slate-100 dark:bg-slate-700 flex items-center justify-center", className)}>
                <Box className="h-10 w-10 text-slate-400" />
            </div>
        );
    }

    return (
        <img
            src={src}
            alt={alt}
            className={cn("object-cover", className)}
            onError={() => setError(true)}
        />
    );
}

interface CartItemProps {
    item: CartItemType;
    issue?: ValidationIssue;
    isRemoving?: boolean;
    isHighlighted?: boolean;
    isLoading?: boolean;
    onQuantityChange: (itemId: number, quantity: number) => void;
    onRemove: (itemId: number) => void;
}

export function CartItem({
    item,
    issue,
    isRemoving = false,
    isHighlighted = false,
    isLoading = false,
    onQuantityChange,
    onRemove,
}: CartItemProps) {
    const [quantity, setQuantity] = useState(item.quantity);
    const [isEditing, setIsEditing] = useState(false);
    const [mounted, setMounted] = useState(false);

    useEffect(() => {
        setMounted(true);
    }, []);

    const formatPrice = (price: number) => {
        return new Intl.NumberFormat('tr-TR', {
            style: 'currency',
            currency: 'TRY',
        }).format(price);
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('tr-TR', {
            year: 'numeric',
            month: 'short',
        });
    };

    const handleQuantitySubmit = () => {
        if (quantity < 1) {
            onRemove(item.id);
        } else if (quantity !== item.quantity) {
            onQuantityChange(item.id, Math.min(quantity, item.offer.stock));
        }
        setIsEditing(false);
    };

    const handleIncrement = () => {
        if (item.quantity < item.offer.stock) {
            onQuantityChange(item.id, item.quantity + 1);
        }
    };

    const handleDecrement = () => {
        if (item.quantity > 1) {
            onQuantityChange(item.id, item.quantity - 1);
        } else {
            onRemove(item.id);
        }
    };

    const isExpiringSoon = mounted && item.offer.expiry_date &&
        new Date(item.offer.expiry_date) < new Date(Date.now() + 90 * 24 * 60 * 60 * 1000);

    const lineTotal = item.price_at_addition * item.quantity;

    return (
        <div
            className={cn(
                "group relative flex gap-4 p-4 rounded-xl bg-white dark:bg-slate-800 border transition-all duration-300",
                issue ? "border-red-200 bg-red-50/50 dark:bg-red-900/10" : "border-slate-200 dark:border-slate-700",
                isHighlighted && "ring-2 ring-primary ring-offset-2",
                isRemoving && "opacity-0 translate-x-8 scale-95"
            )}
        >
            {/* Product Image */}
            <div className="relative flex-shrink-0">
                <ProductImage
                    src={item.product.image_url || (item.product.image ? `${process.env.NEXT_PUBLIC_API_URL?.replace('/api', '')}/storage/${item.product.image}` : null)}
                    alt={item.product.name}
                    className="rounded-lg w-24 h-24"
                />

                {/* Stock Badge */}
                {item.offer.stock <= 5 && (
                    <Badge
                        variant="destructive"
                        className="absolute -top-2 -right-2 text-[10px] px-1.5 py-0.5"
                    >
                        Son {item.offer.stock}
                    </Badge>
                )}
            </div>

            {/* Product Info */}
            <div className="flex-1 min-w-0 space-y-2">
                {/* Title & Brand */}
                <div>
                    <h3 className="font-semibold text-slate-900 dark:text-white line-clamp-2 group-hover:text-primary transition-colors">
                        {item.product.name}
                    </h3>
                    {item.product.brand && (
                        <p className="text-sm text-slate-500">{item.product.brand}</p>
                    )}
                </div>

                {/* Barcode */}
                <p className="text-xs text-slate-400 font-mono">
                    {item.product.barcode}
                </p>

                {/* Expiry Date Warning */}
                {item.offer.expiry_date && (
                    <div className={cn(
                        "flex items-center gap-1 text-xs",
                        isExpiringSoon ? "text-amber-600" : "text-slate-500"
                    )}>
                        <Calendar className="h-3 w-3" />
                        <span>SKT: {formatDate(item.offer.expiry_date)}</span>
                        {isExpiringSoon && (
                            <Badge variant="outline" className="text-[10px] border-amber-300 text-amber-600 ml-1">
                                Yakin tarih
                            </Badge>
                        )}
                    </div>
                )}

                {/* Issue Warning */}
                {issue && (
                    <div className="flex items-start gap-2 text-sm text-red-600 bg-red-100 dark:bg-red-900/30 p-2 rounded-md">
                        <AlertCircle className="h-4 w-4 flex-shrink-0 mt-0.5" />
                        <div>
                            <p className="font-medium">{issue.message}</p>
                            {issue.type === 'stock' && issue.available_stock !== undefined && (
                                <p className="text-xs mt-1">
                                    Mevcut stok: {issue.available_stock} adet
                                </p>
                            )}
                            {issue.type === 'price_changed' && issue.new_price !== undefined && (
                                <p className="text-xs mt-1">
                                    Yeni fiyat: {formatPrice(issue.new_price)}
                                </p>
                            )}
                        </div>
                    </div>
                )}

                {/* Price & Quantity Row */}
                <div className="flex items-center justify-between pt-2">
                    {/* Unit Price */}
                    <div className="text-sm">
                        <span className="text-slate-500">Birim: </span>
                        <span className="font-medium text-slate-700 dark:text-slate-300">
                            {formatPrice(item.price_at_addition)}
                        </span>
                    </div>

                    {/* Quantity Controls */}
                    <div className="flex items-center gap-2">
                        <Button
                            size="icon"
                            variant="outline"
                            className="h-8 w-8 rounded-lg"
                            onClick={handleDecrement}
                            disabled={isLoading}
                        >
                            <Minus className="h-3 w-3" />
                        </Button>

                        {isEditing ? (
                            <Input
                                type="number"
                                value={quantity}
                                onChange={(e) => setQuantity(parseInt(e.target.value) || 0)}
                                onBlur={handleQuantitySubmit}
                                onKeyDown={(e) => e.key === 'Enter' && handleQuantitySubmit()}
                                className="w-16 h-8 text-center"
                                min={0}
                                max={item.offer.stock}
                                autoFocus
                            />
                        ) : (
                            <button
                                className="w-10 h-8 text-center font-semibold text-slate-900 dark:text-white hover:bg-slate-100 dark:hover:bg-slate-700 rounded-md transition-colors"
                                onClick={() => setIsEditing(true)}
                            >
                                {item.quantity}
                            </button>
                        )}

                        <Button
                            size="icon"
                            variant="outline"
                            className="h-8 w-8 rounded-lg"
                            onClick={handleIncrement}
                            disabled={isLoading || item.quantity >= item.offer.stock}
                        >
                            <Plus className="h-3 w-3" />
                        </Button>
                    </div>
                </div>
            </div>

            {/* Right Section - Total & Remove */}
            <div className="flex flex-col items-end justify-between">
                {/* Remove Button */}
                <Button
                    size="icon"
                    variant="ghost"
                    className="h-8 w-8 text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 opacity-0 group-hover:opacity-100 transition-opacity"
                    onClick={() => onRemove(item.id)}
                    disabled={isLoading}
                >
                    <Trash2 className="h-4 w-4" />
                </Button>

                {/* Line Total */}
                <div className="text-right">
                    <p className="text-lg font-bold text-slate-900 dark:text-white">
                        {formatPrice(lineTotal)}
                    </p>
                    {item.quantity > 1 && (
                        <p className="text-xs text-slate-500">
                            {item.quantity} x {formatPrice(item.price_at_addition)}
                        </p>
                    )}
                </div>
            </div>
        </div>
    );
}
