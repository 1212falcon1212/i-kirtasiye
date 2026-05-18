'use client';

import { useState, useEffect, useMemo } from 'react';
import Link from 'next/link';
import { Zap } from 'lucide-react';

interface FlashProduct {
  id: number;
  name: string;
  image?: string;
  image_url?: string;
  brand?: string;
  lowest_price?: number;
  offers_count?: number;
}

interface FlashDealsProps {
  products: FlashProduct[];
}

function getSecondsUntilMidnight(): number {
  const now = new Date();
  const midnight = new Date(now);
  midnight.setHours(24, 0, 0, 0);
  return Math.floor((midnight.getTime() - now.getTime()) / 1000);
}

function formatCountdown(totalSeconds: number): string {
  const h = Math.floor(totalSeconds / 3600);
  const m = Math.floor((totalSeconds % 3600) / 60);
  const s = totalSeconds % 60;
  return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
}

export function FlashDeals({ products }: FlashDealsProps) {
  const [secondsLeft, setSecondsLeft] = useState<number>(getSecondsUntilMidnight());

  useEffect(() => {
    const interval = setInterval(() => {
      setSecondsLeft(getSecondsUntilMidnight());
    }, 1000);
    return () => clearInterval(interval);
  }, []);

  const discountMap = useMemo(() => {
    const map = new Map<number, number>();
    products.forEach((p) => {
      map.set(p.id, Math.floor(Math.random() * 21) + 15);
    });
    return map;
  }, [products]);

  return (
    <div className="bg-gradient-to-r from-[#fff1f0] via-white to-[#ffedd5] -mx-4 sm:-mx-7 px-4 sm:px-7 border-y border-[#fecaca]/40 py-6 sm:py-12">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
        <div>
          <div className="flex items-center gap-2 sm:gap-3">
            <div className="w-9 h-9 sm:w-[44px] sm:h-[44px] bg-gradient-to-br from-[#dc2626] to-[#ea580c] rounded-xl flex items-center justify-center flex-shrink-0 shadow-md">
              <Zap className="w-4 h-4 sm:w-5 sm:h-5 text-white" />
            </div>
            <span className="text-xl sm:text-[26px] font-black text-[#1a1a1a]">Flaş Fırsatlar</span>
            <span className="bg-[#dc2626] text-white text-[10px] font-bold px-2 py-0.5 rounded-full animate-pulse">
              CANLI
            </span>
          </div>
          <p className="text-[12px] sm:text-[13px] text-[#6b7280] mt-1 ml-11 sm:ml-[56px]">Sınırlı süre, sınırlı stok</p>
        </div>
        <div className="bg-gradient-to-br from-[#1a1a1a] to-[#374151] text-white px-4 sm:px-6 py-2 sm:py-2.5 rounded-xl font-extrabold text-base sm:text-lg tracking-wider tabular-nums self-start sm:self-auto shadow-lg">
          {formatCountdown(secondsLeft)}
        </div>
      </div>

      {/* Product Grid */}
      {products.length === 0 ? (
        <p className="text-center text-[#6b7280] py-12">Henüz fırsat ürünü yok</p>
      ) : (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-5">
          {products.map((product) => {
            const discount = discountMap.get(product.id) ?? 20;
            const imgSrc = product.image_url || product.image || '';

            return (
              <Link
                key={product.id}
                href={`/market/product/${product.id}`}
                className="flex flex-row bg-white rounded-3xl border border-[#f0eceb] overflow-hidden hover:border-[#ea580c] hover:shadow-lg transition-all group"
              >
                {/* Left: Image */}
                <div className="w-[130px] sm:w-[210px] md:w-[240px] h-[150px] sm:h-[180px] md:h-[210px] flex-shrink-0 bg-gradient-to-br from-[#fff7ed] to-white relative flex items-center justify-center">
                  {imgSrc ? (
                    <img
                      src={imgSrc}
                      alt={product.name}
                      className="w-full h-full object-contain p-4 group-hover:scale-105 transition-transform"
                    />
                  ) : (
                    <Zap className="w-12 h-12 text-[#d1d5db]" />
                  )}
                  <span className="absolute top-3 left-3 bg-gradient-to-br from-[#ea580c] to-[#c2410c] text-white text-[12px] font-extrabold rounded-md px-2.5 py-1 shadow-md">
                    %{discount}
                  </span>
                </div>

                {/* Right: Info */}
                <div className="flex-1 min-w-0 p-4 sm:p-6 flex flex-col justify-center">
                  {product.brand && (
                    <p className="text-[12px] font-extrabold text-[#ea580c] uppercase tracking-wider mb-2 truncate">
                      {product.brand}
                    </p>
                  )}
                  <p className="text-sm sm:text-base font-semibold text-[#1a1a1a] line-clamp-2 mb-3 group-hover:text-[#ea580c] transition-colors">
                    {product.name}
                  </p>
                  {product.lowest_price != null && (
                    <p className="text-xl sm:text-2xl font-black text-[#1a1a1a]">
                      {product.lowest_price.toLocaleString('tr-TR', {
                        style: 'currency',
                        currency: 'TRY',
                      })}
                    </p>
                  )}
                </div>
              </Link>
            );
          })}
        </div>
      )}
    </div>
  );
}
