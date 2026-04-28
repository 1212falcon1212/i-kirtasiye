'use client';

import React, { useState } from 'react';
import Image from 'next/image';
import Link from 'next/link';
import { Package, Check, AlertCircle, ShoppingCart, Loader2, Minus, Plus } from 'lucide-react';
import { useCartStore } from '@/stores/useCartStore';
import { useAuth } from '@/contexts/AuthContext';
import { cn } from '@/lib/utils';

interface GridProductCardProps {
  product: {
    id: number;
    name: string;
    image?: string;
    image_url?: string;
    brand?: string;
    lowest_price?: number;
    highest_price?: number;
    offers_count?: number;
    stock_status?: 'in_stock' | 'low_stock' | 'out_of_stock';
    default_offer_id?: number;
  };
  className?: string;
}

export const GridProductCard = React.memo(function GridProductCard({
  product,
  className,
}: GridProductCardProps) {
  const [imgError, setImgError] = useState(false);
  const [isAddingToCart, setIsAddingToCart] = useState(false);
  const [showAddedFeedback, setShowAddedFeedback] = useState(false);
  const [quantity, setQuantity] = useState(1);
  const { addItem } = useCartStore();
  const { user } = useAuth();

  const canBuy = !user || user.role !== 'seller';

  const offersCount =
    typeof product.offers_count === 'number'
      ? product.offers_count
      : parseInt(product.offers_count as unknown as string) || 0;

  const formatPrice = (price?: number) => {
    if (!price) return '---';
    return new Intl.NumberFormat('tr-TR', {
      style: 'currency',
      currency: 'TRY',
    }).format(price);
  };

  const getStockInfo = () => {
    if (product.stock_status === 'out_of_stock' || offersCount === 0) {
      return {
        status: 'out_of_stock' as const,
        label: 'Stokta Yok',
        badgeClass: 'bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400',
      };
    }
    if (product.stock_status === 'low_stock' || offersCount <= 2) {
      return {
        status: 'low_stock' as const,
        label: 'Son Birimler',
        badgeClass: 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
      };
    }
    return {
      status: 'in_stock' as const,
      label: `${offersCount} satıcıdan`,
      badgeClass: 'bg-[#fbeede] text-[#b8651a] dark:bg-[#934f12]/30 dark:text-[#fbeede]',
    };
  };

  const stockInfo = getStockInfo();

  const handleAddToCart = async (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();
    if (!product.default_offer_id || stockInfo.status === 'out_of_stock' || !canBuy) return;

    setIsAddingToCart(true);
    try {
      await addItem(product.default_offer_id, quantity);
      setShowAddedFeedback(true);
      setQuantity(1);
      setTimeout(() => setShowAddedFeedback(false), 2000);
    } catch (error) {
      console.error('Failed to add to cart:', error);
    } finally {
      setIsAddingToCart(false);
    }
  };

  const handleQuantityChange = (delta: number, e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();
    setQuantity((prev) => Math.max(1, Math.min(99, prev + delta)));
  };

  const hasImage = (product.image_url || product.image) && !imgError;

  return (
    <div
      className={cn(
        'group relative bg-white dark:bg-slate-900 rounded-2xl border border-[#f0eceb] dark:border-slate-700',
        'hover:border-[#fbeede] dark:hover:border-[#b8651a]/50',
        'hover:shadow-[0_8px_32px_rgba(190,24,93,0.08)]',
        'transition-all duration-200 overflow-hidden flex flex-col',
        stockInfo.status === 'out_of_stock' && 'opacity-75',
        className,
      )}
    >
      <Link
        href={`/market/product/${product.id}`}
        className="flex flex-col h-full"
      >
        {/* Image Container */}
        <div className="relative aspect-square bg-[#faf8f6] dark:bg-slate-800 overflow-hidden">
          {hasImage ? (
            <Image
              src={product.image_url || product.image || ''}
              alt={product.name}
              fill
              sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw"
              className="object-contain p-4 group-hover:scale-105 transition-transform duration-300"
              onError={() => setImgError(true)}
            />
          ) : (
            <div className="absolute inset-0 flex items-center justify-center">
              <Package className="w-12 h-12 text-slate-300 dark:text-slate-600" />
            </div>
          )}

          {/* Stock Badge - top left */}
          <div className="absolute top-2.5 left-2.5">
            <span
              className={cn(
                'inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-bold tracking-wide',
                stockInfo.badgeClass,
              )}
            >
              {stockInfo.status === 'out_of_stock' ? (
                <AlertCircle className="w-3 h-3" />
              ) : stockInfo.status === 'in_stock' ? (
                <Check className="w-3 h-3" />
              ) : null}
              {stockInfo.label}
            </span>
          </div>
        </div>

        {/* Content */}
        <div className="flex flex-col flex-1 p-3.5">
          {/* Brand */}
          {product.brand && (
            <p className="text-[10px] font-extrabold text-[#b8651a] dark:text-[#fbeede] uppercase tracking-[0.8px] mb-1">
              {product.brand}
            </p>
          )}

          {/* Product Name */}
          <p className="text-[13px] font-semibold text-[#1a1a1a] dark:text-white line-clamp-2 leading-[1.4] mb-auto group-hover:text-[#b8651a] dark:group-hover:text-[#fbeede] transition-colors">
            {product.name}
          </p>

          {/* Price Section */}
          <div className="mt-3 pt-3 border-t border-[#f0eceb] dark:border-slate-700">
            <div className="flex items-end justify-between gap-2">
              <div>
                <p className="text-lg font-black text-[#1a1a1a] dark:text-white leading-none">
                  {formatPrice(product.lowest_price)}
                </p>
                {product.highest_price &&
                  product.lowest_price &&
                  product.highest_price > product.lowest_price && (
                    <p className="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">
                      {formatPrice(product.highest_price)}&apos;e kadar
                    </p>
                  )}
              </div>
            </div>

            {/* Quick Add to Cart */}
            {product.default_offer_id &&
              stockInfo.status !== 'out_of_stock' &&
              canBuy && (
                <div
                  className="flex items-center gap-1.5 mt-2.5"
                  onClick={(e) => e.preventDefault()}
                >
                  {showAddedFeedback ? (
                    <div className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-accent text-white w-full justify-center">
                      <Check className="w-3.5 h-3.5" />
                      Sepete Eklendi
                    </div>
                  ) : (
                    <>
                      <div className="flex items-center bg-[#faf8f6] dark:bg-slate-800 rounded-lg overflow-hidden border border-[#f0eceb] dark:border-slate-700">
                        <button
                          onClick={(e) => handleQuantityChange(-1, e)}
                          disabled={quantity <= 1}
                          className="w-7 h-7 flex items-center justify-center text-[#6b7280] hover:bg-[#f0eceb] dark:hover:bg-slate-700 disabled:opacity-40 transition-colors"
                        >
                          <Minus className="w-3 h-3" />
                        </button>
                        <span className="w-6 text-center text-xs font-bold text-[#1a1a1a] dark:text-white">
                          {quantity}
                        </span>
                        <button
                          onClick={(e) => handleQuantityChange(1, e)}
                          disabled={quantity >= 99}
                          className="w-7 h-7 flex items-center justify-center text-[#6b7280] hover:bg-[#f0eceb] dark:hover:bg-slate-700 disabled:opacity-40 transition-colors"
                        >
                          <Plus className="w-3 h-3" />
                        </button>
                      </div>
                      <button
                        onClick={handleAddToCart}
                        disabled={isAddingToCart}
                        className="flex-1 flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-[#b8651a] text-white hover:bg-[#934f12] transition-colors disabled:opacity-60"
                      >
                        {isAddingToCart ? (
                          <Loader2 className="w-3.5 h-3.5 animate-spin" />
                        ) : (
                          <ShoppingCart className="w-3.5 h-3.5" />
                        )}
                        Sepete Ekle
                      </button>
                    </>
                  )}
                </div>
              )}
          </div>
        </div>
      </Link>
    </div>
  );
});
